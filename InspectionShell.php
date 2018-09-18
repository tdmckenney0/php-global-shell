<?php
/**
 * InspectionShell
 * 
 * @author Tanner Mckenney<@tdmckenney0>
 */
class InspectionShell {

    /**
     * Valid Commands
     */
    const COMMAND_LIST = [
        'ls',
        'lsr',
        'cd',
        'mkdir',
        'store',
        'rm',
        'rmdir',
        'new',
        'chroot'
    ];

    /**
     * Icons
     */
    const ARRAY_ICON = "📂  ";
    const OBJECT_ICON = "⚙️  ";
    const VALUE_ICON = "🔑  ";
    const VALUE_ARRAY_ICON = "🔗";

    /**
     * Formatting
     */
    const LS_COL_FORMAT = "%-25.25s║ %-10.10s║ %-15s\n";

    private $SCOPE = [];

    /**
     * Main Terminal Loop controller
     */
    private $active = true;

    /**
     * Various Pointers
     */
    private $path = null;
    private $current = null;
    private $prompt = null;

    public function __construct($SCOPE = []) {

        if(is_array($SCOPE) && !empty($SCOPE)) {
            $this->SCOPE = &$SCOPE;
            $this->changeRoot($this->SCOPE, '$SCOPE');
        } else {
            $this->changeRoot($GLOBALS, '$GLOBALS');
        }
        
        while($this->active) {
            $line = $this->readline($this->prompt);
            $args = $this->parseLine($line);
            $command = array_shift($args);

            if(in_array($command, self::COMMAND_LIST)) {
                $this->{$command}($args);
            } else {
                $this->run($line);  
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

    protected function updatePrompt($str = "") {
        if(is_string($str)) {
            return $this->prompt = sprintf('%s > ', $str);
        }
    }

    protected function changeRoot(Array &$root, $name = '$?') {
        if(!empty($root)) {
            $this->path = [$name => &$root];
            $this->current = &$root;
            $this->updatePrompt($name);
        }
    }

    protected function run($str = "") {
        if(!$this->endsWith($str, ';')) {
            $str = $str . ';';
        }

        $payload = static function ($line, &$_) {
            @eval($line); // evil.
        };

        try {
            $payload($str, $this->current);
        } catch(Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
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

        if($dir == '..' && count($this->path) > 1) {
            array_pop($this->path);
        } elseif(array_key_exists($dir, $this->current) && is_array($this->current[$dir])) {
            $this->path[$dir] = &$this->current[$dir];
        }

        end($this->path);
        $key = key($this->path);
        $this->current = &$this->path[$key];

        $this->updatePrompt(implode('/', array_keys($this->path)));
    }

    /**
     * ls
     * 
     * Lists the current Array's contents.
     * 
     * @param Array $args
     */
    public function ls($args = []) {

        echo self::VALUE_ICON . sprintf(self::LS_COL_FORMAT, 'Key', 'Type', 'Value');

        echo str_repeat('═', 50) . PHP_EOL;

        echo self::ARRAY_ICON . sprintf(self::LS_COL_FORMAT, '.', 'Array', self::VALUE_ARRAY_ICON);
        echo self::ARRAY_ICON . sprintf(self::LS_COL_FORMAT, '..', 'Array', self::VALUE_ARRAY_ICON);

        foreach($this->current as $k => $v) {

            // Type Column
            $type = gettype($v);
            $type = ($type === 'object') ? get_class($v) : ucfirst($type);

            // Value Column
            $value = (is_array($v) || is_object($v)) ? self::VALUE_ARRAY_ICON : $v;

            // Draw
            echo $this->getIcon($v) . sprintf(self::LS_COL_FORMAT, $k, $type, $value);
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

    public function rmdir($args = []) {
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

    public function chroot($args = []) {
        $root = array_shift($args);
        if($root == '$GLOBALS') {
            $this->changeRoot($GLOBALS, '$GLOBALS');
        } elseif($root == '$SCOPE') {
            $this->changeRoot($this->SCOPE, '$SCOPE');
        } else {
            if(array_key_exists($root, $this->current) && is_array($this->current[$root])) {
                $this->changeRoot($this->current[$root], '$' . $root);
            }
        }
    }

}