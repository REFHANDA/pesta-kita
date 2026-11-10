/**
 * Pesta Kita - Frontend Interactivity Layer
 * Fitur: Instant Search, Tab Filters, Toast Notifications, Interactive Denah Ruangan, Mobile Navigation
 */

document.addEventListener('DOMContentLoaded', function () {
    initSearchFilter();
    initTabsFilter();
    initInteractiveDenah();
    initFormsFeedback();
    initMobileDrawer();
    initFlashMessages();
});

// 1. Instant Search Filter
function initSearchFilter() {
    const searchInputs = document.querySelectorAll('#cari, .search input[type="search"]');
    searchInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            const tables = document.querySelectorAll('.table-wrap table tbody');
            const cards = document.querySelectorAll('.cards .card, .grid.two section.card .row-link, .grid.stats .stat');

            // Filter tables
            tables.forEach(tbody => {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });

            // Filter cards
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

// 2. Tab Filters
function initTabsFilter() {
    const tabsGroups = document.querySelectorAll('.tabs');
    tabsGroups.forEach(group => {
        const links = group.querySelectorAll('a');
        links.forEach(link => {
            const href = link.getAttribute('href');
            // If tab is an in-page filter (href="#" or href begins with #)
            if (href === '#' || href === '' || href.startsWith('#')) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    links.forEach(l => l.removeAttribute('aria-current'));
                    link.setAttribute('aria-current', 'page');

                    const filterText = link.innerText.trim().toLowerCase();
                    const rows = document.querySelectorAll('.table-wrap table tbody tr, .cards .card');

                    if (filterText === 'semua' || filterText === '') {
                        rows.forEach(r => r.style.display = '');
                    } else {
                        rows.forEach(r => {
                            const text = r.innerText.toLowerCase();
                            r.style.display = text.includes(filterText) ? '' : 'none';
                        });
                    }
                });
            }
        });
    });
}

// 3. Toast Notifications System
window.showToast = function (message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;pointer-events:none;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
        pointer-events:auto;
        padding:14px 18px;
        border-radius:12px;
        background:#ffffff;
        color:var(--ink, #1e1633);
        box-shadow:0 10px 30px rgba(91,42,134,0.18);
        border:1.5px solid ${type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#f5b83d')};
        display:flex;
        align-items:center;
        gap:12px;
        font-size:14px;
        font-weight:600;
        transform:translateY(20px);
        opacity:0;
        transition:all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    `;

    const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
    const iconColor = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#f5b83d');

    toast.innerHTML = `
        <span style="display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;background:${iconColor}20;color:${iconColor};font-weight:800;flex:none">${icon}</span>
        <span style="flex:1">${message}</span>
        <button style="border:none;background:transparent;cursor:pointer;color:var(--muted,#5f5672);font-size:16px;padding:0 4px">&times;</button>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    const closeBtn = toast.querySelector('button');
    const dismiss = () => {
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 250);
    };

    closeBtn.addEventListener('click', dismiss);
    setTimeout(dismiss, 4500);
};

// 4. Forms UX Feedback
function initFormsFeedback() {
    const forms = document.querySelectorAll('form[onsubmit*="return false"]');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"], button.btn:not([type="button"])');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.75';
                submitBtn.innerHTML = 'Memproses…';

                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalText;
                    window.showToast('Perubahan berhasil disimpan! (Pratinjau Frontend)', 'success');
                }, 400);
            }
        });
    });
}

// 5. Interactive Denah Ruangan (Room Layout Visualizer)
function initInteractiveDenah() {
    const denah = document.querySelector('.denah');
    const palette = document.querySelector('.palette');
    if (!denah) return;

    // Palette item buttons
    if (palette) {
        const buttons = palette.querySelectorAll('button, .btn, .pill');
        buttons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const type = btn.dataset.type || btn.innerText.toLowerCase().replace(/\s+/g, '_');
                addElementToDenah(type, btn.innerText.trim());
            });
        });
    }

    // Make existing denah elements interactive
    const elements = denah.querySelectorAll('.el');
    elements.forEach(el => makeDraggable(el, denah));
}

function addElementToDenah(type, label) {
    const denah = document.querySelector('.denah');
    if (!denah) return;

    const el = document.createElement('div');
    el.className = `el ${type}`;
    el.innerText = label;
    el.style.left = '50%';
    el.style.top = '50%';
    el.title = 'Klik dua kali untuk menghapus, seret untuk memindahkan';

    denah.appendChild(el);
    makeDraggable(el, denah);
    window.showToast(`Elemen "${label}" ditambahkan ke denah ruangan`, 'success');
}

function makeDraggable(el, container) {
    let isDragging = false;
    let startX, startY, initialLeft, initialTop;

    el.style.cursor = 'grab';
    el.style.userSelect = 'none';

    el.addEventListener('mousedown', function (e) {
        isDragging = true;
        el.style.cursor = 'grabbing';
        startX = e.clientX;
        startY = e.clientY;
        const rect = container.getBoundingClientRect();
        initialLeft = (el.offsetLeft / rect.width) * 100;
        initialTop = (el.offsetTop / rect.height) * 100;
        e.preventDefault();
    });

    window.addEventListener('mousemove', function (e) {
        if (!isDragging) return;
        const rect = container.getBoundingClientRect();
        const deltaX = ((e.clientX - startX) / rect.width) * 100;
        const deltaY = ((e.clientY - startY) / rect.height) * 100;

        let newLeft = Math.max(5, Math.min(95, initialLeft + deltaX));
        let newTop = Math.max(5, Math.min(95, initialTop + deltaY));

        el.style.left = newLeft + '%';
        el.style.top = newTop + '%';
    });

    window.addEventListener('mouseup', function () {
        if (isDragging) {
            isDragging = false;
            el.style.cursor = 'grab';
        }
    });

    // Double click to remove
    el.addEventListener('dblclick', function () {
        if (confirm('Hapus elemen ini dari denah?')) {
            el.remove();
            window.showToast('Elemen dihapus dari denah', 'info');
        }
    });
}

// 6. Mobile Drawer Navigation
function initMobileDrawer() {
    const navToggle = document.getElementById('nav-toggle');
    const overlay = document.querySelector('.overlay');

    if (overlay && navToggle) {
        overlay.addEventListener('click', () => {
            navToggle.checked = false;
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && navToggle && navToggle.checked) {
            navToggle.checked = false;
        }
    });
}

// 7. Flash Messages from Laravel Session
function initFlashMessages() {
    const flashSuccess = document.querySelector('meta[name="flash-success"]');
    const flashError = document.querySelector('meta[name="flash-error"]');
    if (flashSuccess && flashSuccess.content) {
        window.showToast(flashSuccess.content, 'success');
    }
    if (flashError && flashError.content) {
        window.showToast(flashError.content, 'error');
    }
}

