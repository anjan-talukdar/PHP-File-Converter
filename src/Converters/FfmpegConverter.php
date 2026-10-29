<?php
declare(strict_types=1);
namespace App\Converters;
use App\ProcessRunner;
final class FfmpegConverter
{
    public function __construct(private ProcessRunner $runner,private array $config) {}
    public function convert(string $input,string $output,string $kind,string $format): void {
        $args=['-hide_banner','-nostdin','-v','error','-y','-i',$input,'-map_metadata','-1'];
        if ($kind==='audio') $args=array_merge($args,['-vn','-c:a',match($format){'mp3'=>'libmp3lame','wav'=>'pcm_s16le','flac'=>'flac','ogg'=>'libvorbis','opus'=>'libopus','m4a','aac'=>'aac'}]);
        else $args=array_merge($args,['-c:v',match($format){'mp4','mov','mkv'=>'libx264','webm'=>'libvpx-vp9','avi'=>'mpeg4'},'-c:a',$format==='webm'?'libopus':'aac']);
        $args[]=$output; $this->runner->run($this->config['ffmpeg'],$args,$this->config['process_timeout_seconds']);
    }
}
