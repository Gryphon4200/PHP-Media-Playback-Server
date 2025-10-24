<?php
/**
 * PHP Media Playback Server - Main Entry Point
 * Handles first-run initialization, error handling, and normal operation
 * 
 * @author Chris Hamby
 * @version 1.0
 */

// ========= Main Application Logic =========
try {
    // Include functions first to get access to first-run detection
    include_once 'functions.php';
    
    // Check for first run and handle setup
    if (isFirstRun() && !isset($_GET['continue'])) {
        $setup_results = performFirstRunSetup();
        displayFirstRunSetup($setup_results);
        exit;
    }
    
    // Normal operation - all files should exist now
    // Re-include functions.php to load configuration (it will skip first-run logic)
    include_once 'functions.php';
    include_once 'header.php';
    include_once 'main.php';
    include_once 'footer.php';
    
} catch (Exception $e) {
    displayErrorPage($e);
}

// ========= First-Run Setup Display Function =========
function displayFirstRunSetup($setup_results) {
    $all_success = array_reduce($setup_results, function($carry, $item) {
        return $carry && $item;
    }, true);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PHP Media Server - First Run Setup</title>
        
        <!-- Modular CSS Loading -->
        <link rel="stylesheet" href="styles/base.css">
        <link rel="stylesheet" href="styles/setup.css">
        <link rel="stylesheet" href="styles/components.css">
        
        <link rel="icon" href="favicon.ico">
    </head>
    <body class="setup-page">
        <div class="setup-container">
            <div class="setup-header">
                <h1>🎬 Welcome to PHP Media Server</h1>
                <p>First-time setup complete</p>
            </div>
            
            <div class="setup-results">
                <h3>Setup Results:</h3>
                
                <div class="setup-item">
                    <span class="setup-icon <?php echo $setup_results['config'] ? 'success' : 'error'; ?>">
                        <?php echo $setup_results['config'] ? '✅' : '❌'; ?>
                    </span>
                    <span>Configuration file (config.json)</span>
                </div>
                
                <div class="setup-item">
                    <span class="setup-icon <?php echo $setup_results['image_txt'] ? 'success' : 'error'; ?>">
                        <?php echo $setup_results['image_txt'] ? '✅' : '❌'; ?>
                    </span>
                    <span>Display communication file (image.txt)</span>
                </div>
                
                <div class="setup-item">
                    <span class="setup-icon <?php echo $setup_results['media_dir'] ? 'success' : 'error'; ?>">
                        <?php echo $setup_results['media_dir'] ? '✅' : '❌'; ?>
                    </span>
                    <span>Media directory and welcome files</span>
                </div>
                
                <div class="setup-item">
                    <span class="setup-icon <?php echo $setup_results['samples'] ? 'success' : 'error'; ?>">
                        <?php echo $setup_results['samples'] ? '✅' : '❌'; ?>
                    </span>
                    <span>Documentation and sample files</span>
                </div>
            </div>
            
            <?php if ($all_success): ?>
                <div class="setup-info">
                    <h3>🎉 Setup Complete!</h3>
                    <p>Your media server is ready to use. You can now:</p>
                    <ul>
                        <li>Upload media files (video, audio, images)</li>
                        <li>Configure presets for quick access</li>
                        <li>Manage your media library</li>
                        <li>Connect displays to show your content</li>
                    </ul>
                    <p><strong>Default setup includes:</strong></p>
                    <ul>
                        <li>Welcome image in Media directory</li>
                        <li>Basic configuration with 3 presets</li>
                        <li>Documentation and README files</li>
                        <li>Real-time file monitoring system</li>
                    </ul>
                    <p><strong>Getting Started:</strong></p>
                    <ul>
                        <li>Access via web browser on any device</li>
                        <li>Upload files using drag-and-drop or file picker</li>
                        <li>Configure presets for quick media switching</li>
                        <li>Connect displays to show your content</li>
                    </ul>
                </div>
                
                <a href="?continue=1" class="continue-btn">Start Using Media Server</a>
                
                <script>
                    // Auto-redirect after 10 seconds if user doesn't click
                    let countdown = 10;
                    const btn = document.querySelector('.continue-btn');
                    const originalText = btn.textContent;
                    
                    const timer = setInterval(() => {
                        countdown--;
                        btn.textContent = `${originalText} (${countdown}s)`;
                        
                        if (countdown <= 0) {
                            clearInterval(timer);
                            window.location.href = '?continue=1';
                        }
                    }, 1000);
                    
                    // Clear timer if user clicks
                    btn.addEventListener('click', () => {
                        clearInterval(timer);
                    });
                </script>
                
            <?php else: ?>
                <div class="setup-info">
                    <h3>⚠️ Setup Issues</h3>
                    <p>Some setup steps failed. Please check file permissions and try again.</p>
                    
                    <p><strong>Required permissions:</strong></p>
                    <ul>
                        <li>Write access to the application directory</li>
                        <li>Ability to create files and folders</li>
                        <li>PHP file_put_contents() function enabled</li>
                    </ul>
                    
                    <p><strong>Troubleshooting commands:</strong></p>
                    <ul>
                        <li><code>chmod -R 755 .</code> - Set directory permissions</li>
                        <li><code>chown -R www-data:www-data .</code> - Fix ownership (Linux)</li>
                        <li><code>ls -la</code> - Check current permissions</li>
                    </ul>
                    
                    <p><strong>Windows users:</strong></p>
                    <ul>
                        <li>Right-click folder → Properties → Security</li>
                        <li>Ensure "Full Control" for your user account</li>
                        <li>Check that PHP can write to the directory</li>
                    </ul>
                </div>
                
                <a href="?" class="continue-btn retry-btn">Retry Setup</a>
            <?php endif; ?>
            
            <div class="setup-info">
                <h3>📋 System Information</h3>
                <ul>
                    <li><strong>Server:</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Current Directory:</strong> <?php echo getcwd(); ?></li>
                    <li><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                    <li><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></li>
                    <li><strong>Setup Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Console welcome message for developers -->
        <script>
            console.log('%c🎬 PHP Media Playback Server', 'color: #4CAF50; font-size: 16px; font-weight: bold;');
            console.log('%cFirst-run setup completed', 'color: #2196F3;');
            console.log('System ready for media management and display');
        </script>
    </body>
    </html>
    <?php
}

// ========= Error Display Function =========
function displayErrorPage($exception) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Media Server Error</title>
        
        <!-- Modular CSS Loading -->
        <link rel="stylesheet" href="styles/base.css">
        <link rel="stylesheet" href="styles/setup.css">
        <link rel="stylesheet" href="styles/components.css">
        
        <link rel="icon" href="favicon.ico">
    </head>
    <body class="error-page">
        <div class="error-container">
            <div class="error-header">
                <h1>🚨 Media Server Error</h1>
                <p>An error occurred during initialization</p>
            </div>
            
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($exception->getMessage()); ?>
            </div>
            
            <div class="error-help">
                <h3>Troubleshooting Steps:</h3>
                <ol>
                    <li>Check that all required PHP files are present in the directory</li>
                    <li>Verify write permissions on the application directory</li>
                    <li>Ensure PHP has permission to create files and folders</li>
                    <li>Check PHP error logs for more detailed information</li>
                    <li>Make sure <code>functions.php</code> exists and is readable</li>
                </ol>
                
                <p><strong>Quick fixes to try:</strong></p>
                <ul>
                    <li><code>chmod -R 755 .</code> - Set directory permissions (Linux/Mac)</li>
                    <li><code>chown -R www-data:www-data .</code> - Fix ownership (Linux)</li>
                    <li>Check PHP error log: <code>tail -f /var/log/php_errors.log</code></li>
                    <li>Verify all project files are present</li>
                </ul>
                
                <p><strong>File checklist:</strong></p>
                <ul>
                    <li>✓ index.php (this file)</li>
                    <li><?php echo file_exists('functions.php') ? '✓' : '❌'; ?> functions.php</li>
                    <li><?php echo file_exists('header.php') ? '✓' : '❌'; ?> header.php</li>
                    <li><?php echo file_exists('main.php') ? '✓' : '❌'; ?> main.php</li>
                    <li><?php echo file_exists('footer.php') ? '✓' : '❌'; ?> footer.php</li>
                    <li><?php echo file_exists('script.js') ? '✓' : '❌'; ?> script.js</li>
                </ul>
            </div>
            
            <div class="error-info">
                <h3>📋 System Information</h3>
                <ul>
                    <li><strong>Server:</strong> <?php echo php_uname(); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Current Directory:</strong> <?php echo getcwd(); ?></li>
                    <li><strong>Directory Writable:</strong> <?php echo is_writable('.') ? 'Yes' : 'No'; ?></li>
                    <li><strong>Error Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="?" class="retry-btn">← Try Again</a>
                <a href="https://github.com/Gryphon4200/PHP-Media-Playback-Server" 
                   class="continue-btn" target="_blank">📖 View Documentation</a>
            </div>
        </div>
        
        <!-- Error reporting for developers -->
        <script>
            console.error('PHP Media Server Error:', <?php echo json_encode($exception->getMessage()); ?>);
            console.log('Check the troubleshooting steps above for solutions');
        </script>
    </body>
    </html>
    <?php
}
?>
