<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class T1gor implements AdapterInterface
{
    private $parser;

    public static function isAvailable() : bool
    {
        return class_exists(\RobotsTxtParser::class);
    }

    public function __construct()
    {
    }

    public function setText($text)
    {
        $this->parser = new \RobotsTxtParser($text);
    }

    public function getPackageName() : string
    {
        return 't1gor/robots-txt-parser';
    }

    public function isAllowed($ua, $path) : bool
    {
        return $this->parser->isAllowed($path, $ua);
    }
}
