<?php
include_once 'functions.php';

// Always set JSON content type for all responses
header('Content-Type: application/json');

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
            echo json_encode([
                'success' => true, 
                'message' => 'Preset activated successfully',
                'preset_key' => $preset_key,
                'filename' => $filename,
                'timestamp' => $timestamp
            ]);
        } else {
            throw new Exception("Failed to write display file");
        }
    }
    // Handle direct file selection
    elseif (isset($_GET["file"])) {
        $filename = basename($_GET['file']); // Security: prevent path traversal
        $timestamp = $_GET['timestamp'] ?? time();
        
        // Validate file exists
        $full_path = $MediaPath . $filename;
        if (!file_exists($full_path)) {
            throw new Exception("File not found: " . htmlspecialchars($filename));
        }
        
        $text = $filename . "|" . $timestamp;
        
        if (file_put_contents("image.txt", $text, LOCK_EX) !== false) {
            echo json_encode([
                'success' => true, 
                'message' => 'Media updated successfully',
                'filename' => $filename,
                'timestamp' => $timestamp
            ]);
        } else {
            throw new Exception("Failed to write display file");
        }
    }
    // Handle POST requests (config updates, deletes, etc.)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $update_type = $_POST["update"] ?? '';
        
        switch ($update_type) {
            case "ConfigFileUpdate":
                // Build new configuration
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
                
                // Write to config.json
                $json_text = json_encode($json_data, JSON_PRETTY_PRINT);
                if (file_put_contents("config.json", $json_text) === false) {
                    throw new Exception("Failed to write config.json");
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Configuration updated successfully',
                    'updated_presets' => $updated_presets,
                    'preset_count' => count($updated_presets)
                ]);
                break;
                
            case "Delete":
                $filename = basename($_POST["filename"]);
                $file_path = $MediaPath . $filename;
                
                if (!file_exists($file_path)) {
                    throw new Exception("File not found: " . htmlspecialchars($filename));
                }
                
                if (unlink($file_path)) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'File deleted successfully',
                        'deleted_file' => $filename
                    ]);
                } else {
                    throw new Exception("Failed to delete file: " . htmlspecialchars($filename));
                }
                break;
                
            case "UpdateDisplay":
                // Handle legacy POST display updates
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
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Display updated successfully',
                        'filename' => $filename,
                        'timestamp' => $timestamp
                    ]);
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
    // Ensure we always return JSON for errors
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => 'UPDATE_ERROR'
    ]);
}
?>
