<?php
declare(strict_types=1);
namespace App;
final class ProcessRunner
{
    public function run(string $binary, array $args, int $timeout): array {
        if (!is_file($binary)) throw new \RuntimeException('Configured tool not found: ' . basename($binary));
        $pipes = []; $proc = proc_open(array_merge([$binary], $args), [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($proc)) throw new \RuntimeException('Could not start conversion tool.');
        fclose($pipes[0]); stream_set_blocking($pipes[1],false); stream_set_blocking($pipes[2],false); $out=$err=''; $start=microtime(true); $exit=-1;
        do { $out .= stream_get_contents($pipes[1]); $err .= stream_get_contents($pipes[2]); $status=proc_get_status($proc); if (!$status['running']) { $exit=$status['exitcode']; break; } if (microtime(true)-$start>$timeout) { proc_terminate($proc); usleep(250000); proc_terminate($proc,9); break; } usleep(50000); } while (true);
        $out .= stream_get_contents($pipes[1]); $err .= stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $closed=proc_close($proc);
        if (microtime(true)-$start>$timeout) throw new \RuntimeException('Conversion timed out. Try a smaller file.');
        if (($exit >= 0 ? $exit : $closed) !== 0) {
            $detail = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', ' ', $err) ?? '');
            $detail = substr($detail, 0, 500);
            throw new \RuntimeException($detail !== '' ? 'Conversion tool error: ' . $detail : 'Conversion failed. The input or required codec may not be supported.');
        }
        return [$out,$err];
    }
}
