/**
 * Caches static assets (CSS/JS/images/fonts) so an already-loaded session
 * keeps working when the network drops. Dynamic PHP pages are not cached.
 */
var CACHE_VERSION = 'minical-static-v1';

function isStaticAsset(pathname) {
    return /\/(css|js|images|fonts)\//.test(pathname);
}

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) {
                        return key.indexOf('minical-static-') === 0 && key !== CACHE_VERSION;
                    })
                    .map(function (key) {
                        return caches.delete(key);
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

    var url = new URL(event.request.url);
    if (!isStaticAsset(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_VERSION).then(function (cache) {
            return cache.match(event.request).then(function (cached) {
                var networkFetch = fetch(event.request)
                    .then(function (response) {
                        if (response && response.status === 200) {
                            cache.put(event.request, response.clone());
                        }
                        return response;
                    })
                    .catch(function () {
                        return cached;
                    });

                return cached || networkFetch;
            });
        })
    );
});
