<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\ArticleService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends AbstractController
{
    private ArticleService $articleService;

    /**
     * @param ArticleService $articleService
     */
    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $news = $this->articleService->readAll();

        $query = $this->articleService->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('news/index.html.twig', [
            'news'     => $news,
            'entities' => $pagination
        ]);
    }

    public function delete(int $id): RedirectResponse
    {
        $article = $this->articleService->read($id);

        if ($article) {
            $this->articleService->delete($article);
            $this->addFlash('success', 'Article successfully deleted!');
        }
        else {
            $this->addFlash('warning', 'Article not found!');
        }

        return $this->redirectToRoute('index');
    }
}
