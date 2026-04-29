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

function getAuthHeaders(extraHeaders = {}) {
    const token = localStorage.getItem('token');
    const headers = { ...extraHeaders };
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }
    return headers;
}

async function authFetch(url, options = {}) {
    const headers = getAuthHeaders(options.headers || {});
    const response = await fetch(url, { ...options, headers });

    if (response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.php';
        throw new Error('Authentication required');
    }

    return response;
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
        await authFetch('api.php?action=logout', { method: 'POST' });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.php';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', checkAuth);
