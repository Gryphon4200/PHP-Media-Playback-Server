<?php

    // Read the contents of config file
    $config_file = file_get_contents("config.json");
    $Settings    = json_decode($config_file,true);
    $ServerPath  = $Settings["path"];
    $MediaPath   = $Settings["path"]."Media\\";

    // Retrieve file list
    $File_List = scandir($MediaPath);

    foreach($Settings as $key=>$value) {
        if (!($key == "path")) {
            $Presets[$key] = $value;
        }
    }
?>