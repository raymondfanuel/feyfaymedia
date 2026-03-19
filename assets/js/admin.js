/**
 * FeyFay Media - Admin panel JavaScript
 * Responsive nav toggle + confirm delete
 */
(function() {
    'use strict';

    if (window.FeyFayToast) return;

    function ensureContainer() {
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        return container;
    }

    function normalizeType(type) {
        var allowed = ['success', 'error', 'info', 'warning'];
        return allowed.indexOf(type) !== -1 ? type : 'info';
    }

    window.FeyFayToast = {
        show: function(message, type, timeoutMs) {
            if (!message) return;
            var container = ensureContainer();
            var toast = document.createElement('div');
            var toastType = normalizeType(String(type || 'info').toLowerCase());
            toast.className = 'toast toast-' + toastType;
            toast.setAttribute('role', 'status');

            var text = document.createElement('div');
            text.className = 'toast-message';
            text.textContent = String(message);
            toast.appendChild(text);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'toast-close';
            closeBtn.setAttribute('aria-label', 'Close notification');
            closeBtn.textContent = 'x';
            closeBtn.addEventListener('click', function() {
                toast.classList.remove('is-visible');
                setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 160);
            });
            toast.appendChild(closeBtn);

            container.appendChild(toast);
            requestAnimationFrame(function() { toast.classList.add('is-visible'); });

            var delay = typeof timeoutMs === 'number' ? timeoutMs : 3800;
            setTimeout(function() {
                toast.classList.remove('is-visible');
                setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 180);
            }, delay);
        }
    };
})();

(function() {
    'use strict';

    // Admin navbar toggle (mobile/tablet)
    var adminNavToggle = document.getElementById('adminNavToggle');
    var adminNav = document.getElementById('adminNav');
    if (adminNavToggle && adminNav) {
        function closeAdminNav() {
            adminNav.classList.remove('is-open');
            adminNavToggle.classList.remove('is-open');
            adminNavToggle.setAttribute('aria-expanded', 'false');
        }
        adminNavToggle.addEventListener('click', function() {
            var isOpen = adminNav.classList.toggle('is-open');
            adminNavToggle.classList.toggle('is-open', isOpen);
            adminNavToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        adminNav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', closeAdminNav);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && adminNav.classList.contains('is-open')) closeAdminNav();
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
        });
    });
})();
