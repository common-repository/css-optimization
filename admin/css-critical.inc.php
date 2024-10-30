<?php
namespace O10n;

/**
 * Critical CSS admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

 
// print form header
$this->form_start(__('CSS Delivery Optimization', 'optimization'), 'css');

// critical css files list
$files_list = $view->critical_css_files();

?>
<h3>Critical CSS Editor &amp; Quality Test</h3>
<div id="criticalcss_editor">
    <div class="pageselect">
    <select class="wp-pageselect" placeholder="<?php esc_attr_e('Search a post/page/category by ID, name or URL...', 'optimization'); ?>"><option value=""></option><option value="<?php print home_url(); ?>">Home Page (index)</option></select>
    </div>
    <button type="button" class="button button-large splitview">Quality Test</button>
    <button type="button" id="editorview" class="button button-large editor">Editor</button>

    <div style="margin-top:1em;clear:both;">
    <label><input type="checkbox" value="1" name="o10n[css.critical.editor_public.enabled]"<?php $checked('css.critical.editor_public.enabled'); ?> /> Enable public access (protected against indexing by Google)</label>
    <p class="description">When enabled, you can quickly open the Critical CSS Editor for an URL by adding the query string <code><strong>?o10n-css</strong></code>. Without this option the editor is available to logged in administrators.</p>
    </div>
</div>

<br />
<br />
<div class="advanced-options critical-css-files">
    <table class="advanced-options-table widefat fixed striped">
        <col class="handle_col" />
        <col class="sort_col" />
        <col class="file_col" />
        <col class="options_col"/>
        <thead>
            <tr>
                <th class="singlehead" colspan="4">
                   Critical CSS Files <a href="https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs#critical-css--above-the-fold-optimization" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>
                   <p class="description" style="font-weight:normal;margin-bottom:0px;">Enter files containing the critical CSS that should be included in the <code>&lt;head&gt;</code> of the page.</p>
                </th>
            </tr>
        </thead>
        <tbody id="critical-css-files">
<?php
    // print critical CSS files list
    foreach ($files_list as $file) {
        if (isset($file['conditions'])) {
            $conditions = json_encode($file['conditions']);
            $conditions_count = count($file['conditions']);
        } else {
            $conditions = false;
        }
        if (isset($file['error'])) {
            ?>
                <tr data-file="<?php print esc_attr($file['filepath']); ?>"<?php if ($conditions) {
                print ' data-conditions="'.esc_attr($conditions).'"';
            } ?>>
                    <td class="handle"></td>
                    <td class="priority"></td>
                    <td class="file">
                        <strong><?php print esc_html(((isset($file['title']) && $file['title']) ? $file['title'] : basename($file['file']))); ?></strong>
                        <p class="error"><?php print esc_html($file['error']); ?></p>
                    </td>
                    <td class="options">
                        <a href="javascript:void(0);" class="delete"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
<?php
        } elseif (isset($file['auto'])) {
            ?>
                <tr data-auto="<?php print esc_attr($file['auto']); ?>"<?php if ($conditions) {
                print ' data-conditions="'.esc_attr($conditions).'"';
            } ?>>
                    <td class="handle"><span class="grip"></span></td>
                    <td class="priority"><input type="number" name="o10n[sort][<?php print esc_attr($file['file']); ?>]" value="<?php print esc_attr($file['priority']); ?>" title="Priority (loading order)"></td>
                    <td class="file">
                        Auto-config
                        <p class="foot"></p>
                    </td>
                    <td class="options">
                        <a href="javascript:void(0);" class="delete" title="Delete file from critical CSS list"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
<?php
        } else {
            ?>
                <tr data-file="<?php print esc_attr($file['filepath']); ?>"<?php if ($conditions) {
                print ' data-conditions="'.esc_attr($conditions).'"';
            } ?>>
                    <td class="handle"><span class="grip"></span></td>
                    <td class="priority"><input type="number" name="o10n[sort][<?php print esc_attr($file['file']); ?>]" value="<?php print esc_attr($file['priority']); ?>" title="Priority (loading order)"></td>
                    <td class="file">
                        <?php if ($conditions) {
                ?>
                        <div class="conditions"><span><?php if ($conditions_count === 1) {
                    print '1 condition';
                } else {
                    print $conditions_count . ' conditions';
                } ?></span><div class="json"><?php if (strlen($conditions) > 50) {
                    $conditions = substr($conditions, 0, 40) . '&hellip;]';
                }
                print $conditions; ?></div></div>
                        <?php
            } ?>
                        <a href="<?php print esc_url($file['edit_url']); ?>"><?php print esc_html(((isset($file['title']) && $file['title']) ? $file['title'] : basename($file['file']))); ?></a><span class="size" data-size="<?php print esc_attr($file['size']); ?>">(<span><?php print esc_html($file['hsize']); ?></span>)</span>
                        <p class="foot"><span class="filepath"><?php print $file['filepath']; ?></span> (<span class="date"><?php print $file['date']; ?></span>)</p>
                    </td>
                    <td class="options">
                        <a href="<?php print esc_url($file['edit_url']); ?>" class="button edit" title="Edit CSS">Edit</a>
                        <a href="javascript:void(0);" class="button conditions" title="Edit Critical CSS Conditions">Conditions</a>
                        <a href="javascript:void(0);" class="delete" title="Delete file from critical CSS list"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
<?php
        }
    }
?>
            <template id="critical-css-files-file">
            <tr>
                    <td class="handle"><span class="grip"></span></td>
                    <td class="priority"><input type="number" value="" title="Priority (loading order)"></td>
                    <td class="file">
                        <div class="conditions"><span></span><div class="json"></div></div>
                        <a href="#" class="title"></a><span class="size">(<span></span>)</span>
                        <p class="foot"><span class="filepath"></span> (<span class="date"></span>)</p>
                    </td>
                    <td class="options">
                        <a href="#" class="button edit" title="Edit CSS">Edit</a>
                        <a href="javascript:void(0);" class="button conditions" title="Edit Critical CSS Conditions">Conditions</a>
                        <a href="javascript:void(0);" class="delete" title="Delete file from critical CSS list"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
            </template>
            <template id="critical-css-files-auto">
                <tr>
                    <td class="handle"><span class="grip"></span></td>
                    <td class="priority"><input type="number" value="" title="Priority (loading order)"></td>
                    <td class="file">
                        <span class="title">Auto-config</span>
                        <p class="foot"></p>
                    </td>
                    <td class="options">
                        <a href="javascript:void(0);" class="delete" title="Delete file from critical CSS list"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
            </template>
            <template id="critical-css-files-error">
                <tr>
                    <td class="handle"></td>
                    <td class="priority"></td>
                    <td class="file">
                        <strong class="title"></strong>
                        <p class="error"></p>
                    </td>
                    <td class="options">
                        <a href="javascript:void(0);" class="delete"><span class="dashicons dashicons-dismiss"></span></a>
                    </td>
                </tr>
            </template>
        </tbody>
        <tbody id="critical-css-files-loading" style="display:none;">
            <tr><td colspan="4">Loading files...</td></tr>
        </tbody>
        <tbody id="critical-css-files-empty" style="display:none;">
            <tr><td colspan="4">No Critical CSS have been added yet. <a href="javascript:void(0);" class="add">Add Critical CSS file</a>.</td></tr>
        </tbody>
    </table>
    <div class="submit">
        <input type="hidden" class="json" name="o10n[css.critical.files]" data-json-type="json-array" value="<?php print esc_attr($json('css.critical.files')); ?>" />
        <button type="button" class="button button-large" id="add_critical_css_file">Add file</button>
    </div>
</div>

<div id="add_critical_css_file_form" style="display:none;">
    <h3 style="margin-bottom:0px;">Add Critical CSS File</h3>
    <table class="form-table">
        <tr valign="top">
            <td>
                <h5 class="h">File Name</h5>
                <input type="text" class="file" pattern="^[a-zA-Z0-9-_]+(\.css)?$" placeholder="Filename, e.g. frontpage.css or post-type.css" data-invalid="Invalid filename. Enter a filename with the extension .css." id="critical_css_add_file" style="width:300px;max-width:100%;">
        
                <div class="suboption">
                    <h5 class="h">Title (optional)</h5>
                    <input type="text" class="title" style="width:300px;max-width:100%;">
                </div>
                <div class="suboption">
                    <h5 class="h">Priority</h5>
                    <input type="number" class="priority" placeholder="Priority" data-invalid="Priority should be a positive integer." min="1" value="1" style="width:100px;max-width:100%;">
                </div>
            </td>
        </tr>
    </table>
    <div class="submit">
        <button type="button" class="button button-primary add">Add File</button>
        <button type="button" class="button add-edit">Add File &amp; Edit</button>
        <span class="spinner" style="display:none;float:none;"></span>
        <span class="status"></span>
    </div>
</div>

<br />

<div class="advanced-options" id="critical_css_conditions_container" style="display:none;">
    <table class="advanced-options-table widefat fixed striped">
        <thead>
            <tr>
                <th class="singlehead">
                   Critical CSS Conditions <a href="https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs#critical-css--above-the-fold-optimization" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>
                   <p class="description" style="font-weight:normal;margin-bottom:0px;">Conditions enable to configure tailored Critical CSS for individual posts, pages, categories or templates. You can create custom conditions to apply Critical CSS based on PHP logic, for example based on a multi-site ID or a cookie.</p>
                </th>
            </tr>
            <tr>
                <th class="subhead">
                   File: <code class="file"></code>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="json-no-padding">
                <div id="critical_css_conditions"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
                <input type="hidden" id="critical_css_conditions_src" name="o10n[critical_css_conditions]" data-json-type="json-array" />
            </td></tr>
        </tbody>
    </table>
    <br />
    <p class="suboption description">Enter a JSON array with objects. (<a href="javascript:void(0);" onclick="jQuery('#condition_example').fadeToggle();">show example</a> / <a href="https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs#critical-css--above-the-fold-optimization" target="_blank">documentation</a>)</p>
            <div class="info_yellow" id="condition_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">[{
    "method": "is_page",
    "arguments": [[1,6,19]]
}, {
    "method": "is_front_page"
}]</pre></div>
    

    <div class="submit">
        <button type="button" class="button button-primary save">Save Conditions</button>
        <button type="button" class="button cancel">Cancel</button>
    </div>
</div>

<table class="form-table">
    <tr valign="top">
        <th scope="row">Minify</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.critical.minify.enabled]" data-json-ns="1"<?php $checked('css.critical.minify.enabled'); ?> /> Enabled</label>
            <p class="description">Compress CSS using <a href="https://github.com/JoeScylla/cssmin" target="_blank">PHP CssMin</a>.</p>
        </td>
    </tr>
</table>

<div class="advanced-options" data-ns="css.critical.minify" data-json-advanced="css.critical.minify"<?php $visible('css.critical.minify'); ?>>

    <table class="advanced-options-table widefat fixed striped">
        <colgroup><col style="width: 85px;"/><col style="width: 250px;"/><col /></colgroup>
        <thead class="first">
            <tr>
                <th class="toggle">
                    <a href="javascript:void(0);" class="advanced-toggle-all button button-small">Toggle All</a>
                </th>
                <th class="head">
                  PHP CssMin Options
                </th>
                <th>
                    <p class="poweredby">Powered by <a href="https://github.com/JoeScylla/cssmin" target="_blank">CssMin</a><span class="google-code"><a href="https://code.google.com/archive/p/cssmin/" target="_blank"><img src="<?php print trailingslashit(O10N_CORE_URI); ?>admin/images/google-code-18h.png" width="25" height="18" border="0" alt="Google Code" title="View on Google Code" /></a></span><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/JoeScylla/cssmin" data-icon="octicon-star" data-show-count="true" aria-label="Star JoeScylla/cssmin on GitHub">Star</a></span>
                    </p>
                </th> 
            </tr>
            <tr><td colspan="3" class="subhead">Filters <a href="https://code.google.com/archive/p/cssmin/wikis/MinifierFilters.wiki" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.critical.minify.cssmin.filters');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Plugins <a href="https://code.google.com/archive/p/cssmin/wikis/MinifierPlugins.wiki" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>

<?php
    $advanced_options('css.critical.minify.cssmin.plugins');
?>
        </tbody>
    </table>
</div>

<table class="form-table">
    <tr>
        <th scope="row">HTTP/2 Server Push</th>
        <td><?php if (!$module_loaded('http2')) {
    ?>
<p class="description">Install the <a href="<?php print esc_url(add_query_arg(array('s' => 'o10n', 'tab' => 'search', 'type' => 'author'), admin_url('plugin-install.php'))); ?>">HTTP/2 Optimization</a> plugin to use this feature.</p>
<?php
} else {
        ?>
            <label><input type="checkbox" value="1" name="o10n[css.critical.http2.enabled]"<?php $checked('css.critical.http2.enabled'); ?> /> Enabled</label>
            <p class="description">Load Critical CSS via HTTP/2 Server Push instead of inline. This option requires a HTTP/2 Server Push enabled server.</p>
            <?php
    }
?>
         </td>
    </tr>
</table>

<?php
    submit_button(__('Save'), 'primary large', 'is_submit', false);
?>
<br />

<?php

// print form header
$this->form_end();
