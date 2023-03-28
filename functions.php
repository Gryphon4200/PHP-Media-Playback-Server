<?php

    // Read the contents of config file
    $config_file = file_get_contents("config.json");

    // Split the contents into an array using a delimiter
    $Settings = json_decode($config_file,true);

    $ServerPath = $Settings["path"];

    // Retrieve file list
    $files = scandir($ServerPath);

    foreach($Settings as $key=>$value) {
        if (!($key == "path")) {
            $Presets[$key] = $value;
        }
    }
?>