<?php
declare(strict_types=1);
return [
    'ffmpeg' => getenv('CONVERTER_FFMPEG') ?: dirname(__DIR__) . '/tools/ffmpeg/bin/ffmpeg.exe',
    'ffprobe' => getenv('CONVERTER_FFPROBE') ?: dirname(__DIR__) . '/tools/ffmpeg/bin/ffprobe.exe',
    'magick' => getenv('CONVERTER_MAGICK') ?: dirname(__DIR__) . '/tools/imagemagick/magick.exe',
    'max_upload_bytes' => 100 * 1024 * 1024,
    'process_timeout_seconds' => 180,
    'retention_seconds' => 3600,
    'max_pixels' => 40_000_000,
];
