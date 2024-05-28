<?php

function getUserStatusFromServer()
{
  $access_token = get_option('zmconnector_access_token');

  $remote_url = 'https://zmconnector.ngrok.io/graphql';

  $graphql_query = '
    query getUser($token: String!) {
      user(token: $token) {
        canCreatePosts
        remainingFreePosts
        purchaseStatus
      }
    }
  ';

  $variables = array(
    'token' => $access_token
  );

  $request_args = array(
    'body' => json_encode(array(
      'query' => $graphql_query,
      'variables' => $variables
    )),
    'headers' => array(
      'Content-Type' => 'application/json'
    )
  );

  $response = wp_remote_post($remote_url, $request_args);

  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    // Handle error
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    // Handle response data

    if (isset($data->data->user->purchaseStatus)) {
      $canCreatePosts = $data->data->user->canCreatePosts;
      $purchaseStatus = $data->data->user->purchaseStatus;

      error_log('Can create posts: ' . $canCreatePosts);
      error_log('Purchase status: ' . $purchaseStatus);

      update_option('zmconnector_can_create_posts', $canCreatePosts);
      update_option('zmconnector_purchase_status', $purchaseStatus);

      $isPaid = (strtolower($purchaseStatus) === 'paid');
    } else {
      // Handle the case where purchaseStatus is not present in the response
      $isPaid = false; // Assuming it's not paid if purchaseStatus is not available
    }
  }
}
