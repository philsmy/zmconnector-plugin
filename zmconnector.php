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

// Remote backend URL
$remote_url = 'https://zmconnector.ngrok.io/graphql';


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

function zmconnector_page()
{
  $accessToken = get_option('zmconnector_access_token');
  $isDisabled = empty($accessToken) ? 'disabled' : '';
  $templates = get_page_templates();
?>
  <div class="wrap">
    <h2>ZM Connector - Create a Product Promotion Page</h2>
    <form id="zmConnectorForm" method="post">
      <?php wp_nonce_field('create_post', 'zmconnector_nonce'); ?>
      <table class="form-table">
        <tbody>
          <tr>
            <th>Amazon Marketplace: </th>
            <td>
              <select name="amazon_marketplace">
                <option value="amazon.com">amazon.com</option>
                <option value="amazon.ca">amazon.ca</option>
                <option value="amazon.co.uk">amazon.co.uk</option>
                <option value="amazon.de">amazon.de</option>
                <option value="amazon.fr">amazon.fr</option>
                <!-- Add other marketplaces as needed -->
              </select>
            </td>
          </tr>
          <tr>
            <th>ASIN: </th>
            <td><input type="text" name="asin" value="" /></td>
          </tr>
          <tr>
            <th>Affiliate Code: </th>
            <td><input type="text" name="affiliateCode" value="" /></td>
          </tr>
          <tr>
            <th>Page Style: </th>
            <td>
              <select name="page_style">
                <option value="Style 1">Style 1</option>
                <option value="Style 2">Style 2</option>
                <option value="Style 3">Style 3</option>
              </select>
            </td>
          </tr>
          <tr>
            <th>Page Template:</th>
            <td>
              <select name="page_template">
                <?php foreach ($templates as $template_name => $template_filename) : ?>
                  <option value="<?php echo esc_attr($template_filename); ?>">
                    <?php echo esc_html($template_name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <th>Post Status:</th>
            <td>
              <input type="radio" id="publish" name="post_status" value="publish" checked>
              <label for="publish">Publish</label>
              <input type="radio" id="draft" name="post_status" value="draft">
              <label for="draft">Draft</label>
            </td>
          </tr>
          <tr>
            <th>Post Type:</th>
            <td>
              <input type="radio" id="post" name="post_type" value="post">
              <label for="post">Post</label>
              <input type="radio" id="page" name="post_type" value="page" checked>
              <label for="page">Page</label>
            </td>
          </tr>
        </tbody>
      </table>

      <div id="loadingIndicator" style="display: none;">
        <p>Processing, please wait...</p>
        <!-- Or use an image/gif for a spinner -->
        <!-- <img src="spinner.gif" alt="Loading..." /> -->
      </div>
      <input type="submit" name="create_post" value="Create Post" class="button-primary" <?php echo $isDisabled; ?> />
    </form>
  </div>
<?php
}

add_action('admin_init', 'zmconnector_create_page');


function zmconnector_create_page()
{
  global $remote_url;

  // Verify nonce and user intention
  if (!isset($_POST['create_post'], $_POST['zmconnector_nonce']) || !wp_verify_nonce($_POST['zmconnector_nonce'], 'create_post')) {
    return; // Exit if checks fail
  }

  $accessToken = get_option('zmconnector_access_token');
  if (empty($accessToken)) {
    error_log('Access Token is required.');
    wp_die('Access Token is required.');
  }

  $asin = sanitize_text_field($_POST['asin']);
  $affiliateCode = sanitize_text_field($_POST['affiliateCode']);

  // Construct GraphQL mutation
  $mutation = <<<MUTATION
mutation {
  createContent(input: {
    asin: "$asin"
    affiliateId: "$affiliateCode"
  }) {
    pageContent
    pageTitle
  }
}
MUTATION;

  // Execute POST request
  $response = wp_remote_post($remote_url, [
    'headers' => [
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $accessToken,
    ],
    'body' => json_encode(['query' => $mutation]),
    'timeout' => 300,
  ]);

  // Log the response for debugging
  error_log('Response: ' . print_r($response, true));

  if (is_wp_error($response)) {
    error_log('WP Error: ' . $response->get_error_message());
    wp_die('Error communicating with the server: ' . $response->get_error_message());
  }

  // Decode the response
  $body = json_decode(wp_remote_retrieve_body($response), true);
  if (!isset($body['data']['createContent'])) {
    error_log('Invalid response structure: ' . print_r($body, true));
    wp_die('Unexpected response from the server.');
  }

  // Extract the page content and title
  $pageTitle = $body['data']['createContent']['pageTitle'];
  $pageContentEscaped = $body['data']['createContent']['pageContent'];
  $pageContent = html_entity_decode($pageContentEscaped);

  // Log the extracted content and title
  error_log('Page Title: ' . $pageTitle);
  error_log('Page Content: ' . $pageContent);

  $pageId = wp_insert_post([
    'post_title'   => $pageTitle,
    'post_content' => $pageContent,
    'post_status'  => 'publish',
    'post_author'  => get_current_user_id(),
    'post_type'    => 'page',
  ], true); // Note the true parameter to return WP_Error object on failure.

  if (is_wp_error($pageId)) {
    error_log('Failed to create page: ' . $pageId->get_error_message());
    wp_die('Failed to create the page: ' . $pageId->get_error_message());
  } else {
    // Check if the page was created successfully and you have a valid template selected
    if ($pageId != 0 && !empty($_POST['page_template'])) {
      // Set the page template
      update_post_meta($pageId, '_wp_page_template', $_POST['page_template']);
    }

    error_log('Page created successfully with ID: ' . $pageId);

    // Assuming $pageId is the ID of the newly created page and is not a WP_Error
    if ($pageId > 0) {
      // Construct the URL to the edit page
      $editPageUrl = admin_url('post.php?post=' . $pageId . '&action=edit');

      // Redirect to the edit page
      wp_redirect($editPageUrl);
      exit; // Always call exit() after wp_redirect() to ensure the rest of the script doesn't execute
    }
  }
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

function zmconnector_settings_page()
{
?>
  <h2>ZM Connector Registration</h2>
  <form method="post" id="zmconnector-registration-form">
    <table class="form-table">
      <tbody>
        <tr>
          <th>Email: </th>
          <td><input type="email" name="zmconnector_email" value="" /></td>
        </tr>
        <tr>
          <th>Password: </th>
          <td><input type="password" name="zmconnector_password" value="" /></td>
        </tr>
      </tbody>
    </table>
    <?php submit_button('Register User'); ?>
  </form>
  <br>
  <hr>
  <br>
  <h2>ZM Connector Settings</h2>
  <form method="post" action="options.php">
    <?php settings_fields('zmconnector-settings-group'); ?>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">Access Token</th>
        <td><input type="text" name="zmconnector_access_token" value="<?php echo esc_attr(get_option('zmconnector_access_token')); ?>" /></td>
      </tr>
    </table>
    <?php submit_button(
      'Save Access Token', // Text parameter: the text to display on the button.
      'primary', // Type parameter: the type/style of the button ('primary', 'secondary', 'delete', etc.).
      'submit', // Name parameter: the name attribute for the button.
      true, // Wrap parameter: whether the button should be wrapped in a paragraph tag.
      array('id' => 'save-zmconnector-access-token') // Other attributes: an array of other attributes, such as a custom ID.
    );
    ?>
  </form>
<?php
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

function zmconnector_enqueue_scripts()
{
  wp_enqueue_style('zmconnector-style', plugin_dir_url(__FILE__) . 'zmconnector-style.css', array(), '1.0.0', 'all');

  // wp_enqueue_script('zmconnector-script', plugin_dir_url(__FILE__) . 'zmconnector-script.js', array(), '1.0.0', true);

  wp_enqueue_script('zmconnector-ajax-script', plugin_dir_url(__FILE__) . 'zmconnector-ajax.js', array('jquery'), null, true);
  wp_localize_script('zmconnector-ajax-script', 'zmConnectorAjax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('zmconnector_nonce'),
  ));
}
add_action('admin_enqueue_scripts', 'zmconnector_enqueue_scripts');

function zmconnector_handle_ajax_register()
{
  global $remote_url;

  check_ajax_referer('zmconnector_nonce', 'nonce');

  $email = sanitize_email($_POST['email']);
  $password = sanitize_text_field($_POST['password']);

  // Construct the GraphQL mutation query
  $graphql_mutation = [
    'query' => 'mutation CreateUser($email: String!, $password: String!) {
                    createUser(input: {email: $email, password: $password}) {
                      accessToken
                    }
                }',
    'variables' => [
      'email' => $email,
      'password' => $password,
    ],
  ];

  $response = wp_remote_post($remote_url, [
    'body' => json_encode($graphql_mutation),
    'headers' => [
      'Content-Type' => 'application/json',
    ],
    'data_format' => 'body',
    'timeout' => 60,
  ]);

  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    wp_send_json_error(['error' => 'Remote server error: ' . $error_message]);
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['data']['createUser']['accessToken'])) {
      $accessToken = $data['data']['createUser']['accessToken'];

      // Update the access token in the WordPress database
      update_option('zmconnector_access_token', $accessToken);

      // Send success response back to JavaScript, including the access token
      wp_send_json_success(['access_token' => $accessToken]);
    } else {
      // Check for GraphQL errors
      if (isset($data['errors'])) {
        wp_send_json_error(['error' => $data['errors'][0]['message']]);
      } else {
        wp_send_json_error(['error' => 'Invalid response.']);
      }
    }
  }
}
add_action('wp_ajax_register_zmconnector', 'zmconnector_handle_ajax_register');
