<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$error = '';
$editando = null;

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    foreach (obtenerCategoriasUsuario($conexion, $idUsuario) as $c) {
        if ((int) $c['id_categoria'] === $idEditar) {
            $editando = $c;
            break;
        }
    }
    if (!$editando) {
        guardarMensaje('Categoría no encontrada.', 'error');
        header('Location: categorias.php');
        exit;
    }
}

if (isset($_GET['eliminar'])) {
    $idEliminar = (int) $_GET['eliminar'];
    $stmt = $conexion->prepare('SELECT COUNT(*) AS total FROM movimiento WHERE id_categoria = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $idEliminar, $idUsuario);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total > 0) {
        guardarMensaje('No se puede eliminar: hay movimientos con esa categoría.', 'error');
    } else {
        $stmt = $conexion->prepare('DELETE FROM categoria WHERE id_categoria = ? AND id_usuario = ?');
        $stmt->bind_param('ii', $idEliminar, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) {
            cacheParcheCategoriasUsuario($conexion, $idUsuario, $idEliminar, null, true);
        }
        guardarMensaje($ok ? 'Categoría eliminada.' : 'No se pudo eliminar.', $ok ? 'ok' : 'error');
    }
    header('Location: categorias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? 'gasto';
    $idCat = (int) ($_POST['id_categoria'] ?? 0);

    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        $tipo = 'gasto';
    }

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($idCat > 0) {
            $stmt = $conexion->prepare(
                'SELECT id_categoria, nombre, tipo FROM categoria WHERE id_categoria = ? AND id_usuario = ? LIMIT 1'
            );
            $stmt->bind_param('ii', $idCat, $idUsuario);
            $stmt->execute();
            $actual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$actual) {
                $error = 'Categoría no encontrada.';
            } else {
                $tipoCambia = $tipo !== $actual['tipo'];
                if ($tipoCambia) {
                    $stmt = $conexion->prepare(
                        'SELECT COUNT(*) AS total FROM movimiento WHERE id_categoria = ? AND id_usuario = ?'
                    );
                    $stmt->bind_param('ii', $idCat, $idUsuario);
                    $stmt->execute();
                    $usos = (int) $stmt->get_result()->fetch_assoc()['total'];
                    $stmt->close();
                    if ($usos > 0) {
                        $error = 'No puedes cambiar el tipo: hay movimientos con esta categoría. Crea otra categoría.';
                    }
                }

                if ($error === '') {
                    $stmt = $conexion->prepare(
                        'UPDATE categoria SET nombre = ?, tipo = ? WHERE id_categoria = ? AND id_usuario = ?'
                    );
                    $stmt->bind_param('ssii', $nombre, $tipo, $idCat, $idUsuario);
                    $ok = $stmt->execute();
                    $stmt->close();
                    if ($ok) {
                        cacheParcheCategoriasUsuario($conexion, $idUsuario, $idCat, $nombre, false);
                        guardarMensaje('Categoría actualizada.');
                        header('Location: categorias.php');
                        exit;
                    }
                    $error = 'No se pudo actualizar.';
                }
            }
        } else {
            $stmt = $conexion->prepare(
                'INSERT INTO categoria (nombre, tipo, id_usuario) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('ssi', $nombre, $tipo, $idUsuario);
            if ($stmt->execute()) {
                $stmt->close();
                cacheParcheCategoriasUsuario($conexion, $idUsuario);
                guardarMensaje('Categoría creada. Ya puedes usarla en movimientos.');
                header('Location: categorias.php');
                exit;
            }
            $stmt->close();
            $error = 'No se pudo crear la categoría.';
        }
    }

    if ($idCat > 0) {
        $editando = ['id_categoria' => $idCat, 'nombre' => $nombre, 'tipo' => $tipo];
    }
}

$categorias = obtenerCategoriasUsuario($conexion, $idUsuario);

$titulo = 'Categorías';
$subtitulo = 'Organiza tu dinero por tipo';
$topbarExtra = '<a class="btn btn-ghost" href="movimiento_form.php">Nuevo movimiento</a>';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <div class="panel-header">
                <h2><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h2>
                <?php if ($editando): ?>
                    <a href="categorias.php">Cancelar edición</a>
                <?php endif; ?>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="categorias.php">
                <?php if ($editando): ?>
                    <input type="hidden" name="id_categoria" value="<?= (int) $editando['id_categoria'] ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input class="form-control" type="text" id="nombre" name="nombre" required maxlength="80" placeholder="Ej. Alimentación, Salario…" value="<?= e($editando['nombre'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select class="form-control" id="tipo" name="tipo" required <?= ($editando && (int) ($editando['usos'] ?? 0) > 0) ? 'disabled' : '' ?>>
                            <option value="gasto" <?= ($editando['tipo'] ?? 'gasto') === 'gasto' ? 'selected' : '' ?>>Gasto</option>
                            <option value="ingreso" <?= ($editando['tipo'] ?? '') === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                        </select>
                        <?php if ($editando && (int) ($editando['usos'] ?? 0) > 0): ?>
                            <input type="hidden" name="tipo" value="<?= e($editando['tipo']) ?>">
                            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;">Tipo bloqueado: la categoría ya tiene movimientos.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear categoría' ?></button>
                    <a class="btn btn-ghost" href="resumen.php">Ver resumen →</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Mis categorías</h2>
            </div>
            <?php if (!$categorias): ?>
                <div class="empty-state">
                    <img src="img/empty-categorias.svg" alt="">
                    <h3>Aún no tienes categorías</h3>
                    <p>Crea al menos una de ingreso y una de gasto para clasificar tus movimientos.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Usos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td data-label="Nombre"><strong><?= e($c['nombre']) ?></strong></td>
                                <td data-label="Tipo"><span class="badge badge-<?= e($c['tipo']) ?>"><?= e($c['tipo']) ?></span></td>
                                <td data-label="Usos"><?= (int) $c['usos'] ?></td>
                                <td data-label="Acciones" class="actions">
                                    <a class="btn btn-secondary btn-sm" href="categorias.php?editar=<?= (int) $c['id_categoria'] ?>">Editar</a>
                                    <?php if ((int) $c['usos'] === 0): ?>
                                        <a class="btn btn-danger btn-sm" href="categorias.php?eliminar=<?= (int) $c['id_categoria'] ?>" onclick="return confirm('¿Eliminar esta categoría?');">Eliminar</a>
                                    <?php else: ?>
                                        <span class="muted" style="font-size:0.85rem;">En uso</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
