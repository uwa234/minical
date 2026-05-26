(function ($) {
    'use strict';

    $(function () {
        var $sidebar = $('#pa-sidebar');
        var $overlay = $('#pa-sidebar-overlay');

        function closeSidebar() {
            $sidebar.removeClass('is-open');
            $overlay.removeClass('is-visible');
        }

        $('#pa-sidebar-toggle').on('click', function () {
            $sidebar.toggleClass('is-open');
            $overlay.toggleClass('is-visible');
        });

        $overlay.on('click', closeSidebar);

        $(window).on('resize', function () {
            if (window.innerWidth > 992) {
                closeSidebar();
            }
        });
    });
}(jQuery));
