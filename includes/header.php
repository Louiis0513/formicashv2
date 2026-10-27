<?php
require_once __DIR__ . '/funciones.php';
$paginaActual = basename($_SERVER['PHP_SELF']);
$authPages = ['login.php', 'registro.php'];
$esAuth = in_array($paginaActual, $authPages, true);
$subtitulo = $subtitulo ?? '';
$nombreUsuario = $_SESSION['usuario']['nombre'] ?? '';
$iniciales = '';
if ($nombreUsuario !== '') {
    $partes = preg_split('/\s+/', trim($nombreUsuario));
    $iniciales = strtoupper(mb_substr($partes[0], 0, 1) . (isset($partes[1]) ? mb_substr($partes[1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= isset($titulo) ? e($titulo) . ' · ' : '' ?>FormiCash</title>
    <link rel="icon" href="img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="<?= $esAuth ? 'auth-page' : 'app-page' ?>">
<?php if (!$esAuth && usuarioLogueado()): ?>
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img class="brand-logo" src="img/logo.svg" alt="FormiCash">
        <div>
            <span class="brand-name">FormiCash</span>
            <span class="brand-tag">Finanzas claras</span>
        </div>
    </div>
    <nav class="sidebar-nav" aria-label="Principal">
        <a href="index.php" class="<?= in_array($paginaActual, ['index.php', 'dashboard.php'], true) ? 'active' : '' ?>">
            <?= iconoSvg('icon-dashboard') ?> Dashboard
        </a>
        <a href="movimientos.php" class="<?= in_array($paginaActual, ['movimientos.php', 'movimiento_form.php'], true) ? 'active' : '' ?>">
            <?= iconoSvg('icon-movimientos') ?> Movimientos
        </a>
        <a href="categorias.php" class="<?= $paginaActual === 'categorias.php' ? 'active' : '' ?>">
            <?= iconoSvg('icon-categorias') ?> Categorías
        </a>
        <a href="resumen.php" class="<?= $paginaActual === 'resumen.php' ? 'active' : '' ?>">
            <?= iconoSvg('icon-resumen') ?> Resumen
        </a>
        <a href="perfil.php" class="<?= $paginaActual === 'perfil.php' ? 'active' : '' ?>">
            <?= iconoSvg('icon-perfil') ?> Perfil
        </a>
    </nav>
    <div class="sidebar-cta">
        <p>Registra un ingreso o gasto en segundos y ve el impacto en tu saldo.</p>
        <a class="btn btn-primary" href="movimiento_form.php">+ Nuevo movimiento</a>
    </div>
    <div class="sidebar-footer">
        <div class="user-chip">
            <span class="user-avatar"><?= e($iniciales ?: 'U') ?></span>
            <div class="user-meta">
                <strong><?= e($nombreUsuario) ?></strong>
                <span>Cuenta activa</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Salir</a>
    </div>
</aside>
<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <img src="img/icon-menu.svg" alt="">
            </button>
            <div>
                <h1><?= isset($titulo) ? e($titulo) : 'FormiCash' ?></h1>
                <?php if ($subtitulo !== ''): ?>
                    <p class="topbar-sub"><?= e($subtitulo) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="topbar-actions">
            <?php if (!empty($topbarExtra)): ?>
                <?= $topbarExtra ?>
            <?php endif; ?>
        </div>
    </header>
    <div class="content-wrap">
<?php
$flash = obtenerMensaje();
if ($flash):
?>
        <div class="alert alert-<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div>
<?php endif; ?>
<?php else: ?>
<main class="auth-main">
    <div class="auth-shell">
        <aside class="auth-visual">
            <div>
                <div class="brand-row">
                    <img class="brand-logo" src="img/logo.svg" alt="">
                    <span class="brand-name">FormiCash</span>
                </div>
                <h2>Tu dinero, explicado con claridad.</h2>
                <p>Registra movimientos, entiende categorías y toma mejores decisiones mes a mes.</p>
            </div>
            <img class="hero" src="img/hero-finanzas.jpg" alt="Ilustración de finanzas personales">
        </aside>
        <div class="auth-card">
<?php
$flash = obtenerMensaje();
if ($flash):
?>
        <div class="alert alert-<?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div>
<?php endif; ?>
<?php endif; ?>
