<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

interface AdapterInterface
{
    public static function isAvailable() : bool;
    public function setText($text);
    public function getPackageName() : string;
    public function isAllowed($ua, $path) : bool;
}
