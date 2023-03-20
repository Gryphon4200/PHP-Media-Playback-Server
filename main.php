<?php echo "\n"; ?>
 <body>
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