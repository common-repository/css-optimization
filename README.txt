=== CSS Optimization ===
Contributors: o10n
Donate link: https://github.com/o10n-x/
Tags: css, critical css, async, minify, editor, concat, minifier, concatenation, optimization, optimize, combine, merge, cache
Requires at least: 4.0
Requires PHP: 5.4
Tested up to: 4.9.4
Stable tag: 0.0.56
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced CSS optimization toolkit. Critical CSS, minification, concatenation, async loading, advanced editor, CSS Lint, Clean CSS (professional), beautifier and more.

== Description ==

This plugin is a toolkit for professional CSS optimization.

The plugin provides in a complete solution for CSS code optimization, CSS delivery optimization (async CSS loading) and Critical CSS management.

The plugin provides many unique innovations including conditional Critical CSS, timed CSS loading and/or rendering based on `requestAnimationFrame` with frame target, `requestIdleCallback`, element scrolled into view or a Media Query.

The plugin enables to render and unrender stylesheets based on a Media Query or element scrolled in and out of viewport enabling to optimize the CSS for individual devices (e.g. save +100kb of CSS on mobile devices or based on the [save-data header](https://developers.google.com/web/updates/2016/02/save-data)). 

With debug modus enabled, the browser console will show detailed information about the CSS loading and rendering process including a [Performance API](https://developer.mozilla.org/nl/docs/Web/API/Performance) result for an insight in the CSS loading performance of any given configuration.

The plugin contains an advanced CSS editor with CSS Lint, Clean-CSS code optimization and CSS Beautifier. The editor can be personalized with more than 30 themes.

Additional features can be requested on the [Github forum](https://github.com/o10n-x/wordpress-css-optimization/issues).

**This plugin is a beta release.**

Documentation is available on [Github](https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs).

== Installation ==

### WordPress plugin installation

1. Upload the `css-optimization/` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the plugin settings page.
4. Configure CSS Optimization settings. Documentation is available on [Github](https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs).

== Screenshots ==
1. CSS Code Optimization
2. CSS Delivery Optimization
3. Critical CSS Management
4. CSS Editor
5. Above The Fold Optimization


== Changelog ==

= 0.0.56 =
* Added: plugin update protection (plugin index).

= 0.0.55 =
* Bugfix: Yahoo YUI Compressor settings not saved correctly when values are empty.

= 0.0.54 =
* Added: Proxy option to delete or rewrite script-injected stylesheets ([@cwfaraday](https://wordpress.org/support/topic/emoji-js-isnt-handled/)).

= 0.0.53 =
* Core update (see changelog.txt)

= 0.0.52 =
* Bugfix: Minify based concat option not working.
* Bugfix: Net_URL2 exceptions thrown inside namespace.
* Added: Custom minifier option (support for Node.js, server software etc.)
* Added: Option to disable minification for individual stylesheets in async config filter (`"minify": false`)
* Added: Option to set minifier for individual stylesheets or concat groups in async config filter and concat group config.

= 0.0.51 =
* Added: Notice for CSS Lint code repair feature.

= 0.0.50 =
* Added: Regular Expression [Compressor.php from Minify](https://github.com/mrclay/minify) (mrclay)

= 0.0.49 =
* Added: support for multiple CSS minifiers.
* Added: Yahoo [YUI CSS Compressor PHP Port](https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port) v4.1.1

= 0.0.48 =
* Bugfix: HTTP/2 Server Push applied when HTTP/2 Optimization plugin is disabled.
* Bugfix: Async loaded concatenated stylesheet not pushed by HTTP/2 Server Push.

= 0.0.47 =
* Added: option to suppress CssMin.php CSS parser errors triggered by invalid CSS code. (@amber-tanaka)

= 0.0.45 =
* Bugfix: editor theme not loading after `wp_add_inline_script` update.

= 0.0.44 =
* Bugfix: script-injected stylesheet proxy not disabled.
* Improved: disable localStorage cache for external stylesheets. (@todo add no-cors proxy option)

See changelog.txt for older updates.

== Upgrade Notice ==

None.