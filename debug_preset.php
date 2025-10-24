<?php
include_once 'functions.php';

echo "<h2>Preset Debug Information</h2>";
echo "<pre>";

// Debug the GET request
echo "GET Parameters:\n";
print_r($_GET);

echo "\nPresets Array:\n";
print_r($Presets);

// Test the specific preset
if (isset($_GET["preset"])) {
    $preset_key = $_GET["preset"];
    echo "\nRequested Preset: " . $preset_key . "\n";
    
    if (isset($Presets[$preset_key])) {
        $filename = $Presets[$preset_key];
        echo "Preset File: " . $filename . "\n";
        echo "Media Path: " . $MediaPath . "\n";
        echo "Full File Path: " . $MediaPath . $filename . "\n";
        echo "File Exists: " . (file_exists($MediaPath . $filename) ? 'YES' : 'NO') . "\n";
    } else {
        echo "ERROR: Preset key '{$preset_key}' not found in Presets array\n";
    }
}

echo "\nCurrent image.txt contents:\n";
if (file_exists('image.txt')) {
    echo file_get_contents('image.txt');
} else {
    echo "image.txt does not exist";
}

echo "\n\nCurrent config.json contents:\n";
if (file_exists('config.json')) {
    echo file_get_contents('config.json');
} else {
    echo "config.json does not exist";
}

echo "</pre>";
?>
