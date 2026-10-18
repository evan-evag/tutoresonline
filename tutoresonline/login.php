<?php
require_once __DIR__ . '/includes/init.php';
if (usuario_actual()) redirigir('index.php');

$error  = '';
// Cookie: recuerda el correo del último inicio de sesión
$correo = $_COOKIE['correo_recordado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    $st = conectar()->prepare('SELECT id, nombre, correo, password_hash, rol, foto FROM usuarios WHERE correo = :c');
    $st->execute([':c' => $correo]);
    $fila = $st->fetch();

    if ($fila && password_verify($clave, $fila['password_hash'])) {
        session_regenerate_id(true);   // evita fijación de sesión
        unset($fila['password_hash']);
        $_SESSION['usuario'] = $fila;

        if (!empty($_POST['recordar'])) {
            setcookie('correo_recordado', $correo, [
                'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/',
                'httponly' => true, 'samesite' => 'Lax',
            ]);
        } else {
            setcookie('correo_recordado', '', time() - 3600, '/');
        }
        redirigir('index.php');
    }
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Ingresar';
include __DIR__ . '/includes/header.php';
?>
<section class="caja caja--centro">
  <h1 class="logo logo--grande">TUTORES ON_LINE</h1>
  <h2>Inicia sesión</h2>
  <p class="suave">Ingresa con tu correo y contraseña.</p>
  <?php if ($error): ?><div class="aviso aviso--error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_campo() ?>
    <label>Correo electrónico
      <input type="email" name="correo" value="<?= e($correo) ?>" placeholder="correo@dominio.com" required>
    </label>
    <label>Contraseña
      <input type="password" name="clave" required>
    </label>
    <label class="check"><input type="checkbox" name="recordar" <?= $correo ? 'checked' : '' ?>> Recordar mi correo</label>
    <button class="btn btn--bloque" type="submit">Continuar</button>
  </form>
  <p class="suave">¿No tienes cuenta? <a href="registro.php">Crea una</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
