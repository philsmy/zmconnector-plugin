jQuery(document).ready(function ($) {
  $("form#zmconnector-registration-form").submit(function (e) {
    e.preventDefault();

    var formData = {
      action: "register_zmconnector",
      nonce: zmConnectorAjax.nonce,
      email: $('input[name="zmconnector_email"]').val(),
      password: $('input[name="zmconnector_password"]').val(),
    };

    $.ajax({
      type: "POST",
      url: zmConnectorAjax.ajax_url,
      data: formData,
      success: function (response) {
        if (response.success) {
          $('input[name="zmconnector_access_token"]').val(
            response.data.access_token
          );
          // Optionally, remove any existing notices and display a success message
          $(".zmconnector-notice").remove();
          var successNotice =
            '<div class="notice notice-success is-dismissible zmconnector-notice"><p>Access token successfully updated.</p></div>';
          $(".wrap h1").after(successNotice);
        } else {
          displayAdminNotice(response.data.error, 'error');
        }
      },
      error: function (xhr, status, error) {
        displayAdminNotice('AJAX request failed: ' + error, 'error');
      },
    });
  });

  function displayAdminNotice(message, type) {
    var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
    var html = '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>';
    $(html).insertAfter('.wrap h1');
    // Scroll to top so the user can see the message
    window.scrollTo(0, 0);
  }
});
