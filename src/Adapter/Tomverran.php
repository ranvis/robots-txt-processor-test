<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class Tomverran implements AdapterInterface
{
    private $parser;

    public static function isAvailable() : bool
    {
        return class_exists(\tomverran\Robot\RobotsTxt::class);
    }

    public function __construct()
    {
    }

    public function setText($text)
    {
        $this->parser = new \tomverran\Robot\RobotsTxt($text);
    }

    public function getPackageName() : string
    {
        return 'tomverran/robots-txt-checker';
    }

    public function isAllowed($ua, $path) : bool
    {
        return $this->parser->isAllowed($ua, $path);
    }
}
