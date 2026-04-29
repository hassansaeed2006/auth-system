// Check authentication on page load
function checkAuth() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    const authNav = document.getElementById('authNav');
    const userNav = document.getElementById('userNav');
    
    if (token && user) {
        if (authNav) authNav.style.display = 'none';
        if (userNav) userNav.style.display = 'flex';
    } else {
        if (authNav) authNav.style.display = 'flex';
        if (userNav) userNav.style.display = 'none';
    }
}

// Show alert
function showAlert(element, message, type = 'info') {
    element.textContent = message;
    element.className = `alert ${type}`;
    element.style.display = 'block';
    
    if (type === 'success' || type === 'error') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

// Logout
async function logout() {
    try {
        await fetch('api.php?action=logout', { method: 'POST' });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.php';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', checkAuth);
