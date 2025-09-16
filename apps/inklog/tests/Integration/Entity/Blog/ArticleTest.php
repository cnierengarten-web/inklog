<?php declare(strict_types=1);

namespace App\Tests\Integration\Entity\Blog;

use App\Entity\Blog\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ArticleTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testCreatedAtIsSetOnInsert(): void
    {
        $article = new Article();
        $article
            ->setTitle('T')
            ->setContent('C');

        self::assertNull($article->getCreatedAt(), 'CreatedAt must be null before creation');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->refresh($article);

        self::assertNotNull($article->getCreatedAt(), 'CreatedAt must not be empty after creation');
    }

    public function testUpdatedAtIsSetOnInsertAndUpdate()
    {
        $article = new Article();
        $article
            ->setTitle('T')
            ->setContent('C');

        self::assertNull($article->getUpdatedAt(), 'UpdatedAt must be null before creation');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->refresh($article);

        $old = $article->getUpdatedAt();
        self::assertNotNull($old, 'UpdatedAt must not be empty after creation');

        $article->setTitle('Title change');
        $this->em->flush();
        self::assertNotEquals($old, $article->getUpdatedAt(), 'updatedAt must change when article changes');
    }

    public function testSlugIsGeneratedFromTitle(): void
    {
        $title = 'a title';
        $article = new Article();
        $article
            ->setTitle($title)
            ->setContent('C');
        self::assertNull($article->getSlug(), 'UpdatedAt must be empty before creation');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->refresh($article);

        self::assertNotNull($article->getSlug());
        $expected = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
        self::assertMatchesRegularExpression(
            '/^'.preg_quote($expected, '/').'(?:-\d+)?$/',
            $article->getSlug(),
            'Slug must derived from title (with numerique suffix for unicity)'
        );

        $newTitle = 'another title !';
        $article->setTitle($newTitle);
        $this->em->flush();
        $expected = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($newTitle)), '-');
        self::assertMatchesRegularExpression(
            '/^'.preg_quote($expected, '/').'(?:-\d+)?$/',
            $article->getSlug(),
            'Slug must derived from title (with numerique suffix for unicity)'
        );
    }

    public function testUploadSetsFileMetadataAndUpdatesTimestamp(): void
    {
        $article = new Article();
        $article
            ->setTitle('T')
            ->setContent('C');

        $this->em->persist($article);
        $this->em->flush(); // flush to initialize CreatedAt and UpdatedAt with gedmo
        $this->em->refresh($article);

        $old = $article->getUpdatedAt();

        // create a File to allow to set imageFile
        $tmp = tempnam(sys_get_temp_dir(), 'upload_');
        copy(__DIR__.'/../../../fixtures/test.png', $tmp); // mets un vrai petit jpg dans tests/fixtures
        $file = new UploadedFile(
            $tmp,
            'photo.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        // Update only the file
        $article->setImageFile($file);
        $this->em->flush();

        // 4) Assertions
        self::assertNotNull($article->getImageName(), 'imageName must be defined');
        self::assertNotNull($article->getImageSize(), 'imageSize must be defined');
        self::assertNotNull($article->getImageMimeType(), 'imageMimeType must be defined');
        self::assertNotNull($article->getImageOriginalName(), 'imageOriginalName  must be defined');
        self::assertNotEquals($old, $article->getUpdatedAt(), 'updatedAt must change when image changes');
    }
}
