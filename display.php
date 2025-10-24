<?php
// Read the current media file from image.txt
$current_media = '';
$current_timestamp = '';
$media_path = '';

if (file_exists('image.txt')) {
    $content = trim(file_get_contents('image.txt'));
    $parts = explode('|', $content);
    if (count($parts) >= 1) {
        $current_media = trim($parts[0]);
        $current_timestamp = isset($parts[1]) ? trim($parts[1]) : '';
        $media_path = 'Media/' . $current_media;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        
        body {
            background: #000;
            overflow: hidden;
        }
        
        img, video {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>
<body>
    <?php if ($current_media && file_exists($media_path)): ?>
        <?php
        $extension = strtolower(pathinfo($current_media, PATHINFO_EXTENSION));
        $video_extensions = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv'];
        ?>
        
        <?php if (in_array($extension, $video_extensions)): ?>
            <video src="<?php echo htmlspecialchars($media_path); ?>" autoplay loop></video>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($media_path); ?>" alt="Display">
        <?php endif; ?>
        
    <?php else: ?>
        <div style="color: white; text-align: center; padding: 50px;">
            <h2>No media to display</h2>
            <p><?php echo $current_media ? 'File not found: ' . htmlspecialchars($current_media) : 'No media file specified'; ?></p>
        </div>
    <?php endif; ?>
    
    <script>
        let currentTimestamp = '<?php echo $current_timestamp; ?>';
        
        function checkForUpdate() {
            fetch('image.txt')
                .then(response => response.text())
                .then(data => {
                    const parts = data.trim().split('|');
                    const newTimestamp = parts[1] || '';
                    
                    // Only refresh if the timestamp has changed
                    if (newTimestamp !== currentTimestamp) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.log('Check failed:', error);
                });
        }
        
        // Check for updates every 2 seconds, but only refresh if changed
        setInterval(checkForUpdate, 2000);
    </script>
</body>
</html>
