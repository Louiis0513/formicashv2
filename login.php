<?php
require_once __DIR__ . '/includes/funciones.php';

if (usuarioLogueado()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Completa email y contraseña.';
    } else {
        $stmt = $conexion->prepare('SELECT id_usuario, email, nombre, password_hash FROM usuario WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['usuario'] = [
                'id' => (int) $usuario['id_usuario'],
                'email' => $usuario['email'],
                'nombre' => $usuario['nombre'],
            ];
            guardarMensaje('Bienvenido, ' . $usuario['nombre'] . '. Tu panel está listo.');
            header('Location: index.php');
            exit;
        }
        $error = 'Email o contraseña incorrectos.';
    }
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
        <h2>Iniciar sesión</h2>
        <p class="subtitle">Entra para ver tu saldo, movimientos y resumen por categoría.</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="on">
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? 'admin@demo.com') ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password" required value="">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar al dashboard</button>
        </form>
        <p class="auth-links">¿No tienes cuenta? <a href="registro.php">Regístrate gratis</a></p>
        <div class="demo-chip">Demo: <code>admin@demo.com</code> / <code>Admin123!</code></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
