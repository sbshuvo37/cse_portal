/* ============================================================
   CSE Department Portal — script.js (ES6+)
   ============================================================ */

'use strict';

// ── Sidebar toggle (mobile) ──────────────────────────────────
const toggleSidebar = () => {
    document.querySelector('.sidebar')?.classList.toggle('open');
};

document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    if (window.innerWidth <= 900 && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
            sidebar.classList.remove('open');
        }
    }
});

// ── Mark active nav item ─────────────────────────────────────
const markActiveNav = () => {
    const path = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && path.endsWith(href.split('/').pop())) {
            item.classList.add('active');
        }
    });
};

// ── Toast system: converts any [data-auto-dismiss] flash message
//    into a fixed-position toast so it never shifts page layout ──
const initToasts = () => {
    const flashEls = document.querySelectorAll('[data-auto-dismiss]');
    if (flashEls.length === 0) return;

    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    flashEls.forEach(el => {
        // Determine type from existing alert-* class (success/danger/warning/info)
        const typeMatch = [...el.classList].find(c => c.startsWith('alert-'));
        const type = typeMatch ? typeMatch.replace('alert-', '') : 'info';
        const icons = { success: '✅', danger: '⚠️', warning: '⚠️', info: 'ℹ️' };
        const messageText = el.textContent.trim();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const iconSpan = document.createElement('span');
        iconSpan.textContent = icons[type] || 'ℹ️';

        const textSpan = document.createElement('span');
        textSpan.textContent = messageText; // safe: text node, not parsed as HTML

        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Dismiss');
        closeBtn.textContent = '✕';
        closeBtn.addEventListener('click', () => dismissToast(toast));

        toast.append(iconSpan, textSpan, closeBtn);

        container.appendChild(toast);
        el.remove(); // remove the original in-flow element entirely — no layout shift

        setTimeout(() => dismissToast(toast), 4500);
    });
};

const dismissToast = (toast) => {
    if (!toast || toast.classList.contains('toast-hide')) return;
    toast.classList.add('toast-hide');
    setTimeout(() => toast.remove(), 350);
};

// ── Modal controls ───────────────────────────────────────────
const openModal = (id) => {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
};

const closeModal = (id) => {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
};

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// ── Table client-side search filter ──────────────────────────
const initTableSearch = (inputId, tableId) => {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', () => {
        const val = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        let found = 0;
        rows.forEach(row => {
            const show = row.textContent.toLowerCase().includes(val);
            row.style.display = show ? '' : 'none';
            if (show) found++;
        });
        const empty = table.parentElement.querySelector('.table-empty');
        if (empty) empty.style.display = found === 0 ? 'block' : 'none';
    });
};

// ── Confirm delete / deactivate ──────────────────────────────
const confirmAction = (message) => confirm(message);

// ── Role selector (registration step 1) ──────────────────────
const initRoleSelector = () => {
    document.querySelectorAll('.role-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            const radio = opt.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            // Toggle role-specific field visibility if present
            const role = radio?.value;
            document.querySelectorAll('[data-role-fields]').forEach(el => {
                el.style.display = el.dataset.roleFields === role ? '' : 'none';
            });
        });
    });
};

// ── Profile photo preview (registration / profile update) ───
const initPhotoPreview = () => {
    document.querySelectorAll('.photo-input').forEach(input => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            const preview = document.getElementById(input.dataset.previewTarget);
            if (!file || !preview) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(file);
        });
    });
};

// ── Live total calculation (teacher result entry) ────────────
const initResultCalculator = () => {
    const fields = ['attendance', 'mid1', 'mid2', 'mid3'];
    const inputs = fields.map(f => document.querySelector(`[name="${f}"]`));
    const totalDisplay = document.getElementById('totalPreview');
    if (!totalDisplay || inputs.some(i => !i)) return;

    const recalc = () => {
        const sum = inputs.reduce((acc, el) => acc + (parseFloat(el.value) || 0), 0);
        totalDisplay.textContent = sum.toFixed(2);
        totalDisplay.className = sum >= 60 ? 'total-badge total-good' : (sum >= 40 ? 'total-badge total-ok' : 'total-badge total-low');
    };
    inputs.forEach(el => el.addEventListener('input', recalc));
    recalc();
};

// ── Simple client-side form validation ───────────────────────
const validateForm = (formId, rules) => {
    const form = document.getElementById(formId);
    if (!form) return true;
    let valid = true;

    Object.entries(rules).forEach(([field, rule]) => {
        const el = form.querySelector(`[name="${field}"]`);
        const err = form.querySelector(`#err_${field}`);
        if (!el) return;
        let msg = '';

        if (rule.required && !el.value.trim()) {
            msg = rule.required;
        } else if (rule.email && el.value && !/^\S+@\S+\.\S+$/.test(el.value)) {
            msg = rule.email;
        } else if (rule.min && el.value.length < rule.min) {
            msg = rule.min_msg || 'Too short';
        } else if (rule.match) {
            const other = form.querySelector(`[name="${rule.match}"]`);
            if (other && el.value !== other.value) msg = rule.match_msg || 'Does not match';
        }

        if (err) err.textContent = msg;
        if (msg) { valid = false; el.focus(); }
    });
    return valid;
};

// ── Chat: scroll to bottom on load ───────────────────────────
const scrollChatToBottom = () => {
    const box = document.querySelector('.chat-messages');
    if (box) box.scrollTop = box.scrollHeight;
};

// ── Init on DOM ready ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    markActiveNav();
    initToasts();
    initRoleSelector();
    initPhotoPreview();
    initResultCalculator();
    initTableSearch('searchInput', 'dataTable');
    scrollChatToBottom();
});
