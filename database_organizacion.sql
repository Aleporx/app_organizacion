CREATE DATABASE IF NOT EXISTS organizacion;
USE organizacion;

CREATE TABLE IF NOT EXISTS proyecto (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    presupuesto DECIMAL(12,2) NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE
);

CREATE TABLE IF NOT EXISTS donante (
    id_donante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    direccion VARCHAR(150),
    telefono VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS donacion (
    id_donacion INT AUTO_INCREMENT PRIMARY KEY,
    monto DECIMAL(12,2) NOT NULL,
    fecha DATE NOT NULL,
    id_proyecto INT NOT NULL,
    id_donante INT NOT NULL,
    FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
    FOREIGN KEY (id_donante) REFERENCES donante(id_donante)
);
