CREATE DATABASE IF NOT EXISTS colegio_san_jose CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE colegio_san_jose;
CREATE TABLE IF NOT EXISTS alumnos(
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  dni VARCHAR(20),
  curso VARCHAR(10),
  division VARCHAR(10),
  foto VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notas(
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT NOT NULL,
  anio INT NOT NULL,
  materia VARCHAR(120),
  nota DECIMAL(5,2) NULL,
  promedio_manual DECIMAL(5,2) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notas_alumno FOREIGN KEY(alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE,
  INDEX idx_notas_alumno_anio (alumno_id, anio)
) ENGINE=InnoDB;
