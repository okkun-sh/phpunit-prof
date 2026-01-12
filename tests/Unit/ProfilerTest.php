<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests\Unit;

use OkkunSh\PhpunitProf\Profiler;
use OkkunSh\PhpunitProf\Storage\ProfileData;
use OkkunSh\PhpunitProf\Storage\TestResult;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Framework\TestCase;

final class ProfilerTest extends TestCase
{
    private string $tempJsonFile;
    private string $tempHtmlFile;
    private string $tempCompareFile;

    protected function setUp(): void
    {
        $this->tempJsonFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.json';
        $this->tempHtmlFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.html';
        $this->tempCompareFile = sys_get_temp_dir() . '/phpunit-prof-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempJsonFile)) {
            unlink($this->tempJsonFile);
        }
        if (file_exists($this->tempHtmlFile)) {
            unlink($this->tempHtmlFile);
        }
        if (file_exists($this->tempCompareFile)) {
            unlink($this->tempCompareFile);
        }
    }

    public function testCanBeCreated(): void
    {
        $profiler = new Profiler($this->tempJsonFile);

        $this->assertInstanceOf(Profiler::class, $profiler);
    }

    public function testCanBeCreatedWithAllParameters(): void
    {
        $profiler = new Profiler(
            outputPath: $this->tempJsonFile,
            threshold: 0.5,
            htmlOutput: $this->tempHtmlFile,
            compareWith: null
        );

        $this->assertInstanceOf(Profiler::class, $profiler);
    }

    public function testTracksTestExecutionTimeAndSavesResults(): void
    {
        $profiler = new Profiler($this->tempJsonFile);

        $test = $this->createStub(Test::class);
        $test->method('id')->willReturn('Example::testSample');

        $startTime = HRTime::fromSecondsAndNanoseconds(1000, 0);
        $endTime = HRTime::fromSecondsAndNanoseconds(1001, 500_000_000);

        $profiler->testPrepared($test, $startTime);
        $profiler->testFinished($test, $endTime);

        ob_start();
        $profiler->finish();
        $output = ob_get_clean();

        $this->assertFileExists($this->tempJsonFile);

        $content = file_get_contents($this->tempJsonFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame(1, $data['total_tests']);
        $this->assertEqualsWithDelta(1.5, $data['total_time'], 0.001);
        $this->assertCount(1, $data['tests']);
        $this->assertSame('Example::testSample', $data['tests'][0]['name']);
        $this->assertEqualsWithDelta(1.5, $data['tests'][0]['duration'], 0.001);
    }

    public function testSavesHtmlOutputWhenConfigured(): void
    {
        $profiler = new Profiler(
            outputPath: $this->tempJsonFile,
            htmlOutput: $this->tempHtmlFile
        );

        $test = $this->createStub(Test::class);
        $test->method('id')->willReturn('Example::testSample');

        $startTime = HRTime::fromSecondsAndNanoseconds(1000, 0);
        $endTime = HRTime::fromSecondsAndNanoseconds(1001, 500_000_000);

        $profiler->testPrepared($test, $startTime);
        $profiler->testFinished($test, $endTime);

        ob_start();
        $profiler->finish();
        ob_get_clean();

        $this->assertFileExists($this->tempJsonFile);
        $this->assertFileExists($this->tempHtmlFile);

        $htmlContent = file_get_contents($this->tempHtmlFile);
        $this->assertNotFalse($htmlContent);
        $this->assertStringContainsString('<!DOCTYPE html>', $htmlContent);
        $this->assertStringContainsString('Example::testSample', $htmlContent);
    }

    public function testComparesWithPreviousResults(): void
    {
        $previousData = [
            'total_tests' => 1,
            'total_time' => 1.0,
            'tests' => [
                [
                    'name' => 'Example::testSample',
                    'duration' => 1.0,
                ],
            ],
        ];
        file_put_contents($this->tempCompareFile, json_encode($previousData));

        $profiler = new Profiler(
            outputPath: $this->tempJsonFile,
            compareWith: $this->tempCompareFile
        );

        $test = $this->createStub(Test::class);
        $test->method('id')->willReturn('Example::testSample');

        $startTime = HRTime::fromSecondsAndNanoseconds(1000, 0);
        $endTime = HRTime::fromSecondsAndNanoseconds(1001, 500_000_000);

        $profiler->testPrepared($test, $startTime);
        $profiler->testFinished($test, $endTime);

        ob_start();
        $profiler->finish();
        ob_get_clean();

        $content = file_get_contents($this->tempJsonFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertEqualsWithDelta(1.0, $data['tests'][0]['previous_duration'], 0.001);
        $this->assertEqualsWithDelta(0.5, $data['tests'][0]['change'], 0.001);
        $this->assertEqualsWithDelta(50.0, $data['tests'][0]['change_percent'], 0.1);
    }

    public function testIgnoresTestFinishedWithoutTestPrepared(): void
    {
        $profiler = new Profiler($this->tempJsonFile);

        $test = $this->createStub(Test::class);
        $test->method('id')->willReturn('Example::testSample');

        $endTime = HRTime::fromSecondsAndNanoseconds(1001, 0);

        $profiler->testFinished($test, $endTime);

        ob_start();
        $profiler->finish();
        ob_get_clean();

        $content = file_get_contents($this->tempJsonFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame(0, $data['total_tests']);
    }
}
