<?php

class InspectionShell {


    const COMMAND_LIST = [
        'ls',
        'cd',
        'mkdir'
    ];

    const ARRAY_ICON = "📂  ";
    const OBJECT_ICON = "⚙  ";
    const VALUE_ICON = "🔑  ";

    private $active = true;

    private $path = null;
    private $current = null;
    private $prompt = '$GLOBALS > ';

    public function __construct() {

        $this->path = ['$GLOBALS' => &$GLOBALS];
        $this->current = &$GLOBALS;

        while($this->active) {
            $line = $this->readline($this->prompt);
            $args = $this->parseLine($line);
            $command = array_shift($args);

            if(in_array($command, self::COMMAND_LIST)) {
                $this->{$command}($args);
            } else {
                if(!$this->endsWith($line, ';')) {
                    $line = $line . ';';
                }   
                try {
                    @eval($line);
                } catch(Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }

    /**
     * 
     */
    protected function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * 
     */
    protected function endsWith($haystack, $needle) {
        $length = strlen($needle);

        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    /**
     * 
     */
    protected function readline($prompt = '> ') {
        echo $prompt;
        return rtrim(fgets(STDIN), "\n");
    }
    /**
     * 
     */
    protected function createLsLine($col1, $col2, $col3) {
        echo implode(' ║ ', [
            str_pad($col1, 25),
            str_pad($col2, 10),
            $col3
        ]) . PHP_EOL;
    } 

    /**
     * 
     */
    protected function parseLine($line = '') {
        if(empty($line)) {
            $line = $this->readline();
        }

        $stack = explode(' ', $line);

        return array_map('trim', $stack);
    }

    /**
     * Commands
     */

    /**
     * cd
     * 
     * Changes the directory.
     */
    public function cd($args = []) {
        $dir = array_shift($args);

        if($dir == '..') {
            array_pop($this->path);
        } elseif(array_key_exists($dir, $this->current) && is_array($this->current[$dir])) {
            $this->path[$dir] = $this->current = &$this->current[$dir];
        }

        $this->prompt = implode('/', array_keys($this->path)) . ' > ';
    }

    /**
     * 
     */
    public function ls($args = []) {
        $this->createLsLine('Key', 'Type', 'Value');

        echo str_repeat('═', 50) . PHP_EOL;

        foreach($this->current as $k => $v) {

            if(is_array($v)) {
                $k = substr(self::ARRAY_ICON . $k, 0, 25);
                $value = '...';
            } elseif(is_object($v)) {
                $k = substr(self::OBJECT_ICON . $k, 0, 25);
                $value = '...';
            } else {
                $k = substr(self::VALUE_ICON . $k, 0, 25);
                $value = $v;
            }

            $this->createLsLine(str_pad($k, 25), str_pad(gettype($v), 10), $value);
        }

        echo str_repeat('═', 50) . PHP_EOL;
    }

    /**
     * 
     */
    public function mkdir($args = []) {
        $dir = array_shift($args);
        if(!empty($dir)) {
            $this->current[$dir] = [];
        }
    }

}

new InspectionShell();
