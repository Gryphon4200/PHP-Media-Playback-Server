<?php
    include_once 'functions.php';

    if ($_FILES['fileToUpload']['error'] == UPLOAD_ERR_OK) {
        echo "Saving ".basename($_FILES["fileToUpload"]["name"])." to ".$directory;
        $target_file = $directory . basename($_FILES["fileToUpload"]["name"]);
        move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
        echo '<div class="center">Upload successful!</div>'."\n";
        echo '<a href="index.php">Back to file selection screen.</a>'."\n";
    } else {
        echo "There was an error uploading the file.";
    }

    
?>
