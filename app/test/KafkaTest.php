<?php

namespace app\test;

use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\ConsumerTopic;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use RuntimeException;

class KafkaTest extends TestCase
{
    private string $topic_name = 'user';
    private Producer $producer;
    private Consumer $consumer;
    private MonologTest $log;
    private ProducerTopic $producer_topic;
    private ConsumerTopic $consumer_topic;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->producer = $this->getProducer();
        $this->consumer = $this->getConsumer();
        $this->producer_topic = $this->getProducerTopic($this->topic_name);
        $this->consumer_topic = $this->getConsumerTopic($this->topic_name);
        $this->log = new MonologTest('');
    }

    public function getProducer(): Producer
    {
        $producer_config = new Conf();
        $producer_config->set('log_level', (string)LOG_WARNING);
//        $producer_config->set('debug', 'all');
        $producer_config->set('queue.buffering.max.ms', 10);
        $producer_config->set('bootstrap.servers', 'localhost:9092');
        $producer_config->set('batch.num.messages', 100);
        $producer_config->set('socket.keepalive.enable', "true");
        $producer_config->set('api.version.request', "false");
        $producer_config->setDrMsgCb(function ($kafka, $message) {
            if ($message->err) {
                $this->log->write('kafka_producer_response', 'error', [$kafka, $message]);
            } else {
                $this->log->write('kafka_producer_response', 'error', [$message]);
            }
        });
        $producer_config->setErrorCb(function ($kafka, $err, $reason) { //发送失败后调用
            $this->log->write('kafka_producer_response', 'error', ['kafka' => $kafka, 'err' => $err, 'reason' => $reason]);
        });
        $rk = new Producer($producer_config);
        $rk->addBrokers("127.0.0.1:9092");
        return $rk;
    }

    public function getConsumer(): Consumer
    {
        $consumer_config = new Conf();
        $consumer_config->set('log_level', (string)LOG_WARNING);
//        $conf->set('debug', 'all');
        $consumer_config->set('enable.partition.eof', 'true');
        $consumer_config->set('bootstrap.servers', 'localhost:9092');
        $consumer_config->set('group.id', 'test-consumer-group');
        $rk = new Consumer($consumer_config);
        $rk->addBrokers("127.0.0.1:9092");
        return $rk;
    }

    public function getProducerTopic($name): ProducerTopic
    {
        return $this->producer->newTopic($name);
    }

    public function getConsumerTopic($name): ConsumerTopic
    {
        $topicConf = new TopicConf();
        $topicConf->set('request.required.acks', 1);
        $topicConf->set('auto.offset.reset', 'earliest');
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.commit.interval.ms', 100);
        return $this->consumer->newTopic($name, $topicConf);
    }

    public function testCreateTopic(): void
    {
        $topic = $this->producer->newTopic('test');
        $this->assertNotEmpty($topic->getName());
    }

    public function testProduce(): void
    {
        $this->producer_topic->produce(RD_KAFKA_PARTITION_UA, 0, "Message payload");
        $this->producer->flush(0);
        while ($this->producer->getOutQLen() > 0) {
            $this->producer->poll(1);
        }
    }

    public function testMultiProduce(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->producer_topic->produce(RD_KAFKA_PARTITION_UA, 0, "Message $i");
            $this->producer->poll(0);
        }

        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new RuntimeException('Was unable to flush, messages might be lost!');
        }
    }

    public function testConsumer(): void
    {
        $this->consumer_topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
        while (true) {
            $msg = $this->consumer_topic->consume(0, 120 * 1000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            }
            if ($msg->err) {
                $this->log->write($msg->errstr(), 'error');
                echo $msg->errstr(), "\n";
                break;
            }
            $this->log->write($msg->payload);
        }
    }

    public function testMultiConsumer(): void
    {
        $queue = $this->getConsumer()->newQueue();

        $topic1 = $this->getConsumer()->newTopic("topic1");
        $topic1->consumeQueueStart(0, RD_KAFKA_OFFSET_BEGINNING, $queue);
        $topic1->consumeQueueStart(1, RD_KAFKA_OFFSET_BEGINNING, $queue);

        $topic2 = $this->getConsumer()->newTopic("topic2");
        $topic2->consumeQueueStart(0, RD_KAFKA_OFFSET_BEGINNING, $queue);

        while (true) {
            $msg = $queue->consume(1000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                continue;
            }

            if ($msg->err) {
                $this->log->write($msg->errstr(), 'error');
                break;
            }

            $this->log->write($msg->payload);
        }
    }

    public function testHighLevelConsumer(): void
    {
        $conf = new Conf();
        $conf->set('group.id', 'myConsumerGroup');
        $conf->set('metadata.broker.list', '127.0.0.1');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.partition.eof', 'true');
        $conf->setRebalanceCb(function (KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: ";
                    $this->log->write('partitions', 'error', $partitions); // 日志在进程停止才会写入
                    $kafka->assign($partitions);
                    break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: ";
                    $this->log->write('partitions', 'error', $partitions);
                    $kafka->assign();
                    break;
                default:
                    throw new RuntimeException($err);
            }
        });
        $consumer = new KafkaConsumer($conf);
        try {
            $consumer->subscribe(['user']);
        } catch (Exception $e) {
            $this->log->write($e->getMessage(), 'error', [$e->getFile(), $e->getLine()]);
        }
        while (true) {
            try {
                $message = $consumer->consume(120 * 1000);
            } catch (Exception $e) {
                $this->log->write($e->getMessage(), 'error', [$e->getFile(), $e->getLine()]);
            }
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->log->write($message->payload);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $this->log->write('No more messages; will wait for more', 'notice');
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    $this->log->write('Timed out', 'error');
                    break;
                default:
                    throw new RuntimeException($message->errstr(), $message->err);
            }
        }
    }

    // Errors
    public function testTransactionalProducer(): void
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', 'localhost:9092');
        $conf->set('transactional.id', 'some-id');
        $producer = new Producer($conf);

        $topic = $producer->newTopic("test");
        $producer->initTransactions(10000);
        $producer->beginTransaction();
        for ($i = 0; $i < 10; $i++) {
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, "Transactional Producer Message $i");
            $producer->poll(0);
        }
        $error = $producer->commitTransaction(10000);
        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $error) {
            throw new RuntimeException('Was unable to flush, messages might be lost!');
        }
    }
}