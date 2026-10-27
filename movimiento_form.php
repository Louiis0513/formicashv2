<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = $id > 0;

$movimiento = [
    'descripcion' => '',
    'monto' => '',
    'tipo' => 'gasto',
    'fecha' => date('Y-m-d'),
    'id_categoria' => '',
];
$antes = null;

if ($editando) {
    $antes = cargarMovimientoUsuario($conexion, $id, $idUsuario);
    if (!$antes) {
        guardarMensaje('Movimiento no encontrado.', 'error');
        header('Location: movimientos.php');
        exit;
    }
    $movimiento = $antes;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto = str_replace(',', '.', trim($_POST['monto'] ?? ''));
    $tipo = $_POST['tipo'] ?? 'gasto';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);

    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        $tipo = 'gasto';
    }

    $movimiento = [
        'descripcion' => $descripcion,
        'monto' => $monto,
        'tipo' => $tipo,
        'fecha' => $fecha,
        'id_categoria' => $idCategoria,
    ];

    if ($descripcion === '' || $monto === '' || $fecha === '' || $idCategoria <= 0) {
        $error = 'Completa todos los campos.';
    } elseif (!is_numeric($monto) || (float) $monto <= 0) {
        $error = 'El monto debe ser un número mayor a 0.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $error = 'Fecha inválida.';
    } else {
        $stmt = $conexion->prepare(
            'SELECT id_categoria FROM categoria WHERE id_categoria = ? AND id_usuario = ? AND tipo = ? LIMIT 1'
        );
        $stmt->bind_param('iis', $idCategoria, $idUsuario, $tipo);
        $stmt->execute();
        $catOk = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$catOk) {
            $error = 'La categoría no es válida para ese tipo.';
        } else {
            $montoFloat = (float) $monto;
            if ($editando) {
                $stmt = $conexion->prepare(
                    'UPDATE movimiento SET descripcion = ?, monto = ?, tipo = ?, fecha = ?, id_categoria = ?
                     WHERE id_movimiento = ? AND id_usuario = ?'
                );
                $stmt->bind_param('sdssiii', $descripcion, $montoFloat, $tipo, $fecha, $idCategoria, $id, $idUsuario);
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO movimiento (descripcion, monto, tipo, fecha, id_categoria, id_usuario)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('sdssii', $descripcion, $montoFloat, $tipo, $fecha, $idCategoria, $idUsuario);
            }

            if ($stmt->execute()) {
                if (!$editando) {
                    $id = (int) $stmt->insert_id;
                }
                $stmt->close();
                $despues = cargarMovimientoUsuario($conexion, $id, $idUsuario);
                cacheParcheMovimiento($conexion, $idUsuario, $antes, $despues);
                guardarMensaje($editando ? 'Movimiento actualizado.' : 'Movimiento creado. Revisa el dashboard o el resumen.');
                $redirMes = $despues ? mesDeFecha((string) $despues['fecha']) : date('Y-m');
                header('Location: movimientos.php?mes=' . urlencode($redirMes));
                exit;
            }
            $stmt->close();
            $error = 'No se pudo guardar el movimiento.';
        }
    }
}

$tipoActual = $movimiento['tipo'] ?? 'gasto';
$categorias = obtenerCategoriasForm($conexion, $idUsuario);

$titulo = $editando ? 'Editar movimiento' : 'Nuevo movimiento';
$subtitulo = 'Montos en pesos colombianos (COP)';
$topbarExtra = '<a class="btn btn-ghost" href="movimientos.php">Volver a lista</a>';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!$categorias): ?>
                <div class="empty-state">
                    <img src="img/empty-categorias.svg" alt="">
                    <h3>Necesitas al menos una categoría</h3>
                    <p>Crea categorías de ingreso y gasto antes de registrar movimientos.</p>
                    <a class="btn btn-primary" href="categorias.php">Ir a categorías</a>
                </div>
            <?php else: ?>
            <form method="post" action="movimiento_form.php<?= $editando ? '?id=' . $id : '' ?>">
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <input class="form-control" type="text" id="descripcion" name="descripcion" required maxlength="200" placeholder="Ej. Mercado semanal, nómina…" value="<?= e($movimiento['descripcion']) ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="monto">Monto (COP)</label>
                        <input class="form-control" type="number" id="monto" name="monto" required min="0.01" step="0.01" placeholder="0" value="<?= e((string) $movimiento['monto']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input class="form-control" type="date" id="fecha" name="fecha" required value="<?= e($movimiento['fecha']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="gasto" <?= $tipoActual === 'gasto' ? 'selected' : '' ?>>Gasto</option>
                            <option value="ingreso" <?= $tipoActual === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_categoria">Categoría</label>
                        <select class="form-control" id="id_categoria" name="id_categoria" required>
                            <option value="">Selecciona…</option>
                            <?php foreach ($categorias as $c): ?>
                                <option
                                    value="<?= (int) $c['id_categoria'] ?>"
                                    data-tipo="<?= e($c['tipo']) ?>"
                                    <?= (int) $movimiento['id_categoria'] === (int) $c['id_categoria'] ? 'selected' : '' ?>
                                ><?= e($c['nombre']) ?> (<?= e($c['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear movimiento' ?></button>
                    <a class="btn btn-secondary" href="movimientos.php">Cancelar</a>
                    <a class="btn btn-ghost" href="resumen.php">Ver resumen</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <script>
        (function () {
            var tipo = document.getElementById('tipo');
            var cat = document.getElementById('id_categoria');
            if (!tipo || !cat) return;
            function filtrar() {
                var t = tipo.value;
                var opts = cat.querySelectorAll('option[data-tipo]');
                var firstValid = null;
                opts.forEach(function (o) {
                    var ok = o.getAttribute('data-tipo') === t;
                    o.hidden = !ok;
                    o.disabled = !ok;
                    if (ok && !firstValid) firstValid = o;
                });
                var selected = cat.options[cat.selectedIndex];
                if (!selected || selected.disabled || selected.value === '') {
                    if (firstValid) cat.value = firstValid.value;
                    else cat.value = '';
                }
            }
            tipo.addEventListener('change', filtrar);
            filtrar();
        })();
        </script>
<?php require __DIR__ . '/includes/footer.php'; ?>
