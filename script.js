/**
 * Enhanced Media Server JavaScript with Partial Refresh
 * Handles file monitoring, presets, and application initialization
 */

// Global variables for file monitoring and offline detection
let lastModified = 0;
let lastFileCount = 0;
let checkInterval = null;
let isUpdating = false;
let consecutiveFailures = 0;
let maxFailures = 3;
let isServerOffline = false;
let offlineCheckInterval = 30000; // 30 seconds

// Initialize when DOM is loaded - this is the ONLY initialization point
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Set initial values from PHP data
    if (window.mediaServerData) {
        lastFileCount = window.mediaServerData.initialFileCount;
    }
    
    // Initialize main.js functions if they exist
    if (typeof loadCurrentMedia === 'function') {
        loadCurrentMedia();
    }
    
    if (typeof initializeUploadModal === 'function') {
        initializeUploadModal();
    }
    
    // Start file monitoring with partial refresh
    startFileMonitoring();
    
    // Initialize other features
    initializeUploadHandling();
    initializeDisplaySettings();
    handleDragAndDrop();
    
    // Final setup
    document.body.classList.add('loaded');
    
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Media Server initialized with debug logging enabled');
    }
    
    if (typeof showNotification === 'function') {
        showNotification('Media Server ready', 'info');
    }
}

// ========= Enhanced File Monitoring with Partial Refresh =========

function startFileMonitoring() {
    // Clear any existing intervals
    if (checkInterval) {
        clearInterval(checkInterval);
    }
    
    // Reset offline state
    isServerOffline = false;
    consecutiveFailures = 0;
    
    // Initial check to set baseline values
    checkForChangesPartial();
    
    // Start periodic checking every 3 seconds
    checkInterval = setInterval(checkForChangesPartial, 3000);
}

function checkForChangesPartial() {
    // Skip if we know the server is offline
    if (isServerOffline) return;
    
    // Prevent multiple simultaneous checks
    if (isUpdating) return;
    
    isUpdating = true;
    
    fetch('check_changes.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            // Reset failure count on success
            consecutiveFailures = 0;
            isServerOffline = false;
            
            if (data.success) {
                const filesChanged = data.count !== lastFileCount || data.last_modified > lastModified;
                
                if (filesChanged) {
                    updateStatus('Files changed - updating list...', 'updating');
                    updateFileListPartial(data.files);
                    lastFileCount = data.count;
                    lastModified = data.last_modified;
                    updateFileCountDisplay(data.count);
                    updateStatus(`Monitoring ${data.count} files...`, 'monitoring');
                } else {
                    updateStatus(`Monitoring ${data.count} files...`, 'monitoring');
                }
            } else {
                throw new Error(data.message || 'Unknown server error');
            }
            isUpdating = false;
        })
        .catch(error => {
            isUpdating = false;
            consecutiveFailures++;
            
            // Check if it's a connection error
            const isConnectionError = error.name === 'TypeError' || 
                                    error.message.includes('Failed to fetch') ||
                                    error.message.includes('ERR_CONNECTION_REFUSED') ||
                                    error.message.includes('404') ||
                                    error.message.includes('HTTP 0:');
            
            // Show specific error message based on the problem
            if (error.message.includes('404')) {
                updateStatus('check_changes.php not found', 'error');
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.error('check_changes.php file missing. Create this file for file monitoring.');
                }
            } else if (isConnectionError && consecutiveFailures >= maxFailures) {
                isServerOffline = true;
                updateStatus('Server offline - monitoring paused', 'offline');
                
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.log('Server appears offline. Pausing file monitoring.');
                }
                
                if (checkInterval) {
                    clearInterval(checkInterval);
                    checkInterval = null;
                }
                startOfflineMonitoring();
                
                if (typeof showNotification === 'function') {
                    showNotification('Server connection lost. Monitoring paused.', 'warning');
                }
            } else if (!isConnectionError) {
                updateStatus('Monitor error: ' + error.message, 'error');
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.error('Error checking for changes:', error);
                }
            }
            // For connection errors before hitting maxFailures, we silently ignore
        });
}

function startOfflineMonitoring() {
    // Check if server comes back online every 30 seconds
    const offlineInterval = setInterval(() => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Checking if server is back online...');
        }
        
        fetch('check_changes.php', {
            method: 'GET',
            cache: 'no-cache'
        })
        .then(response => {
            if (response.ok) {
                // Server is back online!
                isServerOffline = false;
                consecutiveFailures = 0;
                
                clearInterval(offlineInterval);
                
                updateStatus('Server reconnected - resuming monitoring', 'monitoring');
                
                if (window.mediaServerData && window.mediaServerData.debug) {
                    console.log('Server reconnected. Resuming file monitoring.');
                }
                
                if (typeof showNotification === 'function') {
                    showNotification('Server connection restored!', 'success');
                }
                
                // Resume regular monitoring
                startFileMonitoring();
            } else {
                throw new Error('Server not ready');
            }
        })
        .catch(() => {
            // Still offline, continue checking (silent unless debug)
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('Server still offline...');
            }
        });
    }, offlineCheckInterval);
}

function updateFileListPartial(newFiles) {
    const tbody = document.querySelector('.menu tbody');
    if (!tbody) return;
    
    // Get the upload row to preserve it
    const uploadRow = tbody.querySelector('.upload-row');
    const emptyState = tbody.querySelector('.empty-state');
    
    // Clear current file rows (but keep upload row and empty state)
    const fileRows = tbody.querySelectorAll('.row:not(.upload-row):not(.empty-state):not(.header-row)');
    fileRows.forEach(row => row.remove());
    
    // Remove empty state if we have files now
    if (Object.keys(newFiles).length > 0 && emptyState) {
        emptyState.remove();
    }
    
    if (Object.keys(newFiles).length === 0) {
        // Add empty state if no files
        if (!emptyState) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'row empty-state';
            emptyRow.innerHTML = `
                <td colspan="2" class="center">
                    <p>No media files found.</p>
                    <p>Upload files using the button below.</p>
                </td>
            `;
            tbody.insertBefore(emptyRow, uploadRow);
        }
    } else {
        // Add new file rows
        Object.entries(newFiles).forEach(([filename, fileData]) => {
            const row = createFileRow(filename, fileData);
            tbody.insertBefore(row, uploadRow);
        });
    }
    
    // Add subtle animation to indicate update
    tbody.style.opacity = '0.7';
    setTimeout(() => {
        tbody.style.opacity = '1';
    }, 200);
}

function createFileRow(filename, fileData) {
    const row = document.createElement('tr');
    row.className = 'row';
    row.id = 'file_' + filename.replace(/[^a-zA-Z0-9]/g, '');
    row.setAttribute('data-filename', filename);
    
    // Create file cell
    const fileCell = document.createElement('td');
    fileCell.className = 'file';
    fileCell.onclick = function() { 
        if (typeof displayFile === 'function') {
            displayFile(filename); 
        }
    };
    fileCell.innerHTML = `
        <span class="file-icon">${fileData.icon}</span>
        <span class="filename">${escapeHtml(filename)}</span>
        <span class="file-info">${fileData.info}</span>
    `;
    
    // Create actions cell - exactly match the original HTML
    const actionsCell = document.createElement('td');
    actionsCell.className = 'file-actions';
    actionsCell.setAttribute('width', '100'); // This matches your table header
    
    const deleteBtn = document.createElement('button');
    deleteBtn.onclick = function() { DeleteFile(filename); };
    deleteBtn.className = 'btn-small btn-delete';
    deleteBtn.title = 'Delete ' + filename;
    deleteBtn.textContent = '✕';
    
    actionsCell.appendChild(deleteBtn);
    
    row.appendChild(fileCell);
    row.appendChild(actionsCell);
    
    return row;
}

function updateFileCountDisplay(count) {
    const fileCountElement = document.querySelector('.file-count');
    if (fileCountElement) {
        fileCountElement.textContent = `Files: ${count}`;
    }
    
    // Update presets dropdown text
    const dropBtn = document.querySelector('.dropbtn');
    if (dropBtn) {
        const presetsCount = document.querySelectorAll('select[name^="Preset"]').length;
        dropBtn.textContent = `Presets (${presetsCount})`;
    }
}

function updateStatus(message, type = 'monitoring') {
    const status = document.getElementById('fileMonitorStatus');
    if (status) {
        status.textContent = message;
        status.className = 'file-monitor-status status-' + type;
    }
}

// ========= Utility Functions =========

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function WriteFile(params) {
    const xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log(this.responseText);
            }
        }
    };

    xhttp.open("POST", "update.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send(params);
}

// ========= Original Functions (keeping existing functionality) =========

function ToggleElement() {
    const menu = document.getElementById("PresetMenu");
    if (!menu) return;
    
    if (menu.style.display === "none" || menu.style.display === "") {
        menu.style.display = "block";
        menu.classList.add('show');
    } else {
        menu.style.display = "none";
        menu.classList.remove('show');
    }
}

function DeleteFile(filename) {
    if (!confirm(`Are you sure you want to delete '${filename}'?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    if (typeof showNotification === 'function') {
        showNotification('Deleting file...', 'updating');
    }

    const params = "update=Delete&filename=" + encodeURIComponent(filename);
    WriteFile(params);
}

function SavePresetsToConfig(Presets) {
    const params = "update=ConfigFileUpdate";

    Object.entries(Presets).forEach(([key, value]) => {
        params += "&" + encodeURIComponent(key) + "=" + encodeURIComponent(value);
    });

    WriteFile(params);
}

function Update_Presets() {
    const presets = {};
    const selects = document.querySelectorAll('select[name^="Preset"]');
    
    selects.forEach(function(select) {
        const presetKey = select.getAttribute('data-preset-key');
        if (presetKey && select.value) {
            presets[presetKey] = select.value;
        }
    });
    
    // Only log if debug is enabled
    if (window.mediaServerData && window.mediaServerData.debug) {
        console.log('Updating presets:', presets);
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('update', 'ConfigFileUpdate');
    
    Object.entries(presets).forEach(([key, value]) => {
        formData.append(key, value);
    });
    
    // Add explicit AJAX header
    fetch('update.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Response status:', response.status);
        }
        return response.text();
    })
    .then(data => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Raw response from server:', data);
        }
        
        // Try to parse as JSON
        try {
            const jsonData = JSON.parse(data);
            
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('Successfully parsed JSON:', jsonData);
            }
            
            if (jsonData.success) {
                if (typeof showNotification === 'function') {
                    showNotification('Presets updated successfully!', 'success');
                }
                document.getElementById('PresetMenu').style.display = 'none';
            } else {
                if (typeof showNotification === 'function') {
                    showNotification('Error: ' + jsonData.message, 'error');
                }
            }
        } catch (e) {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.error('JSON parse failed. Raw response was:', data.substring(0, 500));
            }
            if (typeof showNotification === 'function') {
                showNotification('Server returned unexpected response. Please try again.', 'error');
            }
        }
    })
    .catch(error => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.error('Network error:', error);
        }
        if (typeof showNotification === 'function') {
            showNotification('Network error. Please check your connection.', 'error');
        }
    });
    
    return false; // Prevent form submission
}

function validateUpload() {
    const fileInput = document.getElementById('fileToUpload');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (typeof showNotification === 'function') {
            showNotification('Please select a file to upload.', 'error');
        }
        return false;
    }
    
    const file = fileInput.files[0];
    
    // Check file size (500MB limit)
    const maxSize = 500 * 1024 * 1024;
    if (file.size > maxSize) {
        if (typeof showNotification === 'function') {
            showNotification('File is too large. Maximum size is 500MB.', 'error');
        }
        return false;
    }
    
    showUploadProgress();
    return true;
}

function initializeUploadHandling() {
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            if (!validateUpload()) {
                e.preventDefault();
                return false;
            }
        });
    }
}

function showUploadProgress() {
    const progressDiv = document.getElementById('uploadProgress');
    const uploadBtn = document.querySelector('.upload-btn');
    
    if (progressDiv) {
        progressDiv.style.display = 'block';
    }
    
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.value = 'Uploading...';
    }
}

function showNotification(message, type = 'info') {
    let notification = document.getElementById('notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    notification.textContent = message;
    notification.className = `notification notification-${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// ========= Enhanced Features =========

function initializeDisplaySettings() {
    // Auto-hide cursor after inactivity
    let inactivityTimer;
    const hideDelay = 5000;
    
    const resetInactivityTimer = () => {
        document.body.classList.remove('inactive');
        clearTimeout(inactivityTimer);
        
        inactivityTimer = setTimeout(() => {
            document.body.classList.add('inactive');
        }, hideDelay);
    };
    
    document.addEventListener('mousemove', resetInactivityTimer);
    document.addEventListener('keypress', resetInactivityTimer);
    resetInactivityTimer();
}

function handleDragAndDrop() {
    const uploadArea = document.querySelector('.upload-cell');
    if (!uploadArea) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('drag-over');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('drag-over');
        }, false);
    });
    
    uploadArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const fileInput = document.getElementById('fileToUpload');
            if (fileInput) {
                fileInput.files = files;
                if (typeof showNotification === 'function') {
                    showNotification(`File "${files[0].name}" ready for upload`, 'info');
                }
            }
        }
    }, false);
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
}

// ========= Cleanup =========

window.addEventListener('beforeunload', function() {
    if (checkInterval) {
        clearInterval(checkInterval);
    }
});
