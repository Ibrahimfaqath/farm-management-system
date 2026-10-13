{{--
    Runs before the body paints so a stored theme is applied without a flash
    of the wrong palette. Kept inline and dependency-free on purpose: it is the
    only script that must run synchronously in <head>.
--}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('farm-theme');
            var theme = (stored === 'light' || stored === 'black')
                ? stored
                : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'black' : 'light');

            document.documentElement.setAttribute('data-theme', theme);
        } catch (error) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>