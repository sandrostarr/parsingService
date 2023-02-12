<?php declare(strict_types=1);

namespace App\Factory;

use App\Entity\Article;

class ArticleFactory
{
    public function makeArticle(
        string $title,
        string $description,
        string $picture,
        string $publishedAt
    ): Article
    {
        $article = new Article();
        $article->setTitle($title);
        $article->setDescription($description);
        $article->setPicture($picture);
        $article->setPublishedAt($publishedAt);
        $article->setCreatedAt(new \DateTimeImmutable('now'));

        return $article;
    }
}
