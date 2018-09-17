<?php

if(!function_exists('readline')) {
    function readline( $prompt = '> ' )
    {
        echo $prompt;
        return rtrim(fgets(STDIN), "\n");
    }
}

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function createLsLine($col1, $col2, $col3) {
    echo implode(' | ', [
        str_pad($col1, 25),
        str_pad($col2, 10),
        $col3
    ]) . PHP_EOL;
}

$path = ['$GLOBALS' => &$GLOBALS];

while(true) {

    $path_string = implode('/', array_keys($path));
    $prompt = $path_string . ' > ';
    $line = readline($prompt);
    $end = end($path);

    if(startsWith($line, 'cd')) {
        $line = trim(substr($line, 2));
        
        if($line == '..') {
            array_pop($path);
        } elseif(array_key_exists($line, $end) && is_array($end[$line])) {
            $path[$line] = &$end[$line];
        }
    } elseif(startsWith($line, 'ls')) {

        echo 'Contents of: ' . $path_string . PHP_EOL;

        createLsLine('Key', 'Type', 'Value');

        echo str_repeat('-', 50) . PHP_EOL;

        foreach($end as $k => $v) {

            if(is_array($v)) {
                $k = substr('📂  ' . $k, 0, 25);
                $value = '...';
            } elseif(is_object($v)) {
                $k = substr('⚙  ' . $k, 0, 25);
                $value = '...';
            } else {
                $k = substr('🔑  ' . $k, 0, 25);
                $value = $v;
            }

            echo implode(' | ', [
                str_pad($k, 25),
                str_pad(gettype($v), 10),
                $value
            ]) . PHP_EOL;
        }

        echo str_repeat('-', 50) . PHP_EOL;
    } elseif(startsWith($line, 'mkdir')) {
        $line = trim(substr($line, strlen('mkdir')));

        $end[$line] = [];

    } else {
        eval($line);
    }
}