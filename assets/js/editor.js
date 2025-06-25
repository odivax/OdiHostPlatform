// Monaco Editor integration and functionality

let editor = null;
let currentFile = 'index.html';
let files = {};

// Initialize Monaco Editor
require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs' } });

require(['vs/editor/editor.main'], function () {
    editor = monaco.editor.create(document.getElementById('codeEditor'), {
        value: '',
        language: 'html',
        theme: 'vs-dark',
        automaticLayout: true,
        fontSize: 14,
        wordWrap: 'on',
        minimap: { enabled: false }
    });

    // Load initial files
    loadFiles();
    
    // Auto-save on content change
    let saveTimeout;
    editor.onDidChangeModelContent(() => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            saveCurrentFile();
        }, 1000);
    });
});

// Tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const file = this.dataset.file;
            switchFile(file);
        });
    });
    
    // File upload handling
    const fileUpload = document.getElementById('fileUpload');
    fileUpload.addEventListener('change', handleFileUpload);
    
    // Load file list
    loadFileList();
});

async function loadFiles() {
    try {
        // Load all three main files
        const files = ['index.html', 'style.css', 'script.js'];
        
        for (const file of files) {
            const response = await fetch(`/api/files.php?project=${PROJECT_SLUG}&file=${file}`);
            const result = await response.json();
            
            if (result.content !== undefined) {
                window.files = window.files || {};
                window.files[file] = result.content;
            }
        }
        
        // Load the first file
        if (window.files && window.files[currentFile]) {
            editor.setValue(window.files[currentFile]);
        }
    } catch (error) {
        console.error('Error loading files:', error);
    }
}

function switchFile(filename) {
    // Save current file content
    if (editor) {
        window.files = window.files || {};
        window.files[currentFile] = editor.getValue();
    }
    
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-file="${filename}"]`).classList.add('active');
    
    // Switch to new file
    currentFile = filename;
    
    // Set editor language and content
    const language = getLanguageFromFile(filename);
    const model = monaco.editor.createModel(
        window.files[filename] || '',
        language
    );
    
    editor.setModel(model);
}

function getLanguageFromFile(filename) {
    const extension = filename.split('.').pop();
    const languageMap = {
        'html': 'html',
        'css': 'css',
        'js': 'javascript'
    };
    return languageMap[extension] || 'text';
}

async function saveCurrentFile() {
    if (!editor) return;
    
    const content = editor.getValue();
    
    try {
        const response = await fetch('/api/files.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project: PROJECT_SLUG,
                file: currentFile,
                content: content
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update preview
            refreshPreview();
        }
    } catch (error) {
        console.error('Error saving file:', error);
    }
}

async function saveProject() {
    // Save current file content
    if (editor) {
        window.files = window.files || {};
        window.files[currentFile] = editor.getValue();
    }
    
    // Save all files
    const savePromises = Object.entries(window.files).map(([filename, content]) => {
        return fetch('/api/files.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                project: PROJECT_SLUG,
                file: filename,
                content: content
            })
        });
    });
    
    try {
        await Promise.all(savePromises);
        refreshPreview();
        
        // Show success feedback
        const saveBtn = document.querySelector('[onclick="saveProject()"]');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saved!';
        saveBtn.style.background = '#10b981';
        
        setTimeout(() => {
            saveBtn.textContent = originalText;
            saveBtn.style.background = '';
        }, 2000);
    } catch (error) {
        console.error('Error saving project:', error);
        alert('Failed to save project');
    }
}

function refreshPreview() {
    const iframe = document.getElementById('previewFrame');
    iframe.src = iframe.src;
}

async function loadFileList() {
    try {
        const response = await fetch(`/api/files.php?project=${PROJECT_SLUG}`);
        const result = await response.json();
        
        if (result.files) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            result.files.forEach(file => {
                // Skip main files that are in tabs
                if (['index.html', 'style.css', 'script.js'].includes(file.name)) {
                    return;
                }
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.textContent = file.name;
                fileList.appendChild(fileItem);
            });
        }
    } catch (error) {
        console.error('Error loading file list:', error);
    }
}

async function handleFileUpload(event) {
    const files = event.target.files;
    if (!files.length) return;
    
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    try {
        const response = await fetch(`/api/files.php?project=${PROJECT_SLUG}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.uploaded && result.uploaded.length > 0) {
            loadFileList();
            
            // Show success message
            alert(`Uploaded: ${result.uploaded.join(', ')}`);
        }
        
        if (result.errors && result.errors.length > 0) {
            alert(`Errors: ${result.errors.join(', ')}`);
        }
    } catch (error) {
        console.error('Error uploading files:', error);
        alert('Failed to upload files');
    }
    
    // Clear the input
    event.target.value = '';
}

async function updateCustomDomain() {
    const customDomain = document.getElementById('customDomain').value.trim();
    
    try {
        const response = await fetch('/api/projects.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                slug: PROJECT_SLUG,
                custom_domain: customDomain
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Custom domain updated successfully!');
            // Reload to show DNS instructions if domain was added
            window.location.reload();
        } else {
            alert('Failed to update custom domain: ' + result.error);
        }
    } catch (error) {
        console.error('Error updating custom domain:', error);
        alert('Failed to update custom domain');
    }
}
