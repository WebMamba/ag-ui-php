<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/packages', __DIR__.'/tools'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        // Generated docblocks carry the schema's long descriptions: aligned columns would
        // push them off the screen.
        'phpdoc_align' => ['align' => 'left'],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
