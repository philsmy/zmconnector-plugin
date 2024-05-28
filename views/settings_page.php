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
<?php
if ($isPaid) { ?>
  <h2>ZM Connector Purchase</h2>
  <p>You have paid for ZM Connector.</p>
  Thank you for your purchase! You can now create posts.
<?php
} else {
?>
  <h2>ZM Connector Subscription</h2>
  You are not subscribed. Clck the button below to subscribe to ZM Connector.
  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="zm_connector_submit">
    <table class="form-table">

    </table>
    <?php submit_button(
      'Subscribe to ZM Connector',
      'primary',
      'submit',
      true,
      array('id' => 'subscribe-zmconnector')
    ); ?>
  </form>
<?php
}
?>
<br>
<hr>
<br>
<h2>ZM Connector Settings</h2>
<table class="access-token-table">
  <tr valign="top">
    <th scope="row">Access Token</th>
    <td>
      <input type="text" name="zmconnector_access_token" disabled placeholder="Your Access Token will appear here" class="regular-text" value="<?php echo esc_attr(get_option('zmconnector_access_token')); ?>" />
    </td>
  </tr>
</table>