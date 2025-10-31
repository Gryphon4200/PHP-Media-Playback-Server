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
        
        // Reset all form elements and UI state
        const form = document.getElementById('uploadForm');
        const selectedFile = document.getElementById('selectedFile');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadBtn = document.getElementById('uploadBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const progressFill = document.getElementById('progressFill');
        const uploadArea = document.getElementById('uploadArea');
        
        if (form) form.reset();
        if (selectedFile) selectedFile.style.display = 'none';
        if (uploadProgress) uploadProgress.style.display = 'none';
        if (uploadArea) uploadArea.style.display = 'block';
        
        // Reset button states
        if (uploadBtn) {
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Upload File';
        }
        
        if (cancelBtn) {
            cancelBtn.textContent = 'Cancel';
            // Remove custom handlers
            cancelBtn.onclick = function() { closeUploadModal(); };
        }
        
        if (progressFill) progressFill.style.width = '0%';
        
        console.log('Upload modal closed and reset');
    }
}

function initializeUploadModal() {
    const fileInput = document.getElementById('fileToUpload');
    const uploadArea = document.getElementById('uploadArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');
    
    if (!fileInput || !uploadArea || !uploadBtn) {
        console.log('Upload modal elements not found');
        return;
    }
    
    console.log('Initializing upload modal...');
    
    // Clear any existing event listeners by removing and re-adding the upload area
    const newUploadArea = uploadArea.cloneNode(true);
    uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);
    
    // Update reference to the new element
    const finalUploadArea = document.getElementById('uploadArea');
    
    // File selection handler - this should only fire once per selection
    fileInput.addEventListener('change', function(e) {
        console.log('File input change event fired');
        
        const file = e.target.files[0];
        if (file) {
            console.log('File selected:', file.name);
            showSelectedFile(file);
            uploadBtn.disabled = false;
            
            // Hide the upload area and show the selected file
            finalUploadArea.style.display = 'none';
            document.getElementById('selectedFile').style.display = 'block';
        } else {
            console.log('No file selected');
            finalUploadArea.style.display = 'block';
            document.getElementById('selectedFile').style.display = 'none';
            uploadBtn.disabled = true;
        }
    });
    
    // Simple click handler - just trigger file input
    finalUploadArea.addEventListener('click', function(e) {
        console.log('Upload area clicked');
        fileInput.click();
    });
    
    // Drag and drop
    finalUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        finalUploadArea.classList.add('drag-over');
    });
    
    finalUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        finalUploadArea.classList.remove('drag-over');
    });
    
    finalUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        finalUploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            // Manually trigger the change event
            const changeEvent = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(changeEvent);
        }
    });
    
    // Form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
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
    
    if (progressDiv) progressDiv.style.display = 'block';
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
    }
    
    const formData = new FormData();
    formData.append('fileToUpload', file);
    formData.append('submit', 'Upload File');
    
    const xhr = new XMLHttpRequest();
    const startTime = Date.now();
    let lastProgressTime = startTime;
    let lastProgressLoaded = 0;
    let isUploadCancelled = false;
    let isUploadComplete = false;
    
    console.log('Upload start time:', new Date(startTime).toLocaleTimeString());
    
    // Enhanced cancel functionality
    function cancelUpload() {
        console.log('Cancelling upload...');
        isUploadCancelled = true;
        
        // Abort the XMLHttpRequest
        if (xhr && xhr.readyState !== XMLHttpRequest.DONE) {
            xhr.abort();
        }
        
        // Reset UI immediately
        resetUploadUI();
        
        // Close modal
        closeUploadModal();
        
        // Show notification
        if (typeof showNotification === 'function') {
            showNotification('Upload cancelled', 'info');
        }
    }
    
    function resetUploadUI() {
        console.log('Resetting upload UI...');
        
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload File';
        }
        
        if (progressDiv) progressDiv.style.display = 'none';
        if (progressFill) progressFill.style.width = '0%';
        if (progressPercent) progressPercent.textContent = '0%';
        if (progressText) progressText.textContent = 'Uploading...';
        if (progressSpeed) progressSpeed.textContent = '--';
        if (progressTime) progressTime.textContent = '--';
    }
    
    // Set up cancel button
    if (cancelBtn) {
        // Remove any existing click handlers
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        // Add new click handler
        newCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Cancel button clicked');
            cancelUpload();
        });
        
        // Also update the onclick attribute as backup
        newCancelBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            cancelUpload();
            return false;
        };
        
        // Change button text to show it's active
        newCancelBtn.textContent = 'Cancel Upload';
    }
    
    // Progress tracking (with cancellation checks)
    xhr.upload.addEventListener('progress', function(e) {
        // Check if upload was cancelled
        if (isUploadCancelled) {
            console.log('Progress event received but upload was cancelled');
            return;
        }
        
        const currentTime = Date.now();
        const elapsedTotal = (currentTime - startTime) / 1000;
        
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            
            // Calculate speeds (same as before)
            const timeSinceLastUpdate = (currentTime - lastProgressTime) / 1000;
            const bytesSinceLastUpdate = e.loaded - lastProgressLoaded;
            
            let currentSpeed = 0;
            if (timeSinceLastUpdate > 0) {
                currentSpeed = bytesSinceLastUpdate / timeSinceLastUpdate;
            }
            
            const averageSpeed = elapsedTotal > 0 ? e.loaded / elapsedTotal : 0;
            
            console.log(`Progress: ${percentComplete}% | Elapsed: ${elapsedTotal.toFixed(1)}s | Current Speed: ${formatFileSize(currentSpeed)}/s | Avg Speed: ${formatFileSize(averageSpeed)}/s`);
            
            // Update UI only if not cancelled
            if (!isUploadCancelled) {
                if (progressFill) progressFill.style.width = percentComplete + '%';
                if (progressPercent) progressPercent.textContent = percentComplete + '%';
                if (progressText) progressText.textContent = `Uploading... ${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`;
                if (progressSpeed) progressSpeed.textContent = `${formatFileSize(averageSpeed)}/s`;
                
                if (averageSpeed > 0 && progressTime) {
                    const remaining = (e.total - e.loaded) / averageSpeed;
                    if (remaining > 0) {
                        progressTime.textContent = `${Math.round(remaining)}s remaining`;
                    }
                }
            }
            
            lastProgressTime = currentTime;
            lastProgressLoaded = e.loaded;
        }
    });
    
    // Upload completion
    xhr.addEventListener('load', function() {
        if (isUploadCancelled) {
            console.log('Load event received but upload was cancelled');
            return;
        }
        
        isUploadComplete = true;
        
        const endTime = Date.now();
        const totalTime = (endTime - startTime) / 1000;
        
        console.log('Upload completed at:', new Date(endTime).toLocaleTimeString());
        console.log('Total upload time:', totalTime, 'seconds');
        console.log('Average upload speed:', formatFileSize(file.size / totalTime) + '/s');
        
        if (xhr.status === 200) {
            if (progressFill) progressFill.style.width = '100%';
            if (progressPercent) progressPercent.textContent = '100%';
            if (progressText) progressText.textContent = `Upload complete! (${totalTime.toFixed(1)}s)`;
            if (progressSpeed) progressSpeed.textContent = formatFileSize(file.size / totalTime) + '/s';
            
            // Reset cancel button
            const currentCancelBtn = document.getElementById('cancelBtn');
            if (currentCancelBtn) {
                currentCancelBtn.textContent = 'Close';
                currentCancelBtn.onclick = function() { closeUploadModal(); };
            }
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    if (typeof showNotification === 'function') {
                        showNotification(`File uploaded: ${response.filename} in ${totalTime.toFixed(1)}s`, 'success');
                    }
                } else {
                    throw new Error(response.message || 'Upload failed');
                }
            } catch (e) {
                if (xhr.responseText.includes('Upload Successful') || xhr.responseText.includes('✅')) {
                    if (typeof showNotification === 'function') {
                        showNotification(`File uploaded successfully in ${totalTime.toFixed(1)}s!`, 'success');
                    }
                } else {
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
    
    // Handle upload errors
    xhr.addEventListener('error', function() {
        if (isUploadCancelled) {
            console.log('Error event received but upload was already cancelled');
            return;
        }
        
        const endTime = Date.now();
        const totalTime = (endTime - startTime) / 1000;
        console.error('Upload error after', totalTime, 'seconds');
        
        resetUploadUI();
        
        if (typeof showNotification === 'function') {
            showNotification('Upload failed. Please try again.', 'error');
        }
    });
    
    // Handle upload abort (when cancelled)
    xhr.addEventListener('abort', function() {
        console.log('Upload was aborted');
        isUploadCancelled = true;
        resetUploadUI();
    });
    
    // Handle timeout
    xhr.addEventListener('timeout', function() {
        console.log('Upload timed out');
        resetUploadUI();
        
        if (typeof showNotification === 'function') {
            showNotification('Upload timed out. Please try again.', 'error');
        }
    });
    
    console.log('Sending upload request...');
    xhr.open('POST', 'upload.php');
    
    // Set a reasonable timeout (5 minutes)
    xhr.timeout = 300000;
    
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
