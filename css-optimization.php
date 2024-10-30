<?php
namespace O10n;

/**
 * CSS Optimization
 *
 * Advanced CSS optimization toolkit. Critical CSS, minification, concatenation, async loading, advanced editor, CSS Lint, Clean CSS (professional), beautifier and more.
 *
 * @link              https://github.com/o10n-x/
 * @package           o10n
 *
 * @wordpress-plugin
 * Plugin Name:       CSS Optimization
 * Description:       Advanced CSS optimization toolkit. Critical CSS, minification, concatenation, async loading, advanced editor, CSS Lint, Clean CSS (professional), beautifier and more.
 * Version:           0.0.56
 * Author:            Optimization.Team
 * Author URI:        https://optimization.team/
 * Text Domain:       o10n
 * Domain Path:       /languages
 */

if (! defined('WPINC')) {
    die;
}

// abort loading during upgrades
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

// settings
$module_version = '0.0.56';
$minimum_core_version = '0.0.38';
$plugin_path = dirname(__FILE__);

// load the optimization module loader
if (!class_exists('\O10n\Module')) {
    require $plugin_path . '/core/controllers/module.php';
}

// load module
new Module(
    'css',
    'CSS Optimization',
    $module_version,
    $minimum_core_version,
    array(
        'core' => array(
            'http',
            'client',
            'proxy',
            'tools',
            'css',
            'criticalcss',
            'cssminifytest'
        ),
        'admin' => array(
            'AdminCss',
            'AdminEditor'
        ),
        'admin_global' => array(
            'AdminGlobalcss'
        )
    ),
    2,
    array(
        'src' => array(
            'path' => 'css/src/',
            'file_ext' => '.css',
            'alt_exts' => array('.css.map'),
            'expire' => 259200 // expire after 3 days
        ),
        'concat' => array(
            'hash_id' => true, // store data by database index id
            'path' => 'css/concat/',
            'id_dir' => 'css/',
            'file_ext' => '.css',
            'alt_exts' => array('.css.map'),
            'expire' => 86400 // expire after 1 day
        ),
        'proxy' => array(
            'path' => 'css/proxy/',
            'file_ext' => '.css',
            'expire' => 86400 // expire after 1 day
        )
    ),
    __FILE__
);
