$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$toolsPath = Join-Path $projectRoot '.tools'
$phpVersion = '8.3.36-1'
$phpPath = Join-Path $toolsPath "php-$phpVersion-nts-Win32-vs16-x64"
$phpExecutable = Join-Path $phpPath 'php.exe'
$phpIni = Join-Path $phpPath 'php.ini'
$composerPath = Join-Path $toolsPath 'composer.phar'

New-Item -ItemType Directory -Path $toolsPath -Force | Out-Null

if (-not (Test-Path $phpExecutable)) {
    if (Test-Path $phpPath) {
        throw "La carpeta de PHP existe pero no contiene php.exe: $phpPath. Revisa esa carpeta antes de continuar."
    }

    $archive = Join-Path $toolsPath "php-$phpVersion-nts-Win32-vs16-x64.zip"
    try {
        Invoke-WebRequest `
            -Uri "https://downloads.php.net/~windows/releases/php-$phpVersion-nts-Win32-vs16-x64.zip" `
            -OutFile $archive `
            -UseBasicParsing
        Expand-Archive -LiteralPath $archive -DestinationPath $phpPath
    }
    finally {
        if (Test-Path $archive) {
            Remove-Item -LiteralPath $archive
        }
    }
}

if (-not (Test-Path $phpIni)) {
    @"
extension_dir="$phpPath\ext"
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
date.timezone=America/Lima
memory_limit=512M
"@ | Set-Content -LiteralPath $phpIni -Encoding ASCII
}

$phpModules = & $phpExecutable -c $phpIni -m
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo iniciar el PHP portátil. Revisa los requisitos del runtime de Visual C++.'
}
foreach ($extension in @('curl', 'fileinfo', 'mbstring', 'openssl', 'PDO', 'pdo_sqlite', 'sqlite3', 'zip')) {
    if ($phpModules -notcontains $extension) {
        throw "Falta la extensión PHP requerida: $extension"
    }
}
& $phpExecutable -c $phpIni -v

if (-not (Test-Path $composerPath)) {
    Invoke-WebRequest `
        -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' `
        -OutFile $composerPath `
        -UseBasicParsing
}

$isolatedEnvPath = Join-Path $projectRoot '.env.isolated'
if (-not (Test-Path $isolatedEnvPath)) {
    @'
APP_NAME="NEXO Bodega"
APP_ENV=isolated
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_PE
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
DB_CONNECTION=sqlite
DB_DATABASE=database/database-isolated.sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
'@ | Set-Content -LiteralPath $isolatedEnvPath -Encoding ASCII
}

Set-Location $projectRoot
$env:APP_ENV = 'isolated'
& $phpExecutable -c $phpIni $composerPath install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) {
    throw 'Composer no pudo instalar las dependencias con PHP portátil.'
}

$isolatedEnv = Get-Content -LiteralPath $isolatedEnvPath -Raw
if ($isolatedEnv -notmatch '(?m)^APP_KEY=.+$') {
    & $phpExecutable -c $phpIni 'artisan' 'key:generate'
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo generar la clave para el entorno aislado.'
    }
    $isolatedEnv = Get-Content -LiteralPath $isolatedEnvPath -Raw
}

$testEnvPath = Join-Path $projectRoot '.env.testing'
if (-not (Test-Path $testEnvPath)) {
    $keyMatch = [regex]::Match($isolatedEnv, '(?m)^APP_KEY=(.+)$')
    if (-not $keyMatch.Success) {
        throw 'No se encontró APP_KEY en .env.isolated para configurar las pruebas.'
    }

    @"
APP_ENV=testing
APP_KEY=$($keyMatch.Groups[1].Value)
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
"@ | Set-Content -LiteralPath $testEnvPath -Encoding ASCII
}

& $phpExecutable -c $phpIni 'artisan' 'migrate' '--force' '--seed'
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudieron aplicar las migraciones al SQLite aislado.'
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw 'Node.js y npm son necesarios para compilar los recursos de Vite.'
}
npm ci
if ($LASTEXITCODE -ne 0) {
    throw 'npm ci no pudo instalar las dependencias del frontend.'
}
npm run build
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudieron compilar los recursos del frontend.'
}

Write-Host 'Entorno local aislado listo. Inícialo con .\scripts\start-isolated.ps1'
