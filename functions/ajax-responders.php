<?php
// create / login the user
function zmconnector_handle_ajax_register()
{
  error_log('Handling zmconnector_handle_ajax_register AJAX request');
  global $remote_url;

  check_ajax_referer('zmconnector_nonce', 'nonce');

  $email = sanitize_email($_POST['email']);
  $password = sanitize_text_field($_POST['password']);

  // Construct the GraphQL mutation query
  $graphql_mutation = [
    'query' => 'mutation CreateUser($email: String!, $password: String!, $adminUrl: String!) {
                    userCreate(input: {email: $email, password: $password, adminUrl: $adminUrl}) {
                      user {
                        apiAccessToken
                        remainingFreePosts
                        purchaseStatus
                        canCreatePosts                
                      }
                    }
                }',
    'variables' => [
      'email' => $email,
      'password' => $password,
      'adminUrl' => get_full_admin_url(),
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

    if (isset($data['data']['userCreate']['user']['apiAccessToken'])) {
      $accessToken = $data['data']['userCreate']['user']['apiAccessToken'];

      // Update the access token in the WordPress database
      update_option('zmconnector_access_token', $accessToken);

      // update the can create posts in the WordPress database
      $canCreatePosts = $data['data']['userCreate']['user']['canCreatePosts'];
      update_option('zmconnector_can_create_posts', $canCreatePosts);

      $purchaseStatus = $data['data']['userCreate']['user']['purchaseStatus'];
      update_option('zmconnector_purchase_status', $purchaseStatus);

      // Send success response back to JavaScript, including the access token
      wp_send_json_success([
        'accessToken' => $accessToken,
        'remainingFreePosts' => $data['data']['userCreate']['user']['canCreatePosts']
      ]);
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

// for creating a page
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

add_action('admin_init', 'zmconnector_create_page');

// subscribe
function subscribe_zmconnector()
{
  error_log('Handling subscribe_zmconnector AJAX request');

  $remote_url = 'https://zmconnector.ngrok.io/create_checkout_session';

  check_ajax_referer('zmconnector_nonce', 'nonce');

  $accessToken = get_option('zmconnector_access_token');
  if (empty($accessToken)) {
    error_log('Access Token is required.');
    wp_die('Access Token is required.');
  }

  $formData = [
    'subscribe' => $_POST['subscribe'],
    'returnUrl' => get_full_admin_url(),
  ];

  // Execute POST request
  $response = wp_remote_post($remote_url, [
    'headers' => [
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $accessToken,
    ],
    'body' => json_encode($formData),
  ]);

  // Log the response for debugging
  error_log('Response: ' . print_r($response, true));

  if (is_wp_error($response)) {
    error_log('WP Error: ' . $response->get_error_message());
    wp_die('Error communicating with the server: ' . $response->get_error_message());
  }
}

// add_action('wp_ajax_subscribe_zmconnector', 'subscribe_zmconnector');
// add_action('wp_ajax_nopriv_subscribe_zmconnector', 'subscribe_zmconnector');

// Inside your WordPress plugin
add_action('admin_post_zm_connector_submit', 'zm_connector_submit_handler');
function zm_connector_submit_handler()
{
  $url = 'https://zmconnector.ngrok.io/create_checkout_session';
  $accessToken = get_option('zmconnector_access_token'); // Assuming you store the access token in WP options

  error_log('returnUrl: ' . get_full_admin_url());

  // Setup the cURL session
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true); // Set request method to POST
  curl_setopt($ch, CURLOPT_HTTPHEADER, [ // Set headers
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/x-www-form-urlencoded'
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'returnUrl' => get_full_admin_url()
  ]));

  // Execute the request
  $response = curl_exec($ch);
  $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code

  if (curl_errno($ch)) {
    error_log('cURL error: ' . curl_error($ch));
  } else {
    error_log('Response status code: ' . $status_code);
    if ($status_code == 303) { // Check if the response is a redirect
      $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
      if ($redirect_url) {
        wp_redirect($redirect_url); // Use WordPress's wp_redirect function to redirect the client
        exit; // Don't forget to call exit after wp_redirect
      }
    }
  }
  // Close cURL session
  curl_close($ch);
}
