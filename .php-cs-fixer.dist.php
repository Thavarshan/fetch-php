<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$config = new Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'cast_spaces' => true,
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'general_phpdoc_annotation_remove' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'method_argument_space' => ['keep_multiple_spaces_after_comma' => false],
        'native_constant_invocation' => true,
        'new_with_parentheses' => true,
        'global_namespace_import' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_break_comment' => false,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'not_operator_with_successor_space' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_align' => true,
        'phpdoc_indent' => true,
        'phpdoc_separation' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => false,
        'protected_to_private' => false,
        'single_quote' => true,
        'single_line_throw' => false,
        'no_superfluous_phpdoc_tags' => false,
        'nullable_type_declaration' => true,
        'ternary_to_null_coalescing' => true,
        'trim_array_spaces' => true,
        'yoda_style' => false,
        'php_unit_method_casing' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true,
            'phpdoc_tags' => [],
        ],
        // Rule to solve error "Expected 1 space after class keyword"
        'class_definition' => [
            'space_before_parenthesis' => true,
        ],
    ])
    ->setFinder(
        Finder::create()
        ->exclude('vendor')
        ->in(__DIR__)
    );
