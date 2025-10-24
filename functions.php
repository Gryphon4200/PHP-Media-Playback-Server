<?php

/**
 * Core Functions - Media Server Configuration and File Management
 * Cross-platform compatible (Windows, Linux, macOS)
 */

// ========= Error Handling Functions =========

function handleError($message, $details = '') {
    $error = "Configuration Error: " . $message;
    if ($details) {
        $error .= "\nDetails: " . $details;
    }
    error_log($error);
    throw new Exception($message);
}

// ========= Configuration Functions =========

function loadConfiguration() {
    if (!file_exists('config.json')) {
        handleError('config.json file not found!', 'Please create a config.json file in the application directory.');
    }
    
    $config_content = file_get_contents('config.json');
    if ($config_content === false) {
        handleError('Could not read config.json file!', 'Check file permissions.');
    }
    
    $settings = json_decode($config_content, true);
    if ($settings === null) {
        handleError('Invalid JSON in config.json!', 'JSON Error: ' . json_last_error_msg());
    }
    
    if (!isset($settings['path'])) {
        handleError('Missing "path" configuration in config.json!');
    }
    
    return $settings;
}

// ========= Path Management Functions =========

function normalizePath($path) {
    // Replace all slashes with OS-appropriate directory separator
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    
    // Handle relative vs absolute paths
    if ($normalized === '.') {
        $normalized = './';
    } elseif ($normalized === '..') {
        $normalized = '../';
    }
    
    // Ensure path ends with directory separator
    if (substr($normalized, -1) !== DIRECTORY_SEPARATOR) {
        $normalized .= DIRECTORY_SEPARATOR;
    }
    
    return $normalized;
}

function ensureDirectory($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            handleError("Could not create directory: " . $path, "Check parent directory permissions.");
        }
    }
    
    if (!is_writable($path)) {
        handleError("Directory is not writable: " . $path, "Check directory permissions.");
    }
    
    return true;
}

// ========= File Management Functions =========

function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'mp4':
        case 'avi':
        case 'mov':
        case 'wmv':
        case 'webm':
        case 'mkv':
        case 'flv':
        case 'm4v':
            return '🎥';
        case 'mp3':
        case 'wav':
        case 'ogg':
        case 'aac':
        case 'flac':
        case 'm4a':
        case 'wma':
            return '🎵';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
        case 'webp':
        case 'svg':
            return '🖼️';
        case 'txt':
        case 'md':
            return '📄';
        default:
            return '📁';
    }
}

function getFileInfo($filepath) {
    if (!file_exists($filepath)) {
        return '';
    }
    
    $size = filesize($filepath);
    $modified = date('M j, Y H:i', filemtime($filepath));
    
    // Format file size
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    $size = round($size, 1) . ' ' . $units[$i];
    
    return "({$size} - {$modified})";
}

// ========= Main Initialization =========

// Global variables
$Settings = [];
$ServerPath = '';
$MediaPath = '';
$File_List = [];
$Presets = [];

try {
    // Check for first run - but don't handle it here, let index.php decide
    // We need to include firstrun.php for the isFirstRun() function
    if (file_exists('firstrun.php')) {
        include_once 'firstrun.php';
        
        if (isFirstRun()) {
            // Don't load configuration yet - index.php will handle first run
            return;
        }
    }
    
    // Load configuration
    $Settings = loadConfiguration();
    
    // Setup paths with cross-platform compatibility
    $ServerPath = normalizePath($Settings['path']);
    $MediaPath = $ServerPath . 'Media' . DIRECTORY_SEPARATOR;
    
    // Ensure directories exist
    ensureDirectory($ServerPath);
    ensureDirectory($MediaPath);
    
    // Get file list with error handling
    $File_List = scandir($MediaPath);
    if ($File_List === false) {
        handleError("Could not read Media directory: " . $MediaPath);
    }
    
    // Build presets array
    $Presets = [];
    foreach ($Settings as $key => $value) {
        if ($key !== 'path' && $key !== 'debug' && $key !== 'auto_refresh' && $key !== 'kiosk_mode') {
            $Presets[$key] = $value;
        }
    }
    
    // Debug output (if enabled)
    if (isset($Settings['debug']) && $Settings['debug']) {
        error_log("Media Server Debug - MediaPath: " . $MediaPath . ", Files: " . count($File_List));
    }
    
} catch (Exception $e) {
    // Don't handle the error here - let index.php handle it
    throw $e;
}

// ========= Utility Functions for Other Files =========

function getMediaPath() {
    global $MediaPath;
    return $MediaPath;
}

function getFileList() {
    global $File_List;
    return array_filter($File_List, function($file) {
        return !in_array($file, ['.', '..', '.DS_Store']);
    });
}

function getPresets() {
    global $Presets;
    return $Presets;
}

function getSettings() {
    global $Settings;
    return $Settings;
}

// ========= Debug Functions =========

function showDebugInfo() {
    global $Settings, $ServerPath, $MediaPath, $File_List;
    
    $shouldDebug = (isset($Settings['debug']) && $Settings['debug']) || 
                   (isset($_GET['debug']) && $_GET['debug'] == '1');
    
    if ($shouldDebug) {
        echo "<!-- Debug Information:\n";
        echo "OS: " . PHP_OS . "\n";
        echo "Directory Separator: '" . DIRECTORY_SEPARATOR . "'\n";
        echo "Current Working Directory: " . getcwd() . "\n";
        echo "ServerPath: " . $ServerPath . "\n";
        echo "MediaPath: " . $MediaPath . "\n";
        echo "MediaPath exists: " . (is_dir($MediaPath) ? 'YES' : 'NO') . "\n";
        echo "MediaPath readable: " . (is_readable($MediaPath) ? 'YES' : 'NO') . "\n";
        echo "MediaPath writable: " . (is_writable($MediaPath) ? 'YES' : 'NO') . "\n";
        echo "File count in Media: " . (count($File_List) - 2) . "\n"; // -2 for . and ..
        echo "PHP upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
        echo "PHP post_max_size: " . ini_get('post_max_size') . "\n";
        echo "-->\n";
    }
}

// Call debug info if enabled and not in first-run mode
if (file_exists('firstrun.php')) {
    include_once 'firstrun.php';
    if (!isFirstRun()) {
        showDebugInfo();
    }
} else {
    showDebugInfo();
}

?>
