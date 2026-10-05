@echo off
setlocal
cd /d "%~dp0"
if /i "%~1"=="install" goto install
if not exist "tools\tailwindcss.exe" (
    echo Falta la CLI. Ejecuta primero: .\tailwind.cmd install
    exit /b 1
)
if /i "%~1"=="watch" goto watch
if "%~1"=="" goto build
if /i "%~1"=="build" goto build
echo Uso: .\tailwind.cmd [install^|build^|watch]
exit /b 1

:install
if not exist "tools" mkdir "tools"
powershell.exe -NoProfile -Command "$ErrorActionPreference='Stop'; Invoke-WebRequest -UseBasicParsing 'https://github.com/tailwindlabs/tailwindcss/releases/download/v4.3.3/tailwindcss-windows-x64.exe' -OutFile 'tools/tailwindcss.download'; if ((Get-FileHash 'tools/tailwindcss.download' -Algorithm SHA256).Hash -ne 'E0E260CE048014E9268F6237FF18F8CCF02CEF521CBD0AE04E82C2CDF7AA3955') { throw 'La verificacion SHA256 fallo.' }; Move-Item -LiteralPath 'tools/tailwindcss.download' -Destination 'tools/tailwindcss.exe' -Force"
if errorlevel 1 exit /b 1
echo Tailwind CSS CLI 4.3.3 instalado y verificado.
exit /b 0

:build
"tools\tailwindcss.exe" -i "src\tailwind.css" -o "public\css\tailwind.css" --minify
exit /b %errorlevel%

:watch
"tools\tailwindcss.exe" -i "src\tailwind.css" -o "public\css\tailwind.css" --watch
exit /b %errorlevel%
