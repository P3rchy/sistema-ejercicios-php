// Registro del Service Worker para PWA
// Incluir este script en todas las páginas principales

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/sistema_entrenamiento/service-worker.js')
      .then(registration => {
        console.log('✅ Service Worker registrado:', registration.scope);
        
        // Verificar actualizaciones cada hora
        setInterval(() => {
          registration.update();
        }, 60 * 60 * 1000);
        
        // Detectar nueva versión disponible
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // Hay una nueva versión disponible
              mostrarNotificacionActualizacion();
            }
          });
        });
      })
      .catch(err => {
        console.log('❌ Error al registrar Service Worker:', err);
      });
  });
  
  // Detectar cuando el SW está listo para controlar la página
  navigator.serviceWorker.ready.then(registration => {
    console.log('✅ Service Worker listo');
  });
}

// Mostrar notificación de actualización disponible
function mostrarNotificacionActualizacion() {
  if (!document.getElementById('update-notification')) {
    const notification = document.createElement('div');
    notification.id = 'update-notification';
    notification.innerHTML = `
      <div style="position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%); 
                  background: #2563eb; color: white; padding: 15px 20px; 
                  border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); 
                  z-index: 10000; display: flex; gap: 10px; align-items: center;
                  max-width: 90%; animation: slideUp 0.3s ease;">
        <span>✨ Nueva versión disponible</span>
        <button onclick="actualizarApp()" 
                style="background: white; color: #2563eb; border: none; 
                       padding: 8px 16px; border-radius: 5px; font-weight: bold; 
                       cursor: pointer;">
          Actualizar
        </button>
        <button onclick="cerrarNotificacion()" 
                style="background: transparent; color: white; border: 1px solid white; 
                       padding: 8px 12px; border-radius: 5px; cursor: pointer;">
          Ahora no
        </button>
      </div>
      <style>
        @keyframes slideUp {
          from { transform: translate(-50%, 100px); opacity: 0; }
          to { transform: translate(-50%, 0); opacity: 1; }
        }
      </style>
    `;
    document.body.appendChild(notification);
  }
}

// Actualizar la app
function actualizarApp() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistration().then(registration => {
      if (registration && registration.waiting) {
        // Decirle al SW que tome control inmediatamente
        registration.waiting.postMessage({ action: 'skipWaiting' });
        
        // Recargar la página cuando el nuevo SW tome control
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          window.location.reload();
        });
      }
    });
  }
}

// Cerrar notificación de actualización
function cerrarNotificacion() {
  const notification = document.getElementById('update-notification');
  if (notification) {
    notification.remove();
  }
}

// Detectar si la app está instalada
function esAppInstalada() {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true;
}

// Mostrar banner de instalación personalizado
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  // Prevenir el mini-infobar de Chrome
  e.preventDefault();
  
  // Guardar el evento para usarlo después
  deferredPrompt = e;
  
  // Solo mostrar si no está instalada
  if (!esAppInstalada()) {
    mostrarBannerInstalacion();
  }
});

// Mostrar banner de instalación
function mostrarBannerInstalacion() {
  // Verificar si ya se mostró antes
  if (localStorage.getItem('install-banner-dismissed')) {
    return;
  }
  
  const banner = document.createElement('div');
  banner.id = 'install-banner';
  banner.innerHTML = `
    <div style="position: fixed; bottom: 0; left: 0; right: 0; 
                background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
                color: white; padding: 15px; z-index: 9999; 
                box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
                display: flex; align-items: center; justify-content: space-between;
                gap: 10px; animation: slideUpBanner 0.3s ease;">
      <div style="flex: 1;">
        <div style="font-weight: bold; margin-bottom: 5px;">📱 Instalar App</div>
        <div style="font-size: 13px; opacity: 0.9;">
          Acceso rápido sin abrir el navegador
        </div>
      </div>
      <button onclick="instalarApp()" 
              style="background: white; color: #2563eb; border: none; 
                     padding: 10px 20px; border-radius: 8px; font-weight: bold; 
                     cursor: pointer; white-space: nowrap;">
        Instalar
      </button>
      <button onclick="cerrarBannerInstalacion()" 
              style="background: transparent; color: white; border: none; 
                     font-size: 24px; cursor: pointer; padding: 5px 10px;">
        ×
      </button>
    </div>
    <style>
      @keyframes slideUpBanner {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
      }
    </style>
  `;
  document.body.appendChild(banner);
}

// Instalar la app
async function instalarApp() {
  if (!deferredPrompt) {
    alert('La instalación no está disponible en este momento');
    return;
  }
  
  // Mostrar el prompt de instalación
  deferredPrompt.prompt();
  
  // Esperar la respuesta del usuario
  const { outcome } = await deferredPrompt.userChoice;
  
  console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
  
  // Limpiar el prompt
  deferredPrompt = null;
  
  // Cerrar el banner
  cerrarBannerInstalacion();
}

// Cerrar banner de instalación
function cerrarBannerInstalacion() {
  const banner = document.getElementById('install-banner');
  if (banner) {
    banner.style.animation = 'slideUpBanner 0.3s ease reverse';
    setTimeout(() => banner.remove(), 300);
  }
  
  // Recordar que el usuario cerró el banner
  localStorage.setItem('install-banner-dismissed', 'true');
}

// Detectar cuando la app se instaló
window.addEventListener('appinstalled', () => {
  console.log('✅ App instalada correctamente');
  
  // Cerrar banner si está abierto
  cerrarBannerInstalacion();
  
  // Mostrar mensaje de éxito
  if (typeof showToast === 'function') {
    showToast('¡App instalada! Ahora puedes acceder desde tu pantalla de inicio', 'success');
  }
});

// Log si la app está ejecutándose como PWA
if (esAppInstalada()) {
  console.log('✅ Ejecutándose como PWA instalada');
} else {
  console.log('ℹ️ Ejecutándose en navegador');
}
