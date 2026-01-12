<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests\Unit\Storage;

use OkkunSh\PhpunitProf\Storage\ProfileData;
use OkkunSh\PhpunitProf\Storage\TestResult;
use PHPUnit\Framework\TestCase;

final class ProfileDataTest extends TestCase
{
    private string $tempJsonFile;

    protected function setUp(): void
    {
        $this->tempJsonFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempJsonFile)) {
            unlink($this->tempJsonFile);
        }
    }

    public function testCanBeCreatedWithResults(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];

        $profileData = new ProfileData($results);

        $this->assertSame(2, $profileData->getTotalTests());
        $this->assertSame(2.0, $profileData->getTotalTime());
    }

    public function testCanBeCreatedWithEmptyResults(): void
    {
        $results = [];

        $profileData = new ProfileData($results);

        $this->assertSame(0, $profileData->getTotalTests());
        $this->assertSame(0.0, $profileData->getTotalTime());
    }

    public function testGetAllResults(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];

        $profileData = new ProfileData($results);

        $this->assertSame($results, $profileData->getAllResults());
    }

    public function testGetSlowTestsFiltersAndSorts(): void
    {
        $results = [
            new TestResult('fast', 0.05),
            new TestResult('slow', 0.5),
            new TestResult('medium', 0.15),
            new TestResult('verySlow', 1.0),
        ];

        $profileData = new ProfileData($results);
        $slowTests = $profileData->getSlowTests(0.1);

        $this->assertCount(3, $slowTests);
        $this->assertSame('verySlow', $slowTests[0]->name);
        $this->assertSame('slow', $slowTests[1]->name);
        $this->assertSame('medium', $slowTests[2]->name);
    }

    public function testGetTestTimeReturnsTimeWhenTestExists(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];

        $profileData = new ProfileData($results);

        $this->assertSame(1.5, $profileData->getTestTime('test1'));
        $this->assertSame(0.5, $profileData->getTestTime('test2'));
    }

    public function testGetTestTimeReturnsNullWhenTestDoesNotExist(): void
    {
        $results = [
            new TestResult('test1', 1.5),
        ];

        $profileData = new ProfileData($results);

        $this->assertNull($profileData->getTestTime('nonexistent'));
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];

        $profileData = new ProfileData($results);
        $array = $profileData->toArray();

        $this->assertIsArray($array);
        $this->assertSame(2, $array['total_tests']);
        $this->assertSame(2.0, $array['total_time']);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('tests', $array);
        $this->assertCount(2, $array['tests']);
        $this->assertSame('test1', $array['tests'][0]['name']);
        $this->assertSame(1.5, $array['tests'][0]['duration']);
    }

    public function testFromJsonFileLoadsDataCorrectly(): void
    {
        $data = [
            'total_tests' => 2,
            'total_time' => 2.0,
            'timestamp' => '2026-01-01T00:00:00+00:00',
            'tests' => [
                [
                    'name' => 'test1',
                    'duration' => 1.5,
                ],
                [
                    'name' => 'test2',
                    'duration' => 0.5,
                ],
            ],
        ];
        file_put_contents($this->tempJsonFile, json_encode($data));

        $profileData = ProfileData::fromJsonFile($this->tempJsonFile);

        $this->assertSame(2, $profileData->getTotalTests());
        $this->assertSame(2.0, $profileData->getTotalTime());
        $this->assertCount(2, $profileData->getAllResults());
    }

    public function testFromJsonFileThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read file');

        ProfileData::fromJsonFile('/nonexistent/file.json');
    }

    public function testFromJsonFileThrowsExceptionWhenJsonIsInvalid(): void
    {
        file_put_contents($this->tempJsonFile, 'invalid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse JSON');

        ProfileData::fromJsonFile($this->tempJsonFile);
    }

    public function testFromJsonFileThrowsExceptionWhenTestsFieldIsInvalid(): void
    {
        $data = [
            'total_tests' => 1,
            'total_time' => 1.0,
            'tests' => 'not an array',
        ];
        file_put_contents($this->tempJsonFile, json_encode($data));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid tests data');

        ProfileData::fromJsonFile($this->tempJsonFile);
    }

    public function testFromJsonFileSkipsNonArrayTestData(): void
    {
        $data = [
            'total_tests' => 2,
            'total_time' => 1.5,
            'tests' => [
                [
                    'name' => 'test1',
                    'duration' => 1.5,
                ],
                'invalid test data',
                null,
                123,
                [
                    'name' => 'test2',
                    'duration' => 0.5,
                ],
            ],
        ];
        file_put_contents($this->tempJsonFile, json_encode($data));

        $profileData = ProfileData::fromJsonFile($this->tempJsonFile);

        $this->assertSame(2, $profileData->getTotalTests());
        $this->assertCount(2, $profileData->getAllResults());
    }

    public function testFromJsonFileHandlesMissingTestsField(): void
    {
        $data = [
            'total_tests' => 0,
            'total_time' => 0.0,
        ];
        file_put_contents($this->tempJsonFile, json_encode($data));

        $profileData = ProfileData::fromJsonFile($this->tempJsonFile);

        $this->assertSame(0, $profileData->getTotalTests());
        $this->assertCount(0, $profileData->getAllResults());
    }
}
