<?php
// Copiá este archivo como correo.local.php (protegido por .htaccess e ignorado por Git).
// Usá una contraseña de aplicación de Google, NUNCA la contraseña habitual del Gmail.
return [
    'host' => 'smtp.gmail.com',
    'puerto' => 587,
    'cifrado' => 'tls',
    'usuario' => 'apexdigitaleventos@gmail.com',
    'clave' => '', // Completar solamente en correo.local.php, en tu computadora.
    'remitente' => 'apexdigitaleventos@gmail.com',
    'nombre' => 'iVento',
    // Al publicar: URL HTTPS del sitio, sin barra final. También admite IVENTO_URL_BASE.
    'url_base' => 'http://localhost/PHP/Proyecto-SEO',
];
