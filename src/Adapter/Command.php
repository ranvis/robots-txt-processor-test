<?php
/**
 * @author SATO Kentaro
 * @license BSD 2-Clause License
 */

namespace Ranvis\RobotsTxt\Adapter;

abstract class Command implements AdapterInterface
{
    protected $name;
    protected $module;
    protected $args;

    private $text;
    private $proc;
    private $pipes;

    public function __construct($name, $module, ...$args)
    {
        $this->name = $name;
        $this->module = $module;
        $this->args = array_map(function ($value) {
            return escapeshellarg($value);
        }, $args);
    }

    public function __destruct()
    {
        if ($this->proc) {
            proc_close($this->proc);
        }
    }

    abstract protected function getCommand();

    public function setText($text)
    {
        $this->text = $text;
    }

    public function isAllowed($ua, $path) : bool
    {
        if (!$this->proc) {
            $proc = proc_open($this->getCommand(), [
                0 => ['pipe', 'r'],
                ['pipe', 'w'],
                ['pipe', 'w'],
            ], $pipes, null, null, [
                'bypass_shell' => true
            ]);
            if (!$proc) {
                throw new \RuntimeException("Unable to spawn external command");
            }
            $this->proc = $proc;
            $this->pipes = $pipes;
        }
        $pipes = $this->pipes;
        $header = pack('N3', strlen($this->text), strlen($ua), strlen($path));
        $content = $this->text . $ua . $path;
        fwrite($pipes[0], $header . $content);
        fflush($pipes[0]);
        $result = rtrim(fgets($pipes[1]));
        if (strlen($result) != 1) {
            throw new \RuntimeException("Invalid external command output: '$result'");
        }
        $result = $result == '1';
        $errors = null;
        while (!feof($pipes[2]) && !preg_match('#^<<<END>>>#', ($line = fgets($pipes[2])))) {
            $line = trim($errors);
            if (strlen($line)) {
                $errors .= $line;
            }
        }
        if ($errors != null) {
            trigger_error($errors, E_USER_WARNING);
        }
        return $result;
    }
}
