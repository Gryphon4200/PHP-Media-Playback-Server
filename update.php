<?php
include_once 'functions.php';

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
            echo "Success: Preset " . htmlspecialchars($preset_key) . " activated";
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
            echo "Success: Now displaying " . htmlspecialchars($filename);
        } else {
            throw new Exception("Failed to write display file");
        }
    }
    // Handle POST requests (config updates, deletes, etc.)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
        $update_type = $_POST["update"] ?? '';
        
        switch ($update_type) {
            case "ConfigFileUpdate":
                // Build new configuration
                $json_data = ['path' => $ServerPath];
                
                foreach ($_POST as $key => $value) {
                    if ($key !== "update") {
                        $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                        $clean_value = trim($value);
                        
                        if (!empty($clean_key) && !empty($clean_value)) {
                            $json_data[$clean_key] = $clean_value;
                        }
                    }
                }
                
                // Write to config.json
                $json_text = json_encode($json_data, JSON_PRETTY_PRINT);
                if (file_put_contents("config.json", $json_text) === false) {
                    throw new Exception("Failed to write config.json");
                }
                
                echo json_encode(['success' => true, 'message' => 'Configuration updated successfully']);
                break;
                
            case "Delete":
                $filename = basename($_POST["filename"]);
                $file_path = $MediaPath . $filename;
                
                if (!file_exists($file_path)) {
                    throw new Exception("File not found: " . htmlspecialchars($filename));
                }
                
                if (unlink($file_path)) {
                    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
                } else {
                    throw new Exception("Failed to delete file");
                }
                break;
                
            default:
                throw new Exception("Invalid update type");
        }
    }
    else {
        throw new Exception("Invalid request method");
    }
    
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
