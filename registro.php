<?php
require_once __DIR__ . '/includes/funciones.php';

if (usuarioLogueado()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $conexion->prepare('SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $error = 'Ese email ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare('INSERT INTO usuario (email, nombre, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $email, $nombre, $hash);
            if ($stmt->execute()) {
                $id = (int) $stmt->insert_id;
                $stmt->close();
                sembrarCategoriasUsuario($conexion, $id);
                $_SESSION['usuario'] = [
                    'id' => $id,
                    'email' => $email,
                    'nombre' => $nombre,
                ];
                guardarMensaje('Cuenta creada. Ya tienes categorías listas: registra tu primer movimiento.');
                header('Location: index.php');
                exit;
            }
            $stmt->close();
            $error = 'No se pudo crear la cuenta.';
        }
    }
}

$titulo = 'Registro';
require __DIR__ . '/includes/header.php';
?>
        <h2>Crear cuenta</h2>
        <p class="subtitle">Organiza ingresos y gastos en pesos colombianos desde el día uno.</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="registro.php" autocomplete="on">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input class="form-control" type="text" id="nombre" name="nombre" required value="<?= e($_POST['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password2">Confirmar contraseña</label>
                <input class="form-control" type="password" id="password2" name="password2" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Crear mi cuenta</button>
        </form>
        <p class="auth-links">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
