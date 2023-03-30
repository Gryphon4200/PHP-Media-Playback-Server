function WriteFile(params) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      console.log(this.responseText);
    }
  };

  xhttp.open("POST", "update.php", true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhttp.send(params);
}

function UpdateDisplay(selectedFile) {
  var timestamp = new Date().toISOString();
  var params = "filename=" + selectedFile + "&timestamp=" + timestamp;

  WriteFile(params);
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

function SavePresetsToConfig(Presets) {
  var params = "UpdatePreset=1";

  // Format HTTP POST param list
  for (var i = 0; i < Presets.length; i++) {
     params += "&"+(i+1)+"="+Presets[i];
  }

  WriteFile(params);
}

function Update_Presets() {
  var selectElements = document.querySelectorAll('select'); // select all HTML select elements
  var dataArray = []; // initialize an empty array to hold the selected data
  
  for (var i = 0; i < selectElements.length; i++) {
    var selectedOption = selectElements[i].options[selectElements[i].selectedIndex]; // get the selected option from the current select element
    var selectedData = selectedOption.value; // get the value of the selected option
    
    if (selectedData !== '') { // check if a valid option was selected
      dataArray[i] = selectedData; // add the selected data to the array
    }
  }

  SavePresetsToConfig(dataArray); // return the populated array
}