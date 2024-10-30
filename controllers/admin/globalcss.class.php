<?php
namespace O10n;

/**
 * Global CSS Optimization Admin Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminGlobalcss extends ModuleAdminController implements Module_Admin_Controller_Interface
{

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
            'client',
            'cache'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // add admin bar menu
        add_action('admin_bar_menu', array( $this, 'admin_bar'), 100);

        // add critical CSS widget extension to client
        $this->client->after('client', '<script>o10n.constructor.prototype.extract=function(t){(function(d,c,s){s=d.createElement(\'script\');s.async=true;s.onload=c;s.src=' . json_encode($this->core->modules('css')->dir_url() . 'public/js/critical-css-widget.min.js').';d.head.appendChild(s);})(document,function(){o10n.extract(t);});}</script>');
    }

    /**
     * Admin bar option
     *
     * @param  object       Admin bar object
     */
    final public function admin_bar($admin_bar)
    {
        // current url
        if (is_admin()
            || (defined('DOING_AJAX') && DOING_AJAX)
            || in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))
        ) {
            $currenturl = home_url();
        } else {
            $currenturl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        // get cache stats
        $stats = $this->cache->stats('css');
        if (!isset($stats['size']) || $stats['size'] === 0) {
            $cache_size = ' ('.__('Empty', 'o10n').')';
        } else {
            $cache_size = ' ('.size_format($stats['size'], 2).')';
        }

        // WPO plugin or more than 1 optimization module, add to optimization menu
        if (defined('O10N_WPO_VERSION') || count($this->core->modules()) > 1) {
            $admin_bar->add_node(array(
                'parent' => 'o10n',
                'id' => 'o10n-css',
                'title' => __('CSS Optimization', 'o10n'),
                'href' => add_query_arg(array( 'page' => 'o10n-css' ), admin_url('admin.php'))
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-cache',
                'id' => 'o10n-css-cache',
                'title' => 'CSS cache' . $cache_size,
                'href' => 'javascript:void(0);'
            ));

            $admin_base = 'admin.php';
        } else {
            $admin_bar->add_menu(array(
                'id' => 'o10n-css',
                'title' => '<span class="ab-label">' . __('CSS', 'o10n') . '</span>',
                'href' => add_query_arg(array( 'page' => 'o10n-css' ), admin_url('themes.php')),
                'meta' => array( 'title' => __('CSS Optimization', 'o10n'), 'class' => 'ab-sub-secondary' )
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-css',
                'id' => 'o10n-css-cache',
                'title' => __('Cache', 'o10n') . $cache_size,
                'href' => '#',
                'meta' => array( 'title' => __('Plugin Cache Management', 'o10n'), 'class' => 'ab-sub-secondary', 'onclick' => 'return false;' )
            ));

            $admin_base = 'themes.php';
        }

        // flush CSS cache
        $admin_bar->add_menu(array(
            'parent' => 'o10n-css-cache',
            'id' => 'o10n-cache-flush-css',
            'href' => $this->cache->flush_url('css'),
            'title' => '<span class="dashicons dashicons-trash o10n-menu-icon"></span> Flush CSS cache'
        ));

        // flush CSS concat index cache
        $admin_bar->add_menu(array(
            'parent' => 'o10n-css-cache',
            'id' => 'o10n-cache-flush-css-concat',
            'href' => $this->cache->flush_url('css', 'concat'),
            'title' => '<span class="dashicons dashicons-trash o10n-menu-icon"></span> Flush CSS concat cache (reset index)'
        ));

        // critical CSS quality test
        $admin_bar->add_node(array(
            'parent' => 'o10n-css',
            'id' => 'o10n-css-editor',
            'title' => '<span class="dashicons dashicons-editor-code o10n-menu-icon"></span> ' . __('CSS Editor', 'o10n'),
            'href' => add_query_arg(array( 'page' => 'o10n-css-editor' ), admin_url('themes.php')),
            'meta' => array( 'title' => __('CSS Editor', 'o10n') )
        ));

        // critical CSS quality test
        $critical_css_editor_url = preg_replace('|\#.*$|Ui', '', $currenturl) . ((strpos($currenturl, '?') !== false) ? '&' : '?') . 'o10n-css=1';
        $admin_bar->add_node(array(
            'parent' => 'o10n-css',
            'id' => 'o10n-critical-css-editor',
            'title' => '<span class="dashicons dashicons-image-flip-horizontal o10n-menu-icon"></span> ' . __('Critical CSS Editor', 'o10n'),
            'href' => $critical_css_editor_url,
            'meta' => array( 'title' => __('Critical CSS Editor (split view)', 'o10n'), 'target' => '_blank', )
        ));

        // extract Critical CSS
        $admin_bar->add_node(array(
            'parent' => 'o10n-css',
            'id' => 'o10n-extract-critical-css-widget',
            'title' => '<span class="dashicons dashicons-download o10n-menu-icon"></span> ' . __('Extract Critical CSS (widget)', 'o10n'),
            'href' => $critical_css_editor_url . '#editor',
            'meta' => array(
                'title' => ((is_admin()) ? __('Use on the frontend to start download directly. On the admin panel, this link opens the editor.', 'o10n') : __('Extract Critical CSS via Javascript widget', 'o10n')),
                'target' => '_blank',
                'onclick' => ((is_admin()) ? '' : 'o10n.extract();return false;')
            )
        ));
   
        $admin_bar->add_node(array(
            'parent' => 'o10n-css',
            'id' => 'o10n-extract-full-css-widget',
            'title' => '<span class="dashicons dashicons-download o10n-menu-icon"></span> ' . __('Extract Full CSS (widget)', 'o10n'),
            'href' => $critical_css_editor_url,
            'meta' => array(
                'title' => ((is_admin()) ? __('Use on the frontend to start download directly. On the admin panel, this link opens the editor.', 'o10n') : __('Extract Full CSS via Javascript widget', 'o10n')),
                'target' => '_blank',
                'onclick' => ((is_admin()) ? '' : "o10n.extract(\"full\");return false;")
            )
        ));
    }
}
