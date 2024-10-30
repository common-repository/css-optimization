<?php
namespace O10n;

/**
 * CSS optimization admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

// print form header
$this->form_start(__('CSS Optimization', 'optimization'), 'css');

?>
<table class="form-table">
	<tr valign="top">
		<th scope="row">Minify</th>
		<td>
			<label><input type="checkbox" value="1" name="o10n[css.minify.enabled]" data-json-ns="1"<?php $checked('css.minify.enabled'); ?> /> Enabled</label>
			<p class="description">Compress, bundle and optimize CSS code.</p>

            <div class="suboption" data-ns="css.minify"<?php $visible('css.minify'); ?>>

                <p class="poweredby" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'cssmin')); ?> data-ns-condition="css.minify.minifier==cssmin">Powered by <a href="https://github.com/JoeScylla/cssmin" target="_blank">CssMin</a><span class="google-code"><a href="https://code.google.com/archive/p/cssmin/" target="_blank"><img src="<?php print trailingslashit(O10N_CORE_URI); ?>admin/images/google-code-18h.png" width="25" height="18" border="0" alt="Google Code" title="View on Google Code" /></a></span><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/JoeScylla/cssmin" data-icon="octicon-star" data-show-count="true" aria-label="Star JoeScylla/cssmin on GitHub">Star</a></span></p>

                <p class="poweredby" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'yui')); ?> data-ns-condition="css.minify.minifier==yui">Powered by <a href="https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port" target="_blank">YUI Compressor PHP Port</a><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port" data-icon="octicon-star" data-show-count="true" aria-label="Star tubalmartin/YUI-CSS-compressor-PHP-port on GitHub">Star</a></span></p>

                <p class="poweredby" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'regex')); ?> data-ns-condition="css.minify.minifier==regex">Powered by <a href="https://github.com/mrclay/minify" target="_blank">Minify's Compressor.php</a><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/mrclay/minify" data-icon="octicon-star" data-show-count="true" aria-label="Star mrclay/minify on GitHub">Star</a></span></p>

                <select name="o10n[css.minify.minifier]" data-ns-change="css.minify" data-json-default="<?php print esc_attr(json_encode('cssmin')); ?>">
                    <option value="cssmin"<?php $selected('css.minify.minifier', 'cssmin'); ?>>CssMin.php (Joe Scylla)</option>
                    <option value="yui"<?php $selected('css.minify.minifier', 'yui'); ?>>Yahoo YUI Compressor PHP Port v4.1.1</option>
                    <option value="regex"<?php $selected('css.minify.minifier', 'regex'); ?>>Regular Expression Compressor.php from Minify (mrclay)</option>
                    <option value="custom"<?php $selected('css.minify.minifier', 'custom'); ?>>Custom minifier (WordPress filter hook)</option>
                </select> 
                <p class="description">Choose a minifier that provides the best performance for your CSS code.</p>
            </div>

            <div class="suboption" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'custom')); ?> data-ns-condition="css.minify.minifier==custom">
                <p style="font-size:16px;line-height:18px;">The Custom Minifier option enables to use any CSS minifier via the WordPress filter hook <code>o10n_css_custom_minify</code>. (<a href="javascript:void(0);" onclick="jQuery('#custom_minify_example').fadeToggle();">show example</a>)</p>
            <div class="info_yellow" id="custom_minify_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">
/* Custom CSS minifier */
add_filter('o10n_css_custom_minify', function ($CSS, $base_href) {

    // apply CSS optimization
    exec('/node /path/to/optimize-css.js /tmp/css-source.css');
    $minified = file_get_contents('/tmp/output.css');

    // alternative
    $minified = CSSCompressor::minify($CSS);

    return $minified;

});</pre></div>
            </div>

            <div class="suboption" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'cssmin')); ?> data-ns-condition="css.minify.minifier==cssmin">
                <label><input type="checkbox" value="1" name="o10n[css.minify.ignore_errors.enabled]" data-json-ns="1"<?php $checked('css.minify.ignore_errors.enabled'); ?> /> Ignore CssMin.php errors</label>
                <p class="description">By default invalid CSS code will trigger a CSS parser error. This option enables to suppress errors.</p>
            </div>
            
            <div class="suboption" data-ns="css.minify"<?php $visible('css.minify', ($get('css.minify.minifier') === 'yui' || $get('css.minify.minifier') === 'regex' || $get('css.minify.minifier') === 'custom')); ?> data-ns-condition="css.minify.minifier==yui||css.minify.minifier==regex||css.minify.minifier==custom">

                <p class="poweredby" data-ns="css.minify.rebase"<?php $visible('css.minify.rebase'); ?>>Powered by <a href="https://github.com/pear/Net_URL2/" target="_blank">Net_URL2</a><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/pear/Net_URL2" data-icon="octicon-star" data-show-count="true" aria-label="Star pear/Net_URL2 on GitHub">Star</a></span></p>

                <label><input type="checkbox" value="1" name="o10n[css.minify.rebase.enabled]" data-json-ns="1"<?php $checked('css.minify.rebase.enabled'); ?> /> Rebase relative paths in the CSS</label>

                <p class="suboption"><label><input type="checkbox" value="1" name="o10n[css.minify.import.enabled]" data-json-ns="1"<?php $checked('css.minify.import.enabled'); ?> /> Import stylesheets defined with the <code>@import</code> at-rule</label></p>
                <div data-ns="css.minify.import"<?php $visible('css.minify.import'); ?>>
                    <div>
                        <label><input type="checkbox" value="1" name="o10n[css.minify.import.filter.enabled]" data-json-ns="1"<?php $checked('css.minify.import.filter.enabled'); ?> /> Enable filter</label>
                        <span style="margin-left:0.2em;" data-ns="css.minify.filter"<?php $visible('css.minify.filter'); ?>>
                            <select name="o10n[css.minify.import.filter.type]" data-ns-change="css.minify.import.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                                <option value="include"<?php $selected('css.minify.import.filter.type', 'include'); ?>>Include List</option>
                                <option value="exclude"<?php $selected('css.minify.import.filter.type', 'exclude'); ?>>Exclude List</option>
                            </select>
                        </span>
                    </div>

                    <div style="margin-top:0.5em;" data-ns="css.minify.import.filter"<?php $visible('css.minify.import.filter', ($get('css.minify.import.filter.type') === 'include')); ?> data-ns-condition="css.minify.import.filter.type==include">
                        <h5 class="h">&nbsp;Import Include List</h5>
                        <textarea class="json-array-lines" name="o10n[css.minify.import.filter.include]" data-json-type="json-array-lines" placeholder="Exclude stylesheets by default. Import stylesheets on this list."><?php $line_array('css.minify.import.filter.include'); ?></textarea>
                        <p class="description">Enter (parts of) stylesheet URLs to import, e.g. <code>bootstrap.min.css</code>. One match string per line.</p>
                    </div>
                    <div style="margin-top:0.5em;" data-ns="css.minify.import.filter"<?php $visible('css.minify.import.filter', ($get('css.minify.import.filter.type') === 'exclude')); ?> data-ns-condition="css.minify.import.filter.type==exclude">
                        <h5 class="h">&nbsp;Import Exclude List</h5>
                        <textarea class="json-array-lines" name="o10n[css.minify.import.filter.exclude]" data-json-type="json-array-lines" placeholder="Import stylesheets by default. Exclude stylesheets on this list."><?php $line_array('css.minify.import.filter.exclude'); ?></textarea>
                        <p class="description">Enter (parts of) stylesheet URLs to exclude from import. One match string per line.</p>
                    </div>
                </div>
            </div>

            <p class="info_yellow suboption"><strong><span class="dashicons dashicons-lightbulb"></span></strong> Invalid CSS code may cause issues for minifiers. You can use the CSS editor's <a href="<?php print esc_url(add_query_arg(array( 'page' => 'o10n-css-editor' ), admin_url('themes.php'))); ?>#csslint">CSS Lint</a> feature to repair invalid CSS code.</p>

            <!--p class="suboption"><a href="" class="button button-large">Test Minifier Performance</a></p>
            <p class="description">Minify a concatenated version of your front page CSS and compare minify speed, compression size and CSS render performance for each minifier. This tool relies on <a href="https://developer.mozilla.org/en-US/docs/Web/API/Performance#Browser_compatibility" target="_blank">Performance API</a>.</p-->

        </td>
    </tr>
    <tr valign="top" data-ns="css.minify"<?php $visible('css.minify'); ?>>
        <th scope="row">Minify Filter</th>
        <td>
            <div>
                <label><input type="checkbox" value="1" name="o10n[css.minify.filter.enabled]" data-json-ns="1"<?php $checked('css.minify.filter.enabled'); ?> /> Enable</label>
                <span style="margin-left:0.2em;" data-ns="css.minify.filter"<?php $visible('css.minify.filter'); ?>>
                    <select name="o10n[css.minify.filter.type]" data-ns-change="css.minify.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.minify.filter.type', 'include'); ?>>Include List</option>
                        <option value="exclude"<?php $selected('css.minify.filter.type', 'exclude'); ?>>Exclude List</option>
                    </select>
                </span>
            </div>

            <div style="margin-top:0.5em;" data-ns="css.minify.filter"<?php $visible('css.minify.filter', ($get('css.minify.filter.type') === 'include')); ?> data-ns-condition="css.minify.filter.type==include">
                <h5 class="h">&nbsp;Minify Include List</h5>
                <textarea class="json-array-lines" name="o10n[css.minify.filter.include]" data-json-type="json-array-lines" placeholder="Exclude stylesheets by default. Include stylesheets on this list."><?php $line_array('css.minify.filter.include'); ?></textarea>
                <p class="description">Enter (parts of) stylesheet <code>&lt;link&gt;</code> elements to minify, e.g. <code>bootstrap.min.css</code> or <code>id="stylesheet"</code>. One match string per line.</p>
            </div>
            <div style="margin-top:0.5em;" data-ns="css.minify.filter"<?php $visible('css.minify.filter', ($get('css.minify.filter.type') === 'exclude')); ?> data-ns-condition="css.minify.filter.type==exclude">
                <h5 class="h">&nbsp;Minify Exclude List</h5>
                <textarea class="json-array-lines" name="o10n[css.minify.filter.exclude]" data-json-type="json-array-lines" placeholder="Include stylesheets by default. Exclude stylesheets on this list."><?php $line_array('css.minify.filter.exclude'); ?></textarea>
                <p class="description">Enter (parts of) stylesheet <code>&lt;link&gt;</code> elements to exclude from minification. One match string per line.</p>
            </div>

        </td>
    </tr>
</table>

<div class="advanced-options" data-ns="css.minify" data-json-advanced="css.minify.cssmin"<?php $visible('css.minify', ($get('css.minify.minifier') === 'cssmin')); ?> data-ns-condition="css.minify.minifier==cssmin">

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
    $advanced_options('css.minify.cssmin.filters');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Plugins <a href="https://code.google.com/archive/p/cssmin/wikis/MinifierPlugins.wiki" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>

<?php
    $advanced_options('css.minify.cssmin.plugins');
?>
        </tbody>
    </table>
<br />
<?php
submit_button(__('Save'), 'primary large', 'is_submit', false);
?>
<br />
</div>


<div class="advanced-options" data-ns="css.minify" data-json-advanced="css.minify.yui"<?php $visible('css.minify', ($get('css.minify.minifier') === 'yui')); ?> data-ns-condition="css.minify.minifier==yui">

    <table class="advanced-options-table widefat fixed striped">
        <colgroup><col style="width: 85px;"/><col style="width: 250px;"/><col /></colgroup>
        <thead class="first">
            <tr>
                <th class="toggle">
                    <a href="javascript:void(0);" class="advanced-toggle-all button button-small">Toggle All</a>
                </th>
                <th class="head">
                  YUI Compressor Options
                </th>
                <th>
                    <p class="poweredby">Powered by <a href="https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port" target="_blank">YUI Compressor PHP Port</a><span class="star">
                    <a class="github-button" data-manual="1" href="https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port" data-icon="octicon-star" data-show-count="true" aria-label="Star tubalmartin/YUI-CSS-compressor-PHP-port on GitHub">Star</a></span>
                    </p>
                </th> 
            </tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.minify.yui.options');
?>
        </tbody>
    </table>
<br />
<?php
submit_button(__('Save'), 'primary large', 'is_submit', false);
?>
<br />
</div>

<table class="form-table">
    <tr valign="top" data-ns="css.minify"<?php $visible('css.minify');?>>
        <th scope="row">Concatenate</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.minify.concat.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.enabled'); ?> /> Enabled</label>
            <p class="description">Merge stylesheets into a single file.</p>
            <p data-ns="css.minify.concat"<?php $visible('css.minify.concat'); ?>>
                <label><input type="checkbox" value="1" name="o10n[css.minify.concat.minify]"<?php $checked('css.minify.concat.minify'); ?> /> Use <code>Minify</code> for concatenation.</label>
            </p>
            <div class="suboption" data-ns="css.minify.concat"<?php $visible('css.minify.concat'); ?>>
                <label><input type="checkbox" value="1" name="o10n[css.minify.concat.filter.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.filter.enabled'); ?> /> Enable group filter</label>
                <span data-ns="css.minify.concat.filter"<?php $visible('css.minify.concat.filter'); ?>>
                    <select name="o10n[css.minify.concat.filter.type]" data-ns-change="css.minify.concat.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.minify.concat.filter.type', 'include'); ?>>Include by default</option>
                        <option value="exclude"<?php $selected('css.minify.concat.filter.type', 'exclude'); ?>>Exclude by default</option>
                    </select>
                </span>
                <p class="description">The group filter enables to create bundles of concatenated stylesheets. This enables to bundle stylesheets that are shared between pages while creating separate bundles for the remaining CSS on a page.</p>
            </div>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat.filter"<?php $visible('css.minify.concat.filter'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Concat Group Filter</h5>
            <div id="css-minify-concat-filter-config"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
            <input type="hidden" class="json" name="o10n[css.minify.concat.filter.config]" data-json-type="json-array" data-json-editor-height="auto" data-json-editor-init="1" value="<?php print esc_attr($json('css.minify.concat.filter.config')); ?>" />
            <p class="description">Enter a JSON array with concat group config objects. (<a href="javascript:void(0);" onclick="jQuery('#concat_group_example').fadeToggle();">show example</a>)</p>
            <div class="info_yellow" id="concat_group_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">{
    "match": ["stylesheet.css", {"string": "/plugin.*.css/", "regex":true}], 
    "group": {"title":"Group title", "key": "group-file-key", "id": "id-attr"}, 
    "minify": true, 
    "exclude": false
}</pre></div>
           
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat"<?php $visible('css.minify.concat');  ?>>
        <th scope="row">Merge Media Queries</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.minify.concat.mediaqueries.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.mediaqueries.enabled'); ?> /> Enabled</label>
            <p class="description">Merge stylesheets with a different media attribute to a <a href="https://developer.mozilla.org/docs/Web/CSS/Media_Queries/Using_media_queries" target="_blank">media query</a>. Example: <code>&lt;link rel="stylesheet" ... media="print"&gt;</code> becomes <code>@media print { /* contents */ }</code>.</p>
            <p data-ns="css.minify.concat.mediaqueries"<?php $visible('css.minify.concat.mediaqueries'); ?>>
                <label><input type="checkbox" value="1" name="o10n[css.minify.concat.mediaqueries.filter.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.mediaqueries.filter.enabled'); ?> /> Enable filter</label>
                <span data-ns="css.minify.concat.mediaqueries.filter"<?php $visible('css.minify.concat.mediaqueries.filter'); ?>>
                    <select name="o10n[css.minify.concat.mediaqueries.filter.type]" data-ns-change="css.minify.concat.mediaqueries.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.minify.concat.mediaqueries.filter.type', 'include'); ?>>Include List</option>
                        <option value="exclude"<?php $selected('css.minify.concat.mediaqueries.filter.type', 'exclude'); ?>>Exclude List</option>
                    </select>
                </span>
            </p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat.mediaqueries.filter"<?php $visible('css.minify.concat.mediaqueries.filter', ($get('css.minify.concat.mediaqueries.filter.type') === 'include')); ?> data-ns-condition="css.minify.concat.mediaqueries.filter.type==include">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Media Query Merge Include List</h5>
            <textarea class="json-array-lines" name="o10n[css.minify.concat.mediaqueries.filter.include]" data-json-type="json-array-lines" placeholder="Leave blank to merge all media queries..."><?php $line_array('css.minify.concat.mediaqueries.filter.include'); ?></textarea>
            <p class="description">Enter (parts of) media queries to merge, e.g. <code>screen and (min-width: 480px)</code>. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat.mediaqueries.filter"<?php $visible('css.minify.concat.mediaqueries.filter', ($get('css.minify.concat.mediaqueries.filter.type') === 'exclude')); ?> data-ns-condition="css.minify.concat.mediaqueries.filter.type==exclude">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Media Query Merge Exclude List</h5>
            <textarea class="json-array-lines" name="o10n[css.minify.concat.mediaqueries.filter.exclude]" data-json-type="json-array-lines"><?php $line_array('css.minify.concat.mediaqueries.filter.exclude'); ?></textarea>
            <p class="description">Enter (parts of) media queries to exclude from merging. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat"<?php $visible('css.minify.concat');  ?>>
        <th scope="row">Merge Inline</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.minify.concat.inline.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.inline.enabled'); ?>> Enabled</label>
            <p class="description">Extract inline <code>&lt;style&gt;</code> elements and include the CSS in the concatenated stylesheet.</p>
            <p data-ns="css.minify.concat.inline"<?php $visible('css.minify.concat.inline'); ?>>
                <label><input type="checkbox" value="1" name="o10n[css.minify.concat.inline.filter.enabled]" data-json-ns="1"<?php $checked('css.minify.concat.inline.filter.enabled'); ?> /> Enable filter</label>
                <span data-ns="css.minify.concat.inline.filter"<?php $visible('css.minify.concat.inline.filter'); ?>>
                    <select name="o10n[css.minify.concat.inline.filter.type]" data-ns-change="css.minify.concat.inline.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.minify.concat.inline.filter.type', 'include'); ?>>Include List</option>
                        <option value="exclude"<?php $selected('css.minify.concat.inline.filter.type', 'exclude'); ?>>Exclude List</option>
                    </select>
                </span>
            </p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat.inline.filter"<?php $visible('css.minify.concat.inline.filter', ($get('css.minify.concat.inline.filter.type') === 'include')); ?> data-ns-condition="css.minify.concat.inline.filter.type==include">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Inline Merge Include List</h5>
            <textarea class="json-array-lines" name="o10n[css.minify.concat.inline.filter.include]" data-json-type="json-array-lines" placeholder="Leave blank to minify all inline CSS..."><?php $line_array('css.minify.concat.inline.filter.include'); ?></textarea>
            <p class="description">Enter (parts of) inline <code>&lt;style&gt;</code> elements to concatenate, e.g. <code>background-color:white;</code> or <code>id="style"</code>. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify.concat.inline.filter"<?php $visible('css.minify.concat.inline.filter', ($get('css.minify.concat.inline.filter.type') === 'exclude')); ?> data-ns-condition="css.minify.concat.inline.filter.type==exclude">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Inline Merge Exclude List</h5>
            <textarea class="json-array-lines" name="o10n[css.minify.concat.inline.filter.exclude]" data-json-type="json-array-lines"><?php $line_array('css.minify.concat.inline.filter.exclude'); ?></textarea>
            <p class="description">Enter (parts of) inline <code>&lt;style&gt;</code> elements to exclude from concatenation. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.minify"<?php $visible('css.minify');  ?>>
        <th scope="row">Search &amp; Replace</th>
        <td>
            <div id="css-replace"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
            <input type="hidden" class="json" name="o10n[css.replace]" data-json-type="json-array" data-json-editor-compact="1" data-json-editor-init="1" value="<?php print esc_attr($json('css.replace')); ?>" />

            <p class="description">This option enables to replace strings in the CSS <strong>before</strong> minification. Enter a JSON array with configuration objects <span class="dashicons dashicons-editor-help"></span>.</p>

            <div class="info_yellow"><strong>Example:</strong> <code id="css_search_replace_example" class="clickselect" data-example-text="show string" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;">{"search":"string to match","replace":"newstring"}</code> (<a href="javascript:void(0);" data-example="css_search_replace_example" data-example-html="<?php print esc_attr(__('{"search":"|string to (match)|i","replace":"newstring $1","regex":true}', 'optimization')); ?>">show regular expression</a>)</div>
        </td>
    </tr>
</table>

<table class="form-table">
    <tr valign="top">
        <th scope="row">URL filter</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.url_filter.enabled]" data-json-ns="1"<?php $checked('css.url_filter.enabled'); ?> /> Enabled</label>
            <p class="description">Use this option to modify stylesheet URLs before processing. The filter can be used to remove a cache busting query string, to (selectively) add or remove a CDN or to delete a stylesheet from the HTML.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.url_filter"<?php $visible('css.url_filter'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;URL filter configuration</h5>
            <div id="css-url_filter-config"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
            <input type="hidden" class="json" name="o10n[css.url_filter.config]" data-json-type="json-array" data-json-editor-compact="1" data-json-editor-init="1" value="<?php print esc_attr($json('css.url_filter.config')); ?>" />
            <p class="description">Enter a JSON array with objects. <code>url</code> is a string or regular expression to match a stylesheet URL, <code>ignore</code>, <code>delete</code> or <code>replace</code> control the filter.</p>
            <div class="info_yellow"><strong>Example:</strong> <code id="pre_url_example" data-example-text="show replace" class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;">{"url":"/\/wp-content\/path\/([a-z]+)$/i","regex":true,"replace":"https://cdn.com/$1"}</code> (<a href="javascript:void(0);" data-example="pre_url_example" data-example-html=" <?php print esc_attr('{"url":"toolbar.","ignore":true}'); ?>">show ignore</a>)</div>
        </td>
    </tr>
		
</table>

<p class="suboption info_yellow"><strong><span class="dashicons dashicons-lightbulb"></span></strong> You can enable debug modus by adding <code>define('O10N_DEBUG', true);</code> to wp-config.php. The browser console will show details about CSS loading and a <a href="https://developer.mozilla.org/nl/docs/Web/API/Performance" target="_blank" rel="noopener">Performance API</a> result for each step of the loading and rendering process.</p>

<hr />
<?php
    submit_button(__('Save'), 'primary large', 'is_submit', false);

// print form header
$this->form_end();
