<?php
// Prueba aislada: nunca escribe en ivento ni en el hosting.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$pdo=new PDO('mysql:host=localhost;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$base='qa_demo_'.bin2hex(random_bytes(6));
function ejecutarDemo(PDO $pdo,string $sql): void {
    $s=$pdo->query($sql); while($s->nextRowset()) {} $s->closeCursor();
}
try {
    $pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4");
    $pdo->exec("USE `$base`");
    $schema=file_get_contents(__DIR__.'/../database/database.sql');
    $schema=preg_replace('/CREATE DATABASE IF NOT EXISTS ivento;|USE ivento;/','',$schema);
    ejecutarDemo($pdo,$schema);
    // El hosting conserva unicode_ci en las tablas antiguas y general_ci en las nuevas.
    foreach(['Usuario','Organizador','Administrador','Categoria','Ubicacion','Evento','Recorrido','CorteCalle','PerfilUsuario','EventoFavorito'] as $tabla) $pdo->exec("ALTER TABLE `$tabla` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach(['funciones_sociales.sql','seguridad.sql'] as $archivo) ejecutarDemo($pdo,file_get_contents(__DIR__.'/../database/'.$archivo));
    $pdo->exec("INSERT INTO Usuario(username,email,password) VALUES('Cuenta conservada','conservar@example.invalid','!sin-acceso'); INSERT INTO Categoria(nombre) VALUES('Anterior'); INSERT INTO Ubicacion(nombre_lugar,direccion) VALUES('Anterior','Anterior'); INSERT INTO Evento(titulo,fecha,hora,id_usuario,id_categoria,id_ubicacion) VALUES('Anterior','2026-10-01','10:00',1,1,1); INSERT INTO Comentario(id_usuario,id_evento,texto) VALUES(1,1,'Anterior'); INSERT INTO EventoFavorito VALUES(1,1); INSERT INTO Recorrido(id_evento) VALUES(1)");
    ejecutarDemo($pdo,file_get_contents(__DIR__.'/../database/presentacion-demo.sql'));
    $esperados=['Evento'=>10,'Usuario'=>5,'Comentario'=>12,'Valoracion'=>4,'Asistencia'=>4,'EventoFavorito'=>20,'Participacion'=>20,'Recorrido'=>1];
    foreach($esperados as $tabla=>$cantidad) {
        $actual=(int)$pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
        if($actual!==$cantidad) throw new RuntimeException("$tabla: $actual != $cantidad");
        echo "$tabla: $actual OK\n";
    }
    if((int)$pdo->query("SELECT COUNT(*) FROM Evento WHERE fecha>'2026-10-18'")->fetchColumn()!==8) throw new RuntimeException('Fechas incorrectas');
    if((int)$pdo->query("SELECT COUNT(*) FROM EventoExtra WHERE ciudad='Juan Lacaze'")->fetchColumn()!==10) throw new RuntimeException('Ciudades incorrectas');
    if((int)$pdo->query("SELECT COUNT(*) FROM Usuario WHERE email='conservar@example.invalid'")->fetchColumn()!==1) throw new RuntimeException('Cuenta previa eliminada');
    echo "Fechas, ciudad, cascadas y cuenta previa: OK\n";
} finally {
    if(preg_match('/^qa_demo_[a-f0-9]{12}$/',$base)) $pdo->exec("DROP DATABASE IF EXISTS `$base`");
}
