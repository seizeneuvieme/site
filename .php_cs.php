<?php

declare(strict_types=1);

$config = new PhpCsFixer\Config();
$config->setUsingCache(false)
    ->setRiskyAllowed(true);

$config->setFinder(
    PhpCsFixer\Finder::create()
        ->in(
            [
                'src',
                'tests',
            ]
        )
);

$config->setRules(
    [
        '@Symfony'                              => true,
        '@PSR2'                                 => true,
        'array_syntax'                          => ['syntax' => 'short'],
        'binary_operator_spaces'                => [
            'operators' => [
                '='  => 'align_single_space_minimal',
                '=>' => 'align_single_space_minimal',
            ],
        ],
        'combine_consecutive_issets'            => true,
        'combine_consecutive_unsets'            => true,
        'linebreak_after_opening_tag'           => true,
        'list_syntax'                           => ['syntax' => 'short'],
        'no_alternative_syntax'                 => true,
        'no_unreachable_default_argument_value' => true,
        'no_unused_imports'                     => true,
        'no_superfluous_elseif'                 => true,
        'no_superfluous_phpdoc_tags'            => ['allow_mixed' => true],
        'phpdoc_to_comment'                     => false,
        'no_useless_else'                       => true,
        'no_useless_return'                     => true,
        'ordered_class_elements'                => true,
        'ordered_imports'                       => true,
        'php_unit_method_casing'                => ['case' => 'snake_case'],
        'semicolon_after_instruction'           => true,
        'strict_param'                          => true,
        'ternary_to_null_coalescing'            => true,
        'void_return'                           => true,
        'yoda_style'                            => [
            'identical'        => false,
            'equal'            => false,
            'less_and_greater' => null,
        ],
    ]
);

return $config;
