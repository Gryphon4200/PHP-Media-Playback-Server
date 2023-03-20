<?php echo "\n"; ?>
 <body>
  <ul>
   <?php
    foreach($files as $file) {
        if (!($file == "." || $file == "..")) {
            echo "<li>\n";
            echo "<input type=\"radio\" name=\"file\" value=\"$file\">";
            echo " ".$file;
            echo "\n</li>\n";
        };
    }
   ?>
  </ul>

  <form action="upload.php" method="post" enctype="multipart/form-data">
   <input type="file" name="fileToUpload" id="fileToUpload">
   <input type="submit" value="Upload File" name="submit">
  </form>

 </body>
 <script src="script.js"></script>