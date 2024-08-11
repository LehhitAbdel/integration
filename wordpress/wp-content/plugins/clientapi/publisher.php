<?php
/*
Plugin Name: Customer Manager
Description: Manage customers and send data to RabbitMQ
Version: 1.0
Author: Abdel Lehhit
*/
 
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;




