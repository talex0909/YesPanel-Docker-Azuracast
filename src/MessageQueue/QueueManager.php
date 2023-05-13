<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Service\RedisFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Exception\TransportException;

final class QueueManager extends AbstractQueueManager
{
    /** @var Connection[] */
    private array $connections = [];

    public function __construct(
        private readonly RedisFactory $redisFactory
    ) {
    }

    public function clearQueue(QueueNames $queue): void
    {
        $connection = $this->getConnection($queue);

        $connection->cleanup();
        $connection->setup();
    }

    public function getTransport(QueueNames $queue): RedisTransport
    {
        return new RedisTransport($this->getConnection($queue));
    }

    /**
     * @return RedisTransport[]
     */
    public function getTransports(): array
    {
        $transports = [];
        foreach (QueueNames::cases() as $queue) {
            $transports[$queue->value] = $this->getTransport($queue);
        }
        return $transports;
    }

    private function getConnection(QueueNames $queue): Connection
    {
        $queueName = $queue->value;

        if (!isset($this->connections[$queueName])) {
            $this->connections[$queueName] = new Connection(
                [
                    'stream' => 'messages.' . $queueName,
                    'delete_after_ack' => true,
                    'redeliver_timeout' => 43200,
                    'claim_interval' => 86400,
                ],
                $this->redisFactory->createInstance()
            );
        }

        return $this->connections[$queueName];
    }

    public function getQueueCount(QueueNames $queue): int
    {
        try {
            return $this->getConnection($queue)->getMessageCount();
        } catch (TransportException) {
            return 0;
        }
    }
}
