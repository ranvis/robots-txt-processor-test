<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class AnyCommand extends Command
{
    public static function isAvailable() : bool
    {
        return true;
    }

    public function __construct($name, ...$args)
    {
        parent::__construct($name, 'any', ...$args);
    }

    public function getPackageName() : string
    {
        return $this->name;
    }

    protected function getCommand()
    {
        return implode(' ', $this->args);
    }
}
