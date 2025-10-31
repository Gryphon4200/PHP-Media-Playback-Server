/**
 * Main Application JavaScript for Media Server
 * Handles current media display, uploads, and UI interactions
 */

// Add this at the very beginning of main.js
console.log('JavaScript loading started');
document.documentElement.className += ' js';

// Also add this simple fallback
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    document.body.classList.add('loaded');
});

// Backup - ensure page shows even if other JS fails
window.addEventListener('load', function() {
    console.log('Window loaded');
    document.body.classList.add('loaded');
});

// Emergency fallback
setTimeout(() => {
    console.log('Emergency fallback triggered');
    document.body.classList.add('loaded');
}, 1000);


// Global variables
let mediaServerData = {};
let lastFileCount = 0;
let lastModified = 0;
let isUpdating = false;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get data from PHP
    if (window.mediaServerData) {
        mediaServerData = window.mediaServerData;
    }
    
    loadCurrentMedia();
    initializeApp();
});

// ========= Current Media Functions =========

function loadCurrentMedia() {
    fetch('image.txt')
        .then(response => response.text())
        .then(data => {
            const parts = data.trim().split('|');
            const filename = parts[0] || 'None';
            const timestamp = parts[1] || '';
            
            const fileElement = document.getElementById('currentMediaFile');
            const timeElement = document.getElementById('currentMediaTime');
            
            if (fileElement) fileElement.textContent = filename;
            if (timeElement) {
                if (timestamp) {
                    const date = new Date(parseInt(timestamp) * 1000);
                    timeElement.textContent = 'Updated: ' + date.toLocaleString();
                } else {
                    timeElement.textContent = '--';
                }
            }
        })
        .catch(error => {
            const fileElement = document.getElementById('currentMediaFile');
            const timeElement = document.getElementById('currentMediaTime');
            
            if (fileElement) fileElement.textContent = 'Error loading';
            if (timeElement) timeElement.textContent = 'Check connection';
            console.error('Failed to load current media:', error);
        });
}

function refreshCurrentMedia() {
    loadCurrentMedia();
    if (typeof showNotification === 'function') {
        showNotification('Current media refreshed', 'info');
    }
}

function displayFile(filename) {
    console.log('Displaying file:', filename);
    
    const timestamp = Math.floor(Date.now() / 1000);
    const url = `update.php?file=${encodeURIComponent(filename)}&timestamp=${timestamp}`;
    
    fetch(url)
        .then(response => response.json())  // Now expecting JSON
        .then(data => {
            if (data.success) {
                loadCurrentMedia();
                if (typeof showNotification === 'function') {
                    showNotification(`Now displaying: ${data.filename}`, 'success');
                }
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(`Error: ${data.message}`, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Display error:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error updating display', 'error');
            }
        });
}

function activatePreset(presetKey) {
    fetch(`update.php?preset=${encodeURIComponent(presetKey)}`)
        .then(response => {
            window.location.reload();
        })
        .catch(error => {
            console.error('Preset activation error:', error);
            window.location.reload();
        });
}

// ========= Upload Modal Functions =========

function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        const form = document.getElementById('uploadForm');
        const selectedFile = document.getElementById('selectedFile');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadBtn = document.getElementById('uploadBtn');
        const progressFill = document.getElementById('progressFill');
        
        if (form) form.reset();
        if (selectedFile) selectedFile.style.display = 'none';
        if (uploadProgress) uploadProgress.style.display = 'none';
        if (uploadBtn) uploadBtn.disabled = true;
        if (progressFill) progressFill.style.width = '0%';
    }
}

function initializeUploadModal() {
    const fileInput = document.getElementById('fileToUpload');
    const uploadArea = document.getElementById('uploadArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');
    
    if (!fileInput || !uploadArea || !uploadBtn) {
        console.log('Upload modal elements not found - probably not on main page');
        return;
    }
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        e.stopPropagation();
        const file = e.target.files[0];
        if (file) {
            showSelectedFile(file);
            uploadBtn.disabled = false;
        }
    });
    
    // Upload area click - prevent conflicts
    uploadArea.addEventListener('click', function(e) {
        if (e.target === uploadArea || e.target.parentNode === uploadArea) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        }
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showSelectedFile(files[0]);
            uploadBtn.disabled = false;
        }
    });
    
    // Form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const file = fileInput.files[0];
            if (file && !uploadBtn.disabled) {
                uploadWithProgress(file);
            }
        });
    }
}

function showSelectedFile(file) {
    const fileNameElement = document.getElementById('selectedFileName');
    const fileSizeElement = document.getElementById('selectedFileSize');
    const selectedFileDiv = document.getElementById('selectedFile');
    
    if (fileNameElement) fileNameElement.textContent = file.name;
    if (fileSizeElement) fileSizeElement.textContent = formatFileSize(file.size);
    if (selectedFileDiv) selectedFileDiv.style.display = 'block';
}

function uploadWithProgress(file) {
    console.log('Starting upload for:', file.name, file.size, 'bytes');
    
    const progressDiv = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');
    const progressSpeed = document.getElementById('progressSpeed');
    const progressTime = document.getElementById('progressTime');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Show progress immediately
    if (progressDiv) progressDiv.style.display = 'block';
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
    }
    
    // Reset progress display
    if (progressFill) progressFill.style.width = '0%';
    if (progressPercent) progressPercent.textContent = '0%';
    if (progressText) progressText.textContent = 'Starting upload...';
    
    const formData = new FormData();
    formData.append('fileToUpload', file);
    formData.append('submit', 'Upload File');
    
    const xhr = new XMLHttpRequest();
    const startTime = Date.now();
    let lastLoaded = 0;
    let lastTime = startTime;
    
    // Enhanced progress tracking
    xhr.upload.addEventListener('progress', function(e) {
        console.log('Upload progress:', e.loaded, '/', e.total);
        
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            const currentTime = Date.now();
            const elapsed = (currentTime - startTime) / 1000;
            
            // Update progress bar
            if (progressFill) progressFill.style.width = percentComplete + '%';
            if (progressPercent) progressPercent.textContent = percentComplete + '%';
            if (progressText) progressText.textContent = `Uploading... ${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`;
            
            // Calculate speed
            if (elapsed > 0.5) {
                const timeDiff = (currentTime - lastTime) / 1000;
                if (timeDiff > 0) {
                    const bytesDiff = e.loaded - lastLoaded;
                    const speed = bytesDiff / timeDiff;
                    
                    if (progressSpeed) {
                        progressSpeed.textContent = formatFileSize(speed) + '/s';
                    }
                    
                    if (speed > 0 && progressTime) {
                        const remaining = (e.total - e.loaded) / speed;
                        if (remaining > 0 && remaining < 3600) {
                            progressTime.textContent = `${Math.round(remaining)}s remaining`;
                        }
                    }
                    
                    lastLoaded = e.loaded;
                    lastTime = currentTime;
                }
            }
        }
    });
    
    xhr.addEventListener('load', function() {
        console.log('Upload completed, status:', xhr.status);
        
        if (xhr.status === 200) {
            if (progressFill) progressFill.style.width = '100%';
            if (progressPercent) progressPercent.textContent = '100%';
            if (progressText) progressText.textContent = 'Processing...';
            
            // Check response
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    if (progressText) progressText.textContent = 'Upload complete!';
                    if (typeof showNotification === 'function') {
                        showNotification(`File uploaded: ${response.filename}`, 'success');
                    }
                } else {
                    throw new Error(response.message || 'Upload failed');
                }
            } catch (e) {
                // Try to parse HTML response
                if (xhr.responseText.includes('Upload Successful') || xhr.responseText.includes('✅')) {
                    if (progressText) progressText.textContent = 'Upload complete!';
                    if (typeof showNotification === 'function') {
                        showNotification('File uploaded successfully!', 'success');
                    }
                } else {
                    console.error('Upload response:', xhr.responseText.substring(0, 200));
                    throw new Error('Unexpected server response');
                }
            }
            
            setTimeout(() => {
                closeUploadModal();
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(`Server error: ${xhr.status}`);
        }
    });
    
    xhr.addEventListener('error', function() {
        console.error('Upload error');
        if (typeof showNotification === 'function') {
            showNotification('Upload failed. Check your connection.', 'error');
        }
        resetUploadModal();
    });
    
    xhr.addEventListener('abort', function() {
        console.log('Upload cancelled');
        if (typeof showNotification === 'function') {
            showNotification('Upload cancelled', 'info');
        }
    });
    
    // Cancel functionality
    if (cancelBtn) {
        const originalClick = cancelBtn.onclick;
        cancelBtn.onclick = function() {
            console.log('Upload cancelled by user');
            xhr.abort();
            closeUploadModal();
            cancelBtn.onclick = originalClick;
        };
    }
    
    console.log('Sending upload request...');
    xhr.open('POST', 'upload.php');
    xhr.send(formData);
}

function resetUploadModal() {
    setTimeout(() => {
        const uploadBtn = document.getElementById('uploadBtn');
        const progressFill = document.getElementById('progressFill');
        
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload File';
        }
        if (progressFill) {
            progressFill.style.width = '0%';
        }
    }, 3000);
}

// ========= Utility Functions =========

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
}

// ========= Initialize App =========

function initializeApp() {
    // Set initial values from PHP data
    if (mediaServerData.initialFileCount !== undefined) {
        lastFileCount = mediaServerData.initialFileCount;
    }
    
    // Initialize upload modal
    initializeUploadModal();
    
    // Start file monitoring (from script.js if available)
    if (typeof startFileMonitoring === 'function') {
        startFileMonitoring();
    }
    
    // Initialize other features (from script.js if available)
    if (typeof initializeUploadHandling === 'function') {
        initializeUploadHandling();
    }
    
    if (typeof initializeDisplaySettings === 'function') {
        initializeDisplaySettings();
    }
    
    if (typeof handleDragAndDrop === 'function') {
        handleDragAndDrop();
    }
    
    // Remove loading state
    document.body.classList.add('loaded');
    
    console.log('Main application initialized');
}

// ========= Event Listeners =========

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target === modal) {
        closeUploadModal();
    }
});

// Ensure body shows even if JS fails
window.addEventListener('load', function() {
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 100);
});
