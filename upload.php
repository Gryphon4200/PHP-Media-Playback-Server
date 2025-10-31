<?php
// Disable output buffering for faster uploads
if (ob_get_level()) {
    ob_end_clean();
}

// Set time limit for large uploads
set_time_limit(300);

// Increase memory limit temporarily
ini_set('memory_limit', '512M');

include_once 'functions.php';

// Function to format file size
function formatFileSize($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

$upload_success = false;
$error_message = "";
$uploaded_filename = "";

// Check if file was uploaded
if (!isset($_FILES['fileToUpload'])) {
    $error_message = "No file selected for upload.";
} else {
    $upload_error = $_FILES['fileToUpload']['error'];
    
    // Enhanced error handling
    switch ($upload_error) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
            $error_message = "File exceeds PHP upload_max_filesize (" . ini_get('upload_max_filesize') . ")";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_message = "File exceeds form MAX_FILE_SIZE directive";
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message = "File was only partially uploaded - try again";
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message = "No file was selected";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message = "Missing temporary folder";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message = "Failed to write file to disk - check permissions";
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_message = "File upload stopped by PHP extension";
            break;
        default:
            $error_message = "Unknown upload error (code: $upload_error)";
    }
    
    if ($upload_error == UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['fileToUpload'];
        $filename = basename($uploaded_file['name']);
        
        // Sanitize filename for better compatibility
        $filename = preg_replace('/[^a-zA-Z0-9._\-\s()]/', '', $filename);
        $target_file = $MediaPath . $filename;
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $error_message = "File '$filename' already exists.";
        }
        // Quick file size check
        elseif ($uploaded_file['size'] > 500 * 1024 * 1024) { // 500MB
            $error_message = "File too large (" . formatFileSize($uploaded_file['size']) . "). Maximum: 500MB.";
        }
        // Use move_uploaded_file for better performance
        elseif (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            $upload_success = true;
            $uploaded_filename = $filename;
        } else {
            $error_message = "Failed to save file. Check directory permissions and disk space.";
        }
    }
}

// Return simple response for AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if ($upload_success) {
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $uploaded_filename,
            'size' => formatFileSize($_FILES['fileToUpload']['size'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $error_message
        ]);
    }
    exit;
}

// HTML response for direct uploads
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Result</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles/base.css">
    <link rel="stylesheet" href="styles/components.css">
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <?php if ($upload_success): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <h2>✅ Upload Successful!</h2>
                <p><strong>File:</strong> <?php echo htmlspecialchars($uploaded_filename); ?></p>
                <p><strong>Size:</strong> <?php echo formatFileSize($_FILES['fileToUpload']['size']); ?></p>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <h2>❌ Upload Failed</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>
        
        <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Back to Media Server</a>
    </div>

    <script>
        // Auto-redirect on success
        <?php if ($upload_success): ?>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
