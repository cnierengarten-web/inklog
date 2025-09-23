<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use App\Entity\Tag;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /** @var Tag[] */
    private array $tagsPool;

    /** @var Category[] */
    private array $categoriesPool;
    private const array AVAILABLE_IMG = ['dog.jpg', 'nature.jpg', 'ocean.jpg', 'desert.jpg'];

    public function __construct(
        private readonly Generator $faker,
    ) {
    }

    private function attachFixtureImage(Article $article, string $fileName = 'nature.jpg'): void
    {
        $source = \dirname(__DIR__).'/DataFixtures/files/'.$fileName;
        $tmp = sys_get_temp_dir().'/'.uniqid('fx_', true).'-'.$fileName;
        copy($source, $tmp);

        $uploaded = new UploadedFile($tmp, $fileName, 'image/jpeg', null, true);
        $article->setImageFile($uploaded);
    }

    public function load(ObjectManager $manager): void
    {
        $this->tagsPool = [
            $this->getReference(TagFixtures::TAG_IA, Tag::class),
            $this->getReference(TagFixtures::TAG_CREATURES, Tag::class),
            $this->getReference(TagFixtures::TAG_BALLADE, Tag::class),
        ];

        $this->categoriesPool = [
            $this->getReference(CategoryFixtures::CAT_INFO, Category::class),
            $this->getReference(CategoryFixtures::CAT_MONTAGE, Category::class),
            $this->getReference(CategoryFixtures::CAT_CONCEPT_ART, Category::class),
        ];

        $this->faker->seed(8524);

        $alice = $this->getReference(UserFixtures::USER_ALICE, User::class);

        $manager->persist($this->createArticle($alice, 'Création d\'une petite créature aquatique', 'ocean.jpg'));
        $manager->persist($this->createArticle($alice, 'Quelle IA pour développer ?', 'desert.jpg'));
        for ($i = 0; $i < 5; $i++) {
            $manager->persist($this->createArticle($alice));
        }

        $albert = $this->getReference(UserFixtures::USER_ALBERT, User::class);
        $manager->persist($this->createArticle($albert, 'Premier montage', 'nature.jpg'));
        for ($i = 0; $i < 10; $i++) {
            $manager->persist($this->createArticle($albert));
        }

        $manager->flush();
    }

    private function createArticle(User $author, ?string $title = null, ?string $image = null): Article
    {
        $article = new Article();

        if (!isset($title)) {
            $title = ucfirst($this->faker->unique()->words(2, true));
        }
        $article->setTitle($title);
        $summary = $this->faker->optional(0.4)->paragraph(2);
        $article->setSummary($summary);

        $content = $this->faker->paragraphs(5, true);
        $article->setContent($content);

        $this->faker->setDefaultTimezone('UTC');
        $publishedAt = $this->faker->optional(0.3)->dateTimeBetween('-2 years', '+2 months');
        $article->setPublishedAt($publishedAt ? DateTimeImmutable::createFromMutable($publishedAt) : null);

        $article->setAuthor($author);
        $image = $image ?? $this->faker->optional(0.3)->randomElement(self::AVAILABLE_IMG);
        if (isset($image)) {
            $this->attachFixtureImage($article, $image);
        }

        $tags = $this->faker->randomElements(
            $this->tagsPool,
            $this->faker->numberBetween(0, 3),
        );
        foreach ($tags as $t) {
            $article->addTag($t);
        }

        $cats = $this->faker->randomElements(
            $this->categoriesPool,
            $this->faker->numberBetween(1, 3),
        );

        foreach ($cats as $c) {
            $article->addCategory($c);
        }

        return $article;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TagFixtures::class,
            CategoryFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
