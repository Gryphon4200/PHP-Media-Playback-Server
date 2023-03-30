<?php
  include_once 'functions.php';

  $num_keys = count($_POST) - 1;

  switch ($_POST["update"]) {
    case "ConfigFileUpdate":
      $JSON_Data["path"] = $ServerPath;
    
      foreach ($_POST as $key => $value) {
        if (!($key == "update")) {
          $JSON_Data[$key] = $value;
        }
      }
  
      $text = json_encode($JSON_Data, JSON_PRETTY_PRINT);
      $file = fopen("config.json", "w") or die("Unable to open file!");
      break;
    case "UpdateDisplay":
        $filename = $_POST["filename"];
        $timestamp = $_POST["timestamp"];
    
        $file = fopen("image.txt", "w") or die("Unable to open file!");
        $text = $filename . "|" . $timestamp;
        break;
    case "":
      break;
  }
  
  if (isset($_GET["preset"])) {
    $timestamp = time();
    $filename = $Presets[$_GET["preset"]];
    $text = $filename . "|" . $timestamp;
    $file = fopen("image.txt", "w") or die("Unable to open file!");
  }

  fwrite($file, $text);
  fclose($file);
?>