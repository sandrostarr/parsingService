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

    public function readAll(): array
    {
        return $this->articleRepository->findAll();
    }

    public function getQuery(): QueryBuilder
    {
        return $this->articleRepository->readAll();
    }

    public function delete(Article $article)
    {
        $this->articleRepository->remove($article, true);
    }
}
