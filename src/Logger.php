<?php

class Logger
{

    public static function log($message)
    {
        $file = __DIR__ . '/../logs/app.log';
        file_put_contents($file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
    }
}
