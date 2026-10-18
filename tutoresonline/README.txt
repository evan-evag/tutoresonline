TUTORES ON_LINE - Web en PHP + MySQL

REQUISITOS
- XAMPP (Apache + PHP 8.1 o superior + MySQL/MariaDB). Alternativa: Laragon.

INSTALACION (Windows con XAMPP)
1. Abre el panel de XAMPP y pulsa Start en Apache y en MySQL.
2. Copia la carpeta "tutoresonline" dentro de C:\xampp\htdocs\
3. Abre http://localhost/phpmyadmin
   - Pestana "Importar" > elige database/tutoresonline.sql > Importar.
   - Se crea la base "tutoresonline" con tablas y datos de prueba.
4. Abre config/db.php y confirma usuario y clave de MySQL
   (XAMPP: usuario root, clave vacia).
5. Entra a http://localhost/tutoresonline/

USUARIOS DE PRUEBA (clave de todos: Clave1234)
- Estudiante: maria@demo.com, carlos@demo.com
- Profesor:   alejandro@demo.com, valeria@demo.com, santiago@demo.com

ESTRUCTURA
- config/db.php        Conexion PDO a MySQL
- includes/init.php    Sesion, CSRF, subida de archivos, consultas comunes
- includes/header.php y footer.php   Plantilla compartida
- index.php            Inicio: categorias, formulario de busqueda, recomendados
- buscar.php           Resultados filtrados (materia, horario, ubicacion)
- profesor.php         Perfil, disponibilidad, reservar y resenas
- reservar.php         Valida disponibilidad y cruces, guarda la reserva
- resena.php           Guarda la resena (solo clases completadas)
- mis_clases.php       Reservas del estudiante o del profesor
- perfil_editar.php    Datos, foto, materias y disponibilidad
- mapa.php             Mapa con los profesores (Leaflet + OpenStreetMap)
- login.php, registro.php, logout.php
- uploads/             Fotos de perfil (bloqueada la ejecucion de PHP)
- database/tutoresonline.sql   Esquema + datos de prueba

DONDE SE CUMPLE LO QUE PIDE LA ENTREGA
- Sesiones:  includes/init.php (session_start), login.php (session_regenerate_id), logout.php
- Cookies:   login.php (correo_recordado), buscar.php (ultima_materia)
- Archivos:  includes/init.php (guardar_foto / borrar_foto), perfil_editar.php
