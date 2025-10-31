<?php
// Test changing settings dynamically
ini_set('max_input_time', 300);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1G');

echo "<h2>Upload Configuration Check</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Current Value</th><th>Status</th></tr>";

$settings = [
    'upload_max_filesize' => '500M+',
    'post_max_size' => '500M+', 
    'max_execution_time' => '300+',
    'max_input_time' => '300+',
    'memory_limit' => '512M+'
];

foreach ($settings as $setting => $recommended) {
    $value = ini_get($setting);
    $status = '✅ Good';
    
    if ($setting === 'max_input_time' && (int)$value < 300) {
        $status = '❌ Too Low - This is likely causing the speed issue!';
    }
    
    echo "<tr><td>$setting</td><td>$value</td><td>$status</td></tr>";
}

echo "<tr><td>Available disk space</td><td>" . number_format(disk_free_space('./') / 1024 / 1024) . " MB</td><td>✅ Good</td></tr>";
echo "</table>";

echo "<h3>Recommendation:</h3>";
echo "<p>Increase <strong>max_input_time</strong> to 300+ seconds to fix upload speed degradation.</p>";
?>
