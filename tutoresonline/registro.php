<?php
require_once __DIR__ . '/includes/init.php';
if (usuario_actual()) redirigir('index.php');

$errores = [];
$datos   = ['nombre' => '', 'correo' => '', 'rol' => 'estudiante'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $datos['nombre'] = trim($_POST['nombre'] ?? '');
    $datos['correo'] = trim($_POST['correo'] ?? '');
    $datos['rol']    = ($_POST['rol'] ?? '') === 'profesor' ? 'profesor' : 'estudiante';
    $clave           = $_POST['clave'] ?? '';

    if (mb_strlen($datos['nombre']) < 3)                    $errores[] = 'Escribe tu nombre completo.';
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
    if (strlen($clave) < 8)                                 $errores[] = 'La contraseña debe tener al menos 8 caracteres.';

    if (!$errores) {
        $pdo = conectar();
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE correo = :c');
        $st->execute([':c' => $datos['correo']]);
        if ($st->fetch()) {
            $errores[] = 'Ese correo ya está registrado.';
        } else {
            $pdo->beginTransaction();
            $st = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (:n, :c, :h, :r)');
            $st->execute([
                ':n' => $datos['nombre'], ':c' => $datos['correo'],
                ':h' => password_hash($clave, PASSWORD_DEFAULT), ':r' => $datos['rol'],
            ]);
            $id = (int)$pdo->lastInsertId();
            if ($datos['rol'] === 'profesor') {
                $pdo->prepare('INSERT INTO profesores (usuario_id) VALUES (:id)')->execute([':id' => $id]);
            }
            $pdo->commit();
            flash('Cuenta creada. Ya puedes iniciar sesión.');
            redirigir('login.php');
        }
    }
}

$titulo = 'Crear cuenta';
include __DIR__ . '/includes/header.php';
?>
<section class="caja caja--centro">
  <h2>Crea una cuenta</h2>
  <?php foreach ($errores as $m): ?><div class="aviso aviso--error"><?= e($m) ?></div><?php endforeach; ?>
  <form method="post" class="form">
    <?= csrf_campo() ?>
    <label>Nombre completo
      <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" required>
    </label>
    <label>Correo electrónico
      <input type="email" name="correo" value="<?= e($datos['correo']) ?>" required>
    </label>
    <label>Contraseña (mínimo 8 caracteres)
      <input type="password" name="clave" minlength="8" required>
    </label>
    <label>Quiero
      <select name="rol">
        <option value="estudiante" <?= $datos['rol'] === 'estudiante' ? 'selected' : '' ?>>Encontrar un profesor</option>
        <option value="profesor" <?= $datos['rol'] === 'profesor' ? 'selected' : '' ?>>Dar clases</option>
      </select>
    </label>
    <button class="btn btn--bloque" type="submit">Crear cuenta</button>
  </form>
  <p class="suave">¿Ya tienes cuenta? <a href="login.php">Ingresa</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
