<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Report;

use OkkunSh\PhpunitProf\Storage\ProfileData;

final class HtmlReporter
{
    public function save(ProfileData $data, string $path): void
    {
        $html = $this->generateHtml($data);

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($path, $html) === false) {
            throw new \RuntimeException("Failed to write HTML report to: {$path}");
        }
    }

    private function generateHtml(ProfileData $data): string
    {
        $results = $data->getAllResults();
        usort($results, fn($a, $b) => $b->duration <=> $a->duration);

        $rows = '';
        foreach ($results as $result) {
            $changeHtml = '';
            if ($result->previousDuration !== null) {
                $diff = $result->duration - $result->previousDuration;
                $percent = ($diff / $result->previousDuration) * 100;

                if (abs($percent) > 0.01) {
                    $symbol = $diff > 0 ? '↑' : '↓';
                    $colorClass = $diff > 0 ? 'worse' : 'better';
                    $changeHtml = sprintf(
                        '<span class="change %s">%s %.1f%%</span>',
                        $colorClass,
                        $symbol,
                        abs($percent)
                    );
                }
            }

            $rows .= sprintf(
                '<tr><td>%s</td><td>%.3fs</td><td>%s</td></tr>',
                htmlspecialchars($result->name),
                $result->duration,
                $changeHtml
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPUnit Profiler Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .summary {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .summary-item {
            flex: 1;
        }

        .summary-item .label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: #2c3e50;
            color: white;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody tr:nth-child(odd) {
            background: #fafafa;
        }

        tbody tr:nth-child(odd):hover {
            background: #f0f0f0;
        }

        .change {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .change.better {
            color: #27ae60;
            background: #d5f4e6;
        }

        .change.worse {
            color: #e74c3c;
            background: #fadbd8;
        }

        .timestamp {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHPUnit Profiler Report</h1>

        <div class="summary">
            <div class="summary-item">
                <div class="label">Total Tests</div>
                <div class="value">{$data->getTotalTests()}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Time</div>
                <div class="value">{$this->formatDuration($data->getTotalTime())}</div>
            </div>
            <div class="summary-item">
                <div class="label">Average Time</div>
                <div class="value">{$this->formatDuration($data->getTotalTime() / max(1, $data->getTotalTests()))}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Duration</th>
                    <th>Change</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>

        <div class="timestamp">
            Generated at: {date('Y-m-d H:i:s')}
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function formatDuration(float $seconds): string
    {
        return sprintf('%.3fs', $seconds);
    }
}
