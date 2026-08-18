<!-- Web Application Manifest -->
<link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
<!-- Chrome for Android theme color -->
<meta name="theme-color" content="{{ $config['theme_color'] }}">

<!-- Add to homescreen for Chrome on Android -->
<meta name="mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="application-name" content="{{ $config['short_name'] }}">
<link rel="icon" sizes="{{ data_get(end($config['icons']), 'sizes') }}" href="{{ asset(data_get(end($config['icons']), 'src')) }}">

<!-- Add to homescreen for Safari on iOS -->
<meta name="apple-mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{  $config['status_bar'] }}">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
<link rel="apple-touch-icon" href="{{ asset(data_get(end($config['icons']), 'src')) }}">


<link href="{{ asset($config['splash']['640x1136']) }}" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['750x1334']) }}" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1242x2208']) }}" media="(device-width: 621px) and (device-height: 1104px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1125x2436']) }}" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['828x1792']) }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1242x2688']) }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1536x2048']) }}" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1668x2224']) }}" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['1668x2388']) }}" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
<link href="{{ asset($config['splash']['2048x2732']) }}" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />

<!-- Tile for Win8 -->
<meta name="msapplication-TileColor" content="{{ $config['background_color'] }}">
<meta name="msapplication-TileImage" content="{{ asset(data_get(end($config['icons']), 'src')) }}">

@auth
    @php
        // The service worker must never use one user's cached HTML for another
        // user.  The opaque value is only a cache namespace, not an identity.
        $offlineCacheKey = hash_hmac(
            'sha256',
            'offline-user:'.auth()->id().':'.auth()->user()->role,
            (string) config('app.key')
        );

        $offlineWarmUrls = [route('dashboard')];

        if (in_array(auth()->user()->role, ['sales'], true)) {
            $offlineWarmUrls[] = route('penjualan.branch-index');
            $offlineWarmUrls[] = route('penjualan.create');
        } elseif (auth()->user()->role === 'admin-cabang') {
            $offlineWarmUrls[] = route('penjualan.branch-index');
        } elseif (in_array(auth()->user()->role, ['superadmin', 'admin-gudang', 'owner'], true)) {
            $offlineWarmUrls[] = route('penjualan.index');
            $offlineWarmUrls[] = route('penjualan.create');
            $offlineWarmUrls[] = route('pembelian.index');
            $offlineWarmUrls[] = route('pembelian.create');
        }
    @endphp
    <meta name="offline-user-scope" content="{{ $offlineCacheKey }}">
    <script>
        (function () {
            var key = @json($offlineCacheKey);
            var warmUrls = @json($offlineWarmUrls);
            document.cookie = 'alami_pwa_user=' + encodeURIComponent(key) + '; path=/; SameSite=Lax';

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(function (registration) {
                    var worker = navigator.serviceWorker.controller || registration.active;
                    if (!worker) {
                        return;
                    }

                    warmUrls.push(window.location.href);
                    worker.postMessage({
                        type: 'set-user-cache',
                        key: key,
                        warmUrls: warmUrls
                    });

                    var warmStaticAssets = function () {
                        var urls = [];

                        document.querySelectorAll('link[href], script[src], img[src]').forEach(function (element) {
                            var value = element.href || element.src;
                            if (value && urls.indexOf(value) === -1) {
                                urls.push(value);
                            }
                        });

                        worker.postMessage({ type: 'warm-static', urls: urls });
                    };

                    if (document.readyState === 'complete') {
                        setTimeout(warmStaticAssets, 0);
                    } else {
                        window.addEventListener('load', warmStaticAssets, { once: true });
                    }
                });
            }
        }());
    </script>
@endauth

<script type="text/javascript">
    // Initialize the service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/serviceworker.js', {
            scope: '/'
        }).then(function (registration) {
            // Registration was successful
            console.log('Laravel PWA: ServiceWorker registration successful with scope: ', registration.scope);
        }, function (err) {
            // registration failed :(
            console.log('Laravel PWA: ServiceWorker registration failed: ', err);
        });
    }
</script>
