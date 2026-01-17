# phpunit-prof

PHPUnit extension for profiling test execution times with JSON and HTML reports.

## Features

- Zero configuration required
- Profile test execution times automatically
- Generate JSON and HTML reports
- Identify slow tests based on configurable threshold
- Compare with previous test runs

## Requirements

- PHP 8.2 or higher
- PHPUnit 10, 11, or 12

## Installation

```bash
composer require --dev okkun-sh/phpunit-prof
```

## Usage

### Basic Configuration

Add the extension to your `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <!-- Your existing configuration -->

    <extensions>
        <bootstrap class="OkkunSh\PhpunitProf\Extension\ProfilerExtension">
        </bootstrap>
    </extensions>
</phpunit>
```

### Configuration Options

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `output` | string | `null` | Path to JSON report file (optional) |
| `html-output` | string | `null` | Path to HTML report file (optional) |
| `threshold` | float | `0.5` | Threshold in seconds for slow tests |
| `compare-with` | string | `null` | Path to previous JSON report for comparison |

### Example: Console Output Only (for CI)

```xml
<extensions>
    <bootstrap class="OkkunSh\PhpunitProf\Extension\ProfilerExtension">
        <!-- Default: console output only, no files generated -->
    </bootstrap>
</extensions>
```

### Example: With All Options

```xml
<extensions>
    <bootstrap class="OkkunSh\PhpunitProf\Extension\ProfilerExtension">
        <parameter name="output" value="reports/phpunit-prof.json"/>
        <parameter name="html-output" value="reports/phpunit-prof.html"/>
        <parameter name="threshold" value="0.3"/>
        <parameter name="compare-with" value="reports/previous.json"/>
    </bootstrap>
</extensions>
```

## Output

### Console Output

When slow tests are detected:

```
==========================================
PHPUnit Profiler Results
==========================================
Total tests: 6
Total time: 1.166s
Slow tests (>0.500s): 1

Slowest tests:
----------------------------------------
  0.702s  OkkunSh\PhpunitProf\Tests\SampleTest::testVerySlowTest ↑ 38.8%

Report saved to: phpunit-prof.json
HTML report saved to: phpunit-prof.html
```

### JSON Report

```json
{
    "total_tests": 6,
    "total_time": 1.166,
    "timestamp": "2026-01-01T00:00:00+00:00",
    "tests": [
        {
            "name": "OkkunSh\\PhpunitProf\\Tests\\SampleTest::testFastTest",
            "duration": 0.000146
        },
        {
            "name": "OkkunSh\\PhpunitProf\\Tests\\SampleTest::testSlowTest",
            "duration": 0.207,
            "previous_duration": 0.207,
            "change": 0.000015,
            "change_percent": 0.007
        },
        {
            "name": "OkkunSh\\PhpunitProf\\Tests\\SampleTest::testVerySlowTest",
            "duration": 0.702,
            "previous_duration": 0.506,
            "change": 0.196,
            "change_percent": 38.809
        }
    ]
}
```

### HTML Report

The HTML report provides an interactive view with:
- Sortable test results table
- Visual indicators for slow tests
- Performance comparison charts (when using `compare-with`)

## License

MIT License. See [LICENSE](LICENSE) file for details.
