<?php

declare(strict_types=1);

namespace OkkunSh\PhpunitProf\Tests;

use PHPUnit\Framework\TestCase;

final class SampleTest extends TestCase
{
    public function testFastTest(): void
    {
        $this->assertTrue(true);
    }

    public function testSlowTest(): void
    {
        usleep(200000);
        $this->assertTrue(true);
    }

    public function testMediumTest(): void
    {
        usleep(100000);
        $this->assertTrue(true);
    }

    public function testVerySlowTest(): void
    {
        usleep(500000);
        $this->assertTrue(true);
    }

    public function testAnotherFastTest(): void
    {
        $result = array_sum([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $result);
    }

    public function testModeratelySlowTest(): void
    {
        usleep(150000);
        $array = range(1, 100);
        $this->assertCount(100, $array);
    }
}
