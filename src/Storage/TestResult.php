<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Storage;

final readonly class TestResult
{
    public function __construct(
        public string $name,
        public float $duration,
        public ?float $previousDuration = null
    ) {
    }

    /**
     * @return array<string, string|float>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'duration' => $this->duration,
        ];

        if ($this->previousDuration !== null) {
            $data['previous_duration'] = $this->previousDuration;
            $data['change'] = $this->duration - $this->previousDuration;
            $data['change_percent'] = (($this->duration - $this->previousDuration) / $this->previousDuration) * 100;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';
        $duration = $data['duration'] ?? 0.0;
        $previousDuration = $data['previous_duration'] ?? null;

        return new self(
            name: is_string($name) ? $name : '',
            duration: is_float($duration) || is_int($duration) ? (float) $duration : 0.0,
            previousDuration: is_float($previousDuration) || is_int($previousDuration) ? (float) $previousDuration : null
        );
    }
}
