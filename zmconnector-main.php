<?php

/**
 * Plugin Name:       ZM Connector
 * Plugin URI:        https://public.zonmaster.com/zm-connector
 * Description:       ZM Connector enables Amazon affiliates to effortlessly create landing pages by fetching product information and generating content through a connected backend service.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Tested up to:      6.5
 * Requires PHP:      7.2
 * Author:            Phil Smy - Zonmaster
 * Author URI:        https://zonmaster.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zm-connector
 * Domain Path:       /languages
 */

 ini_set('max_execution_time', '300');

// Include the admin page setup functions.
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'functions/ajax-responders.php';
require_once plugin_dir_path(__FILE__) . 'functions/functions.php';

add_action('admin_menu', 'zmconnector_menu');

function zmconnector_menu()
{
  add_menu_page(
    'ZM Connector',           // Page title
    'ZM Connector',           // Menu title
    'manage_options',                // Capability
    'zmconnector',           // Menu slug
    'zmconnector_page',      // Function to display the page
    '',                              // Icon URL (optional)
    99                               // Position (optional)
  );
}

function zmconnector_add_submenu_page()
{
  // Submenu page (Settings)
  add_submenu_page(
    'zmconnector',   // Parent slug (same as main menu slug)
    'Settings',                // Page title
    'Settings',                // Menu title
    'manage_options',          // Capability
    'zmconnector-settings',      // Menu slug
    'zmconnector_settings_page'  // Function to display the page content
  );
}
add_action('admin_menu', 'zmconnector_add_submenu_page');

function zmconnector_enqueue_scripts()
{
  error_log('Enqueueing scripts and styles');

  wp_enqueue_style('zmconnector-style', plugin_dir_url(__FILE__) . 'styles/zmconnector-style.css', array(), '1.0.0', 'all');

  wp_enqueue_script('zmconnector-ajax-script', plugin_dir_url(__FILE__) . 'js/zmconnector-ajax.js', array('jquery'), null, true);

  wp_localize_script('zmconnector-ajax-script', 'zmConnectorAjax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('zmconnector_nonce'),
  ));
}

add_action('admin_enqueue_scripts', 'zmconnector_enqueue_scripts');
