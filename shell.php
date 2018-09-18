<?php

class InspectionShell {


    const COMMAND_LIST = [
        'ls',
        'lsr',
        'cd',
        'mkdir',
        'store',
        'rm',
        'rmdir',
        'new'
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
     * 
     */
    protected function getIcon($value) {
        $type = gettype($value);
        $icon = self::VALUE_ICON;
        switch($type) {
            case 'array':
                $icon = self::ARRAY_ICON;
            break;

            case 'object':
                $icon = self::OBJECT_ICON;
            break;
        }
        return $icon;
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
            $this->path[$dir] = &$this->current[$dir];
        }

        end($this->path);
        $key = key($this->path);
        $this->current = &$this->path[$key];

        $this->prompt = implode('/', array_keys($this->path)) . ' > ';
    }

    /**
     * 
     */
    public function ls($args = []) {
        $this->createLsLine('Key', 'Type', 'Value');

        echo str_repeat('═', 50) . PHP_EOL;

        $this->createLsLine(str_pad(self::ARRAY_ICON . '.', 25), 'array', '...');
        $this->createLsLine(str_pad(self::ARRAY_ICON . '..', 25), 'array', '...');

        foreach($this->current as $k => $v) {

            $k = substr($this->getIcon($v) . $k, 0, 25);

            if(is_array($v) || is_object($v)) {
                $value = '...';
            } else {
                $value = $v;
            }

            $this->createLsLine(str_pad($k, 25), str_pad(gettype($v), 10), $value);
        }

        echo str_repeat('═', 50) . PHP_EOL;
    }

    /**
     * 
     */
    public function lsr($args = [], $node = null, $level = 0) {
        if(empty($node) && $level == 0) {
            $node = $this->current;
        }

        foreach($node as $k => $v) {
            echo str_repeat(' ', $level * 5) . '╠⇒ ' . $this->getIcon($v) . $k . PHP_EOL; 

            if(is_array($v)) {
                
                $this->lsr($args, $v, $level + 1);
            }
        }
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

    public function store($args = []) {
        $key = array_shift($args);
        $value = array_shift($args);

        if(!empty($key) && !empty($value)) {
            $this->current[$key] = $value;
        }
    }

    public function new($args = []) {
        $classname = array_shift($args);
        $key = array_pop($args);
        
        try {
            if(empty($key) || $key == 'null') {
                $this->current[] = new $classname(...$args);
            } else {
                $this->current[$key] = new $classname(...$args);
            }
        } catch(Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function rm($args = []) {
        foreach($args as $v) {
            if(array_key_exists($v, $this->current)) {
                if(!is_array($this->current[$v])) {
                    unset($this->current[$v]);
                } else {
                    printf('%s is an Array...%s', $v, PHP_EOL);
                }
            }
        }
    }

    public function rmdir($args) {
        foreach($args as $v) {
            if(array_key_exists($v, $this->current)) {
                if(is_array($v) && empty($v)) {
                    unset($this->current[$v]);
                } else {
                    printf('%s is not an empty Array...%s', $v, PHP_EOL);
                }
            }
        }
    }

}

new InspectionShell();
