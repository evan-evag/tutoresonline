<?php
// Inicio común de todas las páginas: sesión, conexión y funciones de ayuda.
require_once __DIR__ . '/../config/db.php';

session_set_cookie_params([
    'lifetime' => 0,          // la cookie de sesión muere al cerrar el navegador
    'path'     => '/',
    'httponly' => true,       // JavaScript no puede leerla
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set('America/Bogota');

// ---------- Utilidades ----------
function e($texto): string {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}
function redirigir(string $url): never {
    header('Location: ' . $url);
    exit;
}
function flash(string $mensaje, string $tipo = 'ok'): void {
    $_SESSION['flash'] = [$mensaje, $tipo];
}
function mostrar_flash(): void {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash'];
        echo '<div class="aviso aviso--' . e($t) . '">' . e($m) . '</div>';
        unset($_SESSION['flash']);
    }
}
function foto_url(?string $foto): string {
    return $foto ? 'uploads/' . rawurlencode($foto) : 'assets/img/avatar.svg';
}
function pesos($valor): string {
    return '$' . number_format((float)$valor, 0, ',', '.');
}
function hora_corta(string $t): string {
    return substr($t, 0, 5);
}
function estrellas(float $n): string {
    $llenas = (int)round($n);
    return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
}

// ---------- Sesión y permisos ----------
function usuario_actual(): ?array {
    return $_SESSION['usuario'] ?? null;
}
function requiere_login(): array {
    $u = usuario_actual();
    if (!$u) {
        flash('Inicia sesión para continuar.', 'error');
        redirigir('login.php');
    }
    return $u;
}
function requiere_rol(string $rol): array {
    $u = requiere_login();
    if ($u['rol'] !== $rol) {
        flash('No tienes permiso para entrar ahí.', 'error');
        redirigir('index.php');
    }
    return $u;
}

// ---------- CSRF (protege los formularios POST) ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_campo(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function verificar_csrf(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Solicitud no válida. Vuelve atrás y recarga la página.');
    }
}

// ---------- Gestión de archivos (foto de perfil) ----------
function guardar_foto(array $archivo): ?string {
    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($archivo['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo subir la foto.');
    if ($archivo['size'] > 2 * 1024 * 1024) throw new RuntimeException('La foto pesa más de 2 MB.');

    // Se valida el tipo real del archivo, no solo la extensión
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');

    $nombre = bin2hex(random_bytes(12)) . '.' . $ext;   // nombre aleatorio
    if (!move_uploaded_file($archivo['tmp_name'], __DIR__ . '/../uploads/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la foto en el servidor.');
    }
    return $nombre;
}
function borrar_foto(?string $nombre): void {
    if (!$nombre) return;
    $ruta = __DIR__ . '/../uploads/' . basename($nombre);
    if (is_file($ruta)) unlink($ruta);
}

// ---------- Consultas de profesores ----------
// Filtros opcionales: materia, categoria, hora_inicio, hora_fin, ubicacion
function buscar_profesores(array $f = [], int $limite = 20): array {
    $where = ['1=1'];
    $par   = [];

    if (!empty($f['materia'])) {
        $where[] = 'EXISTS (SELECT 1 FROM profesor_materia pm JOIN materias m ON m.id = pm.materia_id
                            WHERE pm.profesor_id = p.id AND (m.nombre LIKE :mat1 OR m.categoria LIKE :mat2))';
        $par[':mat1'] = $par[':mat2'] = '%' . $f['materia'] . '%';
    }
    if (!empty($f['categoria'])) {
        $where[] = 'EXISTS (SELECT 1 FROM profesor_materia pm JOIN materias m ON m.id = pm.materia_id
                            WHERE pm.profesor_id = p.id AND m.categoria = :cat)';
        $par[':cat'] = $f['categoria'];
    }
    if (!empty($f['hora_inicio']) && !empty($f['hora_fin'])) {
        $where[] = 'EXISTS (SELECT 1 FROM disponibilidad d WHERE d.profesor_id = p.id
                            AND d.hora_inicio <= :hi AND d.hora_fin >= :hf)';
        $par[':hi'] = $f['hora_inicio'];
        $par[':hf'] = $f['hora_fin'];
    }
    if (!empty($f['ubicacion'])) {
        $where[] = 'p.direccion LIKE :ubi';
        $par[':ubi'] = '%' . $f['ubicacion'] . '%';
    }

    $sql = "SELECT p.id, u.nombre, u.foto, p.tarifa_hora, p.descripcion, p.direccion, p.modalidad, p.respuesta_min,
              COALESCE((SELECT ROUND(AVG(r.calificacion),1) FROM resenas r WHERE r.profesor_id = p.id), 0) AS promedio,
              (SELECT COUNT(*) FROM resenas r WHERE r.profesor_id = p.id) AS total_resenas,
              (SELECT GROUP_CONCAT(m.nombre SEPARATOR ', ') FROM profesor_materia pm
                 JOIN materias m ON m.id = pm.materia_id WHERE pm.profesor_id = p.id) AS materias
            FROM profesores p JOIN usuarios u ON u.id = p.usuario_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY promedio DESC, total_resenas DESC
            LIMIT " . (int)$limite;

    $st = conectar()->prepare($sql);
    $st->execute($par);
    return $st->fetchAll();
}

function tarjeta_profesor(array $p): string {
    ob_start(); ?>
    <article class="tarjeta">
      <div class="tarjeta__cab">
        <img class="avatar" src="<?= foto_url($p['foto']) ?>" alt="">
        <div>
          <h3><?= e($p['nombre']) ?></h3>
          <p class="suave"><?= e($p['materias'] ?: 'Sin materias') ?></p>
        </div>
      </div>
      <p class="nota"><span class="estrella"><?= estrellas((float)$p['promedio']) ?></span>
        <?= number_format((float)$p['promedio'], 1) ?> (<?= (int)$p['total_resenas'] ?>)</p>
      <p class="precio"><?= pesos($p['tarifa_hora']) ?> <span class="suave">/ hora</span></p>
      <a class="btn btn--bloque" href="profesor.php?id=<?= (int)$p['id'] ?>">Ver perfil</a>
    </article>
    <?php return ob_get_clean();
}
