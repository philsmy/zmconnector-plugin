<?php

// Remote backend URL
$remote_url = 'https://zmconnector.ngrok.io/graphql';

function zmconnector_page()
{
  $accessToken = get_option('zmconnector_access_token');
  $canCreatePosts = get_option('zmconnector_can_create_posts');

  $isDisabled = (empty($accessToken) || empty($canCreatePosts) || $canCreatePosts === false) ? 'disabled' : '';

  $templates = get_page_templates();

  include(plugin_dir_path(__FILE__) . '../views/create_promotion_form.php');
}


function zmconnector_settings_page()
{
  $purchaseStatus = get_option('zmconnector_purchase_status');
  $isPaid = (strtolower($purchaseStatus) === 'paid');
  
  // Check if 'rescan' parameter is set in the URL
  if (isset($_GET['rescan']) && $_GET['rescan'] !== '') {
    getUserStatusFromServer();
  }
  include(plugin_dir_path(__FILE__) . '../views/settings_page.php');
}

function zmconnector_register_settings()
{
  register_setting('zmconnector-settings-group', 'zmconnector_access_token');
}

add_action('admin_init', 'zmconnector_register_settings');

if (isset($_POST['zmconnector_register'])) {
  $email = sanitize_email($_POST['zmconnector_email']);
  $password = sanitize_text_field($_POST['zmconnector_password']);

  // API request to your backend
  $response = wp_remote_post($remote_url, array(
    'body' => array(
      'email' => $email,
      'password' => $password
    )
  ));

  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    // Handle error
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    if (isset($data->access_token)) {
      update_option('zmconnector_access_token', $data->access_token);
      // Redirect or notify of success
    }
  }
}

$access_token = get_option('zmconnector_access_token');
if (empty($access_token)) {
  // Notify the user or disable functionality
  add_action('admin_notices', 'zmconnector_admin_notice__error');
}

function zmconnector_admin_notice__error()
{
?>
  <div class="notice notice-error is-dismissible">
    <p><?php _e('Please enter a valid access token for ZM Connector to function correctly.', 'zmconnector-text-domain'); ?></p>
  </div>
<?php
}

function get_full_admin_url() {
  return admin_url('admin.php?page=zmconnector-settings');
}
