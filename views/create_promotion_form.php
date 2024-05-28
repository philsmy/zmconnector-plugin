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