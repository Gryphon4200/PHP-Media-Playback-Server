<?php
/**
 * Display API - Returns current media information
 */

include_once 'functions.php';

header('Content-Type: application/json');

try {
    if (!file_exists('image.txt')) {
        throw new Exception('Display control file not found');
    }
    
    $content = trim(file_get_contents('image.txt'));
    $parts = explode('|', $content);
    
    if (count($parts) < 2) {
        throw new Exception('Invalid display control file format');
    }
    
    $filename = trim($parts[0]);
    $timestamp = trim($parts[1]);
    $full_path = $MediaPath . $filename;
    
    if (!file_exists($full_path)) {
        throw new Exception("Media file not found: {$filename}");
    }
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'timestamp' => $timestamp,
        'url' => 'Media/' . rawurlencode($filename),
        'size' => filesize($full_path),
        'modified' => filemtime($full_path),
        'type' => getMimeType($filename)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

function getMimeType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'aac' => 'audio/aac'
    ];
    return $mime_types[$extension] ?? 'application/octet-stream';
}
?>
