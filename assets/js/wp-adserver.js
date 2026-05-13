(function() {
    'use strict';

    var serveAds = function() {
        var placeholders = document.querySelectorAll('.wp-adserver-placeholder:not(.wp-ad-loaded)');
        if (placeholders.length === 0) return;

        placeholders.forEach(function(container) {
            container.classList.add('wp-ad-loaded');
            var zone = container.getAttribute('data-zone');
            var ajaxUrl = wpAdServer.ajaxurl;

            var xhr = new XMLHttpRequest();
            xhr.open('GET', ajaxUrl + '?action=wp_adserver_get_ad&zone=' + encodeURIComponent(zone), true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data.html) {
                        container.innerHTML = response.data.html;

                        // Execute scripts within the injected HTML
                        var scripts = container.getElementsByTagName('script');
                        var scriptsCount = scripts.length;
                        for (var i = 0; i < scriptsCount; i++) {
                            var script = document.createElement('script');
                            if (scripts[i].src) {
                                script.src = scripts[i].src;
                            } else {
                                script.textContent = scripts[i].textContent;
                            }
                            document.head.appendChild(script).parentNode.removeChild(script);
                        }
                    }
                }
            };
            xhr.send();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', serveAds);
    } else {
        serveAds();
    }
})();
