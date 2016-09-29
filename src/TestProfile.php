<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt;

class TestProfile
{
    const DELIMITER = '|';
    const ESCAPED_DELIMITER = '<<<|>>>';

    public static function getYamlPath()
    {
        return __DIR__ . '/../testcases.yaml';
    }

    public static function unescapeDelimiter(string $text)
    {
        $text = preg_replace_callback('#<<<\|>>>|\|#', function ($match) {
            return $match[0] === '|' ? "\x0d\x0a" : '|';
        }, $text);
        return $text;
    }
}
