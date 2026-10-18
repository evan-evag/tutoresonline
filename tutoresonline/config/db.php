<?php
// Conexión a MySQL con PDO. Cambia estos valores según tu servidor.
const DB_HOST = 'localhost';
const DB_NAME = 'tutoresonline';
const DB_USER = 'root';   // XAMPP: root
const DB_PASS = '';       // XAMPP: sin contraseña

function conectar(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                    // consultas preparadas reales
        ]);
    }
    return $pdo;
}
