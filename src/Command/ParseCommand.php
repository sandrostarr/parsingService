<?php declare(strict_types=1);

namespace App\Command;

use App\Enum\Url;
use App\Factory\ArticleFactory;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use Goutte\Client;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

class ParseCommand extends Command
{
    protected static $defaultName = 'parser:start';
    private ArticleService $articleService;

    private ArticleFactory $articleFactory;

    /**
     * @param ArticleService $articleService
     * @param ArticleFactory $articleFactory
     * @param string|null $name
     */
    public function __construct(
        ArticleService $articleService,
        ArticleFactory $articleFactory,
        string $name = null
    )
    {
        $this->articleService = $articleService;
        $this->articleFactory = $articleFactory;

        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Start</info>");

        $httpClient = new Client();
        $response = $httpClient->request('GET', Url::HIGHLOAD_TODAY);

        $titles = $response->evaluate('//div[@class="lenta-item"]//a//h2');
        $descriptions = $response->evaluate('//div[@class="lenta-item"]/p');
        $images = $response->evaluate('//div[@class="lenta-item"]//a//div[@class="lenta-image"]/noscript/img/@src');
        $publishedAtDates = $response->evaluate('//div[@class="lenta-item"]//span[@class="meta-datetime"]');

        $news = [];
        $newsTitles = [];
        foreach ($titles as $key => $title) {
            $title = $title->textContent;
            $description = $descriptions->getNode($key)->textContent;
            $image = $images->getNode($key)->textContent;
            $publishedAtDate = $publishedAtDates->getNode($key)->textContent;

            $article = $this->articleFactory->makeArticle(
                $title,
                $description,
                $image,
                $publishedAtDate
            );

            $news[] = $article;
            $newsTitles[] = $title;
        }

        $availableNews = $this->articleService->findBy(['title' => $newsTitles]);

        if ($availableNews) {
            $availableNewsCreatedAt = $this->articleService->getAvailableNewsCreatedAt($availableNews);

            $output->writeln("<info>Some articles are already exists. Created at: $availableNewsCreatedAt</info>");

            $news = $this->articleService->removeIntersectArticles($availableNews, $news);
        }

        $this->articleService->createAll($news);

        $output->writeln("<info>Finish</info>");

        return 0;
    }
}
