function selectFile() {
  var selectedFile = document.querySelector('input[name="file"]:checked').value;

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      console.log(this.responseText);
    }
  };

  xhttp.open("POST", "update.php", true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  var timestamp = new Date().toISOString();
  var params = "filename=" + selectedFile + "&timestamp=" + timestamp;
  xhttp.send(params);
}

  // Create listener for selection change.
var radioButtons = document.querySelectorAll('input[name="file"]');

radioButtons.forEach(function(radioButton) {
  radioButton.addEventListener('change', selectFile);
});