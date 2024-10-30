<?php
namespace O10n;

/**
 * CSS Optimization Admin View Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminViewCss extends AdminViewBase
{
    protected static $view_key = 'css'; // reference key for view
    protected $module_key = 'css';

    // default tab view
    private $default_tab_view = 'intro';
    
    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @param  string     $View View key.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        // instantiate controller
        return parent::construct($Core, array(
            'AdminForm',
            'AdminClient',
            'options',
            'AdminOptions',
            'json'
        ));
    }
    
    /**
     * Setup controller
     */
    protected function setup()
    {
        // set view etc
        parent::setup();
    }

    /**
     * Setup view
     */
    public function setup_view()
    {
        // process form submissions
        add_action('o10n_save_settings_verify_input', array( $this, 'verify_input' ), 10, 1);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), $this->first_priority);
    }

    /**
     * Return help tab data
     */
    final public function help_tab()
    {
        $data = array(
            'name' => __('CSS Optimization', 'o10n'),
            'github' => 'https://github.com/o10n-x/wordpress-css-optimization',
            'wordpress' => 'https://wordpress.org/support/plugin/css-optimization',
            'docs' => 'https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs'
        );

        return $data;
    }

    /**
     * Enqueue scripts and styles
     */
    final public function enqueue_scripts()
    {
        // skip if user is not logged in
        if (!is_admin() || !is_user_logged_in()) {
            return;
        }

        // set module path
        $this->AdminClient->set_config('module_url', $this->module->dir_url());

        // global admin script
        wp_enqueue_script('o10n_view_css', $this->module->dir_url() . 'admin/js/view-css.js', array( 'jquery', 'o10n_cp' ), $this->module->version());
    }

    /**
     * Return view template
     */
    public function template($view_key = false)
    {

        // template view key
        $view_key = false;

        $tab = (isset($_REQUEST['tab'])) ? trim($_REQUEST['tab']) : $this->default_tab_view;
        if ($tab) {
            switch ($tab) {
                case "optimization":
                    $view_key = 'css';
                break;
                case "delivery":
                case "critical":
                case "editor":
                case "intro":
                case "settings":
                    $view_key = 'css-' . $tab;
                break;
                default:
                    throw new Exception('Invalid view ' . esc_html($view_key), 'core');
                break;
            }
        }

        return parent::template($view_key);
    }
    
    /**
     * Verify settings input
     *
     * @param  object   Form input controller object
     */
    final public function verify_input($forminput)
    {
        $tab = (isset($_REQUEST['tab'])) ? trim($_REQUEST['tab']) : 'optimization';
        switch ($tab) {
            case "optimization":

                // CSS code optimization

                $forminput->type_verify(array(
                    'css.minify.enabled' => 'bool',

                    'css.url_filter.enabled' => 'bool'
                ));


                // minify enabled
                if ($forminput->bool('css.minify.enabled')) {
                    $forminput->type_verify(array(
                        'css.minify.ignore_errors.enabled' => 'bool',
                        'css.minify.minifier' => 'string',

                        'css.minify.rebase.enabled' => 'bool',
                        'css.minify.import.enabled' => 'bool',
                        'css.minify.import.filter.enabled' => 'bool',
                        
                        'css.minify.filter.enabled' => 'bool',

                        'css.replace' => 'json-array',

                        'css.minify.concat.enabled' => 'bool',
                        'css.minify.concat.minify' => 'bool',

                        'css.minify.concat.filter.enabled' => 'bool',

                        'css.minify.concat.mediaqueries.enabled' => 'bool',

                        'css.minify.concat.inline.enabled' => 'bool',
                    ));

                    // minifier
                    $minifier = $forminput->get('css.minify.minifier');
                    if ($minifier === 'cssmin') {

                        // cssmin
                        $forminput->type_verify(array(
                            'css.minify.cssmin.filters.ImportImports.enabled' => 'bool',
                            'css.minify.cssmin.filters.ImportImports.filter.enabled' => 'bool',
                            'css.minify.cssmin.filters.RemoveComments.enabled' => 'bool',
                            'css.minify.cssmin.filters.RebaseURLs' => 'bool',
                            'css.minify.cssmin.filters.RemoveEmptyRulesets' => 'bool',
                            'css.minify.cssmin.filters.RemoveEmptyAtBlocks' => 'bool',
                            'css.minify.cssmin.filters.ConvertLevel3Properties' => 'bool',
                            'css.minify.cssmin.filters.ConvertLevel3AtKeyframes' => 'bool',
                            'css.minify.cssmin.filters.Variables' => 'bool',
                            'css.minify.cssmin.filters.RemoveLastDelarationSemiColon' => 'bool',

                            'css.minify.cssmin.plugins.Variables' => 'bool',
                            'css.minify.cssmin.plugins.ConvertFontWeight' => 'bool',
                            'css.minify.cssmin.plugins.ConvertHslColors' => 'bool',
                            'css.minify.cssmin.plugins.ConvertRgbColors' => 'bool',
                            'css.minify.cssmin.plugins.ConvertNamedColors' => 'bool',
                            'css.minify.cssmin.plugins.CompressColorValues' => 'bool',
                            'css.minify.cssmin.plugins.CompressUnitValues' => 'bool',
                            'css.minify.cssmin.plugins.CompressExpressionValues' => 'bool'
                        ));
                        if ($forminput->bool('css.minify.cssmin.filters.ImportImports.filter.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.cssmin.filters.ImportImports.filter.type' => 'string',
                                'css.minify.cssmin.filters.ImportImports.filter.include' => 'newline_array',
                                'css.minify.cssmin.filters.ImportImports.filter.exclude' => 'newline_array'
                            ));
                        }
                        if ($forminput->bool('css.minify.cssmin.filters.RemoveComments.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.cssmin.filters.RemoveComments.whitelist' => 'string'
                            ));
                        }
                    } elseif ($minifier === 'yui') {
                        $forminput->type_verify(array(
                            'css.minify.yui.options.keepSourceMapComment' => 'bool',
                            'css.minify.yui.options.removeImportantComments' => 'bool',
                            'css.minify.yui.options.setLinebreakPosition.enabled' => 'bool',
                            'css.minify.yui.options.setMaxExecutionTime.enabled' => 'bool',
                            'css.minify.yui.options.setMemoryLimit.enabled' => 'bool',
                            'css.minify.yui.options.setMemoryLimit.value' => 'string',
                            'css.minify.yui.options.setPcreBacktrackLimit.enabled' => 'bool',
                            'css.minify.yui.options.setPcreRecursionLimit.enabled' => 'bool'
                        ));

                        if ($forminput->bool('css.minify.yui.options.setLinebreakPosition.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.yui.options.setLinebreakPosition.position' => 'int-empty'
                            ));
                        }

                        if ($forminput->bool('css.minify.yui.options.setMaxExecutionTime.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.yui.options.setMaxExecutionTime.value' => 'int-empty'
                            ));
                        }

                        if ($forminput->bool('css.minify.yui.options.setPcreBacktrackLimit.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.yui.options.setPcreBacktrackLimit.value' => 'int-empty'
                            ));
                        }

                        if ($forminput->bool('css.minify.yui.options.setPcreRecursionLimit.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.yui.options.setPcreRecursionLimit.value' => 'int-empty'
                            ));
                        }
                    } elseif ($minifier === 'regex') {
                    }

                    // minify filter
                    if ($forminput->bool('css.minify.filter.enabled')) {
                        $forminput->type_verify(array(
                            'css.minify.filter.type' => 'string',
                            'css.minify.filter.include' => 'newline_array',
                            'css.minify.filter.exclude' => 'newline_array'
                        ));
                    }

                    // minify import filter
                    if ($forminput->bool('css.minify.import.filter.enabled')) {
                        $forminput->type_verify(array(
                            'css.minify.import.filter.type' => 'string',
                            'css.minify.import.filter.include' => 'newline_array',
                            'css.minify.import.filter.exclude' => 'newline_array'
                        ));
                    }

                    // concat enabled
                    if ($forminput->bool('css.minify.concat.enabled')) {

                        // concat filter
                        if ($forminput->bool('css.minify.concat.filter.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.concat.filter.type' => 'string',
                                'css.minify.concat.filter.config' => 'json-array'
                            ));
                        }

                        // concat media query filter
                        if ($forminput->bool('css.minify.concat.mediaqueries.filter.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.concat.mediaqueries.filter.enabled' => 'bool',
                                'css.minify.concat.mediaqueries.filter.type' => 'string',
                                'css.minify.concat.mediaqueries.filter.include' => 'newline_array',
                                'css.minify.concat.mediaqueries.filter.exclude' => 'newline_array'
                            ));
                        }

                        // concat inline filter
                        if ($forminput->bool('css.minify.concat.inline.filter.enabled')) {
                            $forminput->type_verify(array(
                                'css.minify.concat.inline.filter.enabled' => 'bool',
                                'css.minify.concat.inline.filter.type' => 'string',
                                'css.minify.concat.inline.filter.include' => 'newline_array',
                                'css.minify.concat.inline.filter.exclude' => 'newline_array'
                            ));
                        }
                    }

                    // verify search & replace
                    $cssreplace = $forminput->get('css.replace', 'json-array', array());
                    if (!empty($cssreplace)) {
                        $searchreplace = array();
                        $position = 0;
                        foreach ($cssreplace as $cnf) {
                            if (!is_array($cnf) || !isset($cnf['search']) || !isset($cnf['replace'])) {
                                continue;
                            }
                            $position++;
                            if (isset($cnf['regex'])) {
                                // exec preg_match on null
                                $valid = @preg_match($cnf['search'], null);
                                $error = $this->is_preg_error();
                                if ($valid === false || $error) {
                                    throw new Exception('<code>'.esc_html($cnf['search']).'</code> is an invalid regular expression and has been removed.' . (($error) ? '<br /><p>Error: '.$error.'</p>' : ''), 'settings');
                                }
                            }
                            $searchreplace[] = $cnf;
                        }
                        $cssreplace = $searchreplace;
                    }

                    // set search & replace
                    $forminput->set('css.replace', $cssreplace);
                }

                // url filter
                if ($forminput->bool('css.url_filter.enabled')) {
                    $forminput->type_verify(array(
                        'css.url_filter.config' => 'json-array'
                    ));
                }

            break;
            case "delivery":

                // CSS delivery optimization

                $forminput->type_verify(array(
                    'css.async.enabled' => 'bool',

                    'css.http2_push.enabled' => 'bool',

                    'css.proxy.enabled' => 'bool',

                    'css.cdn.enabled' => 'bool'
                ));

                // async
                if ($forminput->bool('css.async.enabled')) {
                    $forminput->type_verify(array(
                        'css.async.rel_preload' => 'bool',
                        'css.async.noscript' => 'bool',

                        'css.async.render_timing.enabled' => 'bool',

                        'css.async.localStorage.enabled' => 'bool',

                        'css.async.filter.enabled' => 'bool',
                        'css.async.filter.type' => 'string',
                        'css.async.load_position' => 'string'
                    ));

                    // async filter
                    if ($forminput->bool('css.async.filter.enabled')) {
                        $forminput->type_verify(array(
                            'css.async.filter.config' => 'json-array'
                        ));
                    }

                    // load timing
                    if ($forminput->get('css.async.load_position') === 'timing') {
                        $forminput->type_verify(array(
                            'css.async.load_timing.type' => 'string'
                        ));

                        if ($forminput->get('css.async.load_timing.type') === 'requestAnimationFrame') {
                            $forminput->type_verify(array(
                                'css.async.load_timing.frame' => 'int-empty'
                            ));
                        }

                        if ($forminput->get('css.async.load_timing.type') === 'requestIdleCallback') {
                            $forminput->type_verify(array(
                                'css.async.load_timing.timeout' => 'int-empty',
                                'css.async.load_timing.setTimeout' => 'int-empty'
                            ));
                        }
                
                        if ($forminput->get('css.async.load_timing.type') === 'inview') {
                            $forminput->type_verify(array(
                                'css.async.load_timing.selector' => 'string',
                                'css.async.load_timing.offset' => 'int-empty'
                            ));
                        }

                        if ($forminput->get('css.async.load_timing.type') === 'media') {
                            $forminput->type_verify(array(
                                'css.async.load_timing.media' => 'string'
                            ));
                        }
                    }

                    // render timing
                    if ($forminput->bool('css.async.render_timing.enabled')) {
                        $forminput->type_verify(array(
                            'css.async.render_timing.type' => 'string'
                        ));

                        if ($forminput->get('css.async.render_timing.type') === 'requestAnimationFrame') {
                            $forminput->type_verify(array(
                                'css.async.render_timing.frame' => 'int-empty'
                            ));
                        }

                        if ($forminput->get('css.async.render_timing.type') === 'requestIdleCallback') {
                            $forminput->type_verify(array(
                                'css.async.render_timing.timeout' => 'int-empty',
                                'css.async.render_timing.setTimeout' => 'int-empty'
                            ));
                        }
            
                        if ($forminput->get('css.async.render_timing.type') === 'inview') {
                            $forminput->type_verify(array(
                                'css.async.render_timing.selector' => 'string',
                                'css.async.render_timing.offset' => 'int-empty'
                            ));
                        }

                        if ($forminput->get('css.async.render_timing.type') === 'media') {
                            $forminput->type_verify(array(
                                'css.async.render_timing.media' => 'string'
                            ));
                        }
                    }

                    // localStorage
                    if ($forminput->bool('css.async.localStorage.enabled')) {
                        $forminput->type_verify(array(
                            'css.async.localStorage.max_size' => 'int',
                            'css.async.localStorage.expire' => 'int',
                            'css.async.localStorage.update_interval' => 'int',
                            'css.async.localStorage.head_update' => 'bool'
                        ));
                    }
                }

                // HTTP/2
                if ($forminput->bool('css.http2_push.filter.enabled')) {
                    $forminput->type_verify(array(
                        'css.http2_push.filter.type' => 'string',
                        'css.http2_push.filter.include' => 'newline_array',
                        'css.http2_push.filter.exclude' => 'newline_array'
                    ));
                }

                // proxy
                if ($forminput->bool('css.proxy.enabled')) {
                    $forminput->type_verify(array(
                        'css.proxy.include' => 'newline_array',
                        'css.proxy.capture.enabled' => 'bool'
                    ));

                    // proxy capture
                    if ($forminput->bool('css.proxy.capture.enabled')) {
                        $forminput->type_verify(array(
                            'css.proxy.capture.list' => 'json-array'
                        ));
                    }
                }

                if ($forminput->bool('css.cdn.enabled')) {
                    $forminput->type_verify(array(
                        'css.cdn.http2_push' => 'bool',
                        'css.cdn.url' => 'string',
                        'css.cdn.mask' => 'string',
                    ));
                }
            break;
            case "settings":

                // CSS profile
                $css = $forminput->get('css', 'json-array');
                if ($css) {

                    // @todo improve
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveArrayIterator($css),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    $path = [];
                    $flatArray = [];

                    $arrayVal = false;
                    foreach ($iterator as $key => $value) {
                        $path[$iterator->getDepth()] = $key;

                        $dotpath = 'css.'.implode('.', array_slice($path, 0, $iterator->getDepth() + 1));
                        if ($arrayVal && strpos($dotpath, $arrayVal) === 0) {
                            continue 1;
                        }

                        if (!is_array($value) || empty($value) || array_keys($value)[0] === 0) {
                            if (is_array($value) && (empty($value) || array_keys($value)[0] === 0)) {
                                $arrayVal = $dotpath;
                            } else {
                                $arrayVal = false;
                            }

                            $flatArray[$dotpath] = $value;
                        }
                    }

                    // delete existing options
                    $this->options->delete('css.*');

                    // replace all options
                    $this->AdminOptions->save($flatArray);
                }
            break;
            default:
                throw new Exception('Invalid view ' . esc_html($tab), 'core');
            break;
        }
    }
}
