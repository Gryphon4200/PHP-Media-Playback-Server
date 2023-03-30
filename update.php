<?php
  include_once 'functions.php';

  $num_keys = count($_POST) - 1;
  
  if (isset($_POST["UpdatePreset"])) {

    $JSON_Data["path"] = $ServerPath;
    
    foreach ($_POST as $key => $value) {
      if (!($key == "UpdatePreset")) {
        $JSON_Data[$key] = $value;
      }
    }

    $text = json_encode($JSON_Data, JSON_PRETTY_PRINT);
    $file = fopen("config.json", "w") or die("Unable to open file!");
  }
  
  if (isset($_GET["preset"])) {
    $timestamp = time();
    $filename = $Presets[$_GET["preset"]];
    $text = $filename . "|" . $timestamp;
  }

  if (isset($_POST["timestamp"])) {
    $filename = $_POST["filename"];
    $timestamp = $_POST["timestamp"];

    $file = fopen("image.txt", "w") or die("Unable to open file!");
    $text = $filename . "|" . $timestamp;
  }
  

  fwrite($file, $text);
  fclose($file);

  // echo "File updated successfully!";
?>