<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests\Unit\Storage;

use OkkunSh\PhpunitProf\Storage\TestResult;
use PHPUnit\Framework\TestCase;

final class TestResultTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $result = new TestResult(
            name: 'test_example',
            duration: 1.5
        );

        $this->assertSame('test_example', $result->name);
        $this->assertSame(1.5, $result->duration);
        $this->assertNull($result->previousDuration);
    }

    public function testCanBeCreatedWithPreviousDuration(): void
    {
        $result = new TestResult(
            name: 'test_example',
            duration: 1.5,
            previousDuration: 1.0
        );

        $this->assertSame('test_example', $result->name);
        $this->assertSame(1.5, $result->duration);
        $this->assertSame(1.0, $result->previousDuration);
    }

    public function testToArrayWithoutPreviousDuration(): void
    {
        $result = new TestResult(
            name: 'test_example',
            duration: 1.5
        );

        $array = $result->toArray();

        $this->assertSame('test_example', $array['name']);
        $this->assertSame(1.5, $array['duration']);
        $this->assertArrayNotHasKey('previous_duration', $array);
        $this->assertArrayNotHasKey('change', $array);
        $this->assertArrayNotHasKey('change_percent', $array);
    }

    public function testToArrayWithPreviousDuration(): void
    {
        $result = new TestResult(
            name: 'test_example',
            duration: 1.5,
            previousDuration: 1.0
        );

        $array = $result->toArray();

        $this->assertSame('test_example', $array['name']);
        $this->assertSame(1.5, $array['duration']);
        $this->assertSame(1.0, $array['previous_duration']);
        $this->assertSame(0.5, $array['change']);
        $this->assertSame(50.0, $array['change_percent']);
    }

    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'name' => 'test_example',
            'duration' => 1.5,
        ];

        $result = TestResult::fromArray($data);

        $this->assertSame('test_example', $result->name);
        $this->assertSame(1.5, $result->duration);
        $this->assertNull($result->previousDuration);
    }

    public function testFromArrayWithPreviousDuration(): void
    {
        $data = [
            'name' => 'test_example',
            'duration' => 1.5,
            'previous_duration' => 1.0,
        ];

        $result = TestResult::fromArray($data);

        $this->assertSame('test_example', $result->name);
        $this->assertSame(1.5, $result->duration);
        $this->assertSame(1.0, $result->previousDuration);
    }
}
