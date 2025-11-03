<?php
include_once 'functions.php';

// Always start with clean output
if (ob_get_level()) {
    ob_end_clean();
}

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Handle GET request for preset selection
    if (isset($_GET["preset"])) {
        $preset_key = $_GET["preset"];
        
        if (!isset($Presets[$preset_key])) {
            throw new Exception("Invalid preset: " . htmlspecialchars($preset_key));
        }
        
        $filename = $Presets[$preset_key];
        $timestamp = time();
        $text = $filename . "|" . $timestamp;
        
        if (file_put_contents("image.txt", $text, LOCK_EX) !== false) {
            $response = [
                'success' => true, 
                'message' => 'Preset activated successfully',
                'preset_key' => $preset_key,
                'filename' => $filename,
                'timestamp' => $timestamp
            ];
        } else {
            throw new Exception("Failed to write display file");
        }
    }
    // Handle direct file selection
    elseif (isset($_GET["file"])) {
        $filename = basename($_GET['file']);
        $timestamp = $_GET['timestamp'] ?? time();
        
        $full_path = $MediaPath . $filename;
        if (!file_exists($full_path)) {
            throw new Exception("File not found: " . htmlspecialchars($filename));
        }
        
        $text = $filename . "|" . $timestamp;
        
        if (file_put_contents("image.txt", $text, LOCK_EX) !== false) {
            $response = [
                'success' => true, 
                'message' => 'Media updated successfully',
                'filename' => $filename,
                'timestamp' => $timestamp
            ];
        } else {
            throw new Exception("Failed to write display file");
        }
    }
    // Handle POST requests (config updates, deletes, etc.)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update_type = $_POST["update"] ?? '';
        
        switch ($update_type) {
            case "ConfigFileUpdate":
                $json_data = ['path' => $ServerPath];
                $updated_presets = [];
                
                foreach ($_POST as $key => $value) {
                    if ($key !== "update") {
                        $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                        $clean_value = trim($value);
                        
                        if (!empty($clean_key) && !empty($clean_value)) {
                            $json_data[$clean_key] = $clean_value;
                            $updated_presets[$clean_key] = $clean_value;
                        }
                    }
                }
                
                $json_text = json_encode($json_data, JSON_PRETTY_PRINT);
                if (file_put_contents("config.json", $json_text) === false) {
                    throw new Exception("Failed to write config.json");
                }
                
                $response = [
                    'success' => true, 
                    'message' => 'Configuration updated successfully',
                    'updated_presets' => $updated_presets,
                    'preset_count' => count($updated_presets)
                ];
                break;
                
            case "Delete":
                $filename = basename($_POST["filename"]);
                $file_path = $MediaPath . $filename;
                
                if (!file_exists($file_path)) {
                    throw new Exception("File not found: " . htmlspecialchars($filename));
                }
                
                if (unlink($file_path)) {
                    $response = [
                        'success' => true, 
                        'message' => 'File deleted successfully',
                        'deleted_file' => $filename
                    ];
                } else {
                    throw new Exception("Failed to delete file: " . htmlspecialchars($filename));
                }
                break;
                
            case "UpdateDisplay":
                $filename = basename($_POST["filename"] ?? '');
                $timestamp = $_POST["timestamp"] ?? time();
                
                if (empty($filename)) {
                    throw new Exception("No filename provided");
                }
                
                $full_path = $MediaPath . $filename;
                if (!file_exists($full_path)) {
                    throw new Exception("File not found: " . htmlspecialchars($filename));
                }
                
                $text = $filename . "|" . $timestamp;
                
                if (file_put_contents("image.txt", $text, LOCK_EX) !== false) {
                    $response = [
                        'success' => true, 
                        'message' => 'Display updated successfully',
                        'filename' => $filename,
                        'timestamp' => $timestamp
                    ];
                } else {
                    throw new Exception("Failed to write display file");
                }
                break;
                
            default:
                throw new Exception("Invalid update type: " . htmlspecialchars($update_type));
        }
    }
    else {
        throw new Exception("Invalid request method");
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => 'UPDATE_ERROR'
    ];
}

// Determine response format based on request type
$isAjaxRequest = (
    $_SERVER['REQUEST_METHOD'] === 'POST' || 
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($isAjaxRequest) {
    // Return JSON for AJAX requests
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    // Return HTML for browser requests (GET requests for presets/files)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Media Server - Update Result</title>
        <meta charset="utf-8">
        <link rel="stylesheet" href="styles/base.css">
        <link rel="stylesheet" href="styles/components.css">
        <style>
            .result-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
            }
            .success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 5px;
                margin: 10px 0;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 5px;
                margin: 10px 0;
            }
            .back-link {
                display: inline-block;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="result-container">
            <?php if ($response['success']): ?>
                <div class="success">
                    <h2>✅ Success!</h2>
                    <p><?php echo htmlspecialchars($response['message']); ?></p>
                    
                    <?php if (isset($response['filename'])): ?>
                        <p><strong>File:</strong> <?php echo htmlspecialchars($response['filename']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($response['preset_key'])): ?>
                        <p><strong>Preset:</strong> <?php echo htmlspecialchars($response['preset_key']); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="error">
                    <h2>❌ Error</h2>
                    <p><?php echo htmlspecialchars($response['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="back-link">← Back to Media Server</a>
        </div>

        <?php if ($response['success']): ?>
        <script>
            // Auto-redirect on success after 2 seconds
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 2000);
        </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>
