<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class Bee4 implements AdapterInterface
{
    private $parser;

    public static function isAvailable() : bool
    {
        return class_exists(\Bee4\RobotsTxt\Parser::class);
    }

    public function __construct()
    {
    }

    public function setText($text)
    {
        $this->parser = \Bee4\RobotsTxt\Parser::parse($text);
    }

    public function getPackageName() : string
    {
        return 'bee4/robots.txt';
    }

    public function isAllowed($ua, $path) : bool
    {
        return $this->parser->match($ua, $path);
    }
}
