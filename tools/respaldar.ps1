# Respaldo local de XAMPP. No publica nada ni modifica la base original.
# Uso: powershell -NoProfile -File tools/respaldar.ps1
param([string]$Destino = (Join-Path $env:USERPROFILE 'Documents\iVento-respaldos'))
$ErrorActionPreference = 'Stop'
$raizIvento = Split-Path $PSScriptRoot -Parent
$destinoIvento = [IO.Path]::GetFullPath($Destino)
$raizWebIvento = [IO.Path]::GetFullPath('C:\xampp\htdocs')
if ($destinoIvento.StartsWith($raizWebIvento, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Guardá el respaldo fuera de htdocs: contiene datos privados de las cuentas.'
}
$phpIvento = 'C:\xampp\php\php.exe'
$dumpIvento = 'C:\xampp\mysql\bin\mysqldump.exe'
$trabajoIvento = Join-Path $env:TEMP ('ivento-respaldo-' + [guid]::NewGuid().ToString('N'))
$paqueteIvento = Join-Path $trabajoIvento 'contenido'
$opcionesIvento = Join-Path $trabajoIvento 'mysql.ini'
$utf8Ivento = [Text.UTF8Encoding]::new($false)
$previaRaizIvento = $env:IVENTO_RESPALDO_RAIZ
$previaSqlIvento = $env:IVENTO_RESPALDO_SQL
try {
    New-Item -ItemType Directory -Path $paqueteIvento -Force | Out-Null
    New-Item -ItemType Directory -Path $destinoIvento -Force | Out-Null
    $env:IVENTO_RESPALDO_RAIZ = $raizIvento
    $configIvento = @'
<?php
require getenv('IVENTO_RESPALDO_RAIZ').'/database/db.php';
echo json_encode(['host'=>$host,'base'=>$dbname,'usuario'=>$username,'clave'=>$password]);
'@ | & $phpIvento
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo leer la configuración local.' }
    $datosIvento = $configIvento | ConvertFrom-Json
    if ($datosIvento.host -notin @('localhost','127.0.0.1','::1')) {
        throw 'Este comando es solo para XAMPP local. En el hosting exportá desde phpMyAdmin.'
    }
    function Valor-IniIvento([string]$valor) {
        return '"' + $valor.Replace('\','\\').Replace('"','\"').Replace("`r",'').Replace("`n",'') + '"'
    }
    $iniIvento = "[client]`nhost=$(Valor-IniIvento $datosIvento.host)`nuser=$(Valor-IniIvento $datosIvento.usuario)`npassword=$(Valor-IniIvento $datosIvento.clave)`n"
    [IO.File]::WriteAllText($opcionesIvento,$iniIvento,$utf8Ivento)
    $sqlIvento = Join-Path $paqueteIvento 'database.sql'
    & $dumpIvento "--defaults-extra-file=$opcionesIvento" --single-transaction --quick --hex-blob --default-character-set=utf8mb4 "--result-file=$sqlIvento" $datosIvento.base
    if ($LASTEXITCODE -ne 0) { throw 'Falló la exportación de MySQL.' }
    # Se verifica en una base nueva con nombre aleatorio. Nunca se restaura sobre ivento.
    $env:IVENTO_RESPALDO_SQL = $sqlIvento
    $verificacionIvento = @'
<?php
require getenv('IVENTO_RESPALDO_RAIZ').'/database/db.php';
$temporal='qa_respaldo_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$temporal.'` CHARACTER SET utf8mb4');
try {
    $prueba=new PDO("mysql:host=$host;dbname=$temporal;charset=utf8mb4",$username,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $prueba->exec(file_get_contents(getenv('IVENTO_RESPALDO_SQL')));
    $tablas=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $conteos=[];
    foreach ($tablas as $tabla) {
        $nombre='`'.str_replace('`','``',$tabla).'`';
        $original=(int)$pdo->query('SELECT COUNT(*) FROM '.$nombre)->fetchColumn();
        $restaurada=(int)$prueba->query('SELECT COUNT(*) FROM '.$nombre)->fetchColumn();
        if ($original!==$restaurada) { throw new RuntimeException('Los datos cambiaron durante la copia. Repetir sin editar el sitio.'); }
        $conteos[$tabla]=$restaurada;
    }
    echo json_encode(['fecha_utc'=>gmdate('c'),'restauracion'=>'verificada en base temporal','tablas'=>$conteos],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} finally { $pdo->exec('DROP DATABASE `'.$temporal.'`'); }
'@ | & $phpIvento
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo comprobar la restauración. No se generó un ZIP.' }
    [IO.File]::WriteAllText((Join-Path $paqueteIvento 'verificacion.json'),($verificacionIvento -join "`n"),$utf8Ivento)
    foreach ($carpetaIvento in @('eventos','perfiles')) {
        $origenIvento = Join-Path $raizIvento "public\img\$carpetaIvento"
        if (Test-Path -LiteralPath $origenIvento) {
            $imagenesIvento = Join-Path $paqueteIvento 'public\img'
            New-Item -ItemType Directory -Path $imagenesIvento -Force | Out-Null
            Copy-Item -LiteralPath $origenIvento -Destination $imagenesIvento -Recurse
        }
    }
    $huellasIvento = @{}
    Get-ChildItem -LiteralPath $paqueteIvento -File -Recurse | ForEach-Object {
        $relativaIvento = $_.FullName.Substring($paqueteIvento.Length+1).Replace('\','/')
        $huellasIvento[$relativaIvento] = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash
    }
    [IO.File]::WriteAllText((Join-Path $paqueteIvento 'sha256.json'),($huellasIvento | ConvertTo-Json),$utf8Ivento)
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zipIvento = Join-Path $destinoIvento ('ivento-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0,6) + '.zip')
    [IO.Compression.ZipFile]::CreateFromDirectory($paqueteIvento,$zipIvento)
    $extraidoIvento = Join-Path $trabajoIvento 'comprobacion'
    [IO.Compression.ZipFile]::ExtractToDirectory($zipIvento,$extraidoIvento)
    foreach ($rutaIvento in $huellasIvento.Keys) {
        if ((Get-FileHash -LiteralPath (Join-Path $extraidoIvento $rutaIvento) -Algorithm SHA256).Hash -ne $huellasIvento[$rutaIvento]) {
            throw 'El archivo comprimido no coincide con el respaldo original.'
        }
    }
    Write-Output 'Respaldo creado. Restauración SQL y contenido del ZIP verificados.'
    Write-Output $zipIvento
} finally {
    $env:IVENTO_RESPALDO_RAIZ = $previaRaizIvento
    $env:IVENTO_RESPALDO_SQL = $previaSqlIvento
    # Solo borrar el directorio temporal aleatorio creado por este comando.
    $temporalSeguroIvento = [IO.Path]::GetFullPath($trabajoIvento)
    $raizTemporalIvento = [IO.Path]::GetFullPath($env:TEMP).TrimEnd('\') + '\'
    if ($temporalSeguroIvento.StartsWith($raizTemporalIvento,[StringComparison]::OrdinalIgnoreCase) -and
        (Split-Path $temporalSeguroIvento -Leaf) -match '^ivento-respaldo-[a-f0-9]{32}$' -and (Test-Path -LiteralPath $temporalSeguroIvento)) {
        Remove-Item -LiteralPath $temporalSeguroIvento -Recurse -Force
    }
}
