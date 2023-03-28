<?php echo "\n"; ?>
 <body>
<?php
    echo "<hr>\n";

    $Index = 0;

    foreach($Presets as $Preset) {
            $Index += 1;
            echo "Preset $Index: ".$Preset."<br>\n";
    }

    echo "<hr>\n";
?>
  <div class="menu">
   <ul>
   <?php
    foreach($files as $file) {
        if (!($file == "." || $file == "..")) {
            echo "   <a href=\"#\" onclick=\"menuItemClicked('$file');\"><li id='".$file."' class=''>$file</li></a>\n";
        };
    }
   ?>

  <form action="upload.php" method="post" enctype="multipart/form-data">
   <input type="file" name="fileToUpload" id="fileToUpload">
   <input type="submit" value="Upload File" name="submit">
  </form>

 </body>
 <script src="script.js"></script>