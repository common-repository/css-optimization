<?php
namespace O10n;

/**
 * CSS Editor Admin View Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminViewCssEditor extends AdminViewBase
{
    protected static $view_key = 'css-editor'; // reference key for view
    protected $module_key = 'css';

    // default stylesheet (theme/style.css)
    private $theme_stylesheet = null;

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
            'AdminAjax',
            'AdminEditor',
            'AdminClient',
            'AdminScreen',
            'json',
            'file'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // disable nocache headers
        add_filter('o10n_admin_nocache', function () {
            return false;
        });

        // set view etc
        parent::setup();
    }

    /**
     * Setup view
     */
    public function setup_view()
    {
        // save settings
        add_action('wp_ajax_o10n_save_csslint', array( $this, 'ajax_save_csslint'), 10);
        add_action('wp_ajax_o10n_save_cssbeautify', array( $this, 'ajax_save_cssbeautify'), 10);
        add_action('wp_ajax_o10n_save_cssminify', array( $this, 'ajax_save_cssminify'), 10);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), $this->first_priority);

        // add screen options
        $this->AdminScreen->load_screen('editor');
    }

    /**
     * Return help tab data
     */
    final public function help_tab()
    {
        $data = array(
            'name' => __('CSS Editor', 'o10n'),
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

        // get user
        $user = wp_get_current_user();

        // global admin script
        $this->AdminClient->preload_CodeMirror('css');
        
        // editor view styles
        wp_enqueue_style('o10n_view_editor', $this->module->dir_url() . 'admin/css/view-editor.css');

        // global admin script
        wp_enqueue_script('o10n_view_css_editor', $this->module->dir_url() . 'admin/js/view-css-editor.js', array( 'jquery', 'o10n_cp', 'o10n_codemirror' ), $this->module->version());

        // add theme files
        $this->AdminClient->set_config('theme_editor_files', $this->theme_css());

        // retrieve active stylesheet
        $active_stylesheet = $this->theme_stylesheet();
        if ($active_stylesheet) {
            $this->AdminClient->set_config('theme_editor_active_file', $active_stylesheet['filepath']);
        }

        // add css beautify config
        $cssbeautify_options = get_user_meta($user->ID, 'o10n_cssbeautify', true);
        if (!$cssbeautify_options) {
            $cssbeautify_options = array(
                'format' => 'beautify'
            );
        }
        $this->AdminClient->set_config('css_beautify_options', $cssbeautify_options);

        // auto lint
        $autolint = get_user_meta($user->ID, 'o10n_csslint_auto', true);
        $this->AdminClient->set_config('editor_autolint', (($autolint) ? 1 : 0));

        // add css minify config
        $cssminify_options = get_user_meta($user->ID, 'o10n_cssminify', true);
        if (!$cssminify_options) {
            $cssminify_options = array(
                'level' => array(
                    '1' => array(
                        'all' => true
                    ),
                    '2' => array(
                        'all' => true
                    )
                )
            );
        }
        $this->AdminClient->set_config('css_minify_options', $cssminify_options);

        // add phrases
        $this->AdminClient->set_lg(array(
            'no_issues_found' => __('No issues found', 'o10n'),
            'found_x_issues' => __('Found {n} issues.', 'o10n'),
            'found_x_issues_show' => __('Found {n} issues. (<a href="javascript:void(0);">show</a>)', 'o10n'),
            'linting_css_please_wait' => __('Linting CSS...', 'o10n'),
            'saving_csslint_settings' => __('Saving CSS LINT settings...', 'o10n'),
            'saving_cssbeautify_settings' => __('Saving clean-css beautify settings...', 'o10n'),
            'saving_cssminify_settings' => __('Saving clean-css optimization settings...', 'o10n'),
            'minifying_css_please_wait' => __('Optimizing CSS...', 'o10n'),
            'beautifying_css_please_wait' => __('Beautifying CSS...', 'o10n'),
            'saved_x' => __('Saved {n}', 'o10n')
        ));
    }

    /**
     * Return theme CSS
     */
    final public function theme_css()
    {

        // theme directory
        $theme_directory = $this->file->theme_directory();

        // default stylesheet directories in theme
        $directories = apply_filters('o10n_editor_theme_css_directories', array(
            $theme_directory,
            $theme_directory . 'assets/',
            $theme_directory . 'assets/css/',
            $theme_directory . 'css/'
        ));

        $assets = array();

        foreach ($directories as $dir) {
            $files = $this->AdminEditor->scandir($dir, 'css');
            if (!empty($files)) {
                $assets = array_merge($assets, $files);
            }
        }

        return $assets;
    }

    /**
     * Return default theme stylesheet
     */
    final public function theme_stylesheet()
    {
        if (is_null($this->theme_stylesheet)) {
            $this->theme_stylesheet = false;

            // theme directory
            $theme_directory = $this->file->theme_directory();

            $files = array();

            $file = (isset($_GET['file'])) ? $_GET['file'] : false;
            if (strpos($file, '../') !== false) {
                throw new Exception('Relative paths are not allowed.', 'admin');
            }
            if ($file) {

                // absolute path
                if (substr($file, 0, 1) === '/') {
                    $file = realpath($this->file->un_trailingslashit(ABSPATH) . $file);

                    // verify path
                    if ($file && strpos($file, ABSPATH) !== 0) {
                        throw new Exception('Invalid file', 'admin');
                    }
                } else {
                    $file = $theme_directory . $file;
                }

                if ($file && file_exists($file)) {
                    $files[] = array($file,$_GET['file']);
                }
            }
            $files[] = array($theme_directory . 'style.css','style.css');

            foreach ($files as $file) {
                // check for default stylesheet in theme directory
                if (file_exists($file[0])) {
                    $this->theme_stylesheet = array(
                        'filepath' => $file[1],
                        'text' => file_get_contents($file[0])
                    );
                    break;
                }
            }
        }

        return $this->theme_stylesheet;
    }

    /**
     * Save CSS LINT settings
     */
    final public function ajax_save_csslint()
    {
        // parse request
        $request = $this->AdminAjax->request();

        // posted rules
        $rules = $request->data('rules', false);
        if (!$rules) {
            $request->output_errors(__('No CSS LINT rules to save.', 'o10n'));
        }

        $autolint = $request->data('autolint', false);

        // user
        $user_id = $request->user_id();

        // save as user meta
        update_user_meta($user_id, 'o10n_csslint', $rules);
        update_user_meta($user_id, 'o10n_csslint_auto', (($autolint) ? 1 : 0));

        // OK
        $request->output_ok(__('CSS LINT settings saved.', 'o10n'));
    }

    /**
     * Save clean-css beautify settings
     */
    final public function ajax_save_cssbeautify()
    {
        // parse request
        $request = $this->AdminAjax->request();

        // posted rules
        $options = $request->data('options', false);
        if (!$options) {
            $request->output_errors(__('No clean-css beautify options to save.', 'o10n'));
        }

        // parse options
        try {
            $options = $this->json->parse($options, true);
        } catch (\Exception $err) {
            $request->output_errors(__('Failed to parse JSON.', 'o10n'));
        }

        // save as user meta
        update_user_meta($request->user_id(), 'o10n_cssbeautify', $options);

        // OK
        $request->output_ok(__('Beautify settings saved.', 'o10n'));
    }

    /**
     * Save clean-css minify settings
     */
    final public function ajax_save_cssminify()
    {
        // parse request
        $request = $this->AdminAjax->request();

        // posted rules
        $options = $request->data('options', false);
        if (!$options) {
            $request->output_errors(__('No clean-css minify options to save.', 'o10n'));
        }

        // parse options
        try {
            $options = $this->json->parse($options, true);
        } catch (\Exception $err) {
            $request->output_errors(__('Failed to parse JSON.', 'o10n'));
        }

        // save as user meta
        update_user_meta($request->user_id(), 'o10n_cssminify', $options);

        // OK
        $request->output_ok(__('clean-css settings saved.', 'o10n'));
    }
}
