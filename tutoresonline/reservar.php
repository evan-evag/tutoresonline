<?php
require_once __DIR__ . '/includes/init.php';
$u = requiere_rol('estudiante');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir('index.php');
verificar_csrf();

$pid   = (int)($_POST['profesor_id'] ?? 0);
$fecha = $_POST['fecha'] ?? '';
$hi    = $_POST['hora_inicio'] ?? '';
$hf    = $_POST['hora_fin'] ?? '';
$vuelta = 'profesor.php?id=' . $pid;

function falla(string $msg, string $vuelta): never { flash($msg, 'error'); redirigir($vuelta); }

$fechaOk = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fechaOk || $fechaOk->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) falla('Elige una fecha válida (hoy o posterior).', $vuelta);
if (!preg_match('/^\d{2}:\d{2}$/', $hi) || !preg_match('/^\d{2}:\d{2}$/', $hf) || $hi >= $hf) falla('El horario no es válido.', $vuelta);

$pdo = conectar();
$st = $pdo->prepare('SELECT 1 FROM profesores WHERE id = :id');
$st->execute([':id' => $pid]);
if (!$st->fetch()) falla('El profesor no existe.', 'index.php');

// 1) El horario debe caber dentro de la disponibilidad de ese día de la semana
$dia = (int)date('N', strtotime($fecha));
$st = $pdo->prepare('SELECT 1 FROM disponibilidad WHERE profesor_id = :p AND dia_semana = :d AND hora_inicio <= :hi AND hora_fin >= :hf');
$st->execute([':p' => $pid, ':d' => $dia, ':hi' => $hi, ':hf' => $hf]);
if (!$st->fetch()) falla('El profesor no tiene disponibilidad en ese horario.', $vuelta);

// 2) No puede cruzarse con otra reserva activa
$st = $pdo->prepare("SELECT 1 FROM reservas WHERE profesor_id = :p AND fecha = :f AND estado IN ('pendiente','confirmada')
                     AND hora_inicio < :hf AND hora_fin > :hi");
$st->execute([':p' => $pid, ':f' => $fecha, ':hi' => $hi, ':hf' => $hf]);
if ($st->fetch()) falla('Ese horario ya está reservado. Prueba con otro.', $vuelta);

$pdo->prepare('INSERT INTO reservas (estudiante_id, profesor_id, fecha, hora_inicio, hora_fin) VALUES (:e, :p, :f, :hi, :hf)')
    ->execute([':e' => $u['id'], ':p' => $pid, ':f' => $fecha, ':hi' => $hi, ':hf' => $hf]);

flash('Reserva enviada. El profesor la confirmará pronto.');
redirigir('mis_clases.php');
