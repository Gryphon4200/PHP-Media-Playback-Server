<?php
  $filename = $_POST["filename"];
  $timestamp = $_POST["timestamp"];

  $text = $filename . "|" . $timestamp;
  $file = fopen("image.txt", "w") or die("Unable to open file!");
  fwrite($file, $text);
  fclose($file);

  echo "File updated successfully!";
?>