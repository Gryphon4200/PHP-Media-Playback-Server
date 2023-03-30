<?php echo "\n"; ?>
 <body>
  <div class="dropdown">
   <button class="dropbtn" type="button">Presets</button>

   <!-- ############ Preset Config Menu ############ -->

   <div class="dropdown-content">
    <label for="presets" class="hidden">Presets:</label>
<?php

    $Index = 0;

    foreach($Presets as $Preset) {
        $Index += 1;
        // echo "    <a href=\"#\">Preset $Index: $Preset</a>";
        echo "    Preset $Index: ";
        echo "    <select class=\"preset_select\" name=\"Preset$Index\" id=\"Preset$Index\">";

        foreach($File_List as $file) {
            if (!($file == "." || $file == ".." || $file == ".DS_Store")) {
                if ($file == $Preset) {
                    echo "     <option value=\"$file\" selected>$file</option>\n";
                } else {
                    echo "     <option value=\"$file\">$file</option>\n";
                }
            }
        }
        echo "    </select>";
    }
?>
    <form class="right" name="preset_form" method="post" onsubmit="Update_Presets()">
     <input class="form-submit-button" type="submit" value="Update">
    </form>
   </div>
  </div>

  <!-- ############ File Selection List ############ -->

  <div class="menu">
   <?php
    foreach($File_List as $file) {
        if (!($file == "." || $file == ".." || $file == ".DS_Store")) {
            echo "   <div class=\"row\" id='".$file."'>\n";
            echo "    <div class=\"file\" onclick=\"menuItemClicked('$file');\">$file</div>\n";
            echo "    <div class=\"remove\" onclick=\"DeleteFile('$file');\">X</div>\n";
            echo "   </div>\n";
//            echo "   <a href=\"#\" onclick=\"menuItemClicked('$file');\"><li id='".$file."' class=''>$file</li></a>\n";
        };
    }
   ?>

   <form action="upload.php" method="post" enctype="multipart/form-data">
    <label class="hidden" for="fileToUpload">File to Upload</label>
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload File" name="submit">
   </form>
  </div>

 </body>
 <script src="script.js" type="application/javascript"></script>