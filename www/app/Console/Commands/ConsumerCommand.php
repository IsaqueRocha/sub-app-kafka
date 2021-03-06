<?php

namespace App\Console\Commands;

use App\Models\Stats;
use Exception;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class ConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kafka Consumer';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $consumer = new KafkaConsumer($this->getConf());

        /*
         * Subscribe to topic 'inventories'
         * Microservice 1 will push to 'inventories' topic
         */
        $consumer->subscribe(['inventories']);

        while (true) {
            $message = $consumer->consume(120 * 1000);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->processMessage($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    throw new Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    /**
     * Process Kafka message
     *
     * @param \RdKafka\Message $kafkaMessage
     * @return void
     */
    protected function processMessage(Message $kafkaMessage)
    {
        $message = $this->decodeKafkaMessage($kafkaMessage);

        Stats::updateOrCreate(
            ['inventory_id' => $message->body->id],
            ['make' => $message->body->make, 'model' => $message->body->model]
        );

        $this->info(json_encode($message));
    }

    /**
     * Decode kafka message
     *
     * @param \RdKafka\Message $kafkaMessage
     * @return object
     */
    protected function decodeKafkaMessage(Message $kafkaMessage)
    {
        $message = json_decode($kafkaMessage->payload);

        if (is_string($message->body)) {
            $message->body = json_decode($message->body);
        }

        return $message;
    }


    /**
     * Get the kafka config
     *
     * @return \Rdkafka\Conf
     */
    protected function getConf()
    {
        $conf = new Conf();

        /*
         * Configure the group.id.
         * All consumer with the same group.id will consume different partitions.
         */
        $conf->set('group.id', 'myConsumerGroup');

        /*
         * Initial list of kafka brokers
         */
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        /*
         * Set where to start consuming messages when there is no initial offset in
         * offset store or the desired offset is out of range.
         * 'earliest': start from the beginning
         */
        $conf->set('auto.offset.reset', 'latest');

        /*
         * Automatically and periodically commit offsets in the background
         */
        $conf->set('enable.auto.commit', 'false');

        return $conf;
    }
}
