<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class Filter implements AdapterInterface
{
    private $filter;
    private $recordSet;
    private $parser;

    public static function isAvailable() : bool
    {
        return true;
    }

    public function __construct($parser)
    {
        $this->filter = new \Ranvis\RobotsTxt\Filter();
        $this->parser = $parser;
    }

    public function setText($text)
    {
        $this->recordSet = $this->filter->getRecordSet($text);
    }

    public function getPackageName() : string
    {
        return "f:" . $this->parser->getPackageName();
    }

    public function isAllowed($ua, $path) : bool
    {
        $text = (string)$this->recordSet->extract($ua);
        $this->parser->setText($text);
        return $this->parser->isAllowed('*', $path);
    }
}
