/**
 * FeyFay Media - Admin panel JavaScript
 * Responsive nav toggle + confirm delete
 */
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
