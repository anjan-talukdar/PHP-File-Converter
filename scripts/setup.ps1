[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$projectRoot = Split-Path -Parent $PSScriptRoot
$toolsRoot = Join-Path $projectRoot 'tools'
$stageRoot = Join-Path $toolsRoot ('.setup-' + [guid]::NewGuid().ToString('N'))
$ffmpegUrl = 'https://www.gyan.dev/ffmpeg/builds/packages/ffmpeg-9.0.2-essentials_build.7z'
$ffmpegHash = '4705843ccaaf54257c16ad90f3e952ece33c17df964ecf7bfdbb0f49c7171077'
$imageMagickUrl = 'https://github.com/ImageMagick/ImageMagick/releases/download/7.1.2-34/ImageMagick-7.1.2-34-portable-Q16-HDRI-x64.7z'
$imageMagickHash = '8dcfd3644b7fc20a92aeedeb4798024e5947f25f4d7c0c313c883fbca3d723e6'

function Get-VerifiedArchive([string] $Url, [string] $ExpectedHash, [string] $Destination) {
    Write-Host "Downloading $([IO.Path]::GetFileName($Destination))..."
    Invoke-WebRequest -Uri $Url -OutFile $Destination -UseBasicParsing
    $actualHash = (Get-FileHash -LiteralPath $Destination -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($actualHash -ne $ExpectedHash) {
        throw "SHA-256 check failed for $([IO.Path]::GetFileName($Destination)). Delete the incomplete archive and retry."
    }
}

if (-not [Environment]::Is64BitOperatingSystem) {
    throw 'The project-local converter packages require 64-bit Windows.'
}
$tar = Get-Command 'tar.exe' -ErrorAction SilentlyContinue
if (-not $tar) { throw 'Windows tar.exe is required to extract the vendor archives.' }

$ffmpegExe = Join-Path $toolsRoot 'ffmpeg\bin\ffmpeg.exe'
$ffprobeExe = Join-Path $toolsRoot 'ffmpeg\bin\ffprobe.exe'
$magickExe = Join-Path $toolsRoot 'imagemagick\magick.exe'
if ((Test-Path -LiteralPath $ffmpegExe) -and (Test-Path -LiteralPath $ffprobeExe) -and (Test-Path -LiteralPath $magickExe)) {
    Write-Host 'FFmpeg, FFprobe, and ImageMagick are already installed in tools/.'
    exit 0
}
if ((Test-Path -LiteralPath (Join-Path $toolsRoot 'ffmpeg')) -or (Test-Path -LiteralPath (Join-Path $toolsRoot 'imagemagick'))) {
    throw 'A partial tools/ installation exists. Remove the incomplete tools/ folder, then run setup again.'
}

New-Item -ItemType Directory -Path $stageRoot -Force | Out-Null
try {
    $ffmpegArchive = Join-Path $stageRoot 'ffmpeg.7z'
    $imageArchive = Join-Path $stageRoot 'imagemagick.7z'
    Get-VerifiedArchive $ffmpegUrl $ffmpegHash $ffmpegArchive
    Get-VerifiedArchive $imageMagickUrl $imageMagickHash $imageArchive

    $ffmpegExtract = Join-Path $stageRoot 'ffmpeg'
    $imageExtract = Join-Path $stageRoot 'imagemagick'
    New-Item -ItemType Directory -Path $ffmpegExtract,$imageExtract -Force | Out-Null
    & $tar.Source -xf $ffmpegArchive -C $ffmpegExtract
    if ($LASTEXITCODE -ne 0) { throw 'Could not unpack the FFmpeg archive.' }
    & $tar.Source -xf $imageArchive -C $imageExtract
    if ($LASTEXITCODE -ne 0) { throw 'Could not unpack the ImageMagick archive.' }

    $ffmpegSource = Get-ChildItem -LiteralPath $ffmpegExtract -Filter 'ffmpeg.exe' -File -Recurse | Select-Object -First 1
    $ffprobeSource = Get-ChildItem -LiteralPath $ffmpegExtract -Filter 'ffprobe.exe' -File -Recurse | Select-Object -First 1
    $magickSource = Get-ChildItem -LiteralPath $imageExtract -Filter 'magick.exe' -File -Recurse | Select-Object -First 1
    if (-not $ffmpegSource -or -not $ffprobeSource -or -not $magickSource) {
        throw 'The verified archives did not contain the expected Windows executables.'
    }

    $ffmpegBinSource = Split-Path -Parent $ffmpegSource.FullName
    $imageSourceRoot = Split-Path -Parent $magickSource.FullName
    $ffmpegTarget = Join-Path $toolsRoot 'ffmpeg\bin'
    $imageTarget = Join-Path $toolsRoot 'imagemagick'
    New-Item -ItemType Directory -Path $ffmpegTarget,$imageTarget -Force | Out-Null
    Get-ChildItem -LiteralPath $ffmpegBinSource -Force | Copy-Item -Destination $ffmpegTarget -Recurse -Force
    Get-ChildItem -LiteralPath $imageSourceRoot -Force | Copy-Item -Destination $imageTarget -Recurse -Force

    $ffprobeVersion = & $ffprobeExe -version
    $ffprobeExitCode = $LASTEXITCODE
    if ($ffprobeExitCode -ne 0) { throw 'FFprobe did not start after installation.' }
    Write-Host ($ffprobeVersion | Select-Object -First 1)

    $magickVersion = & $magickExe -version
    $magickExitCode = $LASTEXITCODE
    if ($magickExitCode -ne 0) { throw 'ImageMagick did not start after installation.' }
    Write-Host ($magickVersion | Select-Object -First 1)
    Write-Host 'Converter setup complete. Start the app with: php -S 127.0.0.1:8080 -t public'
}
finally {
    $resolvedTools = [IO.Path]::GetFullPath($toolsRoot).TrimEnd([IO.Path]::DirectorySeparatorChar) + [IO.Path]::DirectorySeparatorChar
    $resolvedStage = [IO.Path]::GetFullPath($stageRoot)
    if ($resolvedStage.StartsWith($resolvedTools, [StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedStage)) {
        Remove-Item -LiteralPath $resolvedStage -Recurse -Force
    }
}
