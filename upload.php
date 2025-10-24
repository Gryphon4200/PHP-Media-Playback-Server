<?php
include_once 'functions.php';

// Function to format file size
function formatFileSize($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// Enhanced error handling
function getUploadError($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_OK:
            return null;
        case UPLOAD_ERR_INI_SIZE:
            return "File exceeds PHP upload_max_filesize (" . ini_get('upload_max_filesize') . ")";
        case UPLOAD_ERR_FORM_SIZE:
            return "File exceeds form MAX_FILE_SIZE directive";
        case UPLOAD_ERR_PARTIAL:
            return "File was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was selected";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by PHP extension";
        default:
            return "Unknown upload error (code: $error_code)";
    }
}

$upload_success = false;
$error_message = "";
$uploaded_filename = "";

// Check if file was uploaded
if (!isset($_FILES['fileToUpload'])) {
    $error_message = "No file selected for upload.";
} else {
    $upload_error = $_FILES['fileToUpload']['error'];
    $error_message = getUploadError($upload_error);
    
    if ($upload_error == UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['fileToUpload'];
        $filename = basename($uploaded_file['name']);
        $target_file = $MediaPath . $filename;
        $file_size = $uploaded_file['size'];
        
        // Validate file name (no special characters that could cause issues)
        if (!preg_match('/^[a-zA-Z0-9._\-\s]+$/', $filename)) {
            $error_message = "Invalid filename. Please use only letters, numbers, spaces, dots, hyphens, and underscores.";
        }
        // Check if file already exists
        elseif (file_exists($target_file)) {
            $error_message = "File '$filename' already exists.";
        }
        // Check file size (optional - adjust as needed)
        elseif ($file_size > 500 * 1024 * 1024) { // 500MB limit
            $error_message = "File is too large (" . formatFileSize($file_size) . "). Maximum size is 500MB.";
        }
        // Attempt to move uploaded file
        elseif (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            $upload_success = true;
            $uploaded_filename = $filename;
        } else {
            $error_message = "Failed to save uploaded file. Check directory permissions.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Result</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 0 0; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <?php if ($upload_success): ?>
        <div class="success">
            <h2>Upload Successful!</h2>
            <p><strong>File:</strong> <?php echo htmlspecialchars($uploaded_filename); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($MediaPath); ?></p>
            <p><strong>Size:</strong> <?php echo formatFileSize($_FILES['fileToUpload']['size']); ?></p>
        </div>
    <?php else: ?>
        <div class="error">
            <h2>Upload Failed</h2>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <p><strong>Current Media Directory:</strong> <?php echo htmlspecialchars($MediaPath); ?></p>
        <p><strong>PHP Upload Limits:</strong></p>
        <ul>
            <li>Max file size: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>Max post size: <?php echo ini_get('post_max_size'); ?></li>
            <li>Max execution time: <?php echo ini_get('max_execution_time'); ?>s</li>
        </ul>
    </div>
    
    <a href="index.php" class="button">← Back to Media Server</a>
    <a href="javascript:history.back()" class="button">Try Again</a>

    <script>
        // Auto-redirect on success after 3 seconds
        <?php if ($upload_success): ?>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
