<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class Diggin implements AdapterInterface
{
    private $accepter;

    public static function isAvailable() : bool
    {
        return class_exists(\Diggin\RobotRules\Accepter\TxtAccepter::class);
    }

    public function __construct()
    {
        $this->accepter = new \Diggin\RobotRules\Accepter\TxtAccepter();
    }

    public function setText($text)
    {
        $this->accepter->setRules(\Diggin\RobotRules\Parser\TxtStringParser::parse($text));
    }

    public function getPackageName() : string
    {
        return 'diggin/diggin-robotrules';
    }

    public function isAllowed($ua, $path) : bool
    {
        $this->accepter->setUserAgent($ua);
        return $this->accepter->isAllow($path);
    }
}
