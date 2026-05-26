(function () {
    'use strict';

    var slider = document.getElementById('mc-room-slider');
    var countEl = document.getElementById('mc-room-count');
    var tierCards = document.querySelectorAll('.mc-tier-card[data-tier]');
    var navToggle = document.getElementById('mc-nav-toggle');
    var navLinks = document.getElementById('mc-nav-links');

    if (window.mcMaxRooms && slider) {
        slider.max = window.mcMaxRooms;
    }

    function getTiers() {
        var tiers = [];
        tierCards.forEach(function (el) {
            tiers.push({
                min: parseInt(el.getAttribute('data-min'), 10) || 1,
                max: el.getAttribute('data-max') === '' ? null : parseInt(el.getAttribute('data-max'), 10),
                id: el.getAttribute('data-tier')
            });
        });
        return tiers;
    }

    function highlightTier(roomCount) {
        tierCards.forEach(function (el) {
            el.classList.remove('mc-tier-highlight');
        });
        var tiers = getTiers();
        var matchId = null;
        tiers.forEach(function (t) {
            if (roomCount >= t.min && (t.max === null || roomCount <= t.max)) {
                matchId = t.id;
            }
        });
        if (matchId) {
            var target = document.querySelector('.mc-tier-card[data-tier="' + matchId + '"]');
            if (target) {
                target.classList.add('mc-tier-highlight');
            }
        }
    }

    function updateRoomCount() {
        if (!slider || !countEl) {
            return;
        }
        var rooms = parseInt(slider.value, 10);
        countEl.textContent = rooms + (rooms === 1 ? ' room' : ' rooms');
        highlightTier(rooms);
    }

    if (slider) {
        slider.addEventListener('input', updateRoomCount);
        updateRoomCount();
    }

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            var open = navLinks.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    var nav = document.getElementById('mc-nav');
    if (nav) {
        window.addEventListener('scroll', function () {
            nav.classList.toggle('mc-nav-scrolled', window.scrollY > 8);
        }, { passive: true });
    }
})();
