<?php

namespace App\Controller;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use App\Entity\Tag;
use App\Repository\Blog\ArticleRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/article', name: 'article_')]
final class ArticleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Article $article,
    ): Response {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/tags/{slug}', name: 'by_tag', methods: ['GET'])]
    public function listByTag(
        ArticleRepository $articleRepository,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Tag $tag,
    ): Response {
        $articles = $articleRepository->findLastPublishedByTag($tag->getSlug());

        return $this->render('article/list-by-tag.html.twig', [
            'articles' => $articles,
            'tag' => $tag,
        ]);
    }

    #[Route('/category/{slug}', name: 'by_category', methods: ['GET'])]
    public function listByCategory(
        ArticleRepository $articleRepository,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Category $category,
    ): Response {
        $articles = $articleRepository->findLastPublishByCategory($category->getSlug());

        return $this->render('article/list-by-category.html.twig', [
            'articles' => $articles,
            'category' => $category,
        ]);
    }
}
