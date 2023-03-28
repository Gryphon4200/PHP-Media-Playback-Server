<?php echo "\n"; ?>
 <body>
  <div class="dropdown">
   <button class="dropbtn">Presets</button>
   <div class="dropdown-content">
<?php

    $Index = 0;

    foreach($Presets as $Preset) {
            $Index += 1;
            echo "    <a href=\"#\">Preset $Index: $Preset</a>";
    }
?>
   </div>
  </div>
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