<?php
require_once __DIR__ . '/includes/init.php';
$u = requiere_rol('estudiante');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir('index.php');
verificar_csrf();

$rid    = (int)($_POST['reserva_id'] ?? 0);
$nota   = (int)($_POST['calificacion'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$coment = trim($_POST['comentario'] ?? '');

$pdo = conectar();
// Solo se puede reseñar una reserva propia, completada y sin reseña previa
$st = $pdo->prepare("SELECT v.profesor_id FROM reservas v LEFT JOIN resenas r ON r.reserva_id = v.id
                     WHERE v.id = :id AND v.estudiante_id = :e AND v.estado = 'completada' AND r.id IS NULL");
$st->execute([':id' => $rid, ':e' => $u['id']]);
$pid = $st->fetchColumn();

if (!$pid || $nota < 1 || $nota > 5 || $titulo === '') {
    flash('No se pudo publicar la reseña.', 'error');
    redirigir('mis_clases.php');
}
$pdo->prepare('INSERT INTO resenas (reserva_id, estudiante_id, profesor_id, calificacion, titulo, comentario)
               VALUES (:r, :e, :p, :n, :t, :c)')
    ->execute([':r' => $rid, ':e' => $u['id'], ':p' => $pid, ':n' => $nota, ':t' => mb_substr($titulo, 0, 100), ':c' => $coment]);

flash('Gracias por tu reseña.');
redirigir('profesor.php?id=' . (int)$pid);
