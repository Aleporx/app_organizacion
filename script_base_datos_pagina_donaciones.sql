-- =====================================================================
-- Programacion Web II - Semana 8
-- Script de creacion de base de datos para el sitio web de la
-- organizacion sin fines de lucro (donaciones, campanas y voluntarios)
-- Motor: MySQL / MariaDB (XAMPP)
-- =====================================================================

DROP DATABASE IF EXISTS ong_web;
CREATE DATABASE ong_web CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE ong_web;

-- ---------------------------------------------------------------------
-- Tabla: campanas
-- Almacena las campanas de recaudacion de fondos y su meta.
-- ---------------------------------------------------------------------
CREATE TABLE campanas (
    id_campana      INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255),
    meta_monto      DECIMAL(10,2) NOT NULL,
    monto_recaudado DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_inicio    DATE NOT NULL,
    fecha_termino   DATE
);

-- ---------------------------------------------------------------------
-- Tabla: donaciones
-- Registra cada donacion asociada a una campana.
-- ---------------------------------------------------------------------
CREATE TABLE donaciones (
    id_donacion     INT AUTO_INCREMENT PRIMARY KEY,
    id_campana      INT NOT NULL,
    nombre_donante  VARCHAR(100) NOT NULL,
    email_donante   VARCHAR(100) NOT NULL,
    monto           DECIMAL(10,2) NOT NULL,
    fecha_donacion  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donacion_campana FOREIGN KEY (id_campana)
        REFERENCES campanas(id_campana)
        ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Tabla: voluntarios
-- Registra a las personas interesadas en ser voluntarias.
-- Esta tabla da soporte al issue de "gestion de voluntarios".
-- ---------------------------------------------------------------------
CREATE TABLE voluntarios (
    id_voluntario   INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL,
    area_interes    VARCHAR(100),
    fecha_registro  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Datos de ejemplo para poder probar la pagina de donaciones
-- ---------------------------------------------------------------------
INSERT INTO campanas (nombre, descripcion, meta_monto, monto_recaudado, fecha_inicio, fecha_termino)
VALUES
('Utiles escolares 2026', 'Entrega de utiles escolares a ninos de escasos recursos', 500000.00, 350000.00, '2026-03-01', '2026-08-31'),
('Reforestacion Antofagasta', 'Plantacion de arboles nativos en sectores costeros', 300000.00, 300000.00, '2026-05-01', '2026-07-31');
