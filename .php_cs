<?php
return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'concat_space' => ['spacing' => 'one'],
        'simplified_null_return' => false,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_to_comment' => false,
        'cast_spaces' => false,
        'blank_line_after_opening_tag' => false,
        'single_blank_line_before_namespace' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_no_alias_tag' => false,
        'space_after_semicolon' => false,
        'yoda_style' => false,
        'no_break_comment' => false,

        // 2019 style updates with cs-fixer 2.14, all above are in sync with kernel
        '@PHPUnit57Migration:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'static_lambda' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude([
                'vendor',
                'ezpublish_legacy',
                'bundle/Resources/init_ini',
            ])
            ->files()->name('*.php')
    )
;
