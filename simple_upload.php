<?php
ini_set('max_execution_time', 0);
ini_set('max_input_time', 0);
ini_set('memory_limit', '1G');

include_once 'functions.php';

if ($_FILES && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $target = $MediaPath . basename($file['name']);
    
    // More precise timing measurement
    $start_time = microtime(true);
    echo "Upload started at: " . date('H:i:s.') . substr(microtime(), 2, 3) . "<br>";
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $end_time = microtime(true);
        echo "Upload completed at: " . date('H:i:s.') . substr(microtime(), 2, 3) . "<br>";
        
        // Calculate duration with higher precision
        $duration = $end_time - $start_time;
        $size_mb = $file['size'] / 1024 / 1024;
        
        // Multiple speed calculations
        $speed_mbps = $duration > 0 ? ($size_mb / $duration) * 8 : 0; // Mbps
        $speed_MB_per_sec = $duration > 0 ? ($size_mb / $duration) : 0; // MB/s
        
        echo "<h3>Upload Results:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><td><strong>File:</strong></td><td>" . htmlspecialchars($file['name']) . "</td></tr>";
        echo "<tr><td><strong>Size:</strong></td><td>" . number_format($size_mb, 2) . " MB (" . number_format($file['size']) . " bytes)</td></tr>";
        echo "<tr><td><strong>Start Time:</strong></td><td>" . number_format($start_time, 6) . "</td></tr>";
        echo "<tr><td><strong>End Time:</strong></td><td>" . number_format($end_time, 6) . "</td></tr>";
        echo "<tr><td><strong>Duration:</strong></td><td>" . number_format($duration, 6) . " seconds</td></tr>";
        echo "<tr><td><strong>Speed (MB/s):</strong></td><td>" . number_format($speed_MB_per_sec, 2) . " MB/s</td></tr>";
        echo "<tr><td><strong>Speed (Mbps):</strong></td><td>" . number_format($speed_mbps, 2) . " Mbps</td></tr>";
        echo "</table>";
        
        // Debug info
        echo "<h4>Debug Info:</h4>";
        echo "Raw duration: $duration<br>";
        echo "Duration > 0: " . ($duration > 0 ? 'Yes' : 'No') . "<br>";
        echo "PHP Version: " . phpversion() . "<br>";
        echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
        
        // Performance analysis
        if ($duration < 0.001) {
            echo "<p style='color: orange;'><strong>Warning:</strong> Duration is extremely small (&lt;1ms). This might indicate:</p>";
            echo "<ul>";
            echo "<li>The file was cached or already in memory</li>";
            echo "<li>PHP timing precision issues</li>";
            echo "<li>The file is being moved within the same filesystem (very fast)</li>";
            echo "</ul>";
        } elseif ($speed_MB_per_sec > 1000) {
            echo "<p style='color: orange;'><strong>Note:</strong> Speed over 1000 MB/s suggests local filesystem operation (not network upload).</p>";
        } elseif ($speed_MB_per_sec < 10) {
            echo "<p style='color: red;'><strong>Slow upload detected:</strong> Speed under 10 MB/s may indicate a bottleneck.</p>";
        } else {
            echo "<p style='color: green;'><strong>Good speed:</strong> Upload completed at reasonable rate.</p>";
        }
        
    } else {
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        echo "<p style='color: red;'>Upload failed after " . number_format($duration, 6) . " seconds!</p>";
        echo "Error details: " . error_get_last()['message'] . "<br>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Enhanced Upload Speed Test</title></head>
<body>
    <h2>Enhanced Upload Speed Test</h2>
    <p>This test will measure actual upload time with microsecond precision.</p>
    
    <form method="post" enctype="multipart/form-data">
        <p><input type="file" name="file" required></p>
        <p><button type="submit">Upload and Measure Speed</button></p>
    </form>
    
    <h4>Expected Results:</h4>
    <ul>
        <li><strong>Local network (same machine):</strong> 100+ MB/s</li>
        <li><strong>Local network (different machine):</strong> 10-100 MB/s</li>
        <li><strong>Fast internet:</strong> 1-50 MB/s</li>
        <li><strong>Normal internet:</strong> 0.1-10 MB/s</li>
    </ul>
</body>
</html>
