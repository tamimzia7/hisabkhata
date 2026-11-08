@once
<script>
    (function () {
        var storageKey = 'theme-dark';
        var root = document.documentElement;

        function storedPreference() {
            try {
                var value = localStorage.getItem(storageKey);

                if (value === null) {
                    return null;
                }

                if (value === 'true' || value === '1') {
                    return true;
                }

                if (value === 'false' || value === '0') {
                    return false;
                }

                return null;
            } catch (error) {
                return null;
            }
        }

        function systemPrefersDark() {
            return window.matchMedia
                ? window.matchMedia('(prefers-color-scheme: dark)').matches
                : true;
        }

        function applyTheme(isDark, persist) {
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';

            if (persist) {
                try {
                    localStorage.setItem(storageKey, isDark ? 'true' : 'false');
                } catch (error) {
                    // Storage can be unavailable in private browsing; the theme
                    // still works for the current page.
                }
            }

            window.dispatchEvent(new CustomEvent('hisab-theme-changed', {
                detail: { dark: isDark },
            }));
        }

        var stored = storedPreference();
        applyTheme(stored === null ? systemPrefersDark() : stored, false);

        window.hisabTheme = {
            isDark: function () {
                return root.classList.contains('dark');
            },
            set: function (isDark) {
                applyTheme(Boolean(isDark), true);
            },
            toggle: function () {
                applyTheme(! root.classList.contains('dark'), true);
            },
        };

        var media = window.matchMedia
            ? window.matchMedia('(prefers-color-scheme: dark)')
            : null;

        if (media) {
            var onSystemChange = function (event) {
                if (storedPreference() === null) {
                    applyTheme(event.matches, false);
                }
            };

            if (media.addEventListener) {
                media.addEventListener('change', onSystemChange);
            } else if (media.addListener) {
                media.addListener(onSystemChange);
            }
        }
    })();
</script>
@endonce
