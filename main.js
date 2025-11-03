/**
 * Main Application JavaScript for Media Server
 * Handles current media display, uploads, and UI interactions
 * 
 * Note: Initialization is handled by script.js to avoid conflicts
 */

// Basic page loading indicators
console.log('main.js loaded');
document.documentElement.className += ' js-enabled';

// Emergency CSS fallback only
setTimeout(() => {
    document.body.classList.add('loaded');
}, 500);

// Global variables specific to main.js functionality
let mediaServerData = {};
let isUploadInProgress = false;

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
                if (timestamp && timestamp !== '') {
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
            
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.error('Failed to load current media:', error);
            }
        });
}

function refreshCurrentMedia() {
    loadCurrentMedia();
    if (typeof showNotification === 'function') {
        showNotification('Current media refreshed', 'info');
    }
}

function displayFile(filename) {
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Displaying file:', filename);
    }
    
    const timestamp = Math.floor(Date.now() / 1000);
    const url = `update.php?file=${encodeURIComponent(filename)}&timestamp=${timestamp}`;
    
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Request URL:', url);
    }
    
    fetch(url)
        .then(response => {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
            }
            return response.text(); // Get as text first to see what we're getting
        })
        .then(rawData => {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('Raw response data:', rawData);
            }
            
            // Try to parse as JSON
            try {
                const data = JSON.parse(rawData);
                
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.log('Parsed JSON response:', data);
                }
                
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
            } catch (e) {
                // If JSON parsing fails, check if it's HTML (old format)
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.error('JSON parsing failed:', e);
                    console.log('Trying to parse as HTML response...');
                }
                
                if (rawData.includes('Success:') || rawData.includes('✅')) {
                    loadCurrentMedia();
                    if (typeof showNotification === 'function') {
                        showNotification(`Now displaying: ${filename}`, 'success');
                    }
                } else {
                    if (window.mediaServerData && window.mediaServerData.debug) {
                        console.error('Unexpected response format:', rawData.substring(0, 200));
                    }
                    if (typeof showNotification === 'function') {
                        showNotification('Error updating display - unexpected response', 'error');
                    }
                }
            }
        })
        .catch(error => {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.error('Display error:', error);
            }
            if (typeof showNotification === 'function') {
                showNotification('Error updating display - network error', 'error');
            }
        });
}

function activatePreset(presetKey) {
    fetch(`update.php?preset=${encodeURIComponent(presetKey)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCurrentMedia();
                if (typeof showNotification === 'function') {
                    showNotification(`Preset activated: ${presetKey}`, 'success');
                }
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(`Error: ${data.message}`, 'error');
                }
            }
        })
        .catch(error => {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.error('Preset activation error:', error);
            }
            if (typeof showNotification === 'function') {
                showNotification('Error activating preset', 'error');
            }
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
    // Prevent closing during upload
    if (isUploadInProgress) {
        if (typeof showNotification === 'function') {
            showNotification('Cannot close during upload. Please wait or cancel first.', 'warning');
        }
        return;
    }
    
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
            cancelBtn.onclick = function() { closeUploadModal(); };
        }
        
        if (progressFill) progressFill.style.width = '0%';
        
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload modal closed and reset');
        }
    }
}

function initializeUploadModal() {
    const fileInput = document.getElementById('fileToUpload');
    const uploadArea = document.getElementById('uploadArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');
    
    if (!fileInput || !uploadArea || !uploadBtn) {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload modal elements not found - probably not on main page');
        }
        return;
    }
    
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Initializing upload modal...');
    }
    
    // Clear any existing event listeners by replacing the upload area
    const newUploadArea = uploadArea.cloneNode(true);
    uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);
    
    // Update reference to the new element
    const finalUploadArea = document.getElementById('uploadArea');
    
    // File selection handler
    fileInput.addEventListener('change', function(e) {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('File input change event fired');
        }
        
        const file = e.target.files[0];
        if (file) {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('File selected:', file.name);
            }
            showSelectedFile(file);
            uploadBtn.disabled = false;
            
            // Hide the upload area and show the selected file
            finalUploadArea.style.display = 'none';
            document.getElementById('selectedFile').style.display = 'block';
        } else {
            finalUploadArea.style.display = 'block';
            document.getElementById('selectedFile').style.display = 'none';
            uploadBtn.disabled = true;
        }
    });
    
    // Simple click handler
    finalUploadArea.addEventListener('click', function(e) {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload area clicked');
        }
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
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Starting upload for:', file.name, file.size, 'bytes');
    }
    
    // Set upload in progress flag
    isUploadInProgress = true;
    
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
    
    // Enhanced cancel functionality
    function cancelUpload() {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Cancelling upload...');
        }
        isUploadCancelled = true;
        isUploadInProgress = false; // Clear the flag
        
        if (xhr && xhr.readyState !== XMLHttpRequest.DONE) {
            xhr.abort();
        }
        
        resetUploadUI();
        closeUploadModal();
        
        if (typeof showNotification === 'function') {
            showNotification('Upload cancelled', 'info');
        }
    }
    
    function resetUploadUI() {
        isUploadInProgress = false; // Clear the flag
        
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
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        newCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            cancelUpload();
        });
        
        newCancelBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            cancelUpload();
            return false;
        };
        
        newCancelBtn.textContent = 'Cancel Upload';
    }
    
    // Add ESC key handler to cancel upload
    function handleEscKey(e) {
        if (e.key === 'Escape' && isUploadInProgress) {
            e.preventDefault();
            if (confirm('Cancel the current upload?')) {
                cancelUpload();
            }
        }
    }
    document.addEventListener('keydown', handleEscKey);
    
    // Progress tracking
    xhr.upload.addEventListener('progress', function(e) {
        if (isUploadCancelled) return;
        
        const currentTime = Date.now();
        const elapsedTotal = (currentTime - startTime) / 1000;
        
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            
            const timeSinceLastUpdate = (currentTime - lastProgressTime) / 1000;
            const bytesSinceLastUpdate = e.loaded - lastProgressLoaded;
            
            let currentSpeed = 0;
            if (timeSinceLastUpdate > 0) {
                currentSpeed = bytesSinceLastUpdate / timeSinceLastUpdate;
            }
            
            const averageSpeed = elapsedTotal > 0 ? e.loaded / elapsedTotal : 0;
            
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log(`Progress: ${percentComplete}% | Elapsed: ${elapsedTotal.toFixed(1)}s | Current Speed: ${formatFileSize(currentSpeed)}/s | Avg Speed: ${formatFileSize(averageSpeed)}/s`);
            }
            
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
        if (isUploadCancelled) return;
        
        isUploadComplete = true;
        isUploadInProgress = false; // Clear the flag
        
        // Remove ESC key handler
        document.removeEventListener('keydown', handleEscKey);
        
        const endTime = Date.now();
        const totalTime = (endTime - startTime) / 1000;
        
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload completed at:', new Date(endTime).toLocaleTimeString());
            console.log('Total upload time:', totalTime, 'seconds');
            console.log('Average upload speed:', formatFileSize(file.size / totalTime) + '/s');
        }
        
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
                    if (window.mediaServerData && window.mediaServerData.debug) {
                        console.error('Upload response:', xhr.responseText.substring(0, 200));
                    }
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
        if (isUploadCancelled) return;
        
        isUploadInProgress = false; // Clear the flag
        document.removeEventListener('keydown', handleEscKey);
        
        const endTime = Date.now();
        const totalTime = (endTime - startTime) / 1000;
        
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.error('Upload error after', totalTime, 'seconds');
        }
        
        resetUploadUI();
        
        if (typeof showNotification === 'function') {
            showNotification('Upload failed. Please try again.', 'error');
        }
    });
    
    // Handle upload abort (when cancelled)
    xhr.addEventListener('abort', function() {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload was aborted');
        }
        isUploadCancelled = true;
        isUploadInProgress = false; // Clear the flag
        document.removeEventListener('keydown', handleEscKey);
        resetUploadUI();
    });
    
    // Handle timeout
    xhr.addEventListener('timeout', function() {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Upload timed out');
        }
        isUploadInProgress = false; // Clear the flag
        document.removeEventListener('keydown', handleEscKey);
        resetUploadUI();
        
        if (typeof showNotification === 'function') {
            showNotification('Upload timed out. Please try again.', 'error');
        }
    });
    
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Sending upload request...');
    }
    xhr.open('POST', 'upload.php');
    
    // Set a reasonable timeout (5 minutes)
    xhr.timeout = 300000;
    
    xhr.send(formData);
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

// ========= Event Listeners =========

window.addEventListener('click', function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target === modal) {
        // Only close if no upload is in progress
        if (!isUploadInProgress) {
            closeUploadModal();
        } else {
            // Show a warning that upload is in progress
            if (typeof showNotification === 'function') {
                showNotification('Upload in progress. Please wait or cancel to close.', 'warning');
            }
        }
    }
});

// Final loading state fallback
window.addEventListener('load', function() {
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 100);
});
