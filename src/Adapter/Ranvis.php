<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class Ranvis implements AdapterInterface
{
    private $parser;

    public static function isAvailable() : bool
    {
        return class_exists(\Ranvis\RobotsTxt\Tester::class);
    }

    public function __construct($options = [])
    {
        $this->parser = new \Ranvis\RobotsTxt\Tester($options);
    }

    public function setText($text)
    {
        $this->parser->setSource($text);
    }

    public function getPackageName() : string
    {
        return 'ranvis/robots-txt-processor';
    }

    public function isAllowed($ua, $path) : bool
    {
        return $this->parser->isAllowed($path, $ua);
    }
}
