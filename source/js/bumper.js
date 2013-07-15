(function($){

    $.fn.bumper = function(options){

        var defaults = {
            animate: false,
            viewportPadding: 10
        };
        var settings = $.extend(defaults, options);

        var target = this;
        var body = $('body');
        var currentTop;

        // Add animation, if need be
        if (settings.animate) {
            target.css('webkitTransition', 'top .25s ease-in-out');
        }

        // Set initial position just in case it's different than the regular css position
        updatePosition();

        // Update position on scroll
        $(window).on('scroll', updatePosition);

        function updatePosition() {
            // TODO: Update to use translateY instead of top in modern browsers
            var top = Math.min((body.height() - window.scrollY - (settings.bottom.height() + target.height() + settings.fromBottom)), Math.max(settings.viewportPadding, settings.top.height() + settings.fromTop - window.scrollY));
            if (top != currentTop) {
                target.css('top', top);
                currentTop = top;
            }
        }

    }

}(jQuery));
