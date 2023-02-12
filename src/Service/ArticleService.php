<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleService extends AbstractController
{
    private ArticleRepository $articleRepository;

    /**
     * @param ArticleRepository $articleRepository
     */
    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
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
}
