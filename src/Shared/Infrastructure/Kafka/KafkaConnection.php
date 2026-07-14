<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kafka;

use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RdKafka\Conf;
use RdKafka\Exception as RKafkaException;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicPartition;

class KafkaConnection implements SingletonInterface
{
    use SingletonTrait;
    private const int READ_TOTAL_TIMEOUT_SEC = 5;
    private const int READ_TIMEOUT_MICRO_SEC = 300;
    private const int READ_MICRO_TIMEOUT_ATTEMPT = 3;

    private ?Producer $producer = null;

    private Conf $conf;

    /**
     * @var ProducerTopic[]
     */
    private array $producerTopics = [];


    private function __construct()
    {
        $brokers = Config::getInstance()->getByPath('kafka.brokers');

        $this->conf = new Conf();
        $this->conf->set('metadata.broker.list', $brokers);
    }

    public function publish(string $topic, string $message, int $partition = 0): void
    {
        $topic = $this->getProducerTopic($topic);
        $topic->produce($partition, 0, $message);

        $this->producer->flush(1000);
    }

    /**
     * @throws RKafkaException
     */
    public function read(string $topic, int $limit, int $offset = 0, int $partition = 0): array
    {
        $kafkaConsumer = $this->getConsumer();

        $kafkaConsumer->assign([new TopicPartition(
            topic: $topic,
            partition: $partition,
            offset: $offset,
        )]);

        $messages = [];
        $noMessageCount = 0;
        $startTime = time();

        while (count($messages) < $limit) {
            if (time() - $startTime > self::READ_TOTAL_TIMEOUT_SEC) {
                break;
            }

            $msg = $kafkaConsumer->consume(self::READ_TIMEOUT_MICRO_SEC);

            if ($msg === null
                || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF
            ) {
                break;
            }

            if ($msg->err === RD_KAFKA_RESP_ERR__TIMED_OUT) {
                $noMessageCount++;
                if ($noMessageCount >= self::READ_MICRO_TIMEOUT_ATTEMPT) {
                    break;
                }
                continue;
            }

            $noMessageCount = 0;
            $messages[] = $msg->payload;
        }

        return $messages;
    }

    private function getProducer(): Producer
    {
        if (is_null($this->producer)) {
            $this->producer = new Producer($this->conf);
        }

        return $this->producer;
    }

    private function getConsumer(): KafkaConsumer
    {

        return new KafkaConsumer($this->conf);
    }

    private function getProducerTopic($name): ProducerTopic
    {
        if (!array_key_exists($name, $this->producerTopics)) {
            $this->producerTopics[$name] = $this->getProducer()->newTopic($name);
        }

        return $this->producerTopics[$name];
    }

}
