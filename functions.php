<?php

    // Read the contents of config file
    $config_file = file_get_contents("config.json");

    // Split the contents into an array using a delimiter
       $file_info = explode("|", $file_contents);

       // Get the filename and path
       $file_name = trim((string) $file_info[0]);
       $file_signature = trim((string) $file_info[1]);
   
          // Display the image
       echo "<img src='Media/{$file_name}' alt='{$file_name}'>";
   

    // Retrieve file list
    $directory = "C:\Temp\Web Projects\Video_Wall\Media\\";
    $files = scandir($directory);
?>