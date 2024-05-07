<?php

function get_current_context($path): void
{
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {
                $file = $path.DIRECTORY_SEPARATOR.$file;
                if (!is_dir($file) && str_ends_with($file, '.log')) {
                    $arr_file = explode(DIRECTORY_SEPARATOR, $file);
                    $time = strtotime(str_replace('_', '-', str_replace('.log','', end($arr_file))));
                    if ($time < strtotime('-2 day')) {
                        unlink($file);
                    }
                } elseif (is_dir($file)) {
                    get_current_context($file);
                }
            }
        }
        closedir($handle);
    }
}
get_current_context($argv[1] ?? '.');