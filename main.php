<?php echo "\n"; ?>
 <body>
  <ul>
   <?php foreach($files as $file): ?>
   <li>
    <input type="radio" name="file" value="<?php echo $file; ?>">
    <?php echo $file; ?>
   </li>
   <?php endforeach; ?>
  </ul>

  <form action="upload.php" method="post" enctype="multipart/form-data">
   <input type="file" name="fileToUpload" id="fileToUpload">
   <input type="submit" value="Upload File" name="submit">
  </form>

 </body>
 <script src="script.js"></script>