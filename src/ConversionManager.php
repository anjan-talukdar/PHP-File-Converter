<?php
declare(strict_types=1);
namespace App;
use App\Converters\FfmpegConverter;
use App\Converters\ImageMagickConverter;
final class ConversionManager
{
    public function __construct(private array $config,private ProcessRunner $runner) {}
    public function inspect(string $path): array { return (new FormatRegistry())->detect($path,$this->runner,$this->config); }
    public function convert(string $input,string $output,string $kind,string $format): void {
        if ($kind==='image' && isset(FormatRegistry::IMAGE[$format])) (new ImageMagickConverter($this->runner,$this->config))->convert($input,$output,$format);
        elseif (($kind==='audio' && isset(FormatRegistry::AUDIO[$format])) || ($kind==='video' && isset(FormatRegistry::VIDEO[$format]))) (new FfmpegConverter($this->runner,$this->config))->convert($input,$output,$kind,$format);
        else throw new \RuntimeException('That output format is not supported for this file.');
        if (!is_file($output) || filesize($output)<1) throw new \RuntimeException('Conversion produced no output file.');
    }
}
