<?php
// Read current media from image.txt on page load
$current_media_file = 'None';
$current_media_time = '--';
 
if (file_exists('image.txt')) {
    $content = trim(file_get_contents('image.txt'));
    if (!empty($content)) {
        $parts = explode('|', $content);
        $current_media_file = isset($parts[0]) ? trim($parts[0]) : 'None';
        if (isset($parts[1]) && !empty($parts[1])) {
            $timestamp = trim($parts[1]);
            if (is_numeric($timestamp)) {
                $current_media_time = 'Updated: ' . date('M j, Y H:i:s', $timestamp);
            } else {
                $current_media_time = 'Updated: ' . $timestamp;
            }
        }
    }
}
?>

<body>
 <!-- File Monitor Status -->
 <div id="fileMonitorStatus" class="file-monitor-status status-monitoring">Monitoring files...</div>
 
 <!-- Header Section -->
 <div class="header-section">
  <h1>Media Playback Server</h1>
  <div class="server-info">
   <span>Media Path: <?php echo htmlspecialchars($MediaPath); ?></span>
   <span class="file-count">Files: <?php echo count(getFileList()); ?></span>
  </div>
 </div>
 
 <!-- Current Media Display -->
 <div class="current-media-section">
  <h3>🎬 Currently Displaying:</h3>
  <div id="currentMediaDisplay" class="current-media-display">
   <div class="media-info">
    <span class="media-icon">📺</span>
    <div class="media-details">
     <div class="media-filename" id="currentMediaFile"><?php echo htmlspecialchars($current_media_file); ?></div>
     <div class="media-timestamp" id="currentMediaTime"><?php echo htmlspecialchars($current_media_time); ?></div>
    </div>
    <div class="media-actions">
     <button onclick="refreshCurrentMedia()" class="btn-small">🔄 Refresh</button>
     <a href="display.php" target="_blank" class="btn-small">📺 Open Display</a>
    </div>
   </div>
  </div>
 </div>

 <!-- Presets Dropdown -->
 <div class="dropdown" onclick="ToggleElement();">
  <button class="dropbtn" type="button">Presets (<?php echo count($Presets); ?>)</button>
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
   <label for="Preset<?php echo $Index; ?>">Preset <?php echo $Index; ?> (<?php echo htmlspecialchars($preset_key); ?>):</label>
   <select name="Preset<?php echo $Index; ?>" id="Preset<?php echo $Index; ?>" data-preset-key="<?php echo htmlspecialchars($preset_key); ?>">
    <option value="">-- Select File --</option>
  <?php foreach($available_files as $file): ?>
    <option value="<?php echo htmlspecialchars($file); ?>" 
                   <?php echo ($file === $preset_value) ? 'selected' : ''; ?>>
                   <?php echo htmlspecialchars($file); ?>
    </option>
  <?php endforeach; ?>
   </select>
   <button onclick="activatePreset('<?php echo $preset_key; ?>')" class="btn-small preset-activate">▶️ Activate</button>
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
     <th>Media Files</th>
     <th width="100">Actions</th>
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
     <td class="file" data-filename="<?php echo htmlspecialchars($file); ?>" onclick="displayFile(this.dataset.filename);">
      <span class="file-icon"><?php echo getFileIcon($file); ?></span>
      <span class="filename"><?php echo htmlspecialchars($file); ?></span>
      <span class="file-info"><?php echo getFileInfo($MediaPath . $file); ?></span>
     </td>
     <td class="file-actions">
      <button onclick="displayFile('<?php echo addslashes($file); ?>')" class="btn-small btn-display" title="Display this file">📺</button>
      <button onclick="DeleteFile('<?php echo addslashes($file); ?>')" class="btn-small btn-delete" title="Delete <?php echo htmlspecialchars($file); ?>">✕</button>
     </td>
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
        <input type="submit" value="Upload File" name="submit" class="upload-btn" id="uploadButton">
       </div>
       <div id="uploadProgress" class="upload-progress" style="display: none;">
        <div class="progress-bar">
         <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="progress-info">
         <span id="progressText">Uploading...</span>
         <span id="progressPercent">0%</span>
        </div>
       </div>
      </form>
     </td>
    </tr>
   </tbody>
  </table>
 </div>

 <!-- Data for JavaScript -->
 <script>
    window.mediaServerData = {
        initialFileCount: <?php echo count($available_files); ?>,
        mediaPath: <?php echo json_encode($MediaPath); ?>,
        debug: <?php echo isset($Settings['debug']) && $Settings['debug'] ? 'true' : 'false'; ?>
    };
 </script>
    
 <!-- Load JavaScript Files -->
 <script src="script.js" type="application/javascript"></script>
 <script src="main.js" type="application/javascript"></script>
</body>
