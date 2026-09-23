

INSERT INTO Usuario (id_usuario, username, email, `password`, rol) VALUES
(1, 'juan.perez',      'juan.perez@example.com',      '$2y$10$examplehash0000000000000000000000000000000000000001', 'Usuario'),
(2, 'ana.rodriguez',   'ana.rodriguez@example.com',   '$2y$10$examplehash0000000000000000000000000000000000000002', 'Usuario'),
(3, 'marcos.silva',    'marcos.silva@example.com',    '$2y$10$examplehash0000000000000000000000000000000000000003', 'Usuario'),
(4, 'lucia.fernandez', 'lucia.fernandez@example.com', '$2y$10$examplehash0000000000000000000000000000000000000004', 'Usuario'),
(5, 'donpepe',         'contacto@donpepe.example.com','$2y$10$examplehash0000000000000000000000000000000000000005', 'Organizador'),
(6, 'feriajuanlacaze', 'feria@juanlacaze.example.com','$2y$10$examplehash0000000000000000000000000000000000000006', 'Organizador'),
(7, 'clubdeportivolacaze','club@lacaze.example.com',  '$2y$10$examplehash0000000000000000000000000000000000000007', 'Organizador'),
(8, 'admin.ivento',    'admin@ivento.example.com',    '$2y$10$examplehash0000000000000000000000000000000000000008', 'Administrador'),
(9, 'intendencia.jl',  'transito@intendencia.example.com','$2y$10$examplehash0000000000000000000000000000000000000009', 'AutoridadMunicipal'),
(10,'municipio.jl',    'municipio@intendencia.example.com','$2y$10$examplehash0000000000000000000000000000000000000010', 'AutoridadMunicipal');


INSERT INTO Organizador (id_usuario, telefono_org, nombre_org) VALUES
(5, '098111222', 'Almacén Don Pepe'),
(6, '098333444', 'Feria Vecinal Juan Lacaze'),
(7, '098555666', 'Club Deportivo Lacaze');


INSERT INTO Administrador (id_usuario, permiso) VALUES
(8, 'total');


INSERT INTO PerfilUsuario (id_usuario, telefono, ubicacion, avatar, tema) VALUES
(1, '098100200', 'Juan Lacaze', 'public/img/avatares/juan.png', 'claro'),
(2, '098300400', 'Colonia',      'public/img/avatares/ana.png',  'oscuro'),
(4, '098700800', 'Juan Lacaze', NULL,                            'claro');


INSERT INTO Categoria (id_categoria, nombre, tipo) VALUES
(1, 'Cultural',            'Recreativo'),
(2, 'Deportivo',           'Recreativo'),
(3, 'Ferias y mercados',   'Comunitario'),
(4, 'Música',              'Recreativo'),
(5, 'Gastronomía',         'Comunitario');


INSERT INTO Ubicacion (id_ubicacion, nombre_lugar, direccion, latitud, longitud) VALUES
(1, 'Plaza Artigas',            'Plaza Artigas, Juan Lacaze',            -34.43167000, -57.45028000),
(2, 'Rambla de Juan Lacaze',    'Rambla Costanera, Juan Lacaze',         -34.43500000, -57.45400000),
(3, 'Club Deportivo Lacaze',    'Av. Artigas 1234, Juan Lacaze',         -34.42950000, -57.44870000),
(4, 'Centro Cultural Municipal','18 de Julio 567, Juan Lacaze',          -34.43050000, -57.45150000);


INSERT INTO Evento (id_evento, titulo, descripcion, fecha, hora, fecha_fin, hora_fin, tipo_publicacion, precio_uyu, edad_minima, estado, imagen, latitud, longitud, id_usuario, id_categoria, id_ubicacion) VALUES
(1, 'Feria de artesanos',        'Feria de artesanos locales con música en vivo.', '2026-10-04', '10:00:00', '2026-10-04', '18:00:00', 'Evento', 0,    0,  'Aprobado', 'public/img/eventos/feria.jpg',    -34.43167000, -57.45028000, 6, 3, 1),
(2, 'Torneo de fútbol barrial',  'Torneo relámpago entre equipos de la zona.',     '2026-10-11', '09:00:00', '2026-10-11', '13:00:00', 'Evento', 0,    0,  'Aprobado', 'public/img/eventos/torneo.jpg',   -34.42950000, -57.44870000, 7, 2, 3),
(3, 'Noche de música local',     'Presentación de bandas locales en la rambla.',   '2026-10-18', '20:00:00', '2026-10-18', '23:30:00', 'Evento', 150,  0,  'Pendiente','public/img/eventos/musica.jpg',   -34.43510000, -57.45410000, 5, 4, 2),
(4, 'Feria gastronómica',        'Feria de comidas y emprendimientos locales.',    '2026-10-25', '11:00:00', '2026-10-25', '19:00:00', 'Evento', 0,    0,  'Pendiente','public/img/eventos/gastro.jpg',   -34.43060000, -57.45160000, 6, 5, 4),
(5, 'Exposición de arte vecinal','Muestra de artistas plásticos del barrio.',      '2026-11-01', '15:00:00', '2026-11-01', '20:00:00', 'Evento', 0,    0,  'Rechazado','public/img/eventos/expo.jpg',     -34.43040000, -57.45140000, 6, 1, 4);


INSERT INTO Recorrido (id_recorrido, id_evento, calle_inicio, calle_fin, descripcion, puntos) VALUES
(1, 1, 'Calle Artigas', 'Calle Rivera', 'Recorrido de la feria a lo largo de la plaza.',
 '[{"lat":-34.43167,"lng":-57.45028},{"lat":-34.43190,"lng":-57.45060},{"lat":-34.43220,"lng":-57.45090}]');


INSERT INTO CorteCalle (id_corte, id_evento, id_usuario, ciudad, descripcion, fecha_inicio, fecha_fin, recorrido, estado, fecha_solicitud) VALUES
(1, 1,    9, 'Juan Lacaze', 'Corte parcial de la Calle Artigas por la feria de artesanos.', '2026-10-04 08:00:00', '2026-10-04 19:00:00',
 '[{"lat":-34.43167,"lng":-57.45028},{"lat":-34.43190,"lng":-57.45060}]', 'Aprobado', '2026-09-20 10:00:00'),
(2, NULL, 10,'Juan Lacaze', 'Corte total de Av. Artigas por obras de bacheo municipal.',    '2026-10-15 07:00:00', '2026-10-16 18:00:00',
 '[{"lat":-34.42950,"lng":-57.44870},{"lat":-34.42990,"lng":-57.44910}]', 'Pendiente', '2026-09-21 09:30:00');


INSERT INTO EventoFavorito (id_usuario, id_evento) VALUES
(1, 1),
(1, 2),
(2, 1),
(4, 3);