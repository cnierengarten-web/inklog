<?php declare(strict_types=1);

namespace App\Controller\Author;

use App\Entity\User;
use App\Repository\Blog\ArticleRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/author', name: 'author_')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        ArticleRepository $articleRepository,
    ): Response {
        $articles = $articleRepository->findLastPublishByAuthorSlug($this->getUser()->getSlug());

        return $this->render('author/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/{slug}', name: 'by_slug', methods: ['GET'])]
    public function authorBySlug(
        ArticleRepository $articleRepository,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        User $author,
    ): Response {
        $articles = $articleRepository->findLastPublishByAuthorSlug($author->getSlug());

        return $this->render('author/by-slug.html.twig', [
            'articles' => $articles,
            'author' => $author,
        ]);
    }
}
