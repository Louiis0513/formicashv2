-- FormiCash — esquema y datos semilla (COP, mes actual)
CREATE DATABASE IF NOT EXISTS formicash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE formicash;

DROP TABLE IF EXISTS movimiento;
DROP TABLE IF EXISTS categoria;
DROP TABLE IF EXISTS usuario;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    tipo ENUM('ingreso','gasto') NOT NULL,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_categoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(200) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    tipo ENUM('ingreso','gasto') NOT NULL,
    fecha DATE NOT NULL,
    id_categoria INT NOT NULL,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_movimiento_categoria FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria) ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario demo: admin@demo.com / Admin123!
INSERT INTO usuario (id_usuario, email, nombre, password_hash) VALUES
(1, 'admin@demo.com', 'Admin Demo', '$2y$10$XYWQ4r26UgCbeu1y1AlsvOQC0OzSb8hcWC.U6bhS6Vee0yOLK2tuS');

INSERT INTO categoria (id_categoria, nombre, tipo, id_usuario) VALUES
(1, 'Alimentación', 'gasto', 1),
(2, 'Transporte', 'gasto', 1),
(3, 'Vivienda', 'gasto', 1),
(4, 'Servicios', 'gasto', 1),
(5, 'Salud', 'gasto', 1),
(6, 'Entretenimiento', 'gasto', 1),
(7, 'Educación', 'gasto', 1),
(8, 'Compras', 'gasto', 1),
(9, 'Salario', 'ingreso', 1),
(10, 'Freelance', 'ingreso', 1),
(11, 'Transferencias', 'ingreso', 1),
(12, 'Otros ingresos', 'ingreso', 1);

-- Movimientos realistas en COP (mes actual + mes anterior)
INSERT INTO movimiento (descripcion, monto, tipo, fecha, id_categoria, id_usuario) VALUES
-- Ingresos mes actual
('Nómina quincena 1', 1850000.00, 'ingreso', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 9, 1),
('Nómina quincena 2', 1850000.00, 'ingreso', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-15')), 9, 1),
('Proyecto web freelance', 650000.00, 'ingreso', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-08')), 10, 1),
('Transferencia familiar', 150000.00, 'ingreso', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-05')), 11, 1),
('Cashback tarjeta', 28500.00, 'ingreso', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-12')), 12, 1),

-- Gastos mes actual — alimentación
('Mercado Éxito', 245000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-02')), 1, 1),
('Almuerzo corrientazo', 18000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-03')), 1, 1),
('Café y panadería', 12500.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-04')), 1, 1),
('Domicilio Rappi', 42000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-07')), 1, 1),
('Fruver barrio', 56000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-09')), 1, 1),
('Cena restaurante', 89000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-11')), 1, 1),

-- Transporte
('Recarga TransMilenio', 50000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-01')), 2, 1),
('Uber aeropuerto', 65000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-06')), 2, 1),
('Gasolina full', 135000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-10')), 2, 1),
('Parqueadero centro', 12000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-10')), 2, 1),

-- Vivienda y servicios
('Arriendo apartamento', 980000.00, 'gasto', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 3, 1),
('Administración', 185000.00, 'gasto', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 3, 1),
('Factura energía', 142000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-08')), 4, 1),
('Internet fibra', 89900.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-05')), 4, 1),
('Plan móvil', 55900.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-05')), 4, 1),
('Agua y alcantarillado', 48000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-09')), 4, 1),

-- Salud, entretenimiento, educación, compras
('Farmacia genéricos', 67500.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-04')), 5, 1),
('Consulta médica particular', 120000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-13')), 5, 1),
('Cine CineColombia', 38000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-08')), 6, 1),
('Netflix mensual', 44900.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-03')), 6, 1),
('Spotify Familiar', 27900.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-03')), 6, 1),
('Curso online UX', 189000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-07')), 7, 1),
('Libros técnicos', 95000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-12')), 7, 1),
('Ropa Outlet', 156000.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-06')), 8, 1),
('Útiles hogar Éxito', 73500.00, 'gasto', LEAST(CURDATE(), DATE_FORMAT(CURDATE(), '%Y-%m-14')), 8, 1),

-- Mes anterior (comparación)
('Nómina quincena 1 (mes ant.)', 1850000.00, 'ingreso', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 9, 1),
('Nómina quincena 2 (mes ant.)', 1850000.00, 'ingreso', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-15'), 9, 1),
('Logo para cliente', 420000.00, 'ingreso', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-10'), 10, 1),
('Arriendo (mes ant.)', 980000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 3, 1),
('Administración (mes ant.)', 185000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 3, 1),
('Mercado (mes ant.)', 268000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-05'), 1, 1),
('Gasolina (mes ant.)', 128000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-12'), 2, 1),
('Energía (mes ant.)', 138000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-08'), 4, 1),
('Internet (mes ant.)', 89900.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-05'), 4, 1),
('Concierto rock', 185000.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-18'), 6, 1),
('Farmacia (mes ant.)', 45200.00, 'gasto', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-09'), 5, 1);
