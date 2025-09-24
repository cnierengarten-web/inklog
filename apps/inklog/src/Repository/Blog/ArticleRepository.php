<?php declare(strict_types=1);

namespace App\Repository\Blog;

use App\Entity\Blog\Article;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findLastPublishedByTag(string $tagSlug, int $limit = 6): array
    {
        return $this->findLastPublishedBy($limit, null, null, $tagSlug);
    }

    public function findLastPublishByCategory(string $categorySlug, int $limit = 6): array
    {
        return $this->findLastPublishedBy($limit, null, $categorySlug);
    }

    public function findLastPublishByAuthorSlug(string $authorSlug, int $limit = 6): array
    {
        return $this->findLastPublishedBy($limit, $authorSlug);
    }

    public function findLastPublished(int $limit = 6): array
    {
        return $this->findLastPublishedBy($limit);
    }

    public function findLastPublishedBy(
        int $limit = 10,
        ?string $authorSlug = null,
        ?string $categorySlug = null,
        ?string $tagSlug = null,
    ): array {
        $idsQuery = $this->createQueryBuilder('a')
            ->select('a.id')
            ->where('a.publishedAt IS NOT NULL')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('now', new DateTimeImmutable());

        if (isset($authorSlug)) {
            $idsQuery->innerJoin('a.author', 'u')
                ->andWhere('u.slug = :userslug')
                ->setParameter('userslug', $authorSlug);
        }

        if (isset($categorySlug)) {
            $idsQuery->innerJoin('a.categories', 'c')
                ->andWhere('c.slug = :categoryslug')
                ->setParameter('categoryslug', $categorySlug);
        }

        if (isset($tagSlug)) {
            $idsQuery->innerJoin('a.tags', 't')
                ->andWhere('t.slug = :tagslug')
                ->setParameter('tagslug', $tagSlug);
        }

        $ids = $idsQuery
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();
        $ids = array_column($ids, 'id');

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select('a')
            ->leftJoin('a.author', 'u')
            ->addSelect('u')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c')
            ->leftJoin('a.tags', 't')
            ->addSelect('t')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
