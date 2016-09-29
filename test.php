<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

use Ranvis\RobotsTxt\Adapter as TestAdapter;
use Ranvis\RobotsTxt\TestcaseSet;
use Ranvis\RobotsTxt\TestProfile;
use Ranvis\RobotsTxt\TestRunner;

require_once(__DIR__ . '/vendor/autoload.php');

$options = getopt('m:vqnfFt:cX');

$verbosity = isset($options['v']) ? count((array)$options['v']) : 0; // -v/-vv: verbose
$verbosity -= isset($options['q']) ? count((array)$options['q']) : 0; // -q: quiet
$showName = isset($options['n']); // -n: show module long name if possible
$noFilterTests = isset($options['F']); // -F: no Ranvis\RobotsTxt\Filter() prefiltered test
$noDirectTests = isset($options['f']); // -f: no non-filtered test
$outputType = isset($options['t']) ? strtolower(((array)$options['t'])[0]) : null; // -o php/json: non-text output
$spawnChild = isset($options['c']); // -c: spawn child process to test memory usage (PHP only)
$noOtherLang = isset($options['X']); // -X: exclude non-PHP modules
// -m module -m ...: module to test specifically

$targetParsers = [
    'bee4' => [TestAdapter\Bee4::class, []],
    'diggin' => [TestAdapter\Diggin::class, []],
    'm6web' => [TestAdapter\M6web::class, []],
    'ranvis' => [TestAdapter\Ranvis::class, [[]]],
    't1gor' => [TestAdapter\T1gor::class, []],
    'tomverran' => [TestAdapter\Tomverran::class, []],
];

if (!$noOtherLang) {
    $targetParsers += [
        'perlRr' => [TestAdapter\AnyCommand::class, ['perl-RobotRules', 'perl', 'bin/robotrules.pl', 'WWW::RobotRules']],
        'perlRre' => [TestAdapter\AnyCommand::class, ['perl-RobotRules-Extended', 'perl', 'bin/robotrules.pl', 'WWW::RobotRules::Extended']],
        'pythonRfp' => [TestAdapter\AnyCommand::class, ['python-urllib.robotparser', 'python', 'bin/robotfileparser.py']],
        'goTemoto' => [TestAdapter\AnyCommand::class, ['go-temoto-robotstxt', 'go', 'run', 'bin/temoto.go']],
    ];
}

if (isset($options['m'])) {
    $targetIds = array_flip((array)$options['m']);
    foreach ($targetParsers as $id => $defs) {
        $parserClass = $defs[0];
        if (!isset($targetIds[$id])
                || !$parserClass::isAvailable()) {
            unset($targetParsers[$id]);
        }
    }
}

ini_set('memory_limit', '128M');
ini_set('pcre.backtrack_limit', 10000000);
ini_set('pcre.recursion_limit', 100000);

$set = TestcaseSet::parse(TestProfile::getYamlPath());

$testStatuses = [];

$tester = new TestRunner();
$tester->setTestcases($set);
$baseMemory = memory_get_peak_usage();
foreach ($targetParsers as $id => $defs) {
    list($parserClass, $args) = $defs;
    printProgress($id);
    if ($spawnChild) {
        ob_start();
        passthru(implode(' ', array_map(function ($value) {
            return escapeshellarg($value);
        }, [PHP_BINARY, $argv[0], '-m', $id, '-q', '-t', 'php'])));
        $output = ob_get_clean();
        $outputStatus = unserialize($output); // trusted external output
        if ($outputStatus === false) {
            fwrite(STDERR, "unable to unserialize $id result: " . substr($output, 0, 20) . "...\n");
        } else {
            $testStatuses += $outputStatus;
        }
        continue;
    }
    if (!$noDirectTests) {
        $parser = new $parserClass(...$args);
        $tester->run($parser);
        $testStatuses[$id] = $tester->getStatus();
        $testStatuses[$id] = $tester->getStatus() + [
            'memory' => memory_get_peak_usage() - $baseMemory,
        ];
    }
    if (!$noFilterTests) {
        printProgress("f:$id");
        $parser = new $parserClass(...$args);
        $filter = new TestAdapter\Filter($parser);
        $tester->run($filter);
        $testStatuses["f:$id"] = $tester->getStatus() + [
            'memory' => memory_get_peak_usage() - $baseMemory,
        ];
    }
}
if ($verbosity > 0) {
    echo "\n";
}

if ($outputType === 'php') {
    echo serialize($testStatuses);
    exit;
} elseif ($outputType === 'json') {
    echo json_encode($testStatuses);
    exit;
}
if ($verbosity) {
    foreach ($testStatuses as $id => $status) {
        ob_start();
        $failures = $status['failures'];
        if ($failures) {
            echo "  Failures:\n";
            foreach ($failures as $failure) {
                if ($verbosity <= 1) {
                    echo "    " . $failure['message'] . "\n";
                } else {
                    $text = preg_replace_callback('/\x0d\x0a?|\x0a/s', function ($match) {
                        return "<" . bin2hex($match[0]) . ">\n                ";
                    }, $failure['text']);
                    echo "    User-agent: " . $failure['ua'] . "\n";
                    echo "    Path      : " . $failure['path'] . "\n";
                    echo "    Robots.txt: " . $text . "\n";
                    echo "    Expects   : " . ($failure['allowed'] ? 'allowed' : 'disallowed') . "\n";
                    echo "    Message   : " . $failure['message'] . "\n";
                    echo "    ----\n";
                }
            }
        }
        $warnings = $status['warnings'];
        if ($warnings) {
            echo "  Warnings:\n";
            foreach ($warnings as $message) {
                echo "    $message\n";
            }
        }
        $output = ob_get_clean();
        if ($output) {
            echo (($showName ? $status['name'] : $id) . "\n");
            echo $output;
        }
    }
}

uksort($testStatuses, function ($a, $b) use ($testStatuses) {
    $a = $testStatuses[$a]['num'];
    $b = $testStatuses[$b]['num'];
    return $a['errors'] <=> $b['errors']
        ?: ($a['failures'] / $a['tests']) <=> ($b['failures'] / $b['tests'])
        ?: $a['failures'] <=> $b['failures']
        ?: $a['warnings'] <=> $b['warnings']
        ?: $b['tests'] <=> $a['tests'];
});
foreach ($testStatuses as $id => $status) {
    echo (($showName ? $status['name'] : $id) . "\n");
    if ($verbosity) {
        foreach ($status['features'] as $feature => $desc) {
            if (!empty($desc)) {
                printf("  %-35s %s\n", $feature, $desc === true ? 'true' : $desc);
            }
        }
    } else {
        echo "  Features: " . implode(' ', array_keys(array_filter($status['features']))) . "\n";
    }
}
$format = "%-30s %6d %6d %4d%% %8d %8d";
$headerFormat = preg_replace('/[d]/', 's', $format);
$header = ["Package", "Tests", "Passed", "Rate", "Errors", "Warnings"];
if ($spawnChild) {
    $format .= " %4.1fM";
    $headerFormat .= " %6s";
    $header[] = "Memory";
}
$format .= "\n";
$headerFormat .= "\n";
printf($headerFormat, ...$header);
foreach ($testStatuses as $id => $status) {
    $num = $status['num'];
    $success = $num['tests'] - $num['failures'];
    $rate = ($success / $num['tests']) * 100;
    $values = [$showName ? $status['name'] : $id, $num['tests'], $success, $rate, $num['errors'], $num['warnings']];
    if ($spawnChild) {
        $values[] = $status['memory'] / 1024 / 1024;
    }
    printf($format, ...$values);
}

printf("Peak memory usage: %.1fMiB\n", memory_get_peak_usage() / 1024 / 1024);
exit;

function printProgress($id)
{
    global $verbosity;
    if ($verbosity < 0) {
        return;
    } elseif ($verbosity) {
        echo $id . "\n";
    } else {
        echo ".";
        flush();
    }
}
