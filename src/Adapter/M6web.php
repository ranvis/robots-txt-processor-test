<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

class M6web implements AdapterInterface
{
    private $parser;
    private $file;

    public static function isAvailable() : bool
    {
        return class_exists(\Roboxt\Parser::class);
    }

    public function __construct()
    {
        $this->parser = new class extends \Roboxt\Parser {
            private $text;

            public function read($filePath)
            {
                return $this->text;
            }

            public function setText($text)
            {
                $this->text = $text;
            }
        };
    }

    public function setText($text)
    {
        $this->parser->setText($text);
        $this->file = $this->parser->parse(null);
    }

    public function getPackageName() : string
    {
        return 'm6web/roboxt';
    }

    public function isAllowed($ua, $path) : bool
    {
        return $this->file->isUrlAllowedByUserAgent($path, $ua);
    }
}
