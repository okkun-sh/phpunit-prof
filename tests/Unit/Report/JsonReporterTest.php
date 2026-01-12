<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests\Unit\Report;

use OkkunSh\PhpunitProf\Report\JsonReporter;
use OkkunSh\PhpunitProf\Storage\ProfileData;
use OkkunSh\PhpunitProf\Storage\TestResult;
use PHPUnit\Framework\TestCase;

final class JsonReporterTest extends TestCase
{
    private string $tempFile;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.json';
        $this->tempDir = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testSavesJsonFile(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];
        $profileData = new ProfileData($results);

        $reporter = new JsonReporter();
        $reporter->save($profileData, $this->tempFile);

        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame(2, $data['total_tests']);
        $this->assertEqualsWithDelta(2.0, $data['total_time'], 0.001);
        $this->assertCount(2, $data['tests']);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $results = [
            new TestResult('test1', 1.5),
        ];
        $profileData = new ProfileData($results);

        $filePath = $this->tempDir . '/nested/report.json';
        $this->assertDirectoryDoesNotExist($this->tempDir);

        $reporter = new JsonReporter();
        $reporter->save($profileData, $filePath);

        $this->assertFileExists($filePath);
        $this->assertDirectoryExists($this->tempDir . '/nested');
    }
}
