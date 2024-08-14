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
    $channel->queue_declare('fossbilling_client_queue', false, true, false, false);


    // Callback function to handle messages
    $callback = function ($msg) {
        echo 'Received ', $msg->body, "\n";
        $data = json_decode($msg->body, true);
        $action = $data['action'];
        switch ($action) {
            case 'create':
                create_user_foss($data);
                break;
            case 'delete':
                delete_user_foss($data);
                break;
            case 'update':
                update_user_foss($data);
                break;
            default:
                // Handle other actions or default case
                break;
        }
        echo "Done\n";
    };

    // Callback function to handle messages
    $callbackfbtowp = function ($msg) {
        echo 'Received ', $msg->body, "\n";
        $data = json_decode($msg->body, true);
        $action = $data['action'];
        switch ($action) {
            case 'create':
                create_user_wp($data);
                break;
            case 'delete':
                delete_user_wp($data);
                break;
            case 'update':
                update_user_wp($data);
                break;
            default:
                // Handle other actions or default case
                break;
        }
        echo "Done\n";
    };

    function create_user_wp($data) {
        $url = "http://192.168.129.30:8080/wp-json/custom-users/v1/users";
    
        $user_data = array(
            'email'    => $data['email'],
            'name'     => $data['name'],
        );
    
        $jsonData = json_encode($user_data);
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo 'Response:' . $response;
        }
    
        curl_close($ch);
    }
    

    function update_user_wp($data) {
        $user_id = $data['clientId']; // Ensure you pass the user's ID in the $data array
        $url = "http://192.168.129.30:8080/wp-json/custom-users/v1/users/{$user_id}";
    
        $user_data = array(
            'email'    => $data['clientData']['email'],
            'name'     => $data['clientData']['first_name'],
        );
    
        $jsonData = json_encode($user_data);
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo 'Response:' . $response;
        }
    
        curl_close($ch);
    }
    
    

    function delete_user_wp($data) {
        $url = "http://192.168.129.30:8080/wp-json/custom-users/v1/users/" . $data['clientId'];
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo 'Response:' . $response;
        }
    
        curl_close($ch);
    }
    
    


    // Function to handle the user creation
    function create_user_foss($data)
    {
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
        curl_setopt($ch, CURLOPT_USERPWD, "admin:4urnukG3TOJgKm2nVYwNDhQuQxibcSMA");

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



    function delete_user_foss($data)
    {
        // API endpoint for deleting a user
        $url = "http://192.168.129.30:8090/api/admin/client/delete";

        // Data to be sent in the request (assuming user deletion is based on id)
        $data = array(
            "id" => $data['id'],
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Set basic authentication
        curl_setopt($ch, CURLOPT_USERPWD, "admin:4urnukG3TOJgKm2nVYwNDhQuQxibcSMA");

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

    function update_user_foss($data)
    {
        // API endpoint for deleting a user
        $url = "http://192.168.129.30:8090/api/admin/client/update";

        // Data to be sent in the request (assuming user deletion is based on id)
        $data = array(
            "id" => $data['id'],
            "email" => $data['email'],
            "first_name" => $data['name'],
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Set basic authentication
        curl_setopt($ch, CURLOPT_USERPWD, "admin:4urnukG3TOJgKm2nVYwNDhQuQxibcSMA");

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


    // Consume messages from the queue
    $channel->basic_consume('wp_user_queue', '', false, true, false, false, $callback);
    $channel->basic_consume('fossbilling_client_queue', '', false, true, false, false, $callbackfbtowp);

    // Wait for messages to arrive
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
}
