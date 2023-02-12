<?php declare(strict_types=1);

namespace App\Controller;

use App\Enum\Url;
use App\Factory\ArticleFactory;
use App\Service\ArticleService;
use Goutte\Client;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends AbstractController
{
    private ArticleService $articleService;

    private ArticleFactory $articleFactory;

    /**
     * @param ArticleService $articleService
     * @param ArticleFactory $articleFactory
     */
    public function __construct(ArticleService $articleService, ArticleFactory $articleFactory)
    {
        $this->articleService = $articleService;
        $this->articleFactory = $articleFactory;
    }

    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->articleService->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('news/index.html.twig', [
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

    public function parse(): Response
    {
        $httpClient = new Client();
        $response   = $httpClient->request('GET', Url::HIGHLOAD_TODAY);

        $titles           = $response->evaluate('//div[@class="lenta-item"]//a//h2');
        $descriptions     = $response->evaluate('//div[@class="lenta-item"]/p');
        $images           = $response->evaluate('//div[@class="lenta-item"]//a//div[@class="lenta-image"]/noscript/img/@src');
        $publishedAtDates = $response->evaluate('//div[@class="lenta-item"]//span[@class="meta-datetime"]');

        $news       = [];
        $newsTitles = [];
        foreach ($titles as $key => $title) {
            $title           = $title->textContent;
            $description     = $descriptions->getNode($key)->textContent;
            $image           = $images->getNode($key)->textContent;
            $publishedAtDate = $publishedAtDates->getNode($key)->textContent;

            $article = $this->articleFactory->makeArticle(
                $title,
                $description,
                $image,
                $publishedAtDate
            );

            $news[]       = $article;
            $newsTitles[] = $title;
        }

        $availableNews = $this->articleService->findBy(['title' => $newsTitles]);

        if ($availableNews) {
            $availableNewsCreatedAt = $this->articleService->getAvailableNewsCreatedAt($availableNews);

            $this->addFlash('warning', "Some articles are already exists. Created at: $availableNewsCreatedAt");

            $news = $this->articleService->removeIntersectArticles($availableNews, $news);

            $this->articleService->createAll($news);
        }

        return $this->redirectToRoute('index');
    }
}
