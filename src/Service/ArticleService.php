<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Factory\ArticleFactory;
use App\Repository\ArticleRepository;
use Doctrine\ORM\QueryBuilder;
use Goutte\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleService extends AbstractController
{
    private ArticleRepository $articleRepository;
    private ArticleFactory    $articleFactory;

    /**
     * @param ArticleRepository $articleRepository
     * @param ArticleFactory    $articleFactory
     */
    public function __construct(ArticleRepository $articleRepository, ArticleFactory $articleFactory)
    {
        $this->articleRepository = $articleRepository;
        $this->articleFactory    = $articleFactory;
    }

    public function read(int $id): ?Article
    {
        return $this->articleRepository->find($id);
    }

    public function getQuery(): QueryBuilder
    {
        return $this->articleRepository->readAll();
    }

    public function delete(Article $article)
    {
        $this->articleRepository->remove($article, true);
    }

    /**
     * @param Article[] $news
     *
     * @return void
     */
    public function createAll(array $news)
    {
        $this->articleRepository->saveAll($news, true);
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->articleRepository->findBy($criteria);
    }

    /**
     * @param Article[] $availableNews
     *
     * @return string
     */
    public function getAvailableNewsCreatedAt(array $availableNews): string
    {
        $availableNewsCreatedAt = array_map(function (Article $article) {
            return $article->getCreatedAt()->format('d.m.Y H:i:s');
        }, $availableNews);

        return implode(', ', array_unique($availableNewsCreatedAt));
    }

    /**
     * @param Article[] $availableNews
     * @param Article[] $news
     *
     * @return Article[]
     */
    public function removeIntersectArticles(array $availableNews, array $news): array
    {
        $availableNewsTitle = array_map(function (Article $article) {
            return $article->getTitle();
        }, $availableNews);
        foreach ($news as $key => $article) {
            if (in_array($article->getTitle(), $availableNewsTitle)) {
                unset($news[$key]);
            }
        }

        return $news;
    }

    public function parse(string $url)
    {
        $httpClient = new Client();
        $response   = $httpClient->request('GET', $url);

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

        $availableNews = $this->findBy(['title' => $newsTitles]);

        if ($availableNews) {
            $availableNewsCreatedAt = $this->getAvailableNewsCreatedAt($availableNews);

            $this->addFlash('warning', "Some articles are already exists. Created at: $availableNewsCreatedAt");

            $news = $this->removeIntersectArticles($availableNews, $news);
        }

        $this->createAll($news);
    }
}
