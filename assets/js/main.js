// Main dashboard functionality

function createProject() {
    document.getElementById('createProjectModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function editProject(slug) {
    window.location.href = `/editor.php?project=${slug}`;
}

async function deleteProject(slug) {
    if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/projects.php?slug=${slug}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove project card from DOM
            const projectCard = document.querySelector(`[data-slug="${slug}"]`);
            if (projectCard) {
                projectCard.remove();
            }
            
            // Check if no projects left
            const projectsGrid = document.getElementById('projectsGrid');
            if (projectsGrid.children.length === 0) {
                projectsGrid.innerHTML = `
                    <div class="empty-state">
                        <h3>No projects yet</h3>
                        <p>Create your first project to get started</p>
                        <button class="btn btn-primary" onclick="createProject()">Create Project</button>
                    </div>
                `;
            }
        } else {
            alert('Failed to delete project: ' + result.error);
        }
    } catch (error) {
        alert('Error deleting project: ' + error.message);
    }
}

// Handle create project form submission
document.getElementById('createProjectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const projectName = document.getElementById('projectName').value.trim();
    
    if (!projectName) {
        alert('Project name is required');
        return;
    }
    
    try {
        const response = await fetch('/api/projects.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name: projectName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            alert('Failed to create project: ' + result.error);
        }
    } catch (error) {
        alert('Error creating project: ' + error.message);
    }
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('createProjectModal');
    if (e.target === modal) {
        closeModal('createProjectModal');
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('createProjectModal');
    }
});
