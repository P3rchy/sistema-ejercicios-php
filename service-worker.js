const CACHE_NAME = 'gym-app-v1';
const urlsToCache = [
  '/sistema_entrenamiento/',
  '/sistema_entrenamiento/mis_rutinas.php',
  '/sistema_entrenamiento/crear_rutina_basica.php',
  '/sistema_entrenamiento/entrenar_rutina_basica.php',
  '/sistema_entrenamiento/styles.css',
  '/sistema_entrenamiento/notifications.css',
  '/sistema_entrenamiento/manifest.json'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
  console.log('Service Worker: Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Archivos en caché');
        return cache.addAll(urlsToCache);
      })
      .catch(err => console.log('Service Worker: Error al cachear', err))
  );
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', event => {
  console.log('Service Worker: Activando...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Limpiando caché antiguo', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Estrategia: Network First, fallback to Cache
self.addEventListener('fetch', event => {
  // Solo cachear GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // No cachear API calls o datos dinámicos
  if (event.request.url.includes('/api/') || 
      event.request.url.includes('guardar_') ||
      event.request.url.includes('eliminar_') ||
      event.request.url.includes('duplicar_')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Clonar la respuesta
        const responseClone = response.clone();
        
        // Guardar en caché
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseClone);
        });
        
        return response;
      })
      .catch(() => {
        // Si falla la red, intentar desde caché
        return caches.match(event.request).then(response => {
          if (response) {
            return response;
          }
          
          // Si no está en caché, mostrar página offline
          if (event.request.mode === 'navigate') {
            return caches.match('/sistema_entrenamiento/offline.html');
          }
        });
      })
  );
});

// Manejo de mensajes (para forzar actualización)
self.addEventListener('message', event => {
  if (event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});

// Sincronización en segundo plano (opcional)
self.addEventListener('sync', event => {
  if (event.tag === 'sync-entrenamientos') {
    console.log('Service Worker: Sincronizando entrenamientos...');
    // Aquí podrías sincronizar datos pendientes
  }
});

// Notificaciones push (opcional - para futuro)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : '¡Es hora de entrenar!',
    icon: '/sistema_entrenamiento/icons/icon-192x192.png',
    badge: '/sistema_entrenamiento/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    tag: 'entrenamiento-reminder',
    requireInteraction: false
  };

  event.waitUntil(
    self.registration.showNotification('Sistema de Entrenamiento', options)
  );
});

// Click en notificación
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/sistema_entrenamiento/mis_rutinas.php')
  );
});
