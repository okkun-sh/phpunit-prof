<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Storage;

final readonly class ProfileData
{
    /** @var array<TestResult> */
    private array $results;

    private float $totalTime;

    /**
     * @param array<TestResult> $results
     */
    public function __construct(array $results)
    {
        $this->results = $results;
        $this->totalTime = array_sum(array_map(fn($r) => $r->duration, $results));
    }

    public function getTotalTests(): int
    {
        return count($this->results);
    }

    public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * @return array<TestResult>
     */
    public function getAllResults(): array
    {
        return $this->results;
    }

    /**
     * @return array<TestResult>
     */
    public function getSlowTests(float $threshold): array
    {
        $slowTests = array_filter(
            $this->results,
            fn($r) => $r->duration >= $threshold
        );

        usort($slowTests, fn($a, $b) => $b->duration <=> $a->duration);

        return $slowTests;
    }

    public function getTestTime(string $testName): ?float
    {
        foreach ($this->results as $result) {
            if ($result->name === $testName) {
                return $result->duration;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_tests' => $this->getTotalTests(),
            'total_time' => $this->totalTime,
            'timestamp' => date('c'),
            'tests' => array_map(fn($r) => $r->toArray(), $this->results),
        ];
    }

    public static function fromJsonFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Failed to parse JSON from file: {$path}");
        }

        $tests = $data['tests'] ?? [];
        if (!is_array($tests)) {
            throw new \RuntimeException("Invalid tests data in JSON file: {$path}");
        }

        $results = [];
        foreach ($tests as $testData) {
            if (!is_array($testData)) {
                continue;
            }

            // Ensure array keys are strings
            /** @var array<string, mixed> $normalizedData */
            $normalizedData = [];
            foreach ($testData as $key => $value) {
                $normalizedData[(string) $key] = $value;
            }

            $results[] = TestResult::fromArray($normalizedData);
        }

        return new self($results);
    }
}
