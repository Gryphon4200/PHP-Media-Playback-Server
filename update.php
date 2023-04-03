<?php
  include_once 'functions.php';

  $num_keys = count($_POST) - 1;

  if (isset($_GET["preset"])) {
    $timestamp = time();
    $filename = $Presets[$_GET["preset"]];
    $text = $filename . "|" . $timestamp;
    $file = fopen("image.txt", "w") or die("Unable to open file!");
    $_POST["update"] = ""; // supresses undefined array error.
  } else {

    switch ($_POST["update"]) {
      case "ConfigFileUpdate":
        $JSON_Data["path"] = $ServerPath;
        foreach ($_POST as $key => $value) { if (!($key == "update")) { $JSON_Data[$key] = $value; } }
        $text = json_encode($JSON_Data, JSON_PRETTY_PRINT);
        $file = fopen("config.json", "w") or die("Unable to open file!");
        break;
      case "UpdateDisplay":
        $filename = $_POST["filename"];
        $timestamp = $_POST["timestamp"];
        $file = fopen("image.txt", "w") or die("Unable to open file!");
        $text = $filename . "|" . $timestamp;
        break;
      case "Delete":
        $FileWithPath = $MediaPath.$_POST["filename"];
        $status=unlink($FileWithPath);
        if($status) { echo "File deleted successfully\n"; } else { echo "There was an error deleting the file.\n"; }
        break;
      default:
        break;
    }
  
  }

  if ($_POST["update"] != "Delete") {
    fwrite($file, $text);
    fclose($file);
  }
?>