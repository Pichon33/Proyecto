<?php
session_start();
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$urlCanonica = $urlBase . '/index.php?ruta=home';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iVento | Encontrá tu próximo plan</title>
    <meta name="description" content="iVento centraliza la difusión de eventos, ferias y actividades recreativas en Juan Lacaze y alrededores. Descubrí qué hacer, dónde y cuándo.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($urlCanonica); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="iVento | Encontrá tu próximo plan">
    <meta property="og:description" content="Eventos, ferias y actividades recreativas de Juan Lacaze y alrededores, todo en un mismo lugar.">
    <meta property="og:image" content="<?php echo htmlspecialchars($urlBase . '/public/img/ivento-logo-horizontal.png'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($urlCanonica); ?>">
    <meta property="og:locale" content="es_UY">
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body class="pagina-inicio">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="index.php?ruta=home" class="logo-header">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>

            <nav class="navegacion-principal">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?ruta=menu">Mi espacio</a>
                <?php else: ?>
                    <a href="index.php?ruta=login">Ingresar</a>
                    <a href="index.php?ruta=registro" class="boton-nav secundario">Registrarse</a>
                <?php endif; ?>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador'): ?>
                    <a href="index.php?ruta=admin" class="boton-nav">Administración</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <section class="portada">
            <div class="texto-portada">
                <span class="etiqueta-portada">Eventos, lugares y experiencias</span>
                <h1>Encontrá tu próximo plan con iVento</h1>
                <p class="descripcion-portada">iVento reúne eventos y actividades para que descubras qué hacer, dónde y cuándo de una manera sencilla.</p>

                <div class="grupo-botones">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="index.php?ruta=mapa" class="boton principal">Explorar eventos</a>
                    <?php else: ?>
                        <a href="index.php?ruta=registro" class="boton principal">Crear cuenta</a>
                        <a href="index.php?ruta=login" class="boton secundario">Ingresar</a>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="tarjeta-portada">
                <img class="logo-portada" src="public/img/ivento-icono-app.png" alt="Icono de iVento">
                <h2>Todo en un solo lugar</h2>
                <p>Centralizá la difusión de actividades locales sin tener que buscar en muchos lados.</p>
                <ul class="lista-portada">
                    <li>Eventos cercanos y claros</li>
                    <li>Mapa fácil de usar</li>
                    <li>Tu perfil y tus datos en orden</li>
                </ul>
            </aside>
        </section>

        <section class="seccion-caracteristicas" id="caracteristicas">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">Cómo funciona</span>
                <h2>Una forma simple de organizar tus salidas</h2>
                <p>La idea es que encuentres un evento sin complicarte y tengas la información importante a mano.</p>
            </div>

            <div class="fila-tarjetas">
                <article class="tarjeta">
                    <div class="icono-tarjeta">🎉</div>
                    <h3>Descubrí</h3>
                    <p>Conocé actividades, ferias, música y propuestas de la zona.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta">📍</div>
                    <h3>Ubicate</h3>
                    <p>Usá el mapa para saber dónde se realiza cada evento.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta">🗓️</div>
                    <h3>Planificá</h3>
                    <p>Revisá la fecha, el tipo de evento y sus detalles.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta">👤</div>
                    <h3>Tu espacio</h3>
                    <p>Mantené actualizada la información básica de tu cuenta.</p>
                </article>
            </div>
        </section>

        <section class="seccion-acerca" id="nosotros">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">Sobre iVento</span>
                <h2>Hecho para acercar a las personas a sus eventos</h2>
            </div>
            <p>iVento es un servicio de ApexDigital SA, una sociedad anónima uruguaya. Nuestro objetivo es darle un espacio claro a la difusión de eventos y ayudar a que más personas encuentren experiencias cerca suyo.</p>
        </section>

        <section class="seccion-empresa" id="apexdigital">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">ApexDigital</span>
                <h2>Una plataforma pensada para Juan Lacaze y sus alrededores</h2>
                <p>ApexDigital surge para facilitar la difusión y el descubrimiento de eventos, fiestas, locales y actividades recreativas mediante una plataforma accesible y fácil de utilizar.</p>
            </div>

            <div class="empresa-cuadrilla">
                <article class="tarjeta">
                    <h3>Misión</h3>
                    <p>Brindar una plataforma práctica para promocionar eventos y conectar a los organizadores con las personas interesadas.</p>
                </article>

                <article class="tarjeta">
                    <h3>Visión</h3>
                    <p>Ser una referencia regional para encontrar actividades sociales con una experiencia moderna, amigable y útil.</p>
                </article>

                <article class="tarjeta">
                    <h3>Valores</h3>
                    <p>Trabajamos con respeto, transparencia, responsabilidad social y capacidad de mejorar a partir de nuestros errores.</p>
                </article>
            </div>
        </section>

        <section class="seccion-llamada">
            <div>
                <h2>¿Listo para encontrar algo para hacer?</h2>
                <p>Registrate y empezá a explorar los eventos disponibles.</p>
            </div>
            <a href="index.php?ruta=registro" class="boton">Registrarse</a>
        </section>
    </main>

    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="#caracteristicas">Características</a>
                <a href="#apexdigital">ApexDigital</a>
                <a href="index.php?ruta=home">Inicio</a>
            </nav>
        </div>
    </footer>
</body>
</html>
