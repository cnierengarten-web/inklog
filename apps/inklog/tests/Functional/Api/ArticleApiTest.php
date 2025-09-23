<?php declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Blog\Article;
use App\Tests\Functional\AbstractWebTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class ArticleApiTest extends AbstractWebTestCase
{
    private function makeArticle(
        EntityManagerInterface $em,
        string $authorEmail,
        ?DateTimeImmutable $publishedAt = null,
        string $title = 'Hello API',
        ?string $content = 'Body',
        ?string $summary = 'Summary',
    ): Article {
        $user = self::createUser($authorEmail);
        $a = new Article();
        $a->setTitle($title);
        $a->setContent($content ?? '');
        $a->setImageName('demo.jpg');
        $a->setSummary($summary);
        $a->setAuthor($user);
        $a->setPublishedAt($publishedAt);
        $em->persist($a);
        $em->flush();

        return $a;
    }

    public function testGetArticleItemReturnsJson(): void
    {
        $client = self::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\Blog\Article')->execute();

        $publishedAt = "2025-09-01T00:00:00+00:00";
        $article = $this->makeArticle(
            $em,
            'itemAuthor@test.fr',
            new DateTimeImmutable($publishedAt),
        );

        $client->request('GET', '/api/articles/'.$article->getSlug(), server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Hello API', $data['title']);
        self::assertSame('/medias/articles/'.$article->getImageName(), $data['imageUrl']);
        self::assertArrayHasKey('publishedAt', $data);
    }

    public function testGetArticlesCollectionReturnsArrayOfItems(): void
    {
        $client = self::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\Blog\Article')->execute();

        $publishedAtArticle1 = "2025-09-21T00:00:00+00:00";
        $article1 = $this->makeArticle(
            $em,
            'author1@collection.test',
            new DateTimeImmutable($publishedAtArticle1),
            'Article 1',
            'body1',
            'summary1',
        );
        $publishedAtArticle2 = "2025-09-19T00:00:00+00:00";
        $article2 = $this->makeArticle(
            $em,
            'author2@collection.test',
            new DateTimeImmutable($publishedAtArticle2),
            'Article 2',
            'body2',
            'summary2',
        );

        $client->request('GET', '/api/articles', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertGreaterThanOrEqual(2, count($data));

        self::assertArrayHasKey('title', $data[0]);
        self::assertSame($article1->getTitle(), $data[0]['title']);
        self::assertArrayHasKey('slug', $data[0]);
        self::assertSame($article1->getSlug(), $data[0]['slug']);
        self::assertArrayHasKey('publishedAt', $data[0]);
        self::assertSame($publishedAtArticle1, $data[0]['publishedAt']);
        self::assertArrayHasKey('imageUrl', $data[0]);
        self::assertSame($article1->getImageUrl(), $data[0]['imageUrl']);
        self::assertArrayHasKey('summary', $data[0]);
        self::assertSame($article1->getSummary(), $data[0]['summary']);

        self::assertArrayHasKey('title', $data[1]);
        self::assertSame($article2->getTitle(), $data[1]['title']);
        self::assertArrayHasKey('slug', $data[1]);
        self::assertSame($article2->getSlug(), $data[1]['slug']);
        self::assertArrayHasKey('publishedAt', $data[1]);
        self::assertSame($publishedAtArticle2, $data[1]['publishedAt']);
        self::assertArrayHasKey('imageUrl', $data[1]);
        self::assertSame($article2->getImageUrl(), $data[1]['imageUrl']);
        self::assertArrayHasKey('summary', $data[1]);
        self::assertSame($article2->getSummary(), $data[1]['summary']);
        self::assertArrayHasKey('author', $data[1]);
        self::assertArrayHasKey('username', $data[1]['author']);
        self::assertSame($article2->getAuthor()->getUsername(), $data[1]['author']['username']);
    }

    public function testGetArticlesPreviewReturnsArrayOfItems(): void
    {

        $client = self::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\Blog\Article')->execute();

        $publishedAtArticle1 = "2025-09-21T00:00:00+00:00";
        $article1 = $this->makeArticle(
            $em,
            'author1@preview.test',
            new DateTimeImmutable($publishedAtArticle1),
            'Article 1',
            'body1',
            'summary1',
        );
        $publishedAtArticle2 = "2025-09-19T00:00:00+00:00";
        $article2 = $this->makeArticle(
            $em,
            'author2@preview.test',
            new DateTimeImmutable($publishedAtArticle2),
            'Article 2',
            'body2',
            'summary2',
        );

        $client->request('GET', '/api/articles/preview', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertGreaterThanOrEqual(2, count($data));

        self::assertArrayHasKey('title', $data[0]);
        self::assertSame($article1->getTitle(), $data[0]['title']);
        self::assertArrayHasKey('slug', $data[0]);
        self::assertSame($article1->getSlug(), $data[0]['slug']);
        self::assertArrayHasKey('publishedAt', $data[0]);
        self::assertSame($publishedAtArticle1, $data[0]['publishedAt']);
        self::assertArrayHasKey('author', $data[0]);
        self::assertArrayHasKey('username', $data[0]['author']);
        self::assertSame($article1->getAuthor()->getUsername(), $data[0]['author']['username']);

        self::assertArrayHasKey('title', $data[1]);
        self::assertSame($article2->getTitle(), $data[1]['title']);
        self::assertArrayHasKey('slug', $data[1]);
        self::assertSame($article2->getSlug(), $data[1]['slug']);
        self::assertArrayHasKey('publishedAt', $data[1]);
        self::assertSame($publishedAtArticle2, $data[1]['publishedAt']);
        self::assertArrayHasKey('author', $data[1]);
        self::assertArrayHasKey('username', $data[1]['author']);
        self::assertSame($article2->getAuthor()->getUsername(), $data[1]['author']['username']);
    }
}
