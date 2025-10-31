/**
 * Enhanced Media Server JavaScript with Partial Refresh
 */

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Set initial values from PHP data
    if (window.mediaServerData) {
        lastFileCount = window.mediaServerData.initialFileCount;
    }
    
    // Start file monitoring with partial refresh
    startFileMonitoring();
    
    // Initialize other features
    initializeUploadHandling();
    initializeDisplaySettings();
    handleDragAndDrop();
    
    console.log('Media Server initialized');
    showNotification('Media Server ready', 'info');
}

// ========= Enhanced File Monitoring with Partial Refresh =========
function startFileMonitoring() {
    // Initial check to set baseline values
    checkForChangesPartial();
    
    // Start periodic checking every 3 seconds (increased from 2 to reduce server load)
    checkInterval = setInterval(checkForChangesPartial, 3000);
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
                    <p>Upload files using the form below.</p>
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
    
    // Create file cell with icon and info
    const fileCell = document.createElement('td');
    fileCell.className = 'file';
    fileCell.onclick = function() { displayFile(filename); };  // <-- FIXED THIS LINE
    fileCell.innerHTML = `
        <span class="file-icon">${fileData.icon}</span>
        <span class="filename">${escapeHtml(filename)}</span>
        <span class="file-info">${fileData.info}</span>
    `;
    
    // Create remove cell
    const removeCell = document.createElement('td');
    removeCell.className = 'remove';
    removeCell.onclick = function() { DeleteFile(filename); };
    removeCell.title = 'Delete ' + filename;
    removeCell.textContent = '✕';
    
    row.appendChild(fileCell);
    row.appendChild(removeCell);
    
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

// ========= Utility Functions =========
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateStatus(message, type = 'monitoring') {
    const status = document.getElementById('fileMonitorStatus');
    if (status) {
        status.textContent = message;
        status.className = 'file-monitor-status status-' + type;
    }
}

function WriteFile(params) {
    // console.log("Params: ", params);

    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log(this.responseText);
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
/*
function menuItemClicked(filename) {
    console.log("Selected file: " + filename);
    
    // Visual feedback - highlight selected file
    const rows = document.querySelectorAll('.row');
    rows.forEach(row => row.classList.remove('selected'));
    
    const selectedRow = document.getElementById('file_' + filename.replace(/[^a-zA-Z0-9]/g, ''));
    if (selectedRow) {
        selectedRow.classList.add('selected');
    }

    var timestamp = new Date().toISOString();
    var params = "update=UpdateDisplay&filename=" + filename + "&timestamp=" + timestamp;

    WriteFile(params);

    showNotification(`Selected: ${filename}`, 'info');

}
*/
function DeleteFile(filename) {
    if (!confirm(`Are you sure you want to delete '${filename}'?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    showNotification('Deleting file...', 'updating');

    var params = "update=Delete&filename="+filename;
    WriteFile(params);
}

function SavePresetsToConfig(Presets) {
    var params = "update=ConfigFileUpdate";

    Object.entries(Presets).forEach(([key, value]) => {
        params += "&"+`${key}`+"="+`${value}`;
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
            'X-Requested-With': 'XMLHttpRequest'  // This helps server detect AJAX
        }
    })
    .then(response => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
        }
        return response.text(); // Get raw text first
    })
    .then(data => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.log('Raw response from server:');
            console.log(data); // This will show us exactly what we're getting
        }
        
        // Try to parse as JSON
        try {
            const jsonData = JSON.parse(data);
            
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.log('Successfully parsed JSON:', jsonData);
            }
            
            if (jsonData.success) {
                showNotification('Presets updated successfully!', 'success');
                document.getElementById('PresetMenu').style.display = 'none';
            } else {
                showNotification('Error: ' + jsonData.message, 'error');
            }
        } catch (e) {
            if (window.mediaServerData && window.mediaServerData.debug) {
                console.error('JSON parse failed. Raw response was:', data.substring(0, 500));
            }
            showNotification('Server returned unexpected response. Please try again.', 'error');
        }
    })
    .catch(error => {
        if (window.mediaServerData && window.mediaServerData.debug) {
            console.error('Network error:', error);
        }
        showNotification('Network error. Please check your connection.', 'error');
    });
    
    return false; // Prevent form submission
}

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
                showNotification(`File "${files[0].name}" ready for upload`, 'info');
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
