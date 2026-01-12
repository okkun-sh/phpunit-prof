<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Report;

use OkkunSh\PhpunitProf\Storage\ProfileData;

final class JsonReporter
{
    public function save(ProfileData $data, string $path): void
    {
        $json = json_encode($data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode profile data to JSON');
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Failed to write JSON report to: {$path}");
        }
    }
}
