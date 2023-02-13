<?php declare(strict_types=1);

namespace App\Controller;

use App\Enum\Url;
use App\Service\ArticleService;
use Knp\Component\Pager\PaginatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
        $connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD'));
        $channel    = $connection->channel();

        $channel->queue_declare('parse', false, false, false, false);

        $msg = new AMQPMessage(Url::HIGHLOAD_TODAY);
        $channel->basic_publish($msg, '', 'parse');

        $channel->close();
        $connection->close();

        return $this->redirectToRoute('index');
    }
}
