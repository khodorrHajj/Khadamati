@php
    $pickerId = $pickerId ?? ('document-picker-' . uniqid());
    $selectedDocuments = collect($selectedDocuments ?? [])->map(fn ($document) => trim((string) $document))->filter()->values();
    $presetDocuments = collect($presetDocuments ?? [])->map(fn ($document) => trim((string) $document))->filter()->unique()->values();
    $inputName = $inputName ?? 'documents';
    $label = $label ?? 'Documents';
    $placeholder = $placeholder ?? 'Search or type a document';
    $helpText = $helpText ?? null;
    $legacyInputName = $legacyInputName ?? null;
@endphp

<div
    id="{{ $pickerId }}"
    class="document-picker"
    data-document-picker
    data-input-name="{{ $inputName }}"
>
    <label>{{ $label }}</label>

    <div class="input-group mb-2">
        <input
            type="text"
            class="form-control"
            list="{{ $pickerId }}-options"
            data-document-picker-input
            placeholder="{{ $placeholder }}">
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-primary" data-document-picker-add>Add</button>
        </div>
    </div>

    <datalist id="{{ $pickerId }}-options">
        @foreach ($presetDocuments as $document)
            <option value="{{ $document }}"></option>
        @endforeach
    </datalist>

    <div class="d-flex flex-wrap mb-2" data-document-picker-tags>
        @foreach ($selectedDocuments as $document)
            <span class="badge badge-light border mr-2 mb-2 px-3 py-2 d-inline-flex align-items-center" data-document-tag="{{ $document }}">
                <span>{{ $document }}</span>
                <button type="button" class="btn btn-link btn-sm text-danger ml-2 p-0" data-document-remove="{{ $document }}" aria-label="Remove {{ $document }}">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        @endforeach
    </div>

    <div data-document-picker-hidden-inputs>
        @foreach ($selectedDocuments as $document)
            <input type="hidden" name="{{ $inputName }}[]" value="{{ $document }}">
        @endforeach
    </div>

    @if ($legacyInputName)
        <textarea name="{{ $legacyInputName }}" class="d-none" data-document-picker-legacy>@foreach ($selectedDocuments as $document){{ $document }}@if (!$loop->last)
@endif @endforeach</textarea>
    @endif

    @if ($helpText)
        <small class="form-text text-muted">{{ $helpText }}</small>
    @endif
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function buildTagHtml(value) {
                    const safe = String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    return `
                        <span class="badge badge-light border mr-2 mb-2 px-3 py-2 d-inline-flex align-items-center" data-document-tag="${safe}">
                            <span>${safe}</span>
                            <button type="button" class="btn btn-link btn-sm text-danger ml-2 p-0" data-document-remove="${safe}" aria-label="Remove ${safe}">
                                <i class="fas fa-times"></i>
                            </button>
                        </span>
                    `;
                }

                function syncLegacyTextarea(root) {
                    const legacyTextarea = root.querySelector('[data-document-picker-legacy]');
                    if (!legacyTextarea) {
                        return;
                    }

                    const values = Array.from(root.querySelectorAll('[data-document-picker-hidden-inputs] input'))
                        .map((input) => input.value);

                    legacyTextarea.value = values.join('\n');
                }

                function addDocument(root, value) {
                    const trimmed = String(value || '').trim();
                    if (!trimmed) {
                        return;
                    }

                    const hiddenContainer = root.querySelector('[data-document-picker-hidden-inputs]');
                    const tagsContainer = root.querySelector('[data-document-picker-tags]');
                    const inputName = root.dataset.inputName;

                    if (!hiddenContainer || !tagsContainer || !inputName) {
                        return;
                    }

                    const exists = Array.from(hiddenContainer.querySelectorAll('input'))
                        .some((input) => input.value.toLowerCase() === trimmed.toLowerCase());

                    if (exists) {
                        return;
                    }

                    tagsContainer.insertAdjacentHTML('beforeend', buildTagHtml(trimmed));
                    hiddenContainer.insertAdjacentHTML('beforeend', `<input type="hidden" name="${inputName}[]" value="${trimmed.replace(/"/g, '&quot;')}">`);
                    syncLegacyTextarea(root);
                }

                function removeDocument(root, value) {
                    const hiddenInputs = Array.from(root.querySelectorAll('[data-document-picker-hidden-inputs] input'));
                    hiddenInputs.forEach((input) => {
                        if (input.value === value) {
                            input.remove();
                        }
                    });

                    const tag = root.querySelector(`[data-document-tag="${CSS.escape(value)}"]`);
                    if (tag) {
                        tag.remove();
                    }

                    syncLegacyTextarea(root);
                }

                function initializeDocumentPickers() {
                    document.querySelectorAll('[data-document-picker]').forEach((root) => {
                        const input = root.querySelector('[data-document-picker-input]');
                        const addButton = root.querySelector('[data-document-picker-add]');

                        if (addButton && input) {
                            addButton.addEventListener('click', function () {
                                addDocument(root, input.value);
                                input.value = '';
                                input.focus();
                            });

                            input.addEventListener('keydown', function (event) {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    addDocument(root, input.value);
                                    input.value = '';
                                }
                            });
                        }

                        root.addEventListener('click', function (event) {
                            const removeButton = event.target.closest('[data-document-remove]');
                            if (!removeButton) {
                                return;
                            }

                            removeDocument(root, removeButton.getAttribute('data-document-remove'));
                        });

                        syncLegacyTextarea(root);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initializeDocumentPickers);
                } else {
                    initializeDocumentPickers();
                }
            }());
        </script>
    @endpush
@endonce
