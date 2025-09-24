<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\Blog\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findLastPublished();

        return $this->render('home/index.html.twig', [
            'articles' => $articles,
        ]);
    }
}
