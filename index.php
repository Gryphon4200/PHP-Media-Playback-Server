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
    // Include first-run functions
    include_once 'firstrun.php';
    
    // Check for first run and handle setup
    if (isFirstRun() && !isset($_GET['continue'])) {
        $setup_results = performFirstRunSetup();
        displayFirstRunSetup($setup_results);
        exit;
    }
    
    // Normal operation - all files should exist now
    include_once 'functions.php';
    include_once 'header.php';
    include_once 'main.php';
    include_once 'footer.php';
    
} catch (Exception $e) {
    displayErrorPage($e);
}

// ========= First-Run Setup Display Function =========
function displayFirstRunSetup($setup_results) {
    // Filter out php_config from basic success check
    $basic_setup = array_filter($setup_results, function($key) {
        return $key !== 'php_config';
    }, ARRAY_FILTER_USE_KEY);
    
    $all_success = array_reduce($basic_setup, function($carry, $item) {
        return $carry && (is_array($item) ? $item['status'] ?? $item : $item);
    }, true);
    
    $php_config = $setup_results['php_config'] ?? ['status' => false, 'details' => []];
    $php_instructions = generatePhpIniInstructions();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PHP Media Server - First Run Setup</title>
        
        <link rel="stylesheet" href="styles/base.css">
        <link rel="stylesheet" href="styles/setup.css">
        <link rel="stylesheet" href="styles/components.css">
        
        <link rel="icon" href="favicon.ico">
    </head>
    <body class="setup-page">
        <div class="setup-container">
            <div class="setup-header">
                <h1>🎬 Welcome to PHP Media Server</h1>
                <p>First-time setup and configuration check</p>
            </div>
            
            <!-- File Setup Results -->
            <div class="setup-results">
                <h3>📁 File Setup Results:</h3>
                
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

            <!-- PHP Configuration Check -->
            <div class="setup-results">
                <h3>⚙️ PHP Configuration Check:</h3>
                
                <?php if ($php_config['status']): ?>
                    <div class="setup-item">
                        <span class="setup-icon success">✅</span>
                        <span>All PHP settings are correctly configured</span>
                    </div>
                <?php else: ?>
                    <div class="setup-item">
                        <span class="setup-icon error">⚠️</span>
                        <span><?php echo $php_config['issues_count']; ?> PHP configuration issue(s) found</span>
                    </div>
                    
                    <?php foreach ($php_config['details'] as $setting => $details): ?>
                    <div class="setup-item">
                        <span class="setup-icon <?php echo $details['status'] ? 'success' : 'error'; ?>">
                            <?php echo $details['status'] ? '✅' : '❌'; ?>
                        </span>
                        <div>
                            <strong><?php echo $setting; ?>:</strong> <?php echo $details['current']; ?>
                            <?php if (!$details['status']): ?>
                                <br><small style="color: #ffab00;">Recommended: <?php echo $details['required']; ?></small>
                                <br><small style="opacity: 0.8;"><?php echo $details['message']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($all_success): ?>
                <div class="setup-info">
                    <h3><?php echo $php_config['status'] ? '🎉 Perfect Setup!' : '✅ Basic Setup Complete!'; ?></h3>
                    
                    <?php if (!$php_config['status']): ?>
                    <div style="background: rgba(255,171,0,0.2); padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffab00;">
                        <p><strong>⚠️ PHP Configuration Notice:</strong></p>
                        <p>Your media server will work, but some PHP settings should be optimized for better performance with large media files.</p>
                    </div>
                    <?php endif; ?>
                    
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
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="?continue=1" class="continue-btn">Start Using Media Server</a>
                    <?php if (!$php_config['status']): ?>
                    <a href="#php-config" class="continue-btn" style="background: #ff9800; margin-left: 10px;" onclick="togglePhpConfig(); return false;">View PHP Config Help</a>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div class="setup-info">
                    <h3>❌ Setup Issues</h3>
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
                </div>
                
                <div style="text-align: center;">
                    <a href="?" class="continue-btn retry-btn">Retry Setup</a>
                </div>
            <?php endif; ?>

            <!-- PHP Configuration Help (Hidden by default) -->
            <?php if (!$php_config['status']): ?>
            <div id="php-config" class="setup-info" style="display: none; margin-top: 30px;">
                <h3>🔧 PHP Configuration Instructions</h3>
                
                <p><strong>Your php.ini file location:</strong></p>
                <code style="display: block; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px; margin: 10px 0;"><?php echo $php_instructions['php_ini_location']; ?></code>
                
                <p><strong>Add or update these settings in php.ini:</strong></p>
                <pre style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; color: #fff; margin: 15px 0;"><?php 
                    foreach ($php_instructions['settings'] as $setting) {
                        echo $setting . "\n";
                    }
                ?></pre>
                
                <p><strong>After editing php.ini, restart your web server:</strong></p>
                <ul>
                    <?php foreach ($php_instructions['restart_commands'] as $command): ?>
                    <li><code><?php echo htmlspecialchars($command); ?></code></li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="background: rgba(76, 175, 80, 0.2); padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #4CAF50;">
                    <p><strong>💡 Pro Tip:</strong> After restarting your web server, refresh this page to see if the PHP configuration issues are resolved.</p>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="?" class="continue-btn">Check Configuration Again</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- System Information -->
            <div class="setup-info">
                <h3>📋 System Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                    <div>
                        <strong>Server:</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?><br>
                        <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                        <strong>Web Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                    </div>
                    <div>
                        <strong>Current Directory:</strong> <?php echo getcwd(); ?><br>
                        <strong>Directory Writable:</strong> <?php echo is_writable('.') ? 'Yes' : 'No'; ?><br>
                        <strong>Setup Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                </div>
                
                <p style="margin-top: 15px;"><strong>Current PHP Upload Settings:</strong></p>
                <ul style="font-family: monospace; font-size: 0.9em;">
                    <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
                    <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
                    <li>max_execution_time: <?php echo ini_get('max_execution_time'); ?></li>
                    <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
                    <li>file_uploads: <?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?></li>
                </ul>
            </div>
        </div>
        
        <!-- JavaScript for enhanced interactions -->
        <script>
            function togglePhpConfig() {
                const phpConfig = document.getElementById('php-config');
                if (phpConfig.style.display === 'none' || phpConfig.style.display === '') {
                    phpConfig.style.display = 'block';
                    phpConfig.scrollIntoView({ behavior: 'smooth' });
                } else {
                    phpConfig.style.display = 'none';
                }
            }
            
            // Auto-redirect countdown (if successful setup)
            <?php if ($all_success): ?>
            let countdown = 15;
            const btn = document.querySelector('.continue-btn');
            const originalText = btn.textContent;
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    btn.textContent = `${originalText} (${countdown}s)`;
                } else {
                    clearInterval(timer);
                    window.location.href = '?continue=1';
                }
            }, 1000);
            
            // Clear timer if user clicks any button
            document.querySelectorAll('.continue-btn').forEach(button => {
                button.addEventListener('click', () => {
                    clearInterval(timer);
                });
            });
            <?php endif; ?>
            
            // Console welcome message for developers
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
                    <li>Make sure <code>functions.php</code> and <code>firstrun.php</code> exist and are readable</li>
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
                    <li><?php echo file_exists('firstrun.php') ? '✓' : '❌'; ?> firstrun.php</li>
                    <li><?php echo file_exists('functions.php') ? '✓' : '❌'; ?> functions.php</li>
                    <li><?php echo file_exists('header.php') ? '✓' : '❌'; ?> header.php</li>
                    <li><?php echo file_exists('main.php') ? '✓' : '❌'; ?> main.php</li>
                    <li><?php echo file_exists('footer.php') ? '✓' : '❌'; ?> footer.php</li>
                    <li><?php echo file_exists('script.js') ? '✓' : '❌'; ?> script.js</li>
                    <li><?php echo file_exists('update.php') ? '✓' : '❌'; ?> update.php</li>
                    <li><?php echo file_exists('upload.php') ? '✓' : '❌'; ?> upload.php</li>
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
            console.log('If the issue persists, check the GitHub repository for help');
        </script>
    </body>
    </html>
    <?php
}
?>
