<?php
  if (isset($_GET["preset"])) {
    $timestamp = time();

    switch ($_GET["preset"]) {
      case 1:
        $filename = "MorningLine Skyline.jpg";
        break;
      case 2:
        $filename = "OpenLine.png";
        break;
      case 3:
        $filename = "InsidePolotics.png";
        break;                
      default:
        $filename = "NC5-Wallpaper.png";
        break;
    }
  } else {
    $filename = $_POST["filename"];
    $timestamp = $_POST["timestamp"];
  }

  $text = $filename . "|" . $timestamp;
  $file = fopen("image.txt", "w") or die("Unable to open file!");
  fwrite($file, $text);
  fclose($file);

  // echo "File updated successfully!";
?>