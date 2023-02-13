<?php declare(strict_types=1);

namespace App\Command;

use App\Enum\Url;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends Command
{
    protected static $defaultName = 'parser:start';

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Start</info>");

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

        $output->writeln("<info>Finish</info>");

        return 0;
    }
}
