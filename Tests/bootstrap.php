<?php

if (file_exists($file = __DIR__.'/autoload.php')) {
    require_once $file;
} else {
    require_once __DIR__.'/autoload.php.dist';
}