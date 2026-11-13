<?php

declare(strict_types=1);

/*
 * Regenerates the core's model classes from the frozen AG-UI JSON Schema.
 *
 *     php tools/generate-model.php
 *
 * Files carrying the "Generated from the AG-UI JSON Schema" header are owned by this
 * script: they are deleted and rewritten on every run. Everything else in src/ is
 * hand-written and left alone.
 */

use AgUi\Tools\ModelGenerator;

require __DIR__.'/../vendor/autoload.php';

$root = dirname(__DIR__);
$src = $root.'/packages/core/src';
$schema = json_decode(file_get_contents($root.'/resources/ag-ui/spec-1.0/schema.json'), true, flags: \JSON_THROW_ON_ERROR);

$marker = '// Generated from the AG-UI JSON Schema';
$deleted = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile() && str_contains(file_get_contents($file->getPathname()), $marker)) {
        unlink($file->getPathname());
        ++$deleted;
    }
}

$files = (new ModelGenerator($schema))->generate();
foreach ($files as $path => $code) {
    @mkdir(dirname($src.'/'.$path), 0o777, true);
    file_put_contents($src.'/'.$path, $code);
}

// The output follows the project's coding standard, so regenerating never leaves a diff for it.
passthru(escapeshellarg($root.'/vendor/bin/php-cs-fixer').' fix --quiet '.escapeshellarg($src), $exitCode);
if (0 !== $exitCode) {
    fwrite(\STDERR, "php-cs-fixer failed on the generated files.\n");
    exit(1);
}

printf("Generated %d files (%d deleted first).\n", count($files), $deleted);
