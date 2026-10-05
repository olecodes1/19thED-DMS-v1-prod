// AdminDash — scripts.js
// Shared JS utilities loaded on all pages

document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts after 4s
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () { el.classList.add('d-none'); }, 4000);
    });

    // Flatpickr for all date inputs
    if (window.flatpickr) {
        document.querySelectorAll('input[type="date"]').forEach(function (el) {
            flatpickr(el, {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        });
    }

    // Choices.js for searchable selects
    if (window.Choices) {
        document.querySelectorAll('select.form-select, select.form-select-sm').forEach(function (el) {
            if (el.dataset.choices === 'off' || el.dataset.dynamicSelect === 'true') return;
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
        });
    }

    function attachSwalConfirm(selector, title, text, confirmText) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                // POST flow: the element is a submit button inside a small form
                // whose action carries the operation URL (destructive ops are POST-only).
                const form = el.closest('form');
                const postAction = form && form.method && form.method.toLowerCase() === 'post' ? form.getAttribute('action') : null;
                if (!postAction) {
                    // Fallback: legacy plain-link flow (e.g. export links)
                    const href = el.getAttribute('href');
                    if (!href) return;
                    if (!window.Swal) {
                        window.location.href = href;
                        return;
                    }
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: confirmText
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                    return;
                }
                if (!window.Swal) {
                    form.submit();
                    return;
                }
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmText
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    }

    attachSwalConfirm('.js-confirm-delete', 'Delete record?', 'This action cannot be undone.', 'Yes, delete');
    attachSwalConfirm('.js-confirm-export', 'Export CSV?', 'A CSV file will be generated and downloaded.', 'Yes, export');

    // Inline validation hints
    document.querySelectorAll('[data-validate-phone="true"]').forEach(function (el) {
        el.addEventListener('input', function () {
            const ok = /^[0-9+\s()\-]{7,20}$/.test(el.value.trim()) || el.value.trim() === '';
            el.setCustomValidity(ok ? '' : 'Invalid contact format');
        });
    });
    document.querySelectorAll('[data-validate-date="true"]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (!el.value) return;
            const chosen = new Date(el.value + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            el.setCustomValidity(chosen > today ? 'Date cannot be in the future' : '');
        });
    });

    // Toast + Undo
    const params = new URLSearchParams(window.location.search);
    if (window.Swal) {
        if (params.get('deleted') === '1') {
            const deletedId = params.get('deleted_item_id');
            if (deletedId) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: true,
                    confirmButtonText: 'Undo',
                    showCloseButton: true,
                    timer: 6000,
                    timerProgressBar: true,
                    icon: 'warning',
                    title: 'Record deleted'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        // Restore is POST-only now — build and submit a one-off form
                        const restoreForm = document.createElement('form');
                        restoreForm.method = 'POST';
                        restoreForm.action = '../actions/restore_deleted.php';
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        idInput.value = deletedId;
                        restoreForm.appendChild(idInput);
                        document.body.appendChild(restoreForm);
                        restoreForm.submit();
                    }
                });
            }
        } else if (params.get('restored') === '1') {
            Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Record restored' });
        } else if (params.get('success') === '1' || params.get('saved') === '1') {
            Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: 'success', title: 'Operation completed' });
        }
    }
});
