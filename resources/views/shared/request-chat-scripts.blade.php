@once
    @push('scripts')
        <script>
            (function () {
                const isLocalEnvironment = @json(app()->environment('local'));

                function escapeHtml(value) {
                    return String(value || '').replace(/[&<>"']/g, function (character) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        }[character];
                    });
                }

                function messageHtml(message, currentUserId, style) {
                    const isMine = Number(message.sender_id) === Number(currentUserId);
                    const body = message.body ? `<div class="mb-2">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>` : '';
                    const attachment = message.attachment_url ? `<a href="${escapeHtml(message.attachment_url)}" target="_blank" rel="noopener">Open attachment</a>` : '';
                    const role = message.sender_role ? `<span class="badge badge-light border ml-2">${escapeHtml(message.sender_role.charAt(0).toUpperCase() + message.sender_role.slice(1))}</span>` : '';
                    const createdAt = message.created_at || message.created_at_human || '';

                    if (style === 'bubbles') {
                        return `
                            <div class="mb-3 d-flex ${isMine ? 'justify-content-end' : 'justify-content-start'}" data-message-id="${message.id}">
                                <div class="rounded-lg border px-3 py-2 ${isMine ? 'bg-primary text-white' : 'bg-white'}" style="max-width: 78%;">
                                    <div class="small ${isMine ? 'text-white-50' : 'text-muted'} mb-1">
                                        ${escapeHtml(message.sender_name)}
                                        ${message.sender_role ? `· ${escapeHtml(message.sender_role.charAt(0).toUpperCase() + message.sender_role.slice(1))}` : ''}
                                    </div>
                                    ${body}
                                    ${attachment ? `<div class="mt-2">${attachment}</div>` : ''}
                                    <div class="small ${isMine ? 'text-white-50' : 'text-muted'} mt-2">${escapeHtml(createdAt)}</div>
                                </div>
                            </div>
                        `;
                    }

                    return `
                        <div class="border rounded p-3 mb-3 ${isMine ? 'bg-light' : ''}" data-message-id="${message.id}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>${escapeHtml(message.sender_name)}</strong>
                                    <span class="text-muted small ml-2">${escapeHtml(createdAt)}</span>
                                    ${role}
                                </div>
                            </div>
                            ${body}
                            ${attachment}
                        </div>
                    `;
                }

                function appendMessage(container, message, currentUserId, style) {
                    if (!container || container.querySelector(`[data-message-id="${message.id}"]`)) {
                        return;
                    }

                    const emptyState = container.querySelector('p.text-muted');
                    if (emptyState) {
                        emptyState.remove();
                    }

                    container.insertAdjacentHTML('beforeend', messageHtml(message, currentUserId, style));
                    container.scrollTop = container.scrollHeight;
                }

                async function refreshUnreadBadges() {
                    const citizenBadge = document.getElementById('citizen-message-badge');
                    const citizenSidebarBadge = document.getElementById('citizen-sidebar-message-badge');
                    const municipalityBadge = document.getElementById('municipality-message-badge');
                    const municipalitySidebarBadge = document.getElementById('municipality-sidebar-message-badge');
                    const endpoint = citizenBadge
                        ? @json(Route::has('citizen.messages.unread-count') ? route('citizen.messages.unread-count') : null)
                        : (municipalityBadge ? @json(Route::has('municipality.messages.unread-count') ? route('municipality.messages.unread-count') : null) : null);

                    if (!endpoint) {
                        return;
                    }

                    try {
                        const response = await fetch(endpoint, {
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        const count = Number(data.unread_count || 0);

                        [citizenBadge, citizenSidebarBadge, municipalityBadge, municipalitySidebarBadge].forEach(function (badge) {
                            if (!badge) {
                                return;
                            }

                            badge.textContent = count;
                            badge.classList.toggle('d-none', count === 0);
                        });
                    } catch (error) {
                        // Badge refresh is opportunistic; chat delivery should not depend on it.
                    }
                }

                function initializeRequestChats() {
                document.querySelectorAll('[data-request-chat]').forEach(function (chat) {
                    const form = chat.querySelector('[data-chat-form]');
                    const messages = chat.querySelector('[data-chat-messages]');
                    const currentUserId = chat.dataset.currentUserId;
                    const requestId = chat.dataset.requestId;
                    const style = chat.dataset.chatStyle || 'cards';

                    if (messages) {
                        messages.scrollTop = messages.scrollHeight;
                    }

                    if (form && messages) {
                        form.addEventListener('submit', async function (event) {
                            event.preventDefault();

                            const submitButton = form.querySelector('[type="submit"]');
                            const originalText = submitButton ? submitButton.innerHTML : null;
                            const formData = new FormData(form);
                            const body = String(formData.get('body') || '').trim();
                            const attachment = form.querySelector('input[type="file"][name="attachment"]');

                            if (!body && (!attachment || !attachment.files.length)) {
                                window.alert('Write a message or attach a file before sending.');
                                return;
                            }

                            if (submitButton) {
                                submitButton.disabled = true;
                                submitButton.innerHTML = 'Sending...';
                            }

                            try {
                                const response = await fetch(form.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    credentials: 'same-origin'
                                });

                                if (!response.ok) {
                                    let message = 'Unable to send message.';

                                    try {
                                        const errorData = await response.json();
                                        if (errorData.message) {
                                            message = errorData.message;
                                        }
                                    } catch (jsonError) {
                                        // Keep the generic message when the server did not return JSON.
                                    }

                                    throw new Error(message);
                                }

                                const data = await response.json();
                                appendMessage(messages, data.message, currentUserId, style);
                                form.reset();
                                refreshUnreadBadges();
                            } catch (error) {
                                window.alert(error.message);
                            } finally {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.innerHTML = originalText;
                                }
                            }
                        });
                    }

                    if (window.Echo && requestId && messages) {
                        window.Echo.private(`request-chat.${requestId}`)
                            .listen('.message.sent', function (event) {
                                appendMessage(messages, event.message, currentUserId, style);
                                refreshUnreadBadges();
                            });
                    } else if (isLocalEnvironment && !window.Echo) {
                        console.warn('Laravel Echo is not available. Run npm run dev/build and verify VITE_REVERB_* values.');
                    }
                });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initializeRequestChats);
                } else {
                    initializeRequestChats();
                }
            }());
        </script>
    @endpush
@endonce
