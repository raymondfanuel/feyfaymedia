/**
 * FeyFay Media - Public site JavaScript
 * Responsive navbar: hamburger toggle and close on link click
 */
(function() {
    'use strict';
    var navToggle = document.getElementById('navToggle');
    var mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        function closeNav() {
            mainNav.classList.remove('is-open');
            navToggle.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        }
        function openNav() {
            mainNav.classList.add('is-open');
            navToggle.classList.add('is-open');
            navToggle.setAttribute('aria-expanded', 'true');
        }
        navToggle.addEventListener('click', function() {
            if (mainNav.classList.contains('is-open')) {
                closeNav();
            } else {
                openNav();
            }
        });
        mainNav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', closeNav);
        });
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mainNav.classList.contains('is-open')) closeNav();
        });
    }
})();
