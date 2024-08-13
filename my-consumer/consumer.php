<?php
require_once './vendor/autoload.php';
    
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Define the interval in seconds
$interval = 10;

while (true) {
    // Perform your task here
    echo "Running task at " . date('Y-m-d H:i:s') . "\n";

    // Sleep for the defined interval
    sleep($interval);
    
    
    // Define connection parameters
    $host = 'rabbitmq'; // Change this to your RabbitMQ host
    $port = 5672;
    $user = 'user';
    $password = 'password';
    
    // Create a new connection
    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();
    
    // Declare a queue
    $channel->queue_declare('wp_user_queue', false, true, false, false);
    
    // Callback function to handle messages
    $callback = function($msg) {
        echo 'Received ', $msg->body, "\n";
        sleep(substr_count($msg->body, '.')); // Simulate work
        echo "Done\n";
    };
    
    // Consume messages from the queue
    $channel->basic_consume('wp_user_queue', '', false, true, false, false, $callback);
    
    // Wait for messages to arrive
    while ($channel->is_consuming()) {
        $channel->wait();
    }
    
    $channel->close();
    $connection->close();
    
}



