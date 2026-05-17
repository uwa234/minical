<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Returns true when $module is a directory under $modules_path.
 */
function is_module_directory($modules_path, $module)
{
    if ($module === '.' || $module === '..') {
        return false;
    }

    return is_dir(rtrim($modules_path, '/\\') . DIRECTORY_SEPARATOR . $module);
}
