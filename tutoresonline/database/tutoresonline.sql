-- TutoresOnLine · Base de datos (MySQL / MariaDB)
-- Importar desde phpMyAdmin (pestaña Importar) o con: mysql -u root < tutoresonline.sql

CREATE DATABASE IF NOT EXISTS tutoresonline
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tutoresonline;

DROP TABLE IF EXISTS resenas;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS disponibilidad;
DROP TABLE IF EXISTS profesor_materia;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS profesores;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  correo        VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('estudiante','profesor') NOT NULL DEFAULT 'estudiante',
  foto          VARCHAR(60) NULL,
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Datos extra de los usuarios con rol profesor (relación 1 a 1)
CREATE TABLE profesores (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT UNSIGNED NOT NULL UNIQUE,
  tarifa_hora   INT UNSIGNED NOT NULL DEFAULT 20000,
  descripcion   TEXT NULL,
  direccion     VARCHAR(200) NULL,
  latitud       DECIMAL(9,6) NULL,
  longitud      DECIMAL(9,6) NULL,
  modalidad     ENUM('presencial','virtual','ambas') NOT NULL DEFAULT 'ambas',
  respuesta_min INT UNSIGNED NOT NULL DEFAULT 30,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE materias (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(80) NOT NULL UNIQUE,
  categoria VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

-- Relación muchos a muchos: un profesor dicta varias materias
CREATE TABLE profesor_materia (
  profesor_id INT UNSIGNED NOT NULL,
  materia_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (profesor_id, materia_id),
  FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE,
  FOREIGN KEY (materia_id)  REFERENCES materias(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- dia_semana: 1 = lunes ... 7 = domingo
CREATE TABLE disponibilidad (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profesor_id INT UNSIGNED NOT NULL,
  dia_semana  TINYINT UNSIGNED NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin    TIME NOT NULL,
  CHECK (dia_semana BETWEEN 1 AND 7),
  CHECK (hora_inicio < hora_fin),
  FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservas (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT UNSIGNED NOT NULL,
  profesor_id   INT UNSIGNED NOT NULL,
  fecha         DATE NOT NULL,
  hora_inicio   TIME NOT NULL,
  hora_fin      TIME NOT NULL,
  estado        ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (hora_inicio < hora_fin),
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)    ON DELETE CASCADE,
  FOREIGN KEY (profesor_id)   REFERENCES profesores(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- Una reseña por reserva completada
CREATE TABLE resenas (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id    INT UNSIGNED NOT NULL UNIQUE,
  estudiante_id INT UNSIGNED NOT NULL,
  profesor_id   INT UNSIGNED NOT NULL,
  calificacion  TINYINT UNSIGNED NOT NULL,
  titulo        VARCHAR(100) NOT NULL,
  comentario    TEXT NULL,
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (calificacion BETWEEN 1 AND 5),
  FOREIGN KEY (reserva_id)    REFERENCES reservas(id)    ON DELETE CASCADE,
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)    ON DELETE CASCADE,
  FOREIGN KEY (profesor_id)   REFERENCES profesores(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_reservas_prof_fecha ON reservas (profesor_id, fecha);
CREATE INDEX idx_disp_prof ON disponibilidad (profesor_id, dia_semana);

-- ---------- Datos de prueba ----------
-- Todos los usuarios tienen la contraseña: Clave1234
INSERT INTO materias (nombre, categoria) VALUES
 ('Matemáticas','Matemáticas'), ('Álgebra lineal','Matemáticas'), ('Cálculo','Cálculo'),
 ('Física','Física'), ('Termodinámica','Física'), ('Química','Química'),
 ('Programación','Programación'), ('Python','Programación'), ('Inglés','Idiomas');

INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
 ('Alejandro Morales','alejandro@demo.com','$2y$10$QrWghOgahqMfuMx4lZYVlubvdD2.sTCDQTJYt4HHeyRr98PfhEIhm','profesor'),
 ('Valeria Gómez','valeria@demo.com','$2y$10$QrWghOgahqMfuMx4lZYVlubvdD2.sTCDQTJYt4HHeyRr98PfhEIhm','profesor'),
 ('Santiago Herrera','santiago@demo.com','$2y$10$QrWghOgahqMfuMx4lZYVlubvdD2.sTCDQTJYt4HHeyRr98PfhEIhm','profesor'),
 ('María Suárez','maria@demo.com','$2y$10$QrWghOgahqMfuMx4lZYVlubvdD2.sTCDQTJYt4HHeyRr98PfhEIhm','estudiante'),
 ('Carlos Peña','carlos@demo.com','$2y$10$QrWghOgahqMfuMx4lZYVlubvdD2.sTCDQTJYt4HHeyRr98PfhEIhm','estudiante');

INSERT INTO profesores (usuario_id, tarifa_hora, descripcion, direccion, latitud, longitud, modalidad, respuesta_min) VALUES
 (1, 18000, 'Ingeniero con 5 años dando clases de álgebra, cálculo y matemáticas básicas. Explico con ejemplos y ejercicios resueltos paso a paso.', 'Av. Insurgentes Sur 1234', 4.710989, -74.072092, 'ambas', 15),
 (2, 28000, 'Docente de física y termodinámica. Preparación para parciales y proyectos con enfoque práctico.', 'Calle 72 # 10-34', 4.659800, -74.058300, 'presencial', 20),
 (3, 20000, 'Desarrollador de software. Enseño programación desde cero: algoritmos, estructuras de datos y Python.', 'Carrera 15 # 85-20', 4.670000, -74.050000, 'virtual', 10);

INSERT INTO profesor_materia (profesor_id, materia_id) VALUES
 (1,1),(1,2),(1,3), (2,4),(2,5), (3,7),(3,8);

INSERT INTO disponibilidad (profesor_id, dia_semana, hora_inicio, hora_fin) VALUES
 (1,1,'08:00','12:00'),(1,3,'08:00','12:00'),(1,5,'14:00','18:00'),
 (2,2,'09:00','13:00'),(2,4,'09:00','13:00'),
 (3,1,'14:00','20:00'),(3,2,'14:00','20:00'),(3,3,'14:00','20:00'),(3,6,'09:00','13:00');

INSERT INTO reservas (estudiante_id, profesor_id, fecha, hora_inicio, hora_fin, estado) VALUES
 (4, 1, '2026-09-14', '08:00', '09:00', 'completada'),
 (5, 1, '2026-09-16', '09:00', '10:00', 'completada'),
 (4, 3, '2026-09-15', '15:00', '16:00', 'completada');

INSERT INTO resenas (reserva_id, estudiante_id, profesor_id, calificacion, titulo, comentario) VALUES
 (1, 4, 1, 5, 'Superó completamente mis expectativas', 'Explica con mucha claridad y resolvimos todas mis dudas de álgebra en una sola sesión.'),
 (2, 5, 1, 4, 'Muy buen profesor', 'Puntual y ordenado. Recomendado para cálculo.'),
 (3, 4, 3, 5, 'Excelente para empezar a programar', 'Paciente y con ejemplos claros en Python.');
