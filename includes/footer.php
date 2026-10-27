<?php
$paginaActual = basename($_SERVER['PHP_SELF']);
$authPages = ['login.php', 'registro.php'];
$esAuth = in_array($paginaActual, $authPages, true);
?>
<?php if (!$esAuth && usuarioLogueado()): ?>
    </div><!-- .content-wrap -->
</main>
<script>
(function () {
    var body = document.body;
    var toggle = document.getElementById('menuToggle');
    var overlay = document.getElementById('sidebarOverlay');
    var sidebar = document.getElementById('sidebar');
    if (!toggle || !overlay || !sidebar) return;

    function openMenu() {
        body.classList.add('sidebar-open');
        overlay.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Cerrar menú');
        var icon = toggle.querySelector('img');
        if (icon) icon.src = 'img/icon-close.svg';
    }
    function closeMenu() {
        body.classList.remove('sidebar-open');
        overlay.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Abrir menú');
        var icon = toggle.querySelector('img');
        if (icon) icon.src = 'img/icon-menu.svg';
    }
    toggle.addEventListener('click', function () {
        if (body.classList.contains('sidebar-open')) closeMenu();
        else openMenu();
    });
    overlay.addEventListener('click', closeMenu);
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMenu);
    });
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 800) closeMenu();
    });
})();
</script>
<?php else: ?>
        </div><!-- .auth-card -->
    </div><!-- .auth-shell -->
</main>
<?php endif; ?>
</body>
</html>
