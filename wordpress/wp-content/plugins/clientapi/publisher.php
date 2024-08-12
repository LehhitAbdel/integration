<?php
/*
Plugin Name: Manage Users
Description: CRUD Users and publish to RabbitMQ
Version: 1.0
Author: Lehhit Abdel
*/
 
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


// WordPress Hooks and Actions
add_action('admin_menu', 'user_overview_menu');
add_action('admin_init', 'user_delete_handler');
add_action('delete_user', 'send_deleted_user_to_rabbitmq');
register_activation_hook(__FILE__, 'create_users_table');

function user_overview_menu() {
    add_menu_page('User Overview', 'User Overview', 'manage_options', 'user-overview', 'user_overview_page');
    add_submenu_page('user-overview', 'Add User', 'Add User', 'manage_options', 'add-user', 'user_add_page');
}

function user_overview_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $users = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap user-overview">
        <div class="user-header">
            <h1 class="user-title">Manage Users</h1>
            <a href="<?php echo admin_url('admin.php?page=add-user'); ?>" class="user-btn user-btn-add">+ Add New User</a>
        </div>
        
        <div class="user-content">
            <?php if (empty($users)): ?>
                <div class="user-notice user-notice-warning">
                    <p>No users are currently registered. Start by adding a new user!</p>
                </div>
            <?php else: ?>
                <div class="user-table-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user->id); ?></td>
                                    <td><?php echo esc_html($user->name); ?></td>
                                    <td><?php echo esc_html($user->email); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=edit-user&id=' . $user->id); ?>" class="user-btn user-btn-edit">Edit</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=delete_user&id=' . $user->id), 'delete_user'); ?>" class="user-btn user-btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .user-overview {
            font-family: 'Arial', sans-serif;
            max-width: 90%;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-title {
            font-size: 24px;
            color: #333;
        }
        .user-btn {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
        }
        .user-btn-add {
            background-color: #28a745;
        }
        .user-btn-edit {
            background-color: #007bff;
        }
        .user-btn-delete {
            background-color: #dc3545;
        }
        .user-content {
            margin-top: 20px;
        }
        .user-notice {
            padding: 15px;
            border-left: 4px solid #f39c12;
            background-color: #fef7e0;
            border-radius: 5px;
        }
        .user-notice p {
            margin: 0;
            font-size: 16px;
        }
        .user-table-wrapper {
            overflow-x: auto;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
        }
        .user-table th {
            background-color: #f1f1f1;
            border-bottom: 2px solid #ddd;
        }
        .user-table td {
            border-bottom: 1px solid #ddd;
        }
    </style>
    <?php
}

function user_add_page() {
    if (isset($_POST['submit_user_form'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'users_custom';
        $wpdb->insert($table_name, [
            'name' => $name,
            'email' => $email,
            'created_at' => current_time('mysql')
        ]);

        // Send data to RabbitMQ
        $rabbit_sender = new RabbitMQPublisher();
        $rabbit_sender->publish(json_encode(['action' => 'create', 'name' => $name, 'email' => $email]));
        echo '<div class="notice notice-success is-dismissible"><p>User added and sent to RabbitMQ!</p></div>';
    }
    ?>
    <div class="wrap user-add">
        <h1 class="user-title">Add New User</h1>
        <form method="post" action="" class="user-form">
            <div class="user-form-row">
                <div class="user-form-group">
                    <label for="name" class="user-label">Name</label>
                    <input type="text" name="name" id="name" required class="user-input" />
                </div>
                <div class="user-form-group">
                    <label for="email" class="user-label">Email</label>
                    <input type="email" name="email" id="email" required class="user-input" />
                </div>
            </div>
            <div class="user-form-actions">
                <input type="submit" name="submit_user_form" class="user-btn user-btn-primary" value="Add User" />
            </div>
        </form>
    </div>

    <style>
        .user-add {
            max-width: 90%;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            font-family: 'Arial', sans-serif;
        }
        .user-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .user-form-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .user-form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .user-label {
            font-size: 14px;
            margin-bottom: 8px;
            color: #555;
        }
        .user-input {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }
        .user-form-actions {
            text-align: right;
        }
        .user-btn {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .user-btn-primary {
            background-color: #007bff;
        }
    </style>
    <?php
}

function user_delete_handler() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'users_custom';
        $user_id = intval($_GET['id']);
        $user = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $user_id");
        
        if ($user) {
            $wpdb->delete($table_name, ['id' => $user_id]);
            
            // Send deleted data to RabbitMQ
            $rabbit_sender = new RabbitMQPublisher();
            $rabbit_sender->publish(json_encode(['action' => 'delete', 'id' => $user_id, 'name' => $user->name, 'email' => $user->email]));
            
            wp_redirect(admin_url('admin.php?page=user-overview'));
            exit;
        }
    }
}



// Create Users Table
function create_users_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// RabbitMQ Publisher Class
class RabbitMQPublisher {
    private $connection;
    private $channel;

    public function __construct() {
        $this->connection = new AMQPStreamConnection('rabbitmq', 5672, 'user', 'password');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('wp_user_queue', false, false, false, false);
    }

    public function publish($message) {
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', 'wp_user_queue');
    }

    public function __destruct() {
        $this->channel->close();
        $this->connection->close();
    }
}
?>
