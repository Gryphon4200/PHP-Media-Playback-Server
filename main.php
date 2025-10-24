<body>
 <!-- File Monitor Status -->
 <div id="fileMonitorStatus" class="file-monitor-status status-monitoring">
   Monitoring files...
 </div>

 <!-- Presets Dropdown -->
 <div class="dropdown" onclick="ToggleElement();">
  <button class="dropbtn" type="button">
   Presets (<?php echo count($Presets); ?>)
  </button>
 </div>

 <!-- ############ Preset Configuration Menu ############ -->
 <div class="dropdown-content" id="PresetMenu" style="display: none;">
  <?php if (empty($Presets)): ?>
  <div class="PresetSelect">
   <p>No presets configured. Add presets in config.json</p>
  </div>
  <?php else: ?>
  <?php 
    $Index = 0;
    $available_files = getFileList();
    foreach($Presets as $preset_key => $preset_value): 
        $Index += 1;
  ?>
  <div class="PresetSelect">
   <label for="Preset<?php echo $Index; ?>">Preset <?php echo $Index; ?>:</label>
   <select name="Preset<?php echo $Index; ?>" id="Preset<?php echo $Index; ?>" data-preset-key="<?php echo htmlspecialchars($preset_key); ?>">
    <?php foreach($available_files as $file): ?>
    <option value="<?php echo htmlspecialchars($file); ?>" 
    <?php echo ($file === $preset_value) ? 'selected' : ''; ?>>
    <?php echo htmlspecialchars($file); ?>
    </option>
    <?php endforeach; ?>
   </select>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
        
  <?php if (!empty($Presets)): ?>
  <div class="preset-actions">
   <form class="right" name="preset_form" method="post" onsubmit="return Update_Presets();">
    <input class="PresetButton" type="submit" value="Update Presets">
   </form>
  </div>
  <?php endif; ?>
 </div>

 <!-- ############ File Selection List ############ -->
 <div class="file-list-container">
  <table class="menu">
   <thead>
    <tr class="header-row">
     <th></th>
     <th width="50"></th>
    </tr>
   </thead>

   <tbody>
   <?php 
       $available_files = getFileList();
       if (empty($available_files)): 
   ?>
   <tr class="row empty-state">
    <td colspan="2" class="center">
     <p>No media files found.</p>
     <p>Upload files using the form below.</p>
    </td>
   </tr>
   <?php else: ?>
   <?php foreach($available_files as $file): ?>
   <tr class="row" id="file_<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" data-filename="<?php echo htmlspecialchars($file); ?>">
    <td class="file" onclick="menuItemClicked('<?php echo addslashes($file); ?>');">
     <span class="file-icon"><?php echo getFileIcon($file); ?></span>
     <span class="filename"><?php echo htmlspecialchars($file); ?></span>
     <span class="file-info"><?php echo getFileInfo($MediaPath . $file); ?></span>
    </td>
    <td class="remove" onclick="DeleteFile('<?php echo addslashes($file); ?>');" title="Delete <?php echo htmlspecialchars($file); ?>">✕</td>
   </tr>
   <?php endforeach; ?>
   <?php endif; ?>
                
   <!-- Upload Row -->
   <tr class="row upload-row">
    <td colspan="2" class="upload-cell">
     <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" onsubmit="return validateUpload();">
      <div class="upload-container">
       <label for="fileToUpload" class="upload-label">Choose media file to upload</label>
       <input type="file" name="fileToUpload" id="fileToUpload" accept="audio/*,video/*,image/*" required>
       <input type="submit" value="Upload File" name="submit" class="upload-btn right">
      </div>
      <div id="uploadProgress" class="upload-progress" style="display: none;">
       <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
       </div>
       <span id="progressText">Uploading...</span>
      </div>
     </form>
    </td>
   </tr>

  </tbody>
 </table>
</div>

 <!-- Hidden data for JavaScript -->
 <script>
        // Data from PHP for JavaScript to use
        window.mediaServerData = {
            initialFileCount: <?php echo count($available_files); ?>,
            mediaPath: <?php echo json_encode($MediaPath); ?>,
            debug: <?php echo isset($Settings['debug']) && $Settings['debug'] ? 'true' : 'false'; ?>
        };
 </script>
    
 <!-- Load JavaScript -->
 <script src="script.js" type="application/javascript"></script>
</body>
