/**
 * Sistema de notificaciones y modales - Sistema de Entrenamiento
 * Modales centrados + Toasts abajo derecha
 */

// ========== TOASTS ==========
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) {
        console.error('Toast container no encontrado');
        return;
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : '⚠️';
    toast.innerHTML = `<span style="font-size: 20px;">${icon}</span><span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========== MODALES ==========
let modalCallback = null;

function showModal(title, message, callback, isDelete = false) {
    const modal = document.getElementById('confirmModal');
    if (!modal) {
        console.error('Modal no encontrado');
        // Fallback a confirm nativo
        if (confirm(message)) callback();
        return;
    }
    
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    
    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.className = 'modal-btn ' + (isDelete ? 'modal-btn-delete' : 'modal-btn-confirm');
    
    modalCallback = callback;
    modal.classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.classList.remove('active');
    modalCallback = null;
}

function confirmAction() {
    if (modalCallback) modalCallback();
    closeModal();
}

// Cerrar modal al hacer click fuera
document.addEventListener('click', (e) => {
    const modal = document.getElementById('confirmModal');
    if (modal && e.target === modal) {
        closeModal();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
    }
});
