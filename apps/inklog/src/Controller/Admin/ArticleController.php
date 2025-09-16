<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Blog\Article;
use App\Form\Blog\ArticleType;
use App\Repository\Blog\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/article', name: 'admin_article_')]
final class ArticleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy([], ['updatedAt' => 'DESC']);

        return $this->render('admin/article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $article = new Article();

        $article->setAuthor($this->getUser());

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'Article créé avec succès !');

            return $this->redirectToRoute('admin_article_index', ['id' => $article->getId()]);
        }

        return $this->render('admin/article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Article modifié avec succès !');

            return $this->redirectToRoute('admin_article_index', ['id' => $article->getId()]);
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('admin/article/show.html.twig', [
            'article' => $article,
        ]);
    }


    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->get('_token'))) {
            $this->entityManager->remove($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'Article supprimé avec succès !');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/publish', name: 'publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('publish'.$article->getId(), $request->getPayload()->get('_token'))) {
            if (!$article->isPublished()) {
                $article->publish();
                $this->entityManager->flush();
                $this->addFlash('success', 'Article publié avec succès !');
            } else {
                $this->addFlash('notice', 'L\'article est déjà publié');

            }
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/unpublish', name: 'unpublish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unpublish(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('unpublish'.$article->getId(), $request->getPayload()->get('_token'))) {
            if ($article->isPublished()) {
                $article->unpublish();
                $this->entityManager->flush();
                $this->addFlash('success', 'Article dépublié avec succès !');
            } else {
                $this->addFlash('notice', 'L\'article n\'est pas publié.');
            }

        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_article_index');
    }
}
