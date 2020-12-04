<?php

declare(strict_types=1);

$root = dirname(realpath(__DIR__) . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

require_once $root . 'tests/_ci/functions.php';

// Folders
$folders = [
    cacheDir(),
    logsDir(),
    outputDir('image'),
    outputDir('image/imagick'),
    outputDir('image/gd'),
];

foreach ($folders as $folder) {
    if (true !== file_exists($folder)) {
        mkdir($folder);
    }
}

loadDefined();
