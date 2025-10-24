<?php
/**
 * First-Run Setup Functions for PHP Media Server
 * Handles initial configuration, file creation, and PHP validation
 * 
 * @author Chris Hamby
 * @version 1.0
 */

// ========= First-Run Detection =========

function isFirstRun() {
    return !file_exists('config.json') || !file_exists('image.txt') || !is_dir('Media');
}

// ========= File Creation Functions =========

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

## System Information:
- Generated: " . date('Y-m-d H:i:s') . "
- Server: " . php_uname() . "
- PHP Version: " . phpversion() . "
- Upload Max Size: " . ini_get('upload_max_filesize') . "
- POST Max Size: " . ini_get('post_max_size') . "
";
    
    file_put_contents($media_dir . 'README.txt', $readme_content);
}

// ========= PHP Configuration Validation =========

function checkPhpConfiguration() {
    $config_results = [
        'file_uploads' => [
            'current' => ini_get('file_uploads'),
            'required' => 'On',
            'status' => false,
            'message' => ''
        ],
        'upload_max_filesize' => [
            'current' => ini_get('upload_max_filesize'),
            'required' => '500M',
            'status' => false,
            'message' => ''
        ],
        'post_max_size' => [
            'current' => ini_get('post_max_size'),
            'required' => '500M',
            'status' => false,
            'message' => ''
        ],
        'max_execution_time' => [
            'current' => ini_get('max_execution_time'),
            'required' => '300',
            'status' => false,
            'message' => ''
        ],
        'memory_limit' => [
            'current' => ini_get('memory_limit'),
            'required' => '512M',
            'status' => false,
            'message' => ''
        ]
    ];
    
    // Check file_uploads
    $config_results['file_uploads']['status'] = (bool)$config_results['file_uploads']['current'];
    $config_results['file_uploads']['message'] = $config_results['file_uploads']['status'] 
        ? 'File uploads enabled' 
        : 'File uploads disabled - media uploads will not work';
    
    // Check upload_max_filesize
    $upload_size_bytes = convertToBytes($config_results['upload_max_filesize']['current']);
    $required_upload_bytes = convertToBytes($config_results['upload_max_filesize']['required']);
    $config_results['upload_max_filesize']['status'] = $upload_size_bytes >= $required_upload_bytes;
    $config_results['upload_max_filesize']['message'] = $config_results['upload_max_filesize']['status']
        ? 'Upload size limit adequate'
        : 'Upload size too small - large media files may fail';
    
    // Check post_max_size
    $post_size_bytes = convertToBytes($config_results['post_max_size']['current']);
    $required_post_bytes = convertToBytes($config_results['post_max_size']['required']);
    $config_results['post_max_size']['status'] = $post_size_bytes >= $required_post_bytes;
    $config_results['post_max_size']['message'] = $config_results['post_max_size']['status']
        ? 'POST size limit adequate'
        : 'POST size too small - large uploads may fail';
    
    // Check max_execution_time
    $current_time = (int)$config_results['max_execution_time']['current'];
    $required_time = (int)$config_results['max_execution_time']['required'];
    $config_results['max_execution_time']['status'] = $current_time == 0 || $current_time >= $required_time;
    $config_results['max_execution_time']['message'] = $config_results['max_execution_time']['status']
        ? ($current_time == 0 ? 'No execution time limit (good)' : 'Execution time adequate')
        : 'Execution time too short - large uploads may timeout';
    
    // Check memory_limit
    $memory_bytes = convertToBytes($config_results['memory_limit']['current']);
    $required_memory_bytes = convertToBytes($config_results['memory_limit']['required']);
    $config_results['memory_limit']['status'] = $memory_bytes == -1 || $memory_bytes >= $required_memory_bytes;
    $config_results['memory_limit']['message'] = $config_results['memory_limit']['status']
        ? ($memory_bytes == -1 ? 'No memory limit (good)' : 'Memory limit adequate')
        : 'Memory limit too low - large files may cause errors';
    
    return $config_results;
}

function convertToBytes($value) {
    if (empty($value)) return 0;
    
    $value = trim($value);
    if ($value === '-1') return -1; // Unlimited
    
    $last = strtolower($value[strlen($value)-1]);
    $number = (int)$value;
    
    switch($last) {
        case 'g': $number *= 1024;
        case 'm': $number *= 1024;
        case 'k': $number *= 1024;
    }
    
    return $number;
}

function getPhpConfigurationStatus() {
    $config_results = checkPhpConfiguration();
    $all_good = array_reduce($config_results, function($carry, $item) {
        return $carry && $item['status'];
    }, true);
    
    return [
        'all_good' => $all_good,
        'results' => $config_results,
        'issues_count' => count(array_filter($config_results, function($item) {
            return !$item['status'];
        }))
    ];
}

function generatePhpIniInstructions() {
    $os = strtolower(PHP_OS);
    $is_windows = strpos($os, 'win') === 0;
    
    $instructions = [
        'php_ini_location' => php_ini_loaded_file() ?: 'php.ini file not found',
        'restart_required' => true,
        'settings' => [
            'file_uploads = On',
            'upload_max_filesize = 500M',
            'post_max_size = 500M',
            'max_execution_time = 300',
            'memory_limit = 512M'
        ]
    ];
    
    if ($is_windows) {
        $instructions['restart_commands'] = [
            'XAMPP: Restart Apache from XAMPP Control Panel',
            'WAMP: Restart Apache from WAMP menu',
            'Manual: Restart your web server service'
        ];
    } else {
        $instructions['restart_commands'] = [
            'sudo systemctl restart apache2',
            'sudo systemctl restart nginx',
            'sudo service php8.3-fpm restart  # If using PHP-FPM'
        ];
    }
    
    return $instructions;
}

// ========= Main Setup Function =========

function performFirstRunSetup() {
    $setup_results = [
        'config' => false,
        'image_txt' => false,
        'media_dir' => false,
        'samples' => false,
        'php_config' => ['status' => false, 'details' => []]
    ];
    
    try {
        // Check PHP configuration first
        $php_status = getPhpConfigurationStatus();
        $setup_results['php_config'] = [
            'status' => $php_status['all_good'],
            'details' => $php_status['results'],
            'issues_count' => $php_status['issues_count']
        ];
        
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

// ========= First-Run Status Check =========

function getFirstRunStatus() {
    if (!isFirstRun()) {
        return null; // Not first run
    }
    
    return performFirstRunSetup();
}
?>
