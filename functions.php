<?php

/**
 * Core Functions - Media Server Configuration and File Management
 * Includes first-run setup and cross-platform compatibility
 */

// ========= First-Run Setup Functions =========

function isFirstRun() {
    return !file_exists('config.json') || !file_exists('image.txt') || !is_dir('Media');
}

function createDefaultConfig() {
    $default_config = [
        "path" => "./",
        "debug" => false,
        "auto_refresh" => 0,
        "kiosk_mode" => false,
        "1" => "welcome.jpg",
        "2" => "sample_video.mp4",
        "3" => "sample_audio.mp3"
    ];
    
    $json_content = json_encode($default_config, JSON_PRETTY_PRINT);
    return file_put_contents('config.json', $json_content) !== false;
}

function createDefaultImageTxt() {
    $default_content = "welcome.jpg|" . time();
    return file_put_contents('image.txt', $default_content) !== false;
}

function createMediaDirectory() {
    if (!is_dir('Media')) {
        if (!mkdir('Media', 0755, true)) {
            return false;
        }
    }
    
    // Create a welcome file so the directory isn't empty
    $welcome_content = createWelcomeImage();
    if ($welcome_content) {
        file_put_contents('Media/welcome.jpg', $welcome_content);
    }
    
    // Create sample documentation
    createMediaDocumentation();
    
    return true;
}

function createWelcomeImage() {
    // Create a simple SVG welcome image
    $svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#4CAF50;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#2E7D32;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="800" height="600" fill="url(#bg)"/>
    <text x="400" y="250" font-family="Arial, sans-serif" font-size="48" font-weight="bold" 
          text-anchor="middle" fill="white">PHP Media Server</text>
    <text x="400" y="300" font-family="Arial, sans-serif" font-size="24" 
          text-anchor="middle" fill="white">Welcome to your media playback system</text>
    <text x="400" y="350" font-family="Arial, sans-serif" font-size="18" 
          text-anchor="middle" fill="white">Upload media files to get started</text>
    <text x="400" y="450" font-family="Arial, sans-serif" font-size="14" 
          text-anchor="middle" fill="rgba(255,255,255,0.8)">System initialized: ' . date('Y-m-d H:i:s') . '</text>
</svg>';
    
    return $svg_content;
}

function createMediaDocumentation() {
    $media_dir = 'Media/';
    
    $readme_content = "# Media Directory

This directory contains your media files for the playback server.

## Supported File Types:
- Video: MP4, AVI, MOV, WMV, WebM, MKV
- Audio: MP3, WAV, OGG, AAC, FLAC
- Images: JPG, PNG, GIF, BMP, WebP, SVG

## Getting Started:
1. Upload media files using the web interface
2. Configure presets to assign files to specific slots
3. Use the display system to show your content

## File Management:
- Files are automatically detected and displayed
- Delete files using the 'X' button in the interface
- Large files (>500MB) may take longer to upload

Generated: " . date('Y-m-d H:i:s') . "
Server: " . php_uname() . "
";
    
    file_put_contents($media_dir . 'README.txt', $readme_content);
}

function performFirstRunSetup() {
    $setup_results = [
        'config' => false,
        'image_txt' => false,
        'media_dir' => false,
        'samples' => false
    ];
    
    try {
        // Create config.json
        $setup_results['config'] = createDefaultConfig();
        
        // Create image.txt
        $setup_results['image_txt'] = createDefaultImageTxt();
        
        // Create Media directory with samples
        $setup_results['media_dir'] = createMediaDirectory();
        $setup_results['samples'] = file_exists('Media/README.txt');
        
        return $setup_results;
        
    } catch (Exception $e) {
        error_log("First-run setup error: " . $e->getMessage());
        return $setup_results;
    }
}

function getFirstRunStatus() {
    if (!isFirstRun()) {
        return null; // Not first run
    }
    
    return performFirstRunSetup();
}

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
    if (isFirstRun()) {
        // Don't load configuration yet - index.php will handle first run
        return;
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

// Call debug info if enabled
if (!isFirstRun()) {
    showDebugInfo();
}

?>
