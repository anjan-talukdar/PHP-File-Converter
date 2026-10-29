<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store');
$action=$_GET['action'] ?? '';
if ($action==='download' && $_SERVER['REQUEST_METHOD']==='GET') {
    $token=(string)($_GET['token'] ?? ''); $id=(string)($_GET['id'] ?? ''); $item=$_SESSION['downloads'][$id] ?? null;
    if (!hash_equals($_SESSION['csrf'],$token) || !$item || !preg_match('/^[a-f0-9-]{36}$/i',$id) || !is_file($item['path'])) { http_response_code(404); exit('Download expired or invalid.'); }
    header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="'.rawurlencode($item['name']).'"'); header('Content-Length: '.filesize($item['path']));
    readfile($item['path']); unset($_SESSION['downloads'][$id],$_SESSION['jobs'][$id]); App\Security::removeTree(dirname($item['path'])); exit;
}
header('Content-Type: application/json; charset=utf-8');
$manager=new App\ConversionManager($config,new App\ProcessRunner());
try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('POST requests are required.');
    App\Security::assertCsrf();
    if ($action==='inspect') {
        if (!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Choose a file that uploads successfully.');
        $file=$_FILES['file'];
        if ($file['size']<1 || $file['size']>$config['max_upload_bytes'] || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('File must be between 1 byte and 100 MB.');
        $id=App\Security::uuid(); $dir=dirname(__DIR__).'/storage/temp/'.$id;
        if (!mkdir($dir,0700)) throw new RuntimeException('Could not prepare private storage.');
        $input=$dir.'/input'; if (!move_uploaded_file($file['tmp_name'],$input)) { App\Security::removeTree($dir); throw new RuntimeException('Could not store uploaded file.'); }
        try { $info=$manager->inspect($input); } catch (Throwable $e) { App\Security::removeTree($dir); throw $e; }
        $_SESSION['jobs'][$id]=['dir'=>$dir,'input'=>$input,'name'=>App\Security::cleanName($file['name']),'kind'=>$info['kind'],'formats'=>array_keys($info['formats'])];
        echo json_encode(['id'=>$id,'name'=>$_SESSION['jobs'][$id]['name'],'kind'=>$info['kind'],'mime'=>$info['mime'],'formats'=>$info['formats']],JSON_THROW_ON_ERROR); exit;
    }
    if ($action==='convert') {
        $id=(string)($_POST['id'] ?? ''); $format=strtolower((string)($_POST['format'] ?? '')); $job=$_SESSION['jobs'][$id] ?? null;
        if (!$job || !preg_match('/^[a-f0-9-]{36}$/i',$id) || !in_array($format,$job['formats'],true)) throw new RuntimeException('Request expired or invalid. Upload the file again.');
        $output=$job['dir'].'/result.'.$format; $manager->convert($job['input'],$output,$job['kind'],$format);
        $_SESSION['downloads'][$id]=['path'=>$output,'name'=>pathinfo($job['name'],PATHINFO_FILENAME).'.'.$format];
        echo json_encode(['download'=>'api.php?action=download&id='.rawurlencode($id)],JSON_THROW_ON_ERROR); exit;
    }
    throw new RuntimeException('Unknown request.');
} catch (Throwable $e) {
    if (http_response_code()<400) http_response_code(400);
    echo json_encode(['error'=>$e instanceof RuntimeException?$e->getMessage():'The request could not be completed.']);
}
