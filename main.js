/**
 * Main Application JavaScript for Media Server
 * Handles current media display, uploads, and UI interactions
 */

// Global variables
let mediaServerData = {};

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
            
            document.getElementById('currentMediaFile').textContent = filename;
            if (timestamp) {
                const date = new Date(parseInt(timestamp) * 1000);
                document.getElementById('currentMediaTime').textContent = 
                    'Updated: ' + date.toLocaleString();
            } else {
                document.getElementById('currentMediaTime').textContent = '--';
            }
        })
        .catch(error => {
            document.getElementById('currentMediaFile').textContent = 'Error loading';
            document.getElementById('currentMediaTime').textContent = 'Check connection';
            console.error('Failed to load current media:', error);
        });
}

function refreshCurrentMedia() {
    loadCurrentMedia();
    showNotification('Current media refreshed', 'info');
}

function displayFile(filename) {
    console.log('Displaying file:', filename);
    
    const timestamp = Math.floor(Date.now() / 1000);
    const url = `update.php?file=${encodeURIComponent(filename)}&timestamp=${timestamp}`;
    
    console.log('Request URL:', url);
    
    fetch(url, {
        method: 'GET',  // Explicitly specify GET
        headers: {
            'Content-Type': 'text/plain'
        }
    })
        .then(response => response.text())
        .then(data => {
            console.log('Response:', data);
            if (data.includes('Success:')) {
                window.location.reload();
            } else {
                console.error('Unexpected response:', data);
            }
        })
        .catch(error => {
            console.error('Display error:', error);
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

// ========= Upload Functions with Progress =========

function validateUpload() {
    const fileInput = document.getElementById('fileToUpload');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showNotification('Please select a file to upload.', 'error');
        return false;
    }
    
    const file = fileInput.files[0];
    
    // Check file size (500MB limit)
    const maxSize = 500 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 500MB.', 'error');
        return false;
    }
    
    // Start upload with progress
    uploadWithProgress(file);
    return false; // Prevent normal form submission
}

function uploadWithProgress(file) {
    const formData = new FormData();
    formData.append('fileToUpload', file);
    formData.append('submit', 'Upload File');
    
    const progressDiv = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');
    const uploadButton = document.getElementById('uploadButton');
    
    // Show progress bar
    progressDiv.style.display = 'block';
    uploadButton.disabled = true;
    uploadButton.value = 'Uploading...';
    
    // Create XMLHttpRequest for progress tracking
    const xhr = new XMLHttpRequest();
    
    // Track upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressFill.style.width = percentComplete + '%';
            progressPercent.textContent = percentComplete + '%';
            progressText.textContent = `Uploading... ${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`;
        }
    });
    
    // Handle completion
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            progressText.textContent = 'Upload complete!';
            showNotification('File uploaded successfully!', 'success');
            
            // Reset form after delay
            setTimeout(() => {
                progressDiv.style.display = 'none';
                uploadButton.disabled = false;
                uploadButton.value = 'Upload File';
                document.getElementById('fileToUpload').value = '';
                progressFill.style.width = '0%';
                
                // Refresh file list
                window.location.reload();
            }, 2000);
        } else {
            progressText.textContent = 'Upload failed';
            showNotification('Upload failed. Please try again.', 'error');
            resetUploadForm();
        }
    });
    
    // Handle errors
    xhr.addEventListener('error', function() {
        progressText.textContent = 'Upload error';
        showNotification('Upload error. Please check your connection.', 'error');
        resetUploadForm();
    });
    
    // Send the request
    xhr.open('POST', 'upload.php');
    xhr.send(formData);
}

function resetUploadForm() {
    const progressDiv = document.getElementById('uploadProgress');
    const uploadButton = document.getElementById('uploadButton');
    const progressFill = document.getElementById('progressFill');
    
    setTimeout(() => {
        progressDiv.style.display = 'none';
        uploadButton.disabled = false;
        uploadButton.value = 'Upload File';
        progressFill.style.width = '0%';
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

function checkForChangesPartial() {
    // Prevent multiple simultaneous checks
    if (isUpdating) return;
    
    isUpdating = true;
    
    fetch('check_changes.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Check if files have changed
                const filesChanged = data.count !== lastFileCount || data.last_modified > lastModified;
                
                if (filesChanged) {
                    updateStatus('Files changed - updating list...', 'updating');
                    updateFileListPartial(data.files);
                    
                    // Update tracking variables
                    lastFileCount = data.count;
                    lastModified = data.last_modified;
                    
                    // Update file count display
                    updateFileCountDisplay(data.count);
                    
                    // Also refresh current media display when files change
                    loadCurrentMedia();
                    
                    updateStatus(`Monitoring ${data.count} files...`, 'monitoring');
                } else {
                    updateStatus(`Monitoring ${data.count} files...`, 'monitoring');
                }
            }
            isUpdating = false;
        })
        .catch(error => {
            updateStatus('Monitor error', 'error');
            console.error('Error checking for changes:', error);
            isUpdating = false;
        });
}

// ========= Initialize App (references script.js functions) =========

function initializeApp() {
    // Set initial values from PHP data
    if (mediaServerData.initialFileCount !== undefined) {
        lastFileCount = mediaServerData.initialFileCount;
    }
    
    // Start file monitoring (from script.js)
    if (typeof startFileMonitoring === 'function') {
        startFileMonitoring();
    }
    
    // Initialize other features (from script.js)
    if (typeof initializeUploadHandling === 'function') {
        initializeUploadHandling();
    }
    
    if (typeof initializeDisplaySettings === 'function') {
        initializeDisplaySettings();
    }
    
    if (typeof handleDragAndDrop === 'function') {
        handleDragAndDrop();
    }
    
    console.log('Main application initialized');
}

