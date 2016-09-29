<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt;

class TestcaseSet
{
    private $info;
    private $testcases;

    public function __construct(array $info, array $testcases)
    {
        $this->info = $info;
        $this->testcases = $testcases;
    }

    public function getInfo() : array
    {
        return $this->info;
    }

    public function getTestcases() : array
    {
        return $this->testcases;
    }

    public static function parse(string $filePath) : self
    {
        $data = yaml_parse_file($filePath, 0, $count, [
            '!build' => __CLASS__ . '::_yamlTagBuild',
            '!escapeDelim' => __CLASS__ . '::_yamlTagEscapeDelim',
            '!repeat' => __CLASS__ . '::_yamlTagRepeat',
        ]);
        if ($data === false) {
            throw new \DomainException("YAML error");
        }
        $info = array_shift($data);
        if (!isset($info['version'])) {
            throw new \DomainException("First record should be a meta information.");
        }
        $testcases = self::flattenTestcases($data, []);
        return new self($info, $testcases);
    }

    private static function flattenTestcases(array $list, array $parent)
    {
        $testcases = [];
        $lastText = null;
        while ($list) {
            $testcase = array_shift($list);
            if (isset($testcase['variations'])) {
                $variations = $testcase['variations'];
                unset($testcase['variations']);
                foreach ($variations as $variation) {
                    $vTestcase = $testcase;
                    foreach ($vTestcase as $key => $value) {
                        if (!is_string($value)) {
                            ;
                        } elseif (preg_match('/\A\{\{([\w-]+)\}\}\z/', $value, $match)) {
                            $vTestcase[$key] = $variation[$match[1]] ?? $match[1];
                        } else {
                            $vTestcase[$key] = preg_replace_callback('/\{\{([\w-]+)\}\}/', function ($match) use ($variation) {
                                return $variation[$match[1]] ?? $match[1];
                            }, $value);
                        }
                    }
                    array_unshift($list, $vTestcase);
                }
            } elseif (isset($testcase['expand'])) {
                $subList = $testcase['expand'];
                unset($testcase['expand']);
                if (isset($testcase['ifSuccess']) || isset($testcase['ifFailed'])) {
                    throw new \DomainException("Cannot have ifSuccess/ifFailed with expand");
                }
                $list = array_merge($list, self::flattenTestcases($subList, $testcase));
            } else {
                if (isset($testcase['when']) && isset($parent['when'])) {
                    $testcase['when'] = array_merge((array)$testcase['when'], (array)$parent['when']);
                }
                $testcase += $parent;
                if (isset($testcase['text'])) {
                    if ($lastText === $testcase['text']) {
                        unset($testcase['text']);
                    } else {
                        $lastText = $testcase['text'];
                    }
                }
                $testcases[] = $testcase;
            }
        }
        return $testcases;
    }

    public static function _yamlTagBuild(array $value)
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = call_user_func(__FUNCTION__, $item);
            }
        }
        unset($item);
        return implode('', $value);
    }

    public static function _yamlTagEscapeDelim(array $value)
    {
        return str_replace(TestProfile::DELIMITER, TestProfile::ESCAPED_DELIMITER, $value[0]);
    }

    public static function _yamlTagRepeat(array $value)
    {
        return str_repeat($value[0], $value[1]);
    }
}
