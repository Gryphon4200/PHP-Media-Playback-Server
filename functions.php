<?php
    // Retrieve file list
    $directory = "C:\Temp\Web Projects\Video_Wall\Media";
    $files = scandir($directory);

    if ($_FILES['fileToUpload']['error'] == UPLOAD_ERR_OK) {
        $target_file = $directory . basename($_FILES["fileToUpload"]["name"]);
        move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
      }
?>