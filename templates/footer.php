</div> <!-- end .container -->

    <!-- Generic Modal Structure -->
    <div id="generic-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 5px; position: relative;">
            <span class="close-btn" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <div id="modal-body">
                <!-- Modal content will be injected here -->
            </div>
        </div>
    </div>

    <script src="<?= site_url('public/js/main.js'); ?>"></script>

    <!-- Floating Language Switcher -->
    <div class="language-switcher">
        <a id="lang-en" href="#">EN</a> | <a id="lang-fa" href="#">FA</a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function setLangUrl(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            return url.toString();
        }
        document.getElementById('lang-en').href = setLangUrl('en');
        document.getElementById('lang-fa').href = setLangUrl('fa');
    });
    </script>
</body>
</html>
