function selectFile() {
    var selectedFile = document.querySelector('input[name="file"]:checked').value;
    // perform server-side event with selected file
}

var radioButtons = document.querySelectorAll('input[name="file"]');
radioButtons.forEach(function(radioButton) {
  radioButton.addEventListener('change', selectFile);
});