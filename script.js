function UpdateDisplay(selectedFile) {

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

function menuItemClicked(index) {
  var menuItems = document.querySelectorAll('.menu li');

  for (var i = 0; i < menuItems.length; i++) {
    menuItems[i].classList.remove('selected');
  }

  const SelectedItem = document.getElementById(index);
  SelectedItem.classList.add('selected');

  UpdateDisplay(index);  
}
