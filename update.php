<?php
// Always return JSON for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Capture any PHP errors/output
    ob_start();
}

include_once 'functions.php';

// Response structure
$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'timestamp' => time(),
    'debug' => []
];

try {
    // Handle GET request for preset selection (existing code)
    if (isset($_GET["preset"])) {
        $preset_key = $_GET["preset"];
        
        if (!isset($Presets[$preset_key])) {
            throw new Exception("Invalid preset: " . htmlspecialchars($preset_key));
        }
        
        $filename = $Presets[$preset_key];
        $timestamp = time();
        $text = $filename . "|" . $timestamp;
        
        if (writeDisplayFile($text)) {
            $response['success'] = true;
            $response['message'] = "Preset '{$preset_key}' activated: {$filename}";
            $response['data'] = [
                'preset' => $preset_key,
                'filename' => $filename,
                'timestamp' => $timestamp
            ];
        } else {
            throw new Exception("Failed to write display file");
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clear any output buffering
        $captured_output = ob_get_clean();
        if (!empty($captured_output)) {
            $response['debug']['captured_output'] = $captured_output;
        }
        
        // Re-start output buffering
        ob_start();
        
        $update_type = $_POST["update"] ?? '';
        $response['debug']['update_type'] = $update_type;
        $response['debug']['post_data'] = $_POST;
        
        switch ($update_type) {
            case "ConfigFileUpdate":
                $response = handleConfigUpdate($_POST, $response);
                break;
                
            case "UpdateDisplay":
                $response = handleDisplayUpdate($_POST, $response);
                break;
                
            case "Delete":
                $response = handleFileDelete($_POST, $response);
                break;
                
            default:
                throw new Exception("Invalid update type: " . htmlspecialchars($update_type));
        }
        
    } else {
        throw new Exception("Invalid request method");
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Capture any additional output
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $captured_output = ob_get_clean();
        if (!empty($captured_output)) {
            $response['debug']['error_output'] = $captured_output;
        }
    }
}

// Return response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Make sure we haven't output anything else
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
} else {
    // For GET requests, show HTML response (existing code)
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Update Result</title></head>
    <body>
        <h2><?php echo $response['success'] ? 'Success' : 'Error'; ?></h2>
        <p><?php echo htmlspecialchars($response['message']); ?></p>
        <a href="index.php">← Back</a>
    </body>
    </html>
    <?php
}

// Updated helper function
function handleConfigUpdate($post_data, $response = null) {
    global $ServerPath;
    
    if ($response === null) {
        $response = ['success' => false, 'message' => '', 'data' => [], 'timestamp' => time()];
    }
    
    try {
        // Build new configuration
        $json_data = ['path' => $ServerPath];
        $updated_presets = [];
        
        foreach ($post_data as $key => $value) {
            if ($key !== "update") {
                $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                $clean_value = trim($value);
                
                if (!empty($clean_key) && !empty($clean_value)) {
                    $json_data[$clean_key] = $clean_value;
                    if ($clean_key !== 'path') {
                        $updated_presets[$clean_key] = $clean_value;
                    }
                }
            }
        }
        
        // Write to config.json
        $json_text = json_encode($json_data, JSON_PRETTY_PRINT);
        if (file_put_contents("config.json", $json_text) === false) {
            throw new Exception("Failed to write config.json");
        }
        
        $response['success'] = true;
        $response['message'] = "Configuration updated successfully";
        $response['data'] = [
            'updated_presets' => $updated_presets,
            'total_presets' => count($updated_presets)
        ];
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = "Config update failed: " . $e->getMessage();
    }
    
    return $response;
}

function handleDisplayUpdate($post_data) {
    $response = [
        'success' => false,
        'message' => '',
        'data' => [],
        'timestamp' => time()
    ];
    
    try {
        // Validate required fields
        if (empty($post_data["filename"])) {
            throw new Exception("Filename is required");
        }
        
        $filename = basename($post_data["filename"]); // Security: prevent path traversal
        $timestamp = $post_data["timestamp"] ?? time();
        
        // Validate filename exists in media directory
        global $MediaPath;
        $full_path = $MediaPath . $filename;
        if (!file_exists($full_path)) {
            throw new Exception("File not found: " . htmlspecialchars($filename));
        }
        
        $text = $filename . "|" . $timestamp;
        
        if (writeDisplayFile($text)) {
            $response['success'] = true;
            $response['message'] = "Display updated successfully";
            $response['data'] = [
                'filename' => $filename,
                'timestamp' => $timestamp,
                'file_size' => filesize($full_path),
                'file_modified' => filemtime($full_path)
            ];
        } else {
            throw new Exception("Failed to write display file");
        }
        
    } catch (Exception $e) {
        $response['message'] = "Display update failed: " . $e->getMessage();
    }
    
    return $response;
}

function handleFileDelete($post_data) {
    global $MediaPath;
    
    $response = [
        'success' => false,
        'message' => '',
        'data' => [],
        'timestamp' => time()
    ];
    
    try {
        // Validate filename
        if (empty($post_data["filename"])) {
            throw new Exception("Filename is required for deletion");
        }
        
        $filename = basename($post_data["filename"]); // Security: prevent path traversal
        $file_path = $MediaPath . $filename;
        
        // Check if file exists
        if (!file_exists($file_path)) {
            throw new Exception("File not found: " . htmlspecialchars($filename));
        }
        
        // Check if file is writable/deletable
        if (!is_writable($file_path)) {
            throw new Exception("File is not writable: " . htmlspecialchars($filename));
        }
        
        // Get file info before deletion
        $file_size = filesize($file_path);
        
        // Attempt deletion
        if (unlink($file_path)) {
            $response['success'] = true;
            $response['message'] = "File deleted successfully";
            $response['data'] = [
                'filename' => $filename,
                'file_size' => $file_size,
                'deleted_at' => date('Y-m-d H:i:s')
            ];
        } else {
            throw new Exception("Failed to delete file: " . htmlspecialchars($filename));
        }
        
    } catch (Exception $e) {
        $response['message'] = "File deletion failed: " . $e->getMessage();
    }
    
    return $response;
}

function writeDisplayFile($text) {
    try {
        $result = file_put_contents("image.txt", $text, LOCK_EX);
        return $result !== false;
    } catch (Exception $e) {
        error_log("Failed to write display file: " . $e->getMessage());
        return false;
    }
}
?>
