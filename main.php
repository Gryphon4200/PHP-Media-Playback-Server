<?php echo "\n"; ?>
 <body>
  <div class="dropdown" onclick='ToggleElement();'>
   <button class="dropbtn" type="button">Presets</button>
  </div>

  <!-- ############ Preset Config Menu ############ -->

  <div class="dropdown-content" id="PresetMenu" style="display: none;">
<?php

    $Index = 0;

    foreach($Presets as $Preset) {
        $Index += 1;
        // echo "    <a href=\"#\">Preset $Index: $Preset</a>";
        echo "    <div class=\"PresetSelect\">\nPreset $Index: \n";
        echo "     <select name=\"Preset$Index\" id=\"Preset$Index\">\n";

        foreach($File_List as $file) {
            if (!($file == "." || $file == ".." || $file == ".DS_Store")) {
                if ($file == $Preset) {
                    echo "     <option value=\"$file\" selected>$file</option>\n";
                } else {
                    echo "     <option value=\"$file\">$file</option>\n";
                }
            }
        }
        echo "    </select>\n";
        echo "   </div>\n";
    }
?>
    <br>
    <form class="right" name="preset_form" method="post" onsubmit="Update_Presets()">
     <input class="PresetButton" type="submit" value="Update">
    </form>
   </div>

  <!-- ############ File Selection List ############ -->

  <table class="menu">
   <?php
    foreach($File_List as $file) {
        if (!($file == "." || $file == ".." || $file == ".DS_Store")) {
            echo "   <tr class=\"row\" id='".$file."'>\n";
            echo "    <td class=\"file\" onclick=\"menuItemClicked('$file');\">$file</td>\n";
            echo "    <td class=\"remove\" onclick=\"DeleteFile('$file');\">X</td>\n";
            echo "   </tr>\n";
//            echo "   <a href=\"#\" onclick=\"menuItemClicked('$file');\"><li id='".$file."' class=''>$file</li></a>\n";
        };
    }
   ?>
   <tr class="row">
    <td colspan=2>
     <form action="upload.php" method="post" enctype="multipart/form-data">
      <label class="hidden" for="fileToUpload">File to Upload</label>
      <input type="file" name="fileToUpload" id="fileToUpload">
      <input type="submit" value="Upload File" name="submit">
     </form>
    </td>
   </tr>
  </table>

 </body>
 <script src="script.js" type="application/javascript"></script>