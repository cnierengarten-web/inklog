<?php declare(strict_types=1);

namespace App\Tests\Unit\Entity\Blog;

use App\Entity\Blog\Article;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

final class ArticleTest extends TestCase
{
    public function testRemoveImageFileFromSerialize(): void
    {
        $article = new Article();
        $article->setTitle('Article title');
        $article->setSummary('Summary of this article');
        $article->setContent('Content of article');

        $pre = (array)$article;
        $key = "\0".Article::class."\0imageFile";
        self::assertArrayHasKey($key, $pre);


        $data = $article->__serialize();
        self::assertArrayNotHasKey($key, $data);
    }

    public function testPublishedAtIsSetWhenPublish(): void
    {
        $article = new Article();
        assertNull($article->getPublishedAt(), 'PublishedAt must be empty before publish');


        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $article->publish($now);
        assertSame($now, $article->getPublishedAt(), 'PublishedAt must be set to now when publish');
    }

    public function testPublishedAtIsEmptyAfterUnpublish(): void
    {
        $article = new Article();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $article->setPublishedAt($now);
        assertSame($now, $article->getPublishedAt(), 'PublishedAt must be set to now before unpublish');

        $article->unpublish();
        assertNull($article->getPublishedAt(), 'PublishedAt must be empty after unpublish');

    }

    public function testIsPublishedReturnFalseIfNotPublished(): void
    {
        $article = new Article();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        self::assertFalse($article->isPublished($now), 'IsPublished must return false when publishedAt is empty');

        $tomorrow = new DateTimeImmutable('tomorrow', new DateTimeZone('UTC'));
        $article->setPublishedAt($tomorrow);
        self::assertFalse($article->isPublished($now), 'IsPublished must return false when publishedAt is in future');
    }

    public function testIsPublishedReturnTrueIfPublished(): void
    {
        $article = new Article();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $yesterday = new DateTimeImmutable('yesterday', new DateTimeZone('UTC'));
        $article->setPublishedAt($yesterday);
        self::assertTrue($article->isPublished($now), 'isPublished mus return true when publishedAt is in past');

        $article->setPublishedAt($now);
        self::assertTrue($article->isPublished($now), 'isPublished mus return true when publishedAt is now');
    }

}
