<?php declare(strict_types=1);

namespace App\Command;

use App\Service\ArticleService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReceiveCommand extends Command
{
    protected static       $defaultName = 'parser:receive';
    private ArticleService $articleService;

    /**
     * @param ArticleService $articleService
     * @param string|null    $name
     */
    public function __construct(
        ArticleService $articleService,
        string         $name = null
    )
    {
        $this->articleService = $articleService;

        parent::__construct($name);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD'));
        $channel    = $connection->channel();

        $channel->queue_declare('parse', false, false, false, false);

        $callback = function ($msg) {
            $this->articleService->parse($msg->body);
        };

        $channel->basic_consume('parse', '', false, true, false, false, $callback);

        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return 0;
    }
}
