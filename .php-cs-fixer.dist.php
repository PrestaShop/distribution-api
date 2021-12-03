<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor', 'var', 'tests/ressources/modules', 'tests/ressources/prestashop'])
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true);
return $config->setRules([
    '@Symfony' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],
    'cast_spaces' => [
        'space' => 'single',
    ],
    'error_suppression' => [
        'mute_deprecation_error' => false,
        'noise_remaining_usages' => false,
        'noise_remaining_usages_exclude' => [],
    ],
    'function_to_constant' => false,
    'no_alias_functions' => false,
    'phpdoc_summary' => false,
    'phpdoc_align' => [
        'align' => 'left',
    ],
    'protected_to_private' => false,
    'psr_autoloading' => false,
    'self_accessor' => false,
    'yoda_style' => false,
    'no_superfluous_phpdoc_tags' => false,
])->setFinder($finder);