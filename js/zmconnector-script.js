document.getElementById("zmConnectorForm").onsubmit = function (event) {
  event.preventDefault();
  
  // Show the loading indicator
  document.getElementById("loadingIndicator").style.display = "block";

  // Optionally, disable the submit button to prevent multiple submissions
  // This line is optional and can be commented out or removed if not needed
  this.querySelector('input[type="submit"]').disabled = true;

  // No need to explicitly submit the form here as we're not preventing the default action
  // The form will continue to submit as normal
  console.log("Form submitted");
};
