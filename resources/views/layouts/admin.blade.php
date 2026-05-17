@extends('template.base')

@section('body')
<body class="hold-transition sidebar-mini layout-navbar-fixed">
    <div class="wrapper">
        @include('includes.admin-navbar')
        @include('includes.admin-sidebar')

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('page-title')</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        @include('includes.admin-footer')
    </div>

    <script>
        (() => {
            const DEFAULT_INTERVAL_MS = 10000;
            const liveCallbacks = window.AdminLiveCallbacks = window.AdminLiveCallbacks || {};
            let refreshInFlight = false;

            const getLiveRegions = () => Array.from(document.querySelectorAll('[data-admin-live-region]'));
            const getNotificationRoot = () => document.querySelector('[data-admin-live-navbar-notifications]');

            const hasDirtyInputs = () => {
                const activeElement = document.activeElement;

                if (activeElement && activeElement.closest('[data-admin-live-region], [data-admin-live-navbar-notifications]')) {
                    return true;
                }

                return Array.from(document.querySelectorAll('input, textarea, select')).some((element) => {
                    if (element.disabled || element.type === 'hidden') {
                        return false;
                    }

                    if ((element.type === 'checkbox' || element.type === 'radio')) {
                        return element.checked !== element.defaultChecked;
                    }

                    return element.value !== element.defaultValue;
                });
            };

            const replaceNotificationBadges = (nextDocument) => {
                const nextBadges = nextDocument.querySelectorAll('[data-notification-count-badge]');
                const currentBadges = document.querySelectorAll('[data-notification-count-badge]');

                currentBadges.forEach((badge, index) => {
                    const nextBadge = nextBadges[index];

                    if (!nextBadge) {
                        return;
                    }

                    badge.textContent = nextBadge.textContent;
                    badge.className = nextBadge.className;
                });

                const currentLabel = document.querySelector('[data-notification-count-label]');
                const nextLabel = nextDocument.querySelector('[data-notification-count-label]');

                if (currentLabel && nextLabel) {
                    currentLabel.textContent = nextLabel.textContent;
                }
            };

            const teardownRegion = (region) => {
                const callbackName = region.dataset.adminLiveInit;
                const callback = callbackName ? liveCallbacks[callbackName] : null;

                if (callback && typeof callback.teardown === 'function') {
                    callback.teardown(region);
                }
            };

            const initRegion = (region) => {
                const callbackName = region.dataset.adminLiveInit;
                const callback = callbackName ? liveCallbacks[callbackName] : null;

                if (callback && typeof callback.init === 'function') {
                    callback.init(region);
                }
            };

            const refreshPage = async () => {
                const liveRegions = getLiveRegions();
                const notificationRoot = getNotificationRoot();

                if (!liveRegions.length && !notificationRoot) {
                    return;
                }

                if (refreshInFlight || document.hidden || hasDirtyInputs()) {
                    return;
                }

                try {
                    refreshInFlight = true;

                    const response = await fetch(window.location.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const html = await response.text();
                    const nextDocument = new DOMParser().parseFromString(html, 'text/html');

                    liveRegions.forEach((currentRegion) => {
                        const regionName = currentRegion.dataset.adminLiveRegion;
                        const selector = '[data-admin-live-region="' + regionName + '"]';
                        const nextRegion = nextDocument.querySelector(selector);

                        if (!nextRegion) {
                            return;
                        }

                        teardownRegion(currentRegion);
                        currentRegion.innerHTML = nextRegion.innerHTML;
                        initRegion(currentRegion);
                    });

                    if (notificationRoot) {
                        const nextNotificationRoot = nextDocument.querySelector('[data-admin-live-navbar-notifications]');

                        if (nextNotificationRoot) {
                            notificationRoot.innerHTML = nextNotificationRoot.innerHTML;
                        }
                    }

                    replaceNotificationBadges(nextDocument);
                    document.dispatchEvent(new CustomEvent('admin:live-refreshed'));
                } catch (error) {
                    // Live refresh should fail quietly during local development interruptions.
                } finally {
                    refreshInFlight = false;
                }
            };

            document.addEventListener('DOMContentLoaded', () => {
                getLiveRegions().forEach(initRegion);
                window.setInterval(refreshPage, DEFAULT_INTERVAL_MS);

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        refreshPage();
                    }
                });
            });
        })();
    </script>
</body>
@endsection
