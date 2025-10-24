<?php
include_once 'functions.php';

// Get current file list and detailed information
$current_files = [];
$last_modified = 0;
$total_size = 0;

if (is_dir($MediaPath)) {
    $files = scandir($MediaPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && $file != '.DS_Store') {
            $filepath = $MediaPath . $file;
            $mtime = filemtime($filepath);
            $size = filesize($filepath);
            
            $current_files[$file] = [
                'modified' => $mtime,
                'size' => $size,
                'icon' => getFileIcon($file),
                'info' => getFileInfo($filepath)
            ];
            
            $last_modified = max($last_modified, $mtime);
            $total_size += $size;
        }
    }
}

// Return comprehensive JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'files' => $current_files,
    'count' => count($current_files),
    'last_modified' => $last_modified,
    'total_size' => $total_size,
    'timestamp' => time(),
    'media_path' => $MediaPath
]);
?>
