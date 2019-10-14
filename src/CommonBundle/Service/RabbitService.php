<?php

namespace CommonBundle\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RabbitService extends BaseService
{
    protected $rabbitMQ_host = '';
    protected $rabbitMQ_port = '';
    protected $rabbitMQ_user = '';
    protected $rabbitMQ_pwd = '';
    protected $rabbitMQ_vhost = '';

    public $container = null;
    public $connect = null;
    public $channel = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->channel = $this->init();
    }

    public function init() {
        $this->rabbitMQ_host = $this->container->getParameter('rabbitMQ_host');
        $this->rabbitMQ_port = $this->container->getParameter('rabbitMQ_port');
        $this->rabbitMQ_user = $this->container->getParameter('rabbitMQ_user');
        $this->rabbitMQ_pwd = $this->container->getParameter('rabbitMQ_pwd');
        $this->rabbitMQ_vhost = $this->container->getParameter('rabbitMQ_vhost');
        // 创建连接
        $this->connect = new AMQPStreamConnection($this->rabbitMQ_host, $this->rabbitMQ_port, $this->rabbitMQ_user, $this->rabbitMQ_pwd, $this->rabbitMQ_vhost);
        return $this->connect->channel();
    }

    // hello模式生产者
    public function helloPub()
    {
         /**
          * queue: 队列名
          * passive: 检查queue是否存在, true为开启
          * durable: 队列持久化,true为开启. 持久化的队列会存盘，在服务器重启的时候可以保证不丢失相关的信息
          * exclusive: 只允许被当前连接中的这个connection连接到这个queue, true为开启. 即是否排他. 如果一个队列被声明为排他队列，该队列对首次声明它的连接可见，并在连接断开时自动删除
          *      1. 排他队列是基于连接（Connection）可见的，同一个连接的不同信道（Channel）是可以同时访问同一个连接创建的排他队列
          *      2. "首次" 是指如果一个连接已经声明了一个排他队列，其他连接是不允许建立同名的排他队列，这个与普通队列不同
          *      3. 即使该队列是持久化的，一旦连接关闭或者客户端退出，该队列都会被自动删除，这种队列适用于一个客户端同时发送和读取消息的应用场景
          * auto_delete: 在consumer断开连接后删除queue, true为开启. 自动删除的前提是：至少有一个消费者连接到这个队列，之后所有与这个队列连接的消费者都断开时，才会自动删除
          * nowait: 执行后不需要等待结果
          * arguments: 自定义参数
          * ticket: 未知
         */

        $this->channel->queue_declare('hello', true, false, false, false, false, null, null);
        for ($i = 1; $i <= 100; $i++) {
            /**
             * 第二个参数传入数组,delivery_mode为2标识持久化消息
            */
            $msg = new AMQPMessage('HELLO'.$i, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            /**
             * exchange: 交换器的名称，指明消息需要发送到哪个交换器。如果设置为空字符串，则消息会被发送到 RabbitMQ 默认的交换器中
             * routing_key: 路由键，交换器根据路由键将消息存储到相应的队列之中
             * mandatory: 当参数设置为 true 时，交换器无法根据自身的类型和路由键找到一个符合条件的队列，那么 RabbitMQ 会调用 Basic.Return 命令消息返回给生产者。当参数设置为 false 时，出现上述情况，则消息直接丢失
             * immediate: 当参数设置为 true 时，如果交换器在将消息路由到队列时发现队列上并不存在任何消费者，那么这条消息将不会存入队列中。当与路由键匹配的所有队列都没有消费者时，该消息会通过 Basic.Return 返回至生产者
             * ticket: 未知
            */
            $this->channel->basic_publish($msg,'', 'hello', false, false, null);
            sleep(1);
        }
        $this->channel->close();
    }

    public function helloCom()
    {
        $this->channel->queue_declare('hello', true, false, false, false, false, null, null);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($msg) {
            echo $msg->body."\n";
        };
        /**
         * queue: 队列名
         * consumer_tag: 消费者标签,用来区分多个消费者
         * no_local: 这个功能属于AMQP的标准,但是rabbitMQ并没有做实现
         * no_ack: 设置是否自动确认.建议设置成 false,即不自动确认
         * exclusive: 排他消费者,即这个队列只能由一个消费者消费.适用于任务不允许进行并发处理的情况下
         * nowait: 不返回执行结果,但是如果排他(exclusive)开启的话,则必须需要等待结果的,如果两个同时开启就会报错
         * callback: 处理消息回调函数
        */
        $this->channel->basic_consume('hello', '', false, true, false, false, $callback);
        while (count($this->channel->callbacks)) {
            /**
             * allowed_methods: 调试方法
             * non_blocking: 是否阻塞等待消息
             * timeout: 消息超时,单位秒. 当超过此事件未获得消息,退出阻塞等待.会抛出异常.
            */
            $this->channel->wait(null, false, 5);
        }
        $this->channel->close();
    }

    // work模式消费者
    public function workCom($queue)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($msg) {
            echo $msg->body."\n";
            // 消息确认,确认后才会处理下一数据
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        /**
         * 此限制语句要放在获取消息的前面,才会生效.
         * prefetch_size: 最大未确认消息的字节数
         * prefetch_count: 最大未确认消息的条数
         * global: 上述限制的限定对象，glotal=true时表示在当前channel上所有的consumer都生效，否则只对设置了之后新建的consumer生效
        */
        $this->channel->basic_qos(null, 1, null);
        /**
         * 开启ACK, 进行手动确认消息
        */
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }

    // 发布订阅模式生产者
    public function publishPub()
    {
        /**
         * exchange: 交换机名称
         * type: 交换机模式 fanout, direct, topic
         * passive: 如果为 true, 则执行声明或者检查交换机是否存在
         * durable: 设置是否持久化.durable 设置为 true 表示持久化，反之非持久化。持久化可以将交换机存盘，在服务器重启的时候不会丢失消息.
         * auto_delete: 设置是否自动删除。auto_delete 设置为 true 则表示自动删除。自动删除的前提是至少有一个队列或者交换机绑定，之后所有与这个交换机绑定的队列或者交换机都与此解绑
         * internal: 设置是否是内置的。如果设置为 true，则表示是内置的交换机，客户端程序无法直接发送消息到这个交换机中，只能通过交换机路由到交换机这种方式
         * nowait: 如果设置为 false, 则不期望 RabbitMQ 服务器有一个 Exchange.DeclareOk 这样响应
         * arguments: 其他一些结构化参数
        */
        $this->channel->exchange_declare('logs', 'fanout', false, false, false, false, false, array(), null);
        for ($i = 1; $i <= 100; $i++) {
            $num = rand(1, 5);
            $msg = new AMQPMessage($num);
            $this->channel->basic_publish($msg, 'logs');
        }
        $this->channel->close();
    }

    // 发布订阅模式消费者
    public function subscribeCom()
    {
        $this->channel->exchange_declare('logs', 'fanout', false, false, false);
        // 创建一个临时队列, 临时队列就是队列名为空. 会自动创建并返回自动生成的队列名
        list($queue_name, ,) = $this->channel->queue_declare('', false, false, true, false);
        /**
         * queue_name: 队列名
         * exchange: 交换机名称
         * routing_key: 用来绑定队列和交换器的路由键
         * nowait: 如果设置为 false, 则不期望 RabbitMQ 服务器有一个 Exchange.DeclareOk 这样响应
        */
        $this->channel->queue_bind($queue_name, 'logs', '', false, array(), null);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($msg) {
            echo $msg->body."\n";
        };
        $this->channel->basic_consume($queue_name, false, true, false, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }

    // 路由模式生产者
    public function routePub()
    {
        $this->channel->exchange_declare('direct_logs', 'direct', false, false, false);
        // 定义3个路由名字, 随机绑定到交换机中, 模拟数据
        $routes = array('info', 'waring', 'error');
        for ($i = 1; $i <= 100; $i++) {
            $num = rand(0, 2);
            $msg = new AMQPMessage($routes[$num].$num);
            $this->channel->basic_publish($msg, 'direct_logs', $routes[$num]);
        }
        $this->channel->close();
    }

    // 路由模式消费者
    public function routeCom()
    {
        $this->channel->exchange_declare('direct_logs', 'direct', false, false, false);
        list($queue_name, ,) = $this->channel->queue_declare('', false, false, true, false);
        // 只接受两种路由的数据, 此处也就是可以选择性的接收数据. 绑定了哪个路由就接收哪个路由的数据. 可以给一个队列绑定多个路由
        $routes = array('info', 'waring');
        foreach ($routes as $route) {
            $this->channel->queue_bind($queue_name, 'direct_logs', $route);
        }
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($msg) {
            echo $msg->delivery_info['routing_key'],"==",$msg->body."\n";
        };
        $this->channel->basic_consume($queue_name, '', false, true, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }

    // 侦听模式生产者Topic
    public function topicPub()
    {
        $this->channel->exchange_declare('topic_logs', 'topic', false, false, false);
        // 随机生成一些组合路由,模拟写入数据
        $a = array('quick', 'lazy');
        $b = array('orange', 'brown', 'pink');
        $c = array('elephant', 'rabbit', 'fox');
        // 组装随机路由
        for ($i = 1; $i <= 100; $i++) {
            $key = $a[rand(0, 1)].".".$b[rand(0, 2)].".".$c[rand(0,2)];
            $num = rand(100000, 999999);
            $msg = new AMQPMessage($num);
            $this->channel->basic_publish($msg, 'topic_logs', $key);
        }
        $this->channel->close();
    }

    // 侦听模式消费者Topic
    public function topicCom()
    {
        $this->channel->exchange_declare('topic_logs', 'topic', false, false, false);
        list($queue_name, ,) = $this->channel->queue_declare('', false, false, true, false);
        $route = 'quick.pink.fox';
        $this->channel->queue_bind($queue_name, 'topic_logs', $route);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($msg) {
            echo $msg->delivery_info['routing_key'],"==",$msg->body."\n";
        };
        $this->channel->basic_consume($queue_name, '', false, true, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }

    // RPC模式生产者
    public function rpcPu()
    {
        $response = null;
        $corr_id = uniqid();
        // 创建队列
        list($callback_queue, ,) = $this->channel->queue_declare('', false, false, true, false);
        $callback = function($msg) use ($corr_id, &$response) {
            if ($msg->get('correlation_id') == $corr_id) {
                $response = $msg->body;
            }
        };
        $this->channel->basic_consume($callback_queue, '', false, false, false, false, $callback);
        $msg = new AMQPMessage(10, array('correlation_id' => $corr_id, 'reply_to' => $callback_queue));
        $this->channel->basic_publish($msg, '', 'rpc_queue');
        while(!$response) {
            $this->channel->wait();
        }
        echo $response;
        $this->channel->close();
    }

    // RPC模式消费者
    public function rpcCom()
    {
        $this->channel->queue_declare('rpc_queue', false, false, false, false);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $callback = function($req) {
            $n = $req->body;
            echo $req->delivery_info['routing_key'],"==",$req->body."\n";
            $msg = new AMQPMessage($n, array('correlation_id' => $req->get('correlation_id')));
            $req->delivery_info['channel']->basic_publish($msg, '', "amq.gen-8YXMSdo0E_8e-JTh0UApaw");
            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
        };
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
    }
}