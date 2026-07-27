// Service Worker para PWA
const CACHE_NAME = 'gestao-escolar-v1';
const urlsToCache = [
  '/',
  '/index.html',
  '/css/output.css',
  '/js/main.js',
  '/js/carousel.js',
  '/js/form-validation.js',
  '/js/loading-states.js',
  '/js/dark-mode.js',
  '/js/accessibility.js',
  '/js/performance-animations.js',
  '/js/notifications-polling.js',
  '/manifest.json',
  '/img/logo.jpg'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Cache antigo removido:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Cache hit - retorna resposta do cache
        if (response) {
          return response;
        }

        // Clone da requisição
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then((response) => {
          // Verifica se resposta válida
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone da resposta
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });

          return response;
        }).catch(() => {
          // Fallback para offline
          return caches.match('/index.html');
        });
      })
  );
});

// Background sync para notificações
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications());
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  const options = {
    body: event.data ? event.data.text() : 'Nova notificação',
    icon: '/img/logo.jpg',
    badge: '/img/logo.jpg',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('Notificação do Sistema', options)
  );
});

// Notificação click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/portal/dashboard.php')
  );
});

// Função para sincronizar notificações
async function syncNotifications() {
  // Lógica para sincronizar notificações quando online
  console.log('Sincronizando notificações...');
}
