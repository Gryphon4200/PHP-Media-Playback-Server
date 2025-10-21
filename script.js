// Toggle dropdown menu visibility
function ToggleElement() {
    var menu = document.getElementById("PresetMenu");
    if (menu.style.display === "none" || menu.style.display === "") {
        menu.style.display = "block";
    } else {
        menu.style.display = "none";
    };
};

function WriteFile(params) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      console.log(this.responseText);
    };
  };

  xhttp.open("POST", "update.php", true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhttp.send(params);

  location.reload();
};

function menuItemClicked(index) {
  var menuItems = document.querySelectorAll('.row');
  for (var i = 0; i < menuItems.length; i++) {
    menuItems[i].classList.remove('selected');
  };
  const SelectedItem = document.getElementById(index);
  SelectedItem.classList.add('selected');
  UpdateDisplay(index);
};

function UpdateDisplay(selectedFile) {
  var timestamp = new Date().toISOString();
  var params = "update=UpdateDisplay&filename=" + selectedFile + "&timestamp=" + timestamp;
  WriteFile(params);
};

// Handle file deletion
function DeleteFile(filename) {
    if (confirm("Are you sure you want to delete '" + filename + "'?")) {
        var params = "update=Delete&filename="+filename;
        // alert("Delete File function called.\n"+file+" to be deleted.");
        WriteFile(params);
    };
};

function SavePresetsToConfig(Presets) {
  var params = "update=ConfigFileUpdate";

  // Format HTTP POST param list
  for (var i = 0; i < Presets.length; i++) {
     params += "&"+(i+1)+"="+Presets[i];
  };

  WriteFile(params);
};

// Handle preset updates
function Update_Presets() {
  var selectElements = document.querySelectorAll('select'); // select all HTML select elements
  var dataArray = []; // initialize an empty array to hold the selected data

  for (var i = 0; i < selectElements.length; i++) {
    var selectedOption = selectElements[i].options[selectElements[i].selectedIndex]; // get the selected option from the current select element
    var selectedData = selectedOption.value; // get the value of the selected option

    if (selectedData !== '') { // check if a valid option was selected
      dataArray[i] = selectedData; // add the selected data to the array
    };
  };

  SavePresetsToConfig(dataArray); // return the populated array
};

// Enhanced upload with progress (optional)
document.addEventListener('DOMContentLoaded', function() {
    var uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            var fileInput = document.getElementById('fileToUpload');
            if (!fileInput.files[0]) {
                alert('Please select a file to upload.');
                e.preventDefault();
                return false;
            };
            
            // Show progress bar
            var progressDiv = document.getElementById('uploadProgress');
            if (progressDiv) {
                progressDiv.style.display = 'block';
            };
        });
    };
});
