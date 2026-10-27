<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$mes = mesValido($_GET['mes'] ?? null);

$dash = obtenerDashboardMes($conexion, $idUsuario, $mes);
$ingresos = (float) $dash['ingresos'];
$gastos = (float) $dash['gastos'];
$recientes = $dash['recientes'];

$saldo = $ingresos - $gastos;
$ahorroPct = $ingresos > 0 ? round(($saldo / $ingresos) * 100) : 0;

$titulo = 'Dashboard';
$subtitulo = 'Resumen de ' . nombreMes($mes);
$topbarExtra = '<a class="btn btn-primary" href="movimiento_form.php">+ Nuevo movimiento</a>';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <form class="filters" method="get" action="index.php">
                <div class="form-group">
                    <label for="mes">Mes a revisar</label>
                    <input class="form-control" type="month" id="mes" name="mes" value="<?= e($mes) ?>" onchange="this.form.submit()">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <a class="btn btn-ghost" href="resumen.php?mes=<?= e(urlencode($mes)) ?>">Ver por categoría →</a>
                </div>
            </form>
        </div>

        <div class="cards">
            <div class="stat-card ingreso">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-ingreso.svg" alt=""></span>
                    <div class="label">Ingresos</div>
                </div>
                <div class="value"><?= formatoPesos($ingresos) ?></div>
                <div class="stat-hint">Dinero que entró este mes</div>
            </div>
            <div class="stat-card gasto">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-gasto.svg" alt=""></span>
                    <div class="label">Gastos</div>
                </div>
                <div class="value"><?= formatoPesos($gastos) ?></div>
                <div class="stat-hint">Dinero que salió este mes</div>
            </div>
            <div class="stat-card saldo<?= $saldo < 0 ? ' negativo' : '' ?>">
                <div class="stat-head">
                    <span class="stat-icon"><img src="img/icon-saldo.svg" alt=""></span>
                    <div class="label">Saldo</div>
                </div>
                <div class="value"><?= formatoPesos($saldo) ?></div>
                <div class="stat-hint">
                    <?php if ($ingresos > 0): ?>
                        Ahorro estimado: <?= (int) $ahorroPct ?>% de ingresos
                    <?php else: ?>
                        Registra ingresos para ver tu ahorro
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="quick-links">
            <a class="quick-link" href="movimientos.php?mes=<?= e(urlencode($mes)) ?>">
                <img src="img/icon-movimientos.svg" alt="">
                <div>
                    <strong>Movimientos</strong>
                    <span>Lista completa del mes</span>
                </div>
            </a>
            <a class="quick-link" href="categorias.php">
                <img src="img/icon-categorias.svg" alt="">
                <div>
                    <strong>Categorías</strong>
                    <span>Organiza ingresos y gastos</span>
                </div>
            </a>
            <a class="quick-link" href="resumen.php?mes=<?= e(urlencode($mes)) ?>">
                <img src="img/icon-resumen.svg" alt="">
                <div>
                    <strong>Resumen</strong>
                    <span>Totales por categoría</span>
                </div>
            </a>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Últimos movimientos</h2>
                <a href="movimientos.php?mes=<?= e(urlencode($mes)) ?>">Ver todos</a>
            </div>
            <?php if (!$recientes): ?>
                <div class="empty-state">
                    <img src="img/empty-movimientos.svg" alt="">
                    <h3>Sin movimientos en <?= e(nombreMes($mes)) ?></h3>
                    <p>Empieza registrando un ingreso o un gasto para ver tu saldo en acción.</p>
                    <a class="btn btn-primary" href="movimiento_form.php">Crear primer movimiento</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recientes as $m): ?>
                            <tr>
                                <td data-label="Fecha"><?= e($m['fecha']) ?></td>
                                <td data-label="Descripción"><?= e($m['descripcion']) ?></td>
                                <td data-label="Categoría"><?= e($m['categoria']) ?></td>
                                <td data-label="Tipo"><span class="badge badge-<?= e($m['tipo']) ?>"><?= e($m['tipo']) ?></span></td>
                                <td data-label="Monto" class="money <?= e($m['tipo']) ?>">
                                    <?= $m['tipo'] === 'gasto' ? '−' : '+' ?><?= formatoPesos($m['monto']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
