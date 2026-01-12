<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests\Unit\Report;

use OkkunSh\PhpunitProf\Report\HtmlReporter;
use OkkunSh\PhpunitProf\Storage\ProfileData;
use OkkunSh\PhpunitProf\Storage\TestResult;
use PHPUnit\Framework\TestCase;

final class HtmlReporterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.html';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testSavesHtmlFile(): void
    {
        $results = [
            new TestResult('test1', 1.5),
            new TestResult('test2', 0.5),
        ];
        $profileData = new ProfileData($results);

        $reporter = new HtmlReporter();
        $reporter->save($profileData, $this->tempFile);

        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('PHPUnit Profiler Report', $content);
        $this->assertStringContainsString('test1', $content);
        $this->assertStringContainsString('test2', $content);
    }
}
