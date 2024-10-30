<?php
namespace O10n;

/**
 * CSS Minifier Performance Test Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Cssminifytest extends Controller implements Controller_Interface
{
    private $stylesheets = array();

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        // instantiate controller
        return parent::construct($Core, array(
            'env',
            'client',
            'file',
            'cache',
            'options',
            'url',
            'admin'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // disabled
        if (!$this->env->is_optimization()) {
            return;
        }

        /**
         * Load critical css in template_redirect hook, apply conditions etc.
         */
        add_action('template_redirect', array($this,'load_test'), $this->first_priority);
    }

    /**
     * Load performance test
     */
    final public function load_test()
    {
        if (!is_admin() && (isset($_GET['o10n-css-perf-test']))) {

            // require logged in admin
            if ((!is_user_logged_in() || !current_user_can('manage_options'))) {
                wp_die('No permission');
            }

            // critical css editor
            if (isset($_GET['o10n-css-perf-test'])) {

                // register stylesheets
                add_filter('o10n_stylesheet_pre', array($this, 'register_stylesheet'), PHP_INT_MAX);

                // delete scripts
                add_filter('o10n_script_pre', function () {
                    return 'delete';
                }, PHP_INT_MAX);

                add_filter('o10n_html_final', array($this, 'output_test_results'), PHP_INT_MAX);
            }
        }
    }

    /**
     * Register stylesheet for test
     */
    final public function register_stylesheet($href)
    {
        $this->stylesheets[] = $href;

        // delete from HTML
        return 'delete';
    }

    /**
     * Critical CSS editor view
     */
    final public function output_test_results($HTML)
    {
        if (stripos($HTML, "<html") === false || stripos($HTML, "<xsl:stylesheet") !== false) {
            // not valid HTML
            return $HTML;
        }

        // disable scripts
        $HTML = preg_replace('|<(\/)?script|i', '<$1noscript', $HTML);

        // minify stylesheets

        // testscript
        $testscript = '<script>alert('.json_encode($this->stylesheets).');</script>';

        // add performance test script
        if (strpos($HTML, '<head') !== false) {
            $HTML = preg_replace('|(<head[^>]*>)|i', '$1' . $testscript, $HTML);
        } else {
            $HTML .= $testscript;
        }

        return $HTML;
    }

    /**
     * Critical CSS editor iframe view
     */
    final public function editor_iframe_view($HTML)
    {
        if (stripos($HTML, "<html") === false || stripos($HTML, "<xsl:stylesheet") !== false) {
            // not valid HTML
            return $HTML;
        }

        // iframe
        $iframe_script = '<script>var o10n_css_path=' . json_encode($this->core->modules('css')->dir_url()) . ';</script><script src="' . $this->core->modules('css')->dir_url() . 'public/js/view-css-editor-iframe.js"></script>';

        if (preg_match('/(<head[^>]*>)/Ui', $HTML, $out)) {
            $HTML = str_replace($out[0], $out[0] . $iframe_script, $HTML);
        } else {
            $HTML .= $iframe_script;
        }

        return $HTML;
    }

    /**
     * Minify stylesheets
     */
    final private function minify($CSS)
    {
        $this->last_used_minifier = false;

        // load PHP minifier
        if (!class_exists('O10n\CssMin')) {
            
            // autoloader
            require_once $this->core->modules('css')->dir_path() . 'lib/CssMin.php';
        }

        // minify
        try {
            $minified = CssMin::minify($CSS, $this->options->get('css.critical.minify.cssmin.filters.*'), $this->options->get('css.critical.minify.cssmin.plugins.*'));
        } catch (\Exception $err) {
            throw new Exception('PHP CssMin failed: ' . $err->getMessage(), 'css');
        }
        if (!$minified && $minified !== '') {
            if (CssMin::hasErrors()) {
                throw new Exception('PHP CssMin failed: <ul><li>' . implode("</li><li>", \CssMin::getErrors()) . '</li></ul>', 'css');
            } else {
                throw new Exception('PHP CssMin failed: unknown error', 'css');
            }
        }

        $this->last_used_minifier = 'php';

        return trim($minified);
    }
}
