<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Telemetry\HRTime;
use OkkunSh\PhpunitProf\Report\JsonReporter;
use OkkunSh\PhpunitProf\Report\HtmlReporter;
use OkkunSh\PhpunitProf\Storage\ProfileData;
use OkkunSh\PhpunitProf\Storage\TestResult;

final class Profiler
{
    public const DEFAULT_THRESHOLD = 0.5;

    /** @var array<string, HRTime> */
    private array $startTimes = [];

    /** @var TestResult[] */
    private array $results = [];

    private ?ProfileData $previousData = null;

    public function __construct(
        private ?string $outputPath = null,
        private float $threshold = self::DEFAULT_THRESHOLD,
        private ?string $htmlOutput = null,
        private ?string $compareWith = null
    ) {
        if ($this->compareWith !== null && file_exists($this->compareWith)) {
            $this->previousData = ProfileData::fromJsonFile($this->compareWith);
        }
    }

    public function testPrepared(Test $test, HRTime $time): void
    {
        $testName = $this->getTestName($test);
        $this->startTimes[$testName] = $time;
    }

    public function testFinished(Test $test, HRTime $time): void
    {
        $testName = $this->getTestName($test);

        if (!isset($this->startTimes[$testName])) {
            return;
        }

        $startTime = $this->startTimes[$testName];
        $endTime = $time;

        $durationNanoseconds = ($endTime->seconds() - $startTime->seconds()) * 1_000_000_000
            + ($endTime->nanoseconds() - $startTime->nanoseconds());

        $durationSeconds = $durationNanoseconds / 1_000_000_000;

        $previousTime = null;
        if ($this->previousData !== null) {
            $previousTime = $this->previousData->getTestTime($testName);
        }

        $this->results[] = new TestResult(
            name: $testName,
            duration: $durationSeconds,
            previousDuration: $previousTime
        );

        unset($this->startTimes[$testName]);
    }

    public function finish(): void
    {
        $profileData = new ProfileData($this->results);

        if ($this->outputPath !== null) {
            $jsonReporter = new JsonReporter();
            $jsonReporter->save($profileData, $this->outputPath);
        }

        if ($this->htmlOutput !== null) {
            $htmlReporter = new HtmlReporter();
            $htmlReporter->save($profileData, $this->htmlOutput);
        }

        $this->printSummary($profileData);
    }

    private function getTestName(Test $test): string
    {
        return $test->id();
    }

    private function printSummary(ProfileData $data): void
    {
        $slowTests = $data->getSlowTests($this->threshold);

        if (empty($slowTests)) {
            return;
        }

        echo "\n";
        echo "==========================================\n";
        echo "PHPUnit Profiler Results\n";
        echo "==========================================\n";
        echo sprintf("Total tests: %d\n", $data->getTotalTests());
        echo sprintf("Total time: %.3fs\n", $data->getTotalTime());
        echo sprintf("Slow tests (>%.3fs): %d\n\n", $this->threshold, count($slowTests));

        echo "Slowest tests:\n";
        echo "----------------------------------------\n";

        foreach (array_slice($slowTests, 0, 10) as $result) {
            $change = '';
            if ($result->previousDuration !== null) {
                $diff = $result->duration - $result->previousDuration;
                $percent = ($diff / $result->previousDuration) * 100;
                if (abs($percent) > 0.01) {
                    $symbol = $diff > 0 ? '↑' : '↓';
                    $change = sprintf(' %s %.1f%%', $symbol, abs($percent));
                }
            }

            echo sprintf(
                "  %.3fs  %s%s\n",
                $result->duration,
                $result->name,
                $change
            );
        }

        echo "\n";

        if ($this->outputPath !== null) {
            echo "Report saved to: {$this->outputPath}\n";
        }

        if ($this->htmlOutput !== null) {
            echo "HTML report saved to: {$this->htmlOutput}\n";
        }
    }
}
