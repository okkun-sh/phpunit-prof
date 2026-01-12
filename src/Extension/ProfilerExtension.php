<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Extension;

use PHPUnit\Event\Application\Finished as ApplicationFinished;
use PHPUnit\Event\Application\FinishedSubscriber as ApplicationFinishedSubscriber;
use PHPUnit\Event\Test\Finished as TestFinished;
use PHPUnit\Event\Test\FinishedSubscriber as TestFinishedSubscriber;
use PHPUnit\Event\Test\Prepared as TestPrepared;
use PHPUnit\Event\Test\PreparedSubscriber as TestPreparedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use OkkunSh\PhpunitProf\Profiler;

final class ProfilerExtension implements Extension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters
    ): void {
        $outputPath = $this->getParameter($parameters, 'output', 'phpunit-prof.json');
        $threshold = $this->getParameter($parameters, 'threshold', 0.5);
        $htmlOutput = $this->getParameter($parameters, 'html-output', null);
        $compareWith = $this->getParameter($parameters, 'compare-with', null);

        $profiler = new Profiler(
            outputPath: is_string($outputPath) ? $outputPath : 'phpunit-prof.json',
            threshold: is_float($threshold) ? $threshold : (float) $threshold,
            htmlOutput: is_string($htmlOutput) || $htmlOutput === null ? $htmlOutput : null,
            compareWith: is_string($compareWith) || $compareWith === null ? $compareWith : null
        );

        $facade->registerSubscriber(new TestPreparedListener($profiler));
        $facade->registerSubscriber(new TestFinishedListener($profiler));
        $facade->registerSubscriber(new ApplicationFinishedListener($profiler));
    }

    private function getParameter(
        ParameterCollection $parameters,
        string $name,
        string|float|null $default
    ): string|float|null {
        return $parameters->has($name) ? $parameters->get($name) : $default;
    }
}

final class TestPreparedListener implements TestPreparedSubscriber
{
    public function __construct(private Profiler $profiler) {}

    public function notify(TestPrepared $event): void
    {
        $this->profiler->testPrepared($event->test(), $event->telemetryInfo()->time());
    }
}

final class TestFinishedListener implements TestFinishedSubscriber
{
    public function __construct(private Profiler $profiler) {}

    public function notify(TestFinished $event): void
    {
        $this->profiler->testFinished($event->test(), $event->telemetryInfo()->time());
    }
}

final class ApplicationFinishedListener implements ApplicationFinishedSubscriber
{
    public function __construct(private Profiler $profiler) {}

    public function notify(ApplicationFinished $event): void
    {
        $this->profiler->finish();
    }
}
