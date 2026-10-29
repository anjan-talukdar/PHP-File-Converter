<?php
declare(strict_types=1);
namespace App\Converters;
use App\ProcessRunner;
final class ImageMagickConverter
{
    public function __construct(private ProcessRunner $runner,private array $config) {}
    public function convert(string $input,string $output,string $format): void {
        $this->runner->run($this->config['magick'],['-limit','memory','512MiB','-limit','map','1GiB','-limit','disk','2GiB',$input,'-strip','-resize',$this->config['max_pixels'].'@>',$format.':'.$output],$this->config['process_timeout_seconds']);
    }
}
