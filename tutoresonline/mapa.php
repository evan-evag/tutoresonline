<?php
require_once __DIR__ . '/includes/init.php';
$pdo = conectar();

// Profesores con coordenadas para mostrar en el mapa
$filas = $pdo->query('SELECT p.id, u.nombre, p.direccion, p.latitud, p.longitud, p.tarifa_hora
                      FROM profesores p JOIN usuarios u ON u.id = p.usuario_id
                      WHERE p.latitud IS NOT NULL AND p.longitud IS NOT NULL')->fetchAll();
$puntos = array_map(fn($f) => [
    'id' => (int)$f['id'], 'nombre' => $f['nombre'], 'direccion' => $f['direccion'],
    'lat' => (float)$f['latitud'], 'lng' => (float)$f['longitud'], 'tarifa' => pesos($f['tarifa_hora']),
], $filas);

$titulo = 'Mapa';
include __DIR__ . '/includes/header.php';
?>
<h1>Profesores en el mapa</h1>
<p class="suave">Toca un marcador para ver al profesor y su dirección.</p>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div id="mapa" class="mapa"></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const puntos = <?= json_encode($puntos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const mapa = L.map('mapa').setView([4.6486, -74.0628], 12);   // Bogotá
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap'
  }).addTo(mapa);

  const lista = [];
  puntos.forEach(p => {
    const caja = document.createElement('div');
    const t = document.createElement('strong'); t.textContent = p.nombre;
    const d = document.createElement('div'); d.textContent = (p.direccion || '') + ' · ' + p.tarifa + '/hr';
    const a = document.createElement('a'); a.href = 'profesor.php?id=' + p.id; a.textContent = 'Ver perfil';
    caja.append(t, d, a);
    lista.push(L.marker([p.lat, p.lng]).addTo(mapa).bindPopup(caja));
  });
  if (lista.length) mapa.fitBounds(L.featureGroup(lista).getBounds().pad(0.2));
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
