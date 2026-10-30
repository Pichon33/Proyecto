<?php
session_start();
require_once dirname(__DIR__,3).'/includes/config.php';
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$urlCanonica = $urlBase . '/index.php?ruta=home';
?>
<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
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
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
</head>
<body class="pagina-inicio max-sm:[&_main]:pt-7 max-sm:[&_main]:pb-11 max-sm:[&_section[id]]:scroll-mt-5">
    <a class="enlace-saltar" href="#contenido">Ir al contenido</a>
    <?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>

    <main id="contenido" tabindex="-1">
        <section class="portada max-md:grid-cols-1 max-md:gap-8 max-sm:p-0 max-sm:[&_h1]:text-[clamp(2.15rem,9vw,2.6rem)]">
            <div class="texto-portada">
                <span class="etiqueta-portada">Colonia, Uruguay</span>
                <h1>Descubrí qué hacer <span>en Colonia</span></h1>
                <p class="descripcion-portada max-sm:text-[1.05rem]">Música, ferias, cultura y actividades cerca de vos. Encontrá dónde son y cuándo ir, todo en iVento.</p>

                <div class="grupo-botones max-sm:[&_.boton]:flex-auto">
                    <a href="index.php?ruta=mapa" class="boton principal">Explorar eventos</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="index.php?ruta=registro" class="boton secundario">Crear cuenta</a>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="iv-bruno-card" aria-labelledby="bruno-titulo">
                <div class="iv-bruno-presentacion">
                    <div>
                        <span class="iv-bruno-etiqueta">Conocé a Bruno</span>
                        <h2 id="bruno-titulo">Un buen plan<br>empieza por descubrir.</h2>
                        <p>Todo lo que necesitás saber antes de salir, en un solo lugar.</p>
                    </div>
                    <img src="public/img/bruno.webp" width="320" height="320" alt="Bruno, la mascota de iVento, con su lupa" fetchpriority="high">
                </div>
                <ul class="iv-bruno-detalles">
                    <li><?= iconoSvg('calendario') ?><span><strong>Elegí cuándo</strong><small>Fechas y horarios a mano.</small></span></li>
                    <li><?= iconoSvg('pin') ?><span><strong>Encontrá dónde</strong><small>La ubicación en el mapa.</small></span></li>
                    <li><?= iconoSvg('entrada') ?><span><strong>Revisá los detalles</strong><small>Precio, acceso y edades.</small></span></li>
                </ul>
                <a class="iv-bruno-enlace" href="index.php?ruta=eventos">Encontrar mi próximo plan <?= iconoSvg('flecha') ?></a>
            </aside>
        </section>

        <section class="seccion-caracteristicas max-sm:mt-11" id="caracteristicas">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">Cómo funciona</span>
                <h2>Encontrá un plan. Organizá tu salida.</h2>
                <p>Explorá las propuestas de la zona y guardá las que te interesan.</p>
            </div>

            <div class="fila-tarjetas max-md:grid-cols-2 max-sm:grid-cols-1">
                <article class="tarjeta">
                    <div class="icono-tarjeta"><?= iconoSvg('brujula') ?></div>
                    <h3>Descubrí</h3>
                    <p>Conocé actividades, ferias, música y propuestas de la zona.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta"><?= iconoSvg('pin') ?></div>
                    <h3>Ubicate</h3>
                    <p>Usá el mapa para saber dónde se realiza cada evento.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta"><?= iconoSvg('calendario') ?></div>
                    <h3>Planificá</h3>
                    <p>Revisá la fecha, el tipo de evento y sus detalles.</p>
                </article>

                <article class="tarjeta">
                    <div class="icono-tarjeta"><?= iconoSvg('corazon') ?></div>
                    <h3>Guardá tus planes</h3>
                    <p>Agregá eventos a favoritos para encontrarlos cuando quieras.</p>
                </article>
            </div>
        </section>

        <section class="seccion-acerca max-sm:mt-11 max-sm:p-6" id="nosotros">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">Sobre iVento</span>
                <h2>Más cerca de lo que pasa en tu comunidad</h2>
            </div>
            <p>iVento es un servicio de ApexDigital SA, una sociedad anónima uruguaya. Reunimos actividades locales para que más personas puedan conocerlas y participar.</p>
        </section>

        <section class="seccion-empresa max-sm:mt-11" id="apexdigital">
            <div class="encabezado-seccion">
                <span class="etiqueta-seccion">ApexDigital</span>
                <h2>Una propuesta local, pensada para crecer</h2>
                <p>Desde Juan Lacaze y sus alrededores, buscamos acercar las actividades del departamento de Colonia a su comunidad.</p>
            </div>

            <div class="empresa-cuadrilla max-md:grid-cols-1">
                <article class="tarjeta">
                    <h3>Misión</h3>
                    <p>Conectar a quienes organizan actividades con las personas que quieren participar.</p>
                </article>

                <article class="tarjeta">
                    <h3>Visión</h3>
                    <p>Ser un lugar de referencia para descubrir actividades en la región.</p>
                </article>

                <article class="tarjeta">
                    <h3>Valores</h3>
                    <p>Respeto, transparencia y compromiso con la comunidad. Aprender y mejorar en cada paso.</p>
                </article>
            </div>
        </section>

        <section class="seccion-llamada max-sm:mt-11 max-sm:p-6 max-sm:items-start max-sm:flex-col max-sm:[&_.boton]:w-full">
            <div>
                <h2>Tu próximo plan empieza acá</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p>Volvé al mapa y encontrá una actividad para vos.</p>
                <?php else: ?>
                    <p>Creá tu cuenta y guardá tus eventos favoritos.</p>
                <?php endif; ?>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?ruta=mapa" class="boton">Explorar eventos</a>
            <?php else: ?>
                <a href="index.php?ruta=registro" class="boton">Crear cuenta</a>
            <?php endif; ?>
        </section>
    </main>

    <?php require dirname(__DIR__,3).'/includes/pie.php'; ?>
</body>
</html>
