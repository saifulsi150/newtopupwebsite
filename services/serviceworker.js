var staticCacheName = 'pwa-static-v2';
var offlinePage = '/offline.html';

self.addEventListener('install', function (event) {
    self.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName).then(function (cache) {
            return cache.addAll([offlinePage]);
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames
                    .filter(function (cacheName) {
                        return cacheName.startsWith('pwa-') && cacheName !== staticCacheName;
                    })
                    .map(function (cacheName) {
                        return caches.delete(cacheName);
                    })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    var requestUrl = new URL(event.request.url);
    var isSameOrigin = requestUrl.origin === self.location.origin;
    var isUploadAsset = isSameOrigin && requestUrl.pathname.startsWith('/uploads/');
    var isStaticAsset = isSameOrigin && (
        requestUrl.pathname.startsWith('/assets/') ||
        requestUrl.pathname.startsWith('/build/') ||
        /\.(?:css|js|png|jpe?g|webp|svg|gif|ico|woff2?)$/i.test(requestUrl.pathname)
    );

    if (isUploadAsset) {
        event.respondWith(
            fetch(event.request)
                .catch(function () {
                    return caches.match(event.request);
                })
        );
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(function () {
                    return caches.match(offlinePage);
                })
        );
        return;
    }

    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request).then(function (cachedResponse) {
                var fetchPromise = fetch(event.request).then(function (networkResponse) {
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse;
                    }

                    var responseClone = networkResponse.clone();
                    caches.open(staticCacheName).then(function (cache) {
                        cache.put(event.request, responseClone);
                    });
                    return networkResponse;
                });

                return cachedResponse || fetchPromise;
            })
        );
    }
});