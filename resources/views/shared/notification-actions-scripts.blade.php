@once
    @push('scripts')
        <script>
            (function () {
                function updateNotificationCount(count) {
                    document.querySelectorAll('[data-notification-count-badge]').forEach(function (element) {
                        element.textContent = count;
                        element.classList.toggle('d-none', Number(count) === 0);
                    });

                    document.querySelectorAll('[data-notification-count-label]').forEach(function (element) {
                        element.textContent = count;
                    });
                }

                function markItemAsRead(form) {
                    const item = form.closest('[data-notification-item]');
                    if (!item) {
                        return;
                    }

                    item.classList.remove('bg-light');
                    const unreadControls = item.querySelectorAll('[data-notification-action]');
                    unreadControls.forEach(function (control) {
                        control.remove();
                    });
                }

                async function handleNotificationFormSubmit(event) {
                    const form = event.target;
                    if (!form.matches('[data-notification-form]')) {
                        return;
                    }

                    event.preventDefault();

                    const formData = new FormData(form);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            window.location.href = form.dataset.redirectUrl || window.location.href;
                            return;
                        }

                        const data = await response.json();
                        updateNotificationCount(Number(data.unread_count || 0));
                        markItemAsRead(form);

                        if (data.redirect_to) {
                            window.location.href = data.redirect_to;
                        }
                    } catch (error) {
                        window.location.href = form.dataset.redirectUrl || window.location.href;
                    }
                }

                document.addEventListener('submit', handleNotificationFormSubmit);
            }());
        </script>
    @endpush
@endonce
