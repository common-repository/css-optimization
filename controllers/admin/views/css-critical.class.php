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

class AdminViewCssCritical extends AdminViewBase
{
    protected static $view_key = 'css-critical'; // reference key for view
    protected $module_key = 'css';


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
            'AdminOptions',
            'AdminClient',
            'AdminAjax',
            'AdminForm',
            'file',
            'json',
            'options'
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

        // ajax handlers
        add_action('wp_ajax_o10n_critical_css_add_file', array( $this, 'ajax_add_file'), 10);
        add_action('wp_ajax_o10n_critical_css_delete_file', array( $this, 'ajax_delete_file'), 10);
        add_action('wp_ajax_o10n_critical_css_files_list', array( $this, 'ajax_files_list'), 10);
        add_action('wp_ajax_o10n_critical_css_save_conditions', array( $this, 'ajax_save_conditions'), 10);
        
        // enqueue scripts
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), $this->first_priority);
    }

    /**
     * Return help tab data
     */
    final public function help_tab()
    {
        $data = array(
            'name' => __('Critical CSS Optimization', 'o10n'),
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

        // include json editor
        $this->AdminClient->preload_JSONEditor();

        // critical css view styles
        wp_enqueue_style('o10n_view_critical_css', $this->module->dir_url() . 'admin/css/view-css-critical.css');

        // global admin script
        wp_enqueue_script('o10n_view_css_critical', $this->module->dir_url() . 'admin/js/view-css-critical.js', array( 'jquery', 'o10n_cp' ), $this->module->version());

        // add phrases
        $this->AdminClient->set_lg(array(
            'saving_file' => __('Saving file...', 'o10n'),
            'file_saved' => __('File saved.', 'o10n'),
            'confirm_delete_critical_css_file' => __("Are you sure you want to delete this file from the list?\n\nNote: The file will not be deleted from the server.", 'o10n'),
            'one_condition' => __('1 condition', 'o10n'),
            'x_conditions' => __('%d conditions', 'o10n'),
        ));
    }

    /**
     * Return critical CSS files
     */
    final public function critical_css_files($critical_css_files = false)
    {

        // get from config
        if (!$critical_css_files) {
            $critical_css_files = $this->options->get('css.critical.files');
        }
        if (!$critical_css_files || !is_array($critical_css_files)) {
            $critical_css_files = array();
        }

        $criticalcss_dir = $this->file->theme_directory(array('critical-css'));

        foreach ($critical_css_files as $index => $file) {

            // auto config
            if (isset($file['auto'])) {
                continue;
            }
            if (!isset($file['file'])) {
                continue;
            }

            $filepath = $criticalcss_dir . $file['file'];
            $critical_css_files[$index]['filepath'] = get_template() . '/critical-css/' . $file['file'];

            // verify if file exists
            if (!file_exists($filepath)) {
                $critical_css_files[$index]['error'] = __('File does not exist.', 'o10n');
            } else {
                $critical_css_files[$index]['size'] = filesize($filepath);
                $critical_css_files[$index]['hsize'] = size_format($critical_css_files[$index]['size'], 2);
                $critical_css_files[$index]['time'] = filemtime($filepath);
                $critical_css_files[$index]['date'] = sprintf(__('%s ago', 'o10n'), human_time_diff($critical_css_files[$index]['time']));
            }

            $critical_css_files[$index]['edit_url'] = add_query_arg(array( 'page' => 'o10n-css-editor', 'file' => urlencode('critical-css/' . $file['file']) ), admin_url('themes.php'));
        }

        // sort priority
        usort($critical_css_files, function ($a, $b) {
            if (intval($a['priority']) === intval($b['priority'])) {
                return 0;
            }

            return (intval($a['priority']) < intval($b['priority'])) ? -1 : 1;
        });

        return $critical_css_files;
    }

    /**
     * Delete Critical CSS File
     */
    final public function ajax_files_list()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        $files = $request->data('files');
        if ($files) {
            try {
                $files = $this->json->parse($files, true);
            } catch (\Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }

        // add to critical css files
        $critical_css_files = $this->critical_css_files($files);

        // json config
        $json = $this->options->get('css.critical.files');
        if (!$json || !is_array($json)) {
            $json = array();
        }

        $request->output_ok(false, array($critical_css_files, $json));
    }

    /**
     * Add Critical CSS File
     */
    final public function ajax_add_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filename = $request->data('file');
        $title = $request->data('title');
        $priority = $request->data('priority');
        $critical_css_files = $request->data('files');
        if ($critical_css_files) {
            try {
                $critical_css_files = $this->json->parse($critical_css_files, true);
            } catch (\Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }

        // verify title
        if ($title) {
            $title = trim(strip_tags($title));
        }

        // verify file path
        if (!$filename) {
            $request->output_errors('no file');
        }

        // verify priority
        if ($priority && (!is_numeric($priority) || intval($priority) < 1)) {
            $request->output_errors('Invalid priority.');
        }

        // verify path
        if (!preg_match('|^[a-zA-Z0-9\-\_]+\.css$|Ui', $filename)) {

            // automatically add .css
            if (preg_match('|^[a-zA-Z0-9\-\_]+\.css$|Ui', $filename)) {
                $filename .= '.css';
            } else {
                $request->output_errors(__('Invalid filename. Enter a filename with the extension .css.', 'o10n'));
            }
        }

        // theme relative or absolute path?
        $file = $this->file->theme_directory(array('critical-css')) . $filename;
        $filepath = get_template() . '/critical-css/' . $filename;

        // verify if file exists
        if (!file_exists($file)) {
            // create new file
            try {
                $this->file->put_contents($file, ' ');
            } catch (\Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }

        $size = filesize($file);
        $date = filemtime($file);

        // add to critical css files
        $critical_css_files = ($critical_css_files && is_array($critical_css_files)) ? $critical_css_files : $this->options->get('css.critical.files');
        if (!$critical_css_files || !is_array($critical_css_files)) {
            $critical_css_files = array();
        }

        // sanitize array (unique entries)
        $sanitized_css_files = array();
        foreach ($critical_css_files as $file) {
            if (!isset($sanitized_css_files[$file['file']])) {
                $sanitized_css_files[$file['file']] = $file;
            }
        }

        // check if file exists
        if (isset($sanitized_css_files[$filename])) {
            $request->output_ok('File exists.');
        }

        // add new file
        $sanitized_css_files[$filename] = array(
            'file' => $filename,
            'priority' => intval($priority)
        );
        if ($title) {
            $sanitized_css_files[$filename]['title'] = $title;
        }


        $critical_css_files = array_values($sanitized_css_files);

        try {
            $this->AdminOptions->save(array('css.critical.files' => $critical_css_files));
        } catch (Exception $err) {
            $request->output_errors($err->getMessage());
        }

        $request->output_ok(false, array($conditions, $filepath));
    }

    /**
     * Delete Critical CSS File
     */
    final public function ajax_delete_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');

        // verify file path
        if (!$filepath) {
            $request->output_errors('no file');
        }

        $filename = basename($filepath);

        $critical_css_files = $request->data('files');
        if ($critical_css_files) {
            try {
                $critical_css_files = $this->json->parse($critical_css_files, true);
            } catch (\Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }

        // theme relative or absolute path?
        $file = $this->file->theme_directory(array('critical-css')) . basename($filepath);

        // add to critical css files
        $critical_css_files = ($critical_css_files && is_array($critical_css_files)) ? $critical_css_files : $this->options->get('css.critical.files');
        if (!$critical_css_files || !is_array($critical_css_files)) {
            $critical_css_files = array();
        }

        // sanitize array (unique entries)
        $sanitized_css_files = array();
        foreach ($critical_css_files as $file) {
            if (!isset($sanitized_css_files[$file['file']])) {
                $sanitized_css_files[$file['file']] = $file;
            }
        }

        // check if file exists
        if (isset($sanitized_css_files[$filename])) {
            unset($sanitized_css_files[$filename]);
        }

        // save critical css list
        $critical_css_files = array_values($sanitized_css_files);
        try {
            $this->AdminOptions->save(array('css.critical.files' => $critical_css_files));
        } catch (Exception $err) {
            $request->output_errors($err->getMessage());
        }

        $request->output_ok();
    }

    /**
     * Save Critical CSS conditions
     */
    final public function ajax_save_conditions()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');
        if (!$filepath) {
            $request->output_errors('No file');
        }
        $filename = basename($filepath);

        // conditions
        $conditions = $request->data('conditions');
        if ($conditions === '') {
            $conditions = array();
        } else {
            try {
                $conditions = $this->json->parse($conditions, true);
            } catch (\Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }
        
        // add condition file modified time to critical css files list
        $critical_css_files = $this->options->get('css.critical.files');
        if (!$critical_css_files || !is_array($critical_css_files)) {
            $critical_css_files = array();
        }

        foreach ($critical_css_files as $index => $fileinfo) {
            if ($fileinfo['file'] === $filename) {
                if (empty($conditions)) {
                    if (isset($critical_css_files[$index]['conditions'])) {
                        unset($critical_css_files[$index]['conditions']);
                    }
                } else {
                    $critical_css_files[$index]['conditions'] = $conditions;
                }
            }
        }
        try {
            $this->AdminOptions->save(array('css.critical.files' => $critical_css_files));
        } catch (Exception $err) {
            $request->output_errors($err->getMessage());
        }

        $request->output_ok(false, array($conditions));
    }
    
    /**
     * Verify settings input
     *
     * @param  object   Form input controller object
     */
    final public function verify_input($forminput)
    {
        // Critical CSS code optimization
        $forminput->type_verify(array(
            'css.critical.http2.enabled' => 'bool',
            'css.critical.editor_public.enabled' => 'bool',
            'css.critical.minify.enabled' => 'bool'
        ));

        // cssmin
        $forminput->type_verify(array(
            'css.critical.minify.cssmin.filters.ImportImports' => 'bool',
            'css.critical.minify.cssmin.filters.RemoveComments.enabled' => 'bool',
            'css.critical.minify.cssmin.filters.RemoveEmptyRulesets' => 'bool',
            'css.critical.minify.cssmin.filters.RemoveEmptyAtBlocks' => 'bool',
            'css.critical.minify.cssmin.filters.ConvertLevel3Properties' => 'bool',
            'css.critical.minify.cssmin.filters.ConvertLevel3AtKeyframes' => 'bool',
            'css.critical.minify.cssmin.filters.Variables' => 'bool',
            'css.critical.minify.cssmin.filters.RemoveLastDelarationSemiColon' => 'bool',

            'css.critical.minify.cssmin.plugins.Variables' => 'bool',
            'css.critical.minify.cssmin.plugins.ConvertFontWeight' => 'bool',
            'css.critical.minify.cssmin.plugins.ConvertHslColors' => 'bool',
            'css.critical.minify.cssmin.plugins.ConvertRgbColors' => 'bool',
            'css.critical.minify.cssmin.plugins.ConvertNamedColors' => 'bool',
            'css.critical.minify.cssmin.plugins.CompressColorValues' => 'bool',
            'css.critical.minify.cssmin.plugins.CompressUnitValues' => 'bool',
            'css.critical.minify.cssmin.plugins.CompressExpressionValues' => 'bool'
        ));
        if ($forminput->bool('css.critical.minify.cssmin.filters.RemoveComments.enabled')) {
            $forminput->type_verify(array(
                'css.critical.minify.cssmin.filters.RemoveComments.whitelist' => 'string'
            ));
        }

        // sort files
        $sort_files = $forminput->get('sort');
        if ($sort_files && !empty($sort_files)) {

            // get file list
            $critical_css_files = $this->options->get('css.critical.files');
            foreach ($critical_css_files as $index => $file) {
                if (isset($file['file']) && isset($sort_files[$file['file']]) && is_numeric($sort_files[$file['file']])) {
                    $critical_css_files[$index]['priority'] = intval($sort_files[$file['file']]);
                }
            }
            
            // save sorted files
            try {
                $this->AdminOptions->save(array('css.critical.files' => $critical_css_files));
            } catch (Exception $err) {
                $request->output_errors($err->getMessage());
            }
        }
    }
}
