<?php
declare(strict_types=1);
namespace App;
final class FormatRegistry
{
    public const AUDIO=['mp3'=>'MP3 audio','wav'=>'WAV audio','flac'=>'FLAC audio','ogg'=>'OGG audio','opus'=>'Opus audio','m4a'=>'M4A audio','aac'=>'AAC audio'];
    public const VIDEO=['mp4'=>'MP4 video','webm'=>'WebM video','mov'=>'MOV video','mkv'=>'Matroska video','avi'=>'AVI video'];
    public const IMAGE=['png'=>'PNG image','jpg'=>'JPEG image','webp'=>'WebP image','gif'=>'GIF image','tiff'=>'TIFF image','bmp'=>'BMP image'];
    public function detect(string $path, ProcessRunner $runner, array $config): array {
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        if (str_starts_with($mime,'image/')) {
            $known=['image/png','image/jpeg','image/webp','image/gif','image/tiff','image/bmp'];
            if (!in_array($mime,$known,true)) throw new \RuntimeException('This image type is not supported.');
            return ['kind'=>'image','mime'=>$mime,'formats'=>$this->available(self::IMAGE,$config['magick'],$runner,$config)];
        }
        if (str_starts_with($mime,'audio/') || str_starts_with($mime,'video/')) {
            try {
                [$json]=$runner->run($config['ffprobe'],['-v','error','-show_entries','stream=codec_type','-of','json',$path],15);
            } catch (\Throwable $e) {
                throw new \RuntimeException('FFprobe could not inspect this file. ' . $e->getMessage());
            }
            $types=array_column(json_decode($json,true)['streams'] ?? [],'codec_type'); $kind=in_array('video',$types,true)?'video':(in_array('audio',$types,true)?'audio':null);
            if (!$kind) throw new \RuntimeException('Media type could not be identified.');
            return ['kind'=>$kind,'mime'=>$mime,'formats'=>$this->available($kind==='audio'?self::AUDIO:self::VIDEO,$config['ffmpeg'],$runner,$config)];
        }
        throw new \RuntimeException('Unsupported file type. Choose a common audio, video, or raster image file.');
    }
    private function available(array $formats,string $binary,ProcessRunner $runner,array $config): array {
        [$out,$err]=$runner->run($binary,$binary===$config['ffmpeg']?['-hide_banner','-formats']:['-list','format'],15); $text=$out.$err; $result=[];
        foreach ($formats as $ext=>$label) {
            $token=match($ext){'jpg'=>'jpeg','mkv'=>'matroska','m4a'=>'ipod','aac'=>'adts',default=>$ext};
            if (stripos($text,$token)!==false) $result[$ext]=$label;
        }
        if (!$result) throw new \RuntimeException('No supported output formats are available in the installed tool.'); return $result;
    }
}
