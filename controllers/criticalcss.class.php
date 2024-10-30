<?php
namespace O10n;

/**
 * Critical CSS Optimization Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Criticalcss extends Controller implements Controller_Interface
{
    private $debug_view = false;

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
        add_action('template_redirect', array($this,'load_critical_css'), $this->first_priority);
    }

    /**
     * Load critical CSS
     */
    final public function load_critical_css()
    {
        if (!is_admin() && (isset($_GET['o10n-css']) || isset($_GET['o10n-no-css']) || isset($_GET['o10n-full-css']))) {
            if (!$this->options->bool('css.critical.editor_public.enabled') && (!is_user_logged_in() || !current_user_can('manage_options'))) {
                wp_die('No permission');
            }

            // critical css editor
            if (isset($_GET['o10n-css'])) {
                add_filter('o10n_html_final', array($this, 'editor_view'), PHP_INT_MAX);
            }

            // no CSS display, filter out CSS
            if (isset($_GET['o10n-no-css'])) {
                add_filter('o10n_stylesheet_pre', function () {
                    return 'delete';
                }, PHP_INT_MAX);

                // mark iframe view
                $this->debug_view = true;

                add_filter('o10n_html_final', array($this, 'editor_iframe_view'), PHP_INT_MAX);
            }

            // full CSS iframe view
            if (isset($_GET['o10n-full-css'])) {

                // mark iframe view
                $this->debug_view = true;
                
                add_filter('o10n_html_final', array($this, 'editor_iframe_view'), PHP_INT_MAX);
            }
        }

        $files = $this->options->get('css.critical.files');
        if (empty($files)) {
            return;
        }

        // critical css directory
        $critical_css_directory = $this->file->theme_directory(array('critical-css'));

        // method cache
        $method_cache = array();
        $method_param_cache = array();

        $active_files = array();
        $updated = false; // file index updated?

        foreach ($files as $index => $file) {

            // index conditions
            if (isset($file['conditions']) && $file['conditions']) {

                // process conditions
                $match = false;
                foreach ($file['conditions'] as $match_any) {
                    if (!is_array($match_any)) {
                        continue;
                    }

                    // single condition
                    if (!isset($match_any[0]) || !is_array($match_any[0])) {
                        $match_all = array($match_any);
                    } else {
                        $match_all = $match_any;
                    }

                    $group_match = true;
                    foreach ($match_all as $condition) {

                        // method to call
                        $method = (isset($condition['method'])) ? $condition['method'] : false;
            
                        // verify method
                        if (!$method || !function_exists($method)) {
                            $this->admin->add_notice('Critical CSS condition method does not exist in '.$this->file->safe_path($conditions_file).' ('.$method.').', 'css');
                            continue;
                        }

                        // parameters to apply to method
                        $arguments = (isset($condition['arguments'])) ? $condition['arguments'] : null;

                        // result to expect from method
                        $expected_result = (isset($condition['result'])) ? $condition['result'] : true;

                        // call method
                        if ($arguments === null) {
                            if (isset($method_cache[$method])) {
                                $result = $method_cache[$method];
                            } else {
                                $result = $method_cache[$method] = call_user_func($method);
                            }
                        } else {
                            $arguments_key = json_encode($arguments);

                            if (isset($method_cache[$method]) && isset($method_cache[$method][$arguments_key])) {
                                $result = $method_cache[$method][$arguments_key];
                            } else {
                                if (!isset($method_param_cache[$method])) {
                                    $method_param_cache[$method] = array();
                                }
                                $result = $method_param_cache[$method][$arguments_key] = call_user_func_array($method, $arguments);
                            }
                        }

                        // expected result is array of options
                        if (is_array($expected_result)) {
                            if (!in_array($result, $expected_result, true)) {
                                $group_match = false; // group doesn't match
                    
                                break 1; // stop processing condition (group)
                            }
                        } else {
                            if ($result !== $expected_result) {
                                $group_match = false; // group doesn't match
                    
                                break 1; // stop processing condition (group)
                            }
                        }
                    }

                    if ($group_match) {
                        $match = true; // match found
            
                        break 1; // stop processing conditions
                    }
                }

                if ($match) {
                    $active_files[] = $file;
                }
            } else {
                $active_files[] = $file;
            }
        }

        if ($updated) {
            $this->options->save(array('css.critical.files' => $files));
        }

        // no active critical CSS files
        if (empty($active_files)) {
            return;
        }

        // sort by priority
        usort($active_files, function ($a, $b) {
            if (intval($a['priority']) === intval($b['priority'])) {
                return 0;
            }

            return (intval($a['priority']) < intval($b['priority'])) ? -1 : 1;
        });

        $criticalcss = array();
        foreach ($active_files as $file) {
            if (isset($file['file'])) {
                $filepath = $this->file->theme_directory(array('critical-css')) . $file['file'];

                // check if file exists
                if (file_exists($filepath)) {
                    $source = trim(file_get_contents($filepath));

                    if ($this->debug_view) {
                        $cssdata = "\n\n/*\n * @file " . $file['file'];

                        if (isset($file['title'])) {
                            $cssdata .= "\n * @title " . $file['title'];
                        }

                        if (isset($file['priority'])) {
                            $cssdata .= "\n * @priority " . $file['priority'];
                        }

                        if (isset($file['conditions']) && isset($file['conditions'][1])) {
                            $cssdata .= "\n * @conditions " . json_encode($file['conditions'][1]);
                        }

                        $cssdata .= "\n */\n" . $source;
                    } else {
                        $cssdata = $source;
                    }

                    $criticalcss[] = $cssdata;
                }
            }
        }

        // concat CSS
        $criticalcss = trim(implode(' ', $criticalcss));

        $concat_hash = false;

        // minify critical CSS
        if ($criticalcss !== '' && $this->options->bool('css.critical.minify.enabled')) {

            // concat hash
            $concat_hash = md5($criticalcss . json_encode(array($this->options->get('css.critical.minify.cssmin.filters'), $this->options->get('css.critical.minify.cssmin.plugins'))));
 
            // load from cache
            if ($this->cache->exists('css', 'concat', $concat_hash)) {

                // preserve cache file based on access
                $this->cache->preserve('css', 'concat', $concat_hash, (time() - 3600));

                // get CSS from cache
                $cachecss = $this->cache->get('css', 'concat', $concat_hash);
                if ($cachecss) {
                    $criticalcss = $cachecss;
                }
            } else {

                // create concatenated file using minifier
                try {
                    $minified = $this->minify($criticalcss);
                } catch (Exception $err) {
                    $minified = false;
                }
                if ($minified) {
                    $criticalcss = $minified;

                    // store cache file
                    $this->cache->put('css', 'concat', $concat_hash, $criticalcss, 'critical');
                }
            }
        }

        if ($this->debug_view) {
            $criticalcss = "/**\n * Critical CSS Editor\n *\n * The extracted Critical CSS has been annotated with file references. \n * The Critical CSS source files are located in the theme directory ".$this->file->safe_path($this->file->theme_directory(array('critical-css')))."\n */\n" . $criticalcss;
        }

        // HTTP/2 Server Push or minify, store in file
        $http2 = ($this->options->bool('css.critical.http2') && $this->core->module_loaded('http2'));
        $concat_url = false;

        if ($http2 && $this->env->is_ssl()) {
            if (!$concat_hash) {

                // concat hash
                $concat_hash = md5($criticalcss);
     
                // load from cache
                if ($this->cache->exists('css', 'concat', $concat_hash)) {

                    // preserve cache file based on access
                    $this->cache->preserve('css', 'concat', $concat_hash, (time() - 3600));

                    // get CSS from cache
                    $cachecss = $this->cache->get('css', 'concat', $concat_hash);
                    if ($cachecss) {
                        $criticalcss = $cachecss;
                    }
                } else {

                    // store cache file
                    $this->cache->put('css', 'concat', $concat_hash, $criticalcss, 'critical');
                }
            }

            // concat URL
            $concat_url = $this->url->remove_host($this->cache->url('css', 'concat', $concat_hash));
        }

        // HTTP/2 Server Push, add direct link to stylesheet
        if ($http2 && $concat_url) {

            // add to header
            $this->client->at('critical-css', '<link '.(($this->debug_view) ? 'id="o10n-critical-css" ' : '').'data-o10n rel="stylesheet" href="'.$concat_url.'"/>');
        } else {

            // add to header
            $this->client->at('critical-css', '<style '.(($this->debug_view) ? 'id="o10n-critical-css" ' : '').'data-o10n="critical">'.$criticalcss.'</style>');
        }
    }

    /**
     * Critical CSS editor view
     */
    final public function editor_view($HTML)
    {
        if (stripos($HTML, "<html") === false || stripos($HTML, "<xsl:stylesheet") !== false) {
            // not valid HTML
            return $HTML;
        }

        $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $parsed = array();
        parse_str(substr($url, strpos($url, '?') + 1), $parsed);
        $extractkey = $parsed['extract-css'];
        unset($parsed['o10n-css']);
        unset($parsed['output']);
        $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . preg_replace('|\?.*$|Ui', '', $_SERVER['REQUEST_URI']);
        if (!empty($parsed)) {
            $url .= '?' . http_build_query($parsed);
        }

        /**
         * Print compare critical CSS page
         */
        $plugin_uri = $this->core->modules('css')->dir_url();
        require_once($this->core->modules('css')->dir_path() . 'includes/critical-css-editor.inc.php');

        return $output;
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
