<?php
require_once __DIR__ . '/includes/funciones.php';
requiereUsuario();

$idUsuario = idUsuario();
$error = '';

$stmt = $conexion->prepare('SELECT id_usuario, email, nombre FROM usuario WHERE id_usuario = ? LIMIT 1');
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    $passwordNueva2 = $_POST['password_nueva2'] ?? '';

    if ($nombre === '' || $email === '') {
        $error = 'Nombre y email son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        $stmt = $conexion->prepare('SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario <> ? LIMIT 1');
        $stmt->bind_param('si', $email, $idUsuario);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $error = 'Ese email ya está en uso.';
        } else {
            $cambiarPass = ($passwordNueva !== '' || $passwordActual !== '' || $passwordNueva2 !== '');

            if ($cambiarPass) {
                if ($passwordActual === '' || $passwordNueva === '') {
                    $error = 'Para cambiar la contraseña indica la actual y la nueva.';
                } elseif (strlen($passwordNueva) < 6) {
                    $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                } elseif ($passwordNueva !== $passwordNueva2) {
                    $error = 'Las contraseñas nuevas no coinciden.';
                } else {
                    $stmt = $conexion->prepare('SELECT password_hash FROM usuario WHERE id_usuario = ? LIMIT 1');
                    $stmt->bind_param('i', $idUsuario);
                    $stmt->execute();
                    $hashRow = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$hashRow || !password_verify($passwordActual, $hashRow['password_hash'])) {
                        $error = 'La contraseña actual no es correcta.';
                    } else {
                        $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
                        $stmt = $conexion->prepare(
                            'UPDATE usuario SET nombre = ?, email = ?, password_hash = ? WHERE id_usuario = ?'
                        );
                        $stmt->bind_param('sssi', $nombre, $email, $nuevoHash, $idUsuario);
                        if ($stmt->execute()) {
                            $stmt->close();
                            $_SESSION['usuario']['nombre'] = $nombre;
                            $_SESSION['usuario']['email'] = $email;
                            $usuario['nombre'] = $nombre;
                            $usuario['email'] = $email;
                            guardarMensaje('Perfil y contraseña actualizados.');
                            header('Location: perfil.php');
                            exit;
                        }
                        $stmt->close();
                        $error = 'No se pudo actualizar el perfil.';
                    }
                }
            } else {
                $stmt = $conexion->prepare('UPDATE usuario SET nombre = ?, email = ? WHERE id_usuario = ?');
                $stmt->bind_param('ssi', $nombre, $email, $idUsuario);
                if ($stmt->execute()) {
                    $stmt->close();
                    $_SESSION['usuario']['nombre'] = $nombre;
                    $_SESSION['usuario']['email'] = $email;
                    $usuario['nombre'] = $nombre;
                    $usuario['email'] = $email;
                    guardarMensaje('Perfil actualizado.');
                    header('Location: perfil.php');
                    exit;
                }
                $stmt->close();
                $error = 'No se pudo actualizar el perfil.';
            }
        }
    }

    $usuario['nombre'] = $nombre;
    $usuario['email'] = $email;
}

$titulo = 'Perfil';
$subtitulo = 'Datos de tu cuenta';
require __DIR__ . '/includes/header.php';
?>
        <div class="panel">
            <div class="panel-header">
                <h2>Mis datos</h2>
                <a class="btn btn-danger btn-sm" href="logout.php">Cerrar sesión</a>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="perfil.php">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input class="form-control" type="text" id="nombre" name="nombre" required value="<?= e($usuario['nombre']) ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" required value="<?= e($usuario['email']) ?>">
                </div>
                <hr class="divider">
                <p class="muted" style="margin-top:0;font-weight:600;">Cambiar contraseña (opcional)</p>
                <div class="form-group">
                    <label for="password_actual">Contraseña actual</label>
                    <input class="form-control" type="password" id="password_actual" name="password_actual">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password_nueva">Nueva contraseña</label>
                        <input class="form-control" type="password" id="password_nueva" name="password_nueva" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="password_nueva2">Confirmar nueva</label>
                        <input class="form-control" type="password" id="password_nueva2" name="password_nueva2" minlength="6">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar perfil</button>
                    <a class="btn btn-secondary" href="index.php">Ir al dashboard</a>
                </div>
            </form>
        </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
