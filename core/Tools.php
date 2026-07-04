<?php

namespace nunas_download;

class Tools
{
    static function delDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delDir("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }

    static function writeLog($content)
    {
        $file = Config::$plugin_dir . "/log/log.txt";
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        $content = "[" . date('Y-m-d H:i:s') . "] " . $content . "\n";
        file_put_contents($file, $content, FILE_APPEND);
    }
}
