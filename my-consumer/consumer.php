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

    // Function to handle the user creation
    function create_user_foss($data){
        // API endpoint
        $url = "http://192.168.129.30:8090/api/admin/client/create";

        // Data to be sent in the request
        $data = array(
            "email" => $data['email'],
            "first_name" => $data['name'],
            "password" => "Userpassword555"
        );

        // Convert the data array to JSON
        $jsonData = json_encode($data);

        // Initialize cURL
        $ch = curl_init($url);

        // Set the cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Set basic authentication
        curl_setopt($ch, CURLOPT_USERPWD, "admin:DGwLM1cXmm2Ua44ldjYiQRzPN9DgwDDa");

        // Execute the request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            // Output the response
            echo 'Response:' . $response;
        }

        // Close the cURL session
        curl_close($ch);
    }

    // Callback function to handle messages
    $callback = function($msg) {
        echo 'Received ', $msg->body, "\n";
        $data = json_decode($msg->body, true);
        $action = $data['action'];
        switch ($action) {
            case 'create':
                create_user_foss($data);
                break;
            default:
                // Handle other actions or default case
                break;
        } 
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
