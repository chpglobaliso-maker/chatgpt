<?php
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    add_editor_style('reference.css');
    add_editor_style('interior.css');
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('chp-global', get_stylesheet_uri(), [], '1.4.1');
    wp_enqueue_style('chp-reference', get_template_directory_uri() . '/reference.css', ['chp-global'], '1.4.1');
    wp_enqueue_style('chp-interior', get_template_directory_uri() . '/interior.css', ['chp-reference'], '1.4.1');
});


