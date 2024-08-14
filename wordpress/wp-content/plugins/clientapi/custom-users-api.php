<?php
/*
Plugin Name: Custom Users API
Description: A custom REST API for managing users in the custom users table.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Register REST API routes
add_action('rest_api_init', 'register_custom_users_routes');

function register_custom_users_routes() {
    register_rest_route('custom-users/v1', '/users', array(
        'methods' => 'GET',
        'callback' => 'get_custom_users',
    ));

    register_rest_route('custom-users/v1', '/users', array(
        'methods' => 'POST',
        'callback' => 'create_custom_user',
    ));

    register_rest_route('custom-users/v1', '/users/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_custom_user',
    ));

    register_rest_route('custom-users/v1', '/users/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_custom_user',
    ));
}

// Get all users
function get_custom_users() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $users = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($users)) {
        return new WP_Error('no_users', 'No users found', array('status' => 404));
    }

    return rest_ensure_response($users);
}

// Get single user by ID
function get_custom_user($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $id = $request['id'];

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

    if (empty($user)) {
        return new WP_Error('no_user', 'User not found', array('status' => 404));
    }

    return rest_ensure_response($user);
}

// Create a new user
function create_custom_user($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $name = sanitize_text_field($request['name']);
    $email = sanitize_email($request['email']);

    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'created_at' => current_time('mysql'),
        )
    );

    $user_id = $wpdb->insert_id;

    if ($user_id) {
        return rest_ensure_response(array('id' => $user_id));
    } else {
        return new WP_Error('insert_failed', 'Failed to create user', array('status' => 500));
    }
}

// Delete a user by ID
function delete_custom_user($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users_custom';
    $id = $request['id'];

    $deleted = $wpdb->delete($table_name, array('id' => $id));

    if ($deleted) {
        return rest_ensure_response(array('deleted' => true));
    } else {
        return new WP_Error('delete_failed', 'Failed to delete user', array('status' => 500));
    }
}
