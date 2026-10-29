# PHP File Converter

A local web app for converting common image, audio, and video files with PHP 8.3+, native Windows FFmpeg, and ImageMagick. No Docker or Composer packages are required.

## Requirements

- Windows 10 or 11, 64-bit
- PHP 8.3+ with `fileinfo` enabled
- Internet access for the one-time converter setup

## Setup and run

1. Install PHP 8.3+ and make `php.exe` available in PATH. Check that `php -m` lists `fileinfo`.
2. From PowerShell in the project folder, run this single setup command:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\setup.ps1
   ```

   It downloads pinned Windows builds of FFmpeg and ImageMagick, verifies their SHA-256 checksums, and installs them under the ignored `tools/` folder. It requires Windows' built-in `tar.exe` to unpack the 7z archives. Run the command again after a fresh clone or when you intentionally want to reinstall these pinned versions.

3. Start the local PHP server from the project folder:

   ```powershell
   php -S 127.0.0.1:8080 -t public
   ```

4. Open <http://127.0.0.1:8080>. Keep the server bound to `127.0.0.1`; PHP's built-in server is for local development and should not be exposed to a public network.

For uploads up to 100 MB, set `upload_max_filesize` to at least `100M` and `post_max_size` slightly higher (for example, `110M`) in `php.ini`, then restart the PHP server.

## Vendor tools

The `tools/` folder is ignored by Git, so converter binaries are not committed. `scripts/setup.ps1` downloads the pinned FFmpeg Essentials build from Gyan.dev (linked from FFmpeg's Windows download page) and the portable ImageMagick Windows build. It checks the published FFmpeg checksum and the pinned ImageMagick release checksum before extraction. The PHP config uses stable paths under `tools/` and can be overridden with `CONVERTER_FFMPEG`, `CONVERTER_FFPROBE`, and `CONVERTER_MAGICK` environment variables.

FFmpeg build: [Gyan.dev FFmpeg builds](https://www.gyan.dev/ffmpeg/builds/). ImageMagick build: [official ImageMagick release](https://github.com/ImageMagick/ImageMagick/releases/tag/7.1.2-34). Review each vendor's license files under `tools/` when using or redistributing those components.

## Configuration and limits

`config/config.php` holds executable paths and resource limits:

- Upload size: 100 MB
- Conversion timeout: 180 seconds
- Temporary job retention: one hour
- Maximum image area: 40 million pixels

Per-job UUID directories live in `storage/temp`, outside `public`. Successful downloads remove their job directory. Expired jobs are cleaned on later requests; if no requests arrive, old folders remain until the next request. Keep `storage` writable by the PHP account and inaccessible through any web server alias.

## Security and supported formats

- Uploads are size-limited and identified with PHP Fileinfo; original filenames and extensions are not trusted for type detection.
- FFprobe inspects media. The UI offers only output formats detected in the installed tool and allowed for the media category; actual codec availability depends on the FFmpeg build.
- Commands use an argument array with shell bypass. Upload names are not interpolated into shell commands or storage paths.
- Random job IDs, session CSRF tokens, restrictive response headers, sanitized download names, private storage, and process timeouts are used.
- ImageMagick's policy remains restrictive; the app accepts common raster images only. Keep FFmpeg and ImageMagick patched because both parse untrusted data.
- The app has no login, rate limiter, or production web server configuration. Use it on loopback only unless you add those protections and harden the deployment.
- Documents, archives, executables, and arbitrary binary conversions are unsupported.

## Layout

```text
public/                 UI, static assets, API and download endpoint
src/                    detection, orchestration, security, process runner, converters
config/config.php       executable paths and limits
scripts/setup.ps1       download, verify, and unpack ignored vendor tools
tools/                  ignored project-local converter binaries
storage/temp/           private per-job files
bootstrap.php           sessions, autoloading, storage and expiry cleanup
composer.json           PHP platform requirements and PSR-4 metadata
```
