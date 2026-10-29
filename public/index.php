<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'");
header('X-Frame-Options: DENY'); header('Referrer-Policy: no-referrer'); header('X-Content-Type-Options: nosniff');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><title>File Converter</title><link rel="stylesheet" href="assets/css/style.css"><script src="assets/js/app.js" defer></script></head>
<body><main class="shell"><header><span class="eyebrow">PRIVATE · LOCAL CONVERSION</span><h1>File converter</h1><p>Convert common audio, video, and image files on this computer.</p></header>
<section class="card"><label class="drop" for="file"><span class="icon">↑</span><strong>Choose a file to convert</strong><span>Audio, video, or image · maximum 100 MB</span><input id="file" type="file"></label>
<div id="details" class="details" hidden><p id="detected"></p><label for="format">Convert to</label><select id="format"></select><button id="convert" type="button">Convert file</button></div>
<p id="status" role="status" aria-live="polite"></p></section><footer>Files are processed locally and temporary files expire after one hour.</footer></main>
</body></html>
