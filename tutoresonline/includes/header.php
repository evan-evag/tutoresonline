<?php $u = usuario_actual(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Inicio') ?> · TutoresOnLine</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap topbar__in">
    <a class="logo" href="index.php">TUTORES ON_LINE</a>
    <nav class="menu">
      <a href="index.php">Inicio</a>
      <a href="buscar.php">Materias</a>
      <a href="mapa.php">Mapa</a>
      <?php if ($u): ?><a href="mis_clases.php">Mis clases</a><?php endif; ?>
    </nav>
    <div class="acciones">
      <?php if ($u): ?>
        <a class="usuario" href="perfil_editar.php"><img class="avatar avatar--s" src="<?= foto_url($u['foto']) ?>" alt=""><?= e($u['nombre']) ?></a>
        <form method="post" action="logout.php">
          <?= csrf_campo() ?>
          <button class="btn btn--claro" type="submit">Salir</button>
        </form>
      <?php else: ?>
        <a class="btn btn--claro" href="login.php">Ingresar</a>
        <a class="btn" href="registro.php">Crear cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="wrap">
<?php mostrar_flash(); ?>
