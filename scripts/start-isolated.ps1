$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$phpPath = Join-Path $projectRoot '.tools\php-8.3.36-1-nts-Win32-vs16-x64'
$phpExecutable = Join-Path $phpPath 'php.exe'
$phpIni = Join-Path $phpPath 'php.ini'

if (-not (Test-Path $phpExecutable) -or -not (Test-Path $phpIni)) {
    throw 'Falta PHP portátil. Ejecuta primero .\scripts\setup-isolated.ps1.'
}
if (-not (Test-Path (Join-Path $projectRoot 'vendor\autoload.php'))) {
    throw 'Faltan dependencias de Composer. Ejecuta primero .\scripts\setup-isolated.ps1.'
}
if (-not (Test-Path (Join-Path $projectRoot 'public\build\manifest.json'))) {
    throw 'Faltan recursos compilados. Ejecuta primero .\scripts\setup-isolated.ps1.'
}

Set-Location $projectRoot
$env:APP_ENV = 'isolated'
$env:APP_DEBUG = 'true'
$env:APP_URL = 'http://127.0.0.1:8000'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = Join-Path $projectRoot 'database\database-isolated.sqlite'
$env:CACHE_STORE = 'file'
$env:SESSION_DRIVER = 'file'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'log'

& $phpExecutable -c $phpIni 'artisan' 'migrate' '--force'
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudieron aplicar las migraciones del entorno aislado.'
}

Write-Host 'Laravel disponible en http://127.0.0.1:8000. Presiona Ctrl+C para detenerlo.'
& $phpExecutable -c $phpIni 'artisan' 'serve' '--host=127.0.0.1' '--port=8000'
if ($LASTEXITCODE -ne 0) {
    throw 'El servidor Laravel se cerró con un error.'
}
