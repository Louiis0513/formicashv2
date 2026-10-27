<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$mes = mesValido($_GET['mes'] ?? null);

$filas = obtenerResumenMes($conexion, $idUsuario, $mes);

$max = 0.0;
$totalIngresos = 0.0;
$totalGastos = 0.0;
foreach ($filas as $f) {
    $t = (float) $f['total'];
    $max = max($max, $t);
    if ($f['tipo'] === 'ingreso') {
        $totalIngresos += $t;
    } else {
        $totalGastos += $t;
    }
}

$titulo = 'Resumen';
$subtitulo = 'Totales por categoría · ' . nombreMes($mes);
$topbarExtra = '<a class="btn btn-ghost" href="movimientos.php?mes=' . e(urlencode($mes)) . '">Ver movimientos</a>';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <form class="filters" method="get" action="resumen.php">
                <div class="form-group">
                    <label for="mes">Mes</label>
                    <input class="form-control" type="month" id="mes" name="mes" value="<?= e($mes) ?>" onchange="this.form.submit()">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <a class="btn btn-secondary" href="index.php?mes=<?= e(urlencode($mes)) ?>">Volver al dashboard</a>
                </div>
            </form>
        </div>

        <?php if ($filas): ?>
        <div class="cards">
            <div class="stat-card ingreso">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-ingreso.svg" alt=""></span>
                    <div class="label">Total ingresos</div>
                </div>
                <div class="value"><?= formatoPesos($totalIngresos) ?></div>
            </div>
            <div class="stat-card gasto">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-gasto.svg" alt=""></span>
                    <div class="label">Total gastos</div>
                </div>
                <div class="value"><?= formatoPesos($totalGastos) ?></div>
            </div>
            <div class="stat-card saldo<?= ($totalIngresos - $totalGastos) < 0 ? ' negativo' : '' ?>">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-saldo.svg" alt=""></span>
                    <div class="label">Saldo del mes</div>
                </div>
                <div class="value"><?= formatoPesos($totalIngresos - $totalGastos) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h2>Distribución por categoría</h2>
            </div>
            <?php if (!$filas): ?>
                <div class="empty-state">
                    <img src="img/empty-resumen.svg" alt="">
                    <h3>Sin datos para <?= e(nombreMes($mes)) ?></h3>
                    <p>Cuando registres movimientos, aquí verás barras claras por categoría.</p>
                    <a class="btn btn-primary" href="movimiento_form.php">Registrar movimiento</a>
                </div>
            <?php else: ?>
                <?php foreach ($filas as $f):
                    $total = (float) $f['total'];
                    $pct = $max > 0 ? ($total / $max) * 100 : 0;
                    $pctMes = ($f['tipo'] === 'gasto' && $totalGastos > 0)
                        ? round(($total / $totalGastos) * 100)
                        : (($f['tipo'] === 'ingreso' && $totalIngresos > 0) ? round(($total / $totalIngresos) * 100) : 0);
                ?>
                    <div class="bar-row">
                        <div class="bar-meta">
                            <span>
                                <strong><?= e($f['nombre']) ?></strong>
                                <span class="badge badge-<?= e($f['tipo']) ?>" style="margin-left:0.4rem;"><?= e($f['tipo']) ?></span>
                                <span class="muted" style="margin-left:0.45rem;font-size:0.8rem;"><?= (int) $pctMes ?>% del tipo</span>
                            </span>
                            <span class="money <?= e($f['tipo']) ?>"><?= formatoPesos($total) ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill <?= e($f['tipo']) ?>" style="width:<?= number_format($pct, 1, '.', '') ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
