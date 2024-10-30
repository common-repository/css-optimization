<?php
namespace O10n;

/**
 * CSS Optimization Admin Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminCss extends ModuleAdminController implements Module_Admin_Controller_Interface
{

    // admin base
    protected $admin_base = 'themes.php';

    // tab menu
    protected $tabs = array(
        'intro' => array(
            'title' => '<span class="dashicons dashicons-admin-home"></span>',
            'title_attr' => 'Intro'
        ),
        'optimization' => array(
            'title' => 'Code Optimization',
            'title_attr' => 'CSS Code Optimization'
        ),
        'delivery' => array(
            'title' => 'Delivery Optimization'
        ),
        'critical' => array(
            'title' => 'Critical CSS'
        ),
        'editor' => array(
            'title' => 'CSS Editor',
            'title_attr' => 'CSS Editor',
            'admin_base' => 'themes.php',
            'pagekey' => 'css-editor',
            'subtabs' => array(
                'minify' => array(
                    'title' => 'Minify',
                    'href' => '#minify'
                ),
                'beautify' => array(
                    'title' => 'Beautify',
                    'href' => '#beautify'
                ),
                'lint' => array(
                    'title' => 'CSS Lint',
                    'href' => '#csslint'
                )
            )
        ),
        'settings' => array(
            'title' => 'Settings'
        )
    );

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
            'AdminView',
            'options',
            'AdminOptions'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        
        // settings link on plugin index
        add_filter('plugin_action_links_' . $this->core->modules('css')->basename(), array($this, 'settings_link'));

        // meta links on plugin index
        add_filter('plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2);

        // title on plugin index
        add_action('pre_current_active_plugins', array( $this, 'plugin_title'), 10);

        // admin options page
        add_action('admin_menu', array($this, 'admin_menu'), 50);

        // reorder menu
        add_filter('custom_menu_order', array($this, 'reorder_menu'), PHP_INT_MAX);

        // upgrade/install hooks
        $this->upgrade();
    }
    
    /**
     * Admin menu option
     */
    final public function admin_menu()
    {
        global $submenu;

        // WPO plugin or more than 1 optimization module, add to optimization menu
        if (count($this->core->modules()) > 1) {
            add_submenu_page('o10n', __('CSS Optimization', 'o10n'), __('CSS', 'o10n'), 'manage_options', 'o10n-css', array(
                 &$this->AdminView,
                 'display'
             ));

            // change base to admin.php
            $this->admin_base = 'admin.php';
        } else {

            // add menu entry to themes page
            add_submenu_page('themes.php', __('CSS Optimization', 'o10n'), __('CSS Optimization', 'o10n'), 'manage_options', 'o10n-css', array(
                 &$this->AdminView,
                 'display'
             ));
        }

        // add menu entry to themes page
        add_submenu_page('themes.php', __('Advanced CSS Editor', 'o10n'), __('CSS Editor', 'o10n'), 'manage_options', 'o10n-css-editor', array(
             &$this->AdminView,
             'display'
         ));
    }
    
    /**
     * Settings link on plugin overview.
     *
     * @param  array $links Plugin settings links.
     * @return array Modified plugin settings links.
     */
    final public function settings_link($links)
    {
        $settings_link = '<a href="'.esc_url(add_query_arg(array('page' => 'o10n-css','tab' => 'optimization'), admin_url($this->admin_base))).'">'.__('Settings').'</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Show row meta on the plugin screen.
     */
    final public function plugin_row_meta($links, $file)
    {
        if ($file == $this->core->modules('css')->basename()) {
            $lgcode = strtolower(get_locale());
            if (strpos($lgcode, '_') !== false) {
                $lgparts = explode('_', $lgcode);
                $lgcode = $lgparts[0];
            }
            if ($lgcode === 'en') {
                $lgcode = '';
            }

            $row_meta = array(
                /*'o10n_scores' => '<a href="' . esc_url('https://optimization.team/pro/') . '" target="_blank" title="' . esc_attr(__('View Google PageSpeed Scores Documentation', 'o10n')) . '" style="font-weight:bold;color:black;">' . __('Upgrade to <span class="g100" style="padding:0px 4px;">PRO</span>', 'o10n') . '</a>'*/
            );

            return array_merge($links, $row_meta);
        }

        return (array) $links;
    }

    /**
     * Plugin title modification
     */
    public function plugin_title()
    {
        ?><script>jQuery(function($){var r=$('*[data-plugin="<?php print $this->core->modules('css')->basename(); ?>"]');
            $('.plugin-title strong',r).html('<?php print $this->core->modules('css')->name(); ?><a href="https://optimization.team" target="_blank" class="g100">O10N</span>');
});</script><?php
    }

    /**
     * Reorder menu
     */
    public function reorder_menu($menu_order)
    {
        global $submenu;

        // move CSS Editor to end of list
        $editor_item = false;
        $wp_editor_index = false;
        foreach ($submenu['themes.php'] as $key => $item) {
            if ($item[2] === 'theme-editor.php') {
                $wp_editor_index = $key;
            } elseif ($item[2] === 'o10n-css-editor') {
                $editor_item = $item;
                unset($submenu['themes.php'][$key]);
            }
        }

        if ($wp_editor_index) {
            $reordered = array();
            foreach ($submenu['themes.php'] as $key => $item) {
                $reordered[] = $item;
                if ($key === $wp_editor_index) {
                    $reordered[] = $editor_item;
                }
            }
            $submenu['themes.php'] = $reordered;
        } else {
            $submenu['themes.php'][] = $editor_item;
        }
    }
    
    /**
     * Upgrade plugin
     */
    final public function upgrade()
    {
        $version = $this->core->modules('css')->version();

        //if (version_compare($version, '0.0.42', '<=')) {

        // get all options
        $options = $this->options->get();

        // updated options
        $update = array();

        // deleted options
        $delete = array();

        if (version_compare($version, '0.0.27', '<=')) {

                // convert critical css array to new format
            $critical_css_files = $this->options->get('css.critical.files');
            if ($critical_css_files && is_array($critical_css_files)) {
                foreach ($critical_css_files as $index => $config) {
                    if (isset($config['filepath'])) {
                        unset($critical_css_files[$index]['filepath']);
                        $updated = true;
                    }
                    if (isset($config['conditions']) && is_array($config['conditions'])) {
                        foreach ($config['conditions'] as $cindex => $condition) {
                            if (is_array($condition) && isset($condition[0]) && is_numeric($condition[0])) {
                                $critical_css_files[$index]['conditions'][$cindex] = $condition[1];
                                $updated = true;
                            }
                        }
                    }
                }
                if ($updated) {
                    $update['css.critical.files'] = $critical_css_files;
                }
            }
        }

        if (!empty($update)) {
            try {
                $this->AdminOptions->save($update);
            } catch (Exception $err) {
            }
        }

        // delete options
        if (!empty($delete)) {
            $this->AdminOptions->delete($delete);
        }
        //}
    }
}
