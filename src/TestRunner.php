<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt;

use Ranvis\RobotsTxt\Adapter\AdapterInterface;

class TestRunner
{
    private $testcases;
    /**
     * @var AdapterInterface
     */
    private $parser;
    private $lastText;
    private $num;
    private $failures;
    private $warnings;
    private $features = [];
    private $lastStatus;

    public function setTestcases(TestcaseSet $set)
    {
        // validate input
        $testcases = $set->getTestcases();
        foreach ($testcases as $t) {
            foreach ($t as $key => $value) {
                if (!preg_match('/\A(?:_.*|text|ua|allowed|message|path|when|mayFail|ifSuccess|ifFailed|always)\z/', $key)) {
                    throw new \DomainException("Unknown key '$key' in the testcase");
                }
            }
        }
        $this->testcases = $testcases;
    }

    public function run(AdapterInterface $parser)
    {
        $this->parser = $parser;
        $this->num = [
            'tests' => 0,
            'failures' => 0,
            'errors' => 0,
            'warnings' => 0,
        ];
        $this->failures = [];
        $this->warnings = [];
        $this->features = [];
        $packageName = $parser->getPackageName();
        $lastErrorHandler = set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $this->num['warnings']++;
            $this->warnings[] = "$errstr at line $errline of $errfile";
            return true;
        });
        $this->doTestcases();
        set_error_handler($lastErrorHandler);
        $features = array_filter($this->features, function ($value, $key) {
            return $key[0] != '_';
        }, ARRAY_FILTER_USE_BOTH);
        ksort($features);
        $this->lastStatus = [
            'name' => $packageName,
            'features' => $features,
            'num' => $this->num,
            'failures' => $this->failures,
            'warnings' => $this->warnings,
        ];
    }

    public function getStatus()
    {
        return $this->lastStatus;
    }

    private function doTestcases()
    {
        $lastUa = null;
        foreach ($this->testcases as $t) {
            if (!isset($t['ua'])) {
                $t['ua'] = $lastUa;
            } else {
                $lastUa = $t['ua'];
            }
            $this->doTestcase($t);
        }
        if (!$this->num['tests']) {
            $this->num['tests'] = $this->num['failures'] = 1;
        }
    }

    private function doTestcase(array $t)
    {
        if (isset($t['when'])) {
            $when = (array)$t['when'];
            foreach ($when as $feature) {
                $isRequired = $feature[0] !== '-';
                if (!$isRequired) {
                    $feature = substr($feature, 1);
                }
                if ($this->hasFeature($feature) != $isRequired) {
                    return;
                }
            }
        }
        if (!isset($t['always'])) {
            $this->num['tests']++;
            if (isset($t['text'])) {
                $this->load($t['text']);
            }
            $ok = $this->tryTest($t['ua'], $t['path'], $t['allowed']);
            if (empty($t['mayFail'])) {
                $this->tested($ok, $t['ua'], $t['path'], $t['allowed'], $t['message'] ?? null);
            }
        } else {
            $ok = $t['always'];
        }
        if (isset($t['ifSuccess'])) {
            $this->setFeature($t['ifSuccess'], $t['message'] ?? null, $ok);
        }
        if (isset($t['ifFailed'])) {
            $this->setFeature($t['ifFailed'], $t['message'] ?? null, !$ok);
        }
    }

    private function load(string $text)
    {
        $text = TestProfile::unescapeDelimiter($text);
        $this->lastText = $text;
        $this->parser->setText($text);
    }

    private function tryTest(string $ua, string $path, bool $allowed)
    {
        try {
            return !!$this->parser->isAllowed($ua, $path) === $allowed;
        } catch (\Throwable $e) {
            $this->num['errors']++;
            $this->addFailure($ua, $path, $allowed, (string)$e);
            return false;
        }
    }

    private function tested($ok, string $ua, string $path, bool $allowed, string $msg = null)
    {
        if (!$ok) {
            $this->num['failures']++;
            $this->addFailure($ua, $path, $allowed, $msg);
        }
        return $ok;
    }

    private function addFailure(string $ua, string $path, bool $allowed, string $msg = null)
    {
        $this->failures[] = [
            'ua' => $ua,
            'path' => $path,
            'text' => $this->lastText,
            'allowed' => $allowed,
            'message' => $msg,
        ];
    }

    private function hasFeature(string $name)
    {
        return !empty($this->features[$name]);
    }

    private function setFeature(string $name, string $desc = null, bool $value)
    {
        $this->features[$name] = $value ? ($desc ?? true) : false;
    }
}
