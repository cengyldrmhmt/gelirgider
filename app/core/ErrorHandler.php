<?php
require_once __DIR__ . '/Logger.php';
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::log("ERROR [$errno] $errstr in $errfile on line $errline");
});
set_exception_handler(function($exception) {
    Logger::log('EXCEPTION: ' . $exception->getMessage());
}); 