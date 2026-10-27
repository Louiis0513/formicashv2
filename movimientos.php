<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$mes = mesValido($_GET['mes'] ?? null);
$tipoFiltro = $_GET['tipo'] ?? '';
if (!in_array($tipoFiltro, ['', 'ingreso', 'gasto'], true)) {
    $tipoFiltro = '';
}

if (isset($_GET['eliminar'])) {
    $idEliminar = (int) $_GET['eliminar'];
    $antes = cargarMovimientoUsuario($conexion, $idEliminar, $idUsuario);
    if ($antes) {
        $stmt = $conexion->prepare('DELETE FROM movimiento WHERE id_movimiento = ? AND id_usuario = ?');
        $stmt->bind_param('ii', $idEliminar, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) {
            cacheParcheMovimiento($conexion, $idUsuario, $antes, null);
        }
        guardarMensaje($ok ? 'Movimiento eliminado.' : 'No se pudo eliminar.', $ok ? 'ok' : 'error');
        $redirMes = mesDeFecha((string) $antes['fecha']);
    } else {
        guardarMensaje('No se pudo eliminar.', 'error');
        $redirMes = $mes;
    }
    $qs = 'mes=' . urlencode($redirMes);
    if ($tipoFiltro !== '') {
        $qs .= '&tipo=' . urlencode($tipoFiltro);
    }
    header('Location: movimientos.php?' . $qs);
    exit;
}

$movimientos = obtenerMovimientosMes($conexion, $idUsuario, $mes, $tipoFiltro);

$totalIn = 0.0;
$totalOut = 0.0;
foreach ($movimientos as $m) {
    if ($m['tipo'] === 'ingreso') {
        $totalIn += (float) $m['monto'];
    } else {
        $totalOut += (float) $m['monto'];
    }
}

$titulo = 'Movimientos';
$subtitulo = nombreMes($mes) . ' · ' . count($movimientos) . ' registros';
$topbarExtra = '<a class="btn btn-primary" href="movimiento_form.php">+ Nuevo</a>';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <div class="panel-header">
                <h2>Filtrar movimientos</h2>
                <a class="btn btn-ghost btn-sm" href="resumen.php?mes=<?= e(urlencode($mes)) ?>">Ir al resumen →</a>
            </div>
            <form class="filters" method="get" action="movimientos.php">
                <div class="form-group">
                    <label for="mes">Mes</label>
                    <input class="form-control" type="month" id="mes" name="mes" value="<?= e($mes) ?>">
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="" <?= $tipoFiltro === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="ingreso" <?= $tipoFiltro === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                        <option value="gasto" <?= $tipoFiltro === 'gasto' ? 'selected' : '' ?>>Gasto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-secondary">Aplicar filtros</button>
                </div>
            </form>
            <?php if ($movimientos): ?>
                <p class="muted" style="margin:0;">
                    En pantalla: <strong class="money ingreso"><?= formatoPesos($totalIn) ?></strong> ingresos ·
                    <strong class="money gasto"><?= formatoPesos($totalOut) ?></strong> gastos
                </p>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Lista del mes</h2>
                <a class="btn btn-primary" href="movimiento_form.php">+ Nuevo movimiento</a>
            </div>

            <?php if (!$movimientos): ?>
                <div class="empty-state">
                    <img src="img/empty-movimientos.svg" alt="">
                    <h3>No hay movimientos con esos filtros</h3>
                    <p>Prueba otro mes o tipo, o crea un movimiento nuevo para empezar el flujo.</p>
                    <a class="btn btn-primary" href="movimiento_form.php">Registrar movimiento</a>
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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td data-label="Fecha"><?= e($m['fecha']) ?></td>
                                <td data-label="Descripción"><?= e($m['descripcion']) ?></td>
                                <td data-label="Categoría"><?= e($m['categoria']) ?></td>
                                <td data-label="Tipo"><span class="badge badge-<?= e($m['tipo']) ?>"><?= e($m['tipo']) ?></span></td>
                                <td data-label="Monto" class="money <?= e($m['tipo']) ?>"><?= formatoPesos($m['monto']) ?></td>
                                <td data-label="Acciones" class="actions">
                                    <a class="btn btn-secondary btn-sm" href="movimiento_form.php?id=<?= (int) $m['id_movimiento'] ?>">Editar</a>
                                    <a class="btn btn-danger btn-sm" href="movimientos.php?eliminar=<?= (int) $m['id_movimiento'] ?>&mes=<?= e(urlencode($mes)) ?><?= $tipoFiltro !== '' ? '&tipo=' . e(urlencode($tipoFiltro)) : '' ?>" onclick="return confirm('¿Eliminar este movimiento?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
