<?php
namespace O10n;

/**
 * CSS editor admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

// get user
$user = wp_get_current_user();

// custom default CSS LINT options
$csslint_options = get_user_meta($user->ID, 'o10n_csslint', true);
if ($csslint_options) {
    $csslint_options = $this->json->parse($csslint_options, true);
    $options = array();
    foreach ($csslint_options as $filter => $filter_settings) {
        foreach ($filter_settings as $key => $value) {
            $options['css.lint.' . $filter . '.' . $key] = $value;
        }
    }
    $this->options->set($options);
}

// auto csslint on changes
$csslint_auto = get_user_meta($user->ID, 'o10n_csslint_auto', true);

// default stylesheet
$theme_css_stylesheet = $view->theme_stylesheet();

// editor theme
$editor_theme = get_user_meta($user->ID, 'o10n_editor_theme', true);
if (!$editor_theme) {
    $editor_theme = 'default';
} else {
    $editor_theme = str_replace('.css', '', $editor_theme);
}

?>
<div class="wrap">

    <table style="width:100%;margin-top:-35px">
        <tr>
        <td valign="bottom">
        <select id="editor_file_select" data-type="css" class="editor_file_select" placeholder="Search a stylesheet relative to the active theme or absolute to WordPress ABSPATH..." data-ext=".css"><option><?php print ($theme_css_stylesheet) ? $theme_css_stylesheet['filepath'] : __('Loading...', 'optimization'); ?></option></select>
        </td>
        <td width="100" align="center"><a href="http://codemirror.net/" target="_blank" style="color:#d30707;font-weight:bold;
    letter-spacing: .5px;text-decoration:none;font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif;">
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" width="32" height="30" align="absmiddle" class="codemirror-logo">
            CodeMirror</a>
            <div style="height:20px;margin-bottom:5px;" class="star"><a class="github-button" data-manual="1" href="https://github.com/codemirror/CodeMirror" data-icon="octicon-star" data-show-count="true" aria-label="Star codemirror/CodeMirror on GitHub">Star</a></div>
        </td>
    </tr>
    </table>
    <div class="clearer"></div>
    <input type="hidden" id="ajax_nonce" value="<?php print esc_attr(wp_create_nonce('o10n')); ?>" />

    <div class="editor_container">
        <div>
            <textarea cols="70" rows="25" id="codemirror_editor" disabled="true" style="display:none;"><?php if ($theme_css_stylesheet) {
    print $theme_css_stylesheet['text'];
} ?></textarea>
            <div class="loading_editor CodeMirror cm-s-<?php print $editor_theme; ?>"><pre class=" CodeMirror-line " role="presentation"><span class="cm-comment"><?php print __('Loading...', 'optimization'); ?></span></pre></div>
        </div>

        <p class="submit">
            <span style="float:right;">
                <button type="button" class="button editor-undo" style="display:none;" title="<?php esc_attr(__('Undo')); ?>"><span class="dashicons dashicons-undo"></span></button>
                <button type="button" class="button editor-redo" style="display:none;" title="<?php esc_attr(__('Redo')); ?>"><span class="dashicons dashicons-redo"></span></button>
                <button type="button" class="button editor-reload-file" style="display:none;"><?php print __('Reload File', 'optimization'); ?></button>
                <button type="button" class="button editor-delete-file" title="<?php print esc_attr(__('Delete File')); ?>"><span class="dashicons dashicons-dismiss"></span></button>
            </span>
            <button type="button" class="button button-primary editor-save-file"><?php print __('Update File'); ?></button>
            <button type="button" class="button css_beautify_start">Beautify</button>
            <button type="button" class="button css_minify_start"><span class="clean-css-logo">clean-css</span></button>
            <button type="button" class="button csslint_start" data-scroll-results="0"><span class="csslint-logo">CSS <strong>LINT</strong></span></button>
            <span class="spinner"></span>
            <span class="status"></span>
        </p>
    </div>
    <br class="clear" />
</div>

<div id="post-body" class="metabox-holder">
    <div id="post-body-content">
        <div class="postbox">
            <div class="inside">

                <h3 style="margin-bottom:0px;" id="csslint"><span class="csslint-logo">CSS <strong>LINT</strong></span></h3>

                <p class="description">Verify the quality and performance of the CSS code using <a href="http://csslint.net/" target="_blank">CSS LINT</a>.</p>

<div class="advanced-options csslint-options" data-json-advanced="custom" data-no-update="1">
    <table class="advanced-options-table widefat fixed striped">
        <col style="width: 85px;"/><col style="width: 250px;"/><col />
        <thead>
            <tr>
                <th class="toggle">
                    <a href="javascript:void(0);" class="advanced-toggle-all button button-small">Toggle All</a>
                </th>
                <th class="middlehead">
                   Rules <a href="https://github.com/CSSLint/csslint/wiki/Rules" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>
                </th>
                <th>
                    <p class="poweredby">Powered by <a href="https://github.com/CSSLint/csslint" target="_blank">CSSLint</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/CSSLint/csslint" data-icon="octicon-star" data-show-count="true" aria-label="Star CSSLint/csslint on GitHub">Star</a></span></span>
                    </p>
                </th>
            </tr>
            <tr><td colspan="3" class="subhead">CSS Errors <a href="https://github.com/CSSLint/csslint/wiki/Rules#possible-errors" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.errors');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Compatibility <a href="https://github.com/CSSLint/csslint/wiki/Rules#compatibility" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.compatibility');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Performance <a href="https://github.com/CSSLint/csslint/wiki/Rules#performance" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.performance');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Maintainability &amp; Duplication <a href="https://github.com/CSSLint/csslint/wiki/Rules#maintainability--duplication" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.maintainability');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">Accessibility <a href="https://github.com/CSSLint/csslint/wiki/Rules#accessibility" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.accessibility');
?>
        </tbody>
        <thead>
            <tr><td colspan="3" class="subhead">OOCSS <a href="https://github.com/CSSLint/csslint/wiki/Rules#oocss" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></td></tr>
        </thead>
        <tbody>
<?php
    $advanced_options('css.lint.oocss');
?>
            <tr style="background-color:inherit;">
                <td colspan="3">
                    <label><input type="checkbox" name="csslint_auto" name="o10n[auto]" value="1" <?php print(($csslint_auto) ? ' checked' : ''); ?> /> Auto-lint on changes.</label>
                </td>
            </tr> 
        </tbody>
    </table>
    <p class="submit">
        <button type="button" class="button" id="csslint_save">Save settings</button>
        <button type="button" class="button csslint_start">Start <span class="csslint-logo">CSS <strong>LINT</strong></span></button>
        <span class="spinner" style="display:none;float:none;"></span>
        <span class="status"></span>
    </p>

    <p id="csslint_status" style="display:none;"></p>
    <table class="advanced-options-table widefat fixed striped" id="csslint_results" style="display:none;">
        <colgroup>
        <col style="width: 40px;"/>
        <col style="width: 60px;"/>
        <col style="width: 200px;"/>
        <col />
        <col style="width: 150px;"/>
        </colgroup>
        <thead>
            <tr>
                <th colspan="5" class="singlehead">
                   CSS Issues
                </th>
            </tr>
        </thead>
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th>Line</th>
                <th>Title</th>
                <th>Description</th>
                <th>Browsers</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div><!-- .advanced-options -->


                <h3 style="margin-top:2em;margin-bottom:0px;" id="beautify">CSS Beautify</h3>

                <p class="description">Beautify the CSS using a browser-build of <a href="https://github.com/jakubpawlowicz/clean-css" target="_blank">clean-css</a>.</p>

                <div class="advanced-options" data-json-advanced="custom">
                    <table class="advanced-options-table widefat fixed striped">
                        <col style="width: 250px;"/><col />
                        <thead>
                            <tr>
                                <th class="singlehead">
                                   Options <a href="https://github.com/jakubpawlowicz/clean-css#formatting-options" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>
                                </th>
                                <th>
                                    <p class="poweredby">Powered by <a href="https://github.com/jakubpawlowicz/clean-css" target="_blank">clean-css</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/jakubpawlowicz/clean-css" data-icon="octicon-star" data-show-count="true" aria-label="Star jakubpawlowicz/clean-css on GitHub">Star</a></span></span>
                                    </p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="2" class="json-no-padding">
                                <div id="css_beautify_options"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
                            </td></tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <button type="button" class="button" id="css_beautify_save">Save settings</button>
                        <button type="button" class="button css_beautify_start">Start Beautify</button>
                        <span class="spinner"></span>
                        <span class="status"></span>
                    </p>
                </div>


                <h3 style="margin-top:2em;margin-bottom:0px;" id="minify">CSS Optimization</h3>

                <p class="description">Minify and optimize the CSS using a browser-build of <a href="https://github.com/jakubpawlowicz/clean-css" target="_blank">clean-css</a></p>

                <div class="advanced-options" data-json-advanced="custom">
                    <table class="advanced-options-table widefat fixed striped">
                        <col style="width: 250px;"/><col />
                        <thead>
                            <tr>
                                <th class="singlehead">
                                   Options <a href="https://github.com/jakubpawlowicz/clean-css#optimization-levels" target="_blank"><span class="dashicons dashicons-editor-help"></span></a>
                                </th>
                                <th>
                                    <p class="poweredby">Powered by <a href="https://github.com/jakubpawlowicz/clean-css" target="_blank">clean-css</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/jakubpawlowicz/clean-css" data-icon="octicon-star" data-show-count="true" aria-label="Star jakubpawlowicz/clean-css on GitHub">Star</a></span></span>
                                    </p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="2" class="json-no-padding">
                                <div id="css_minify_options"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
                            </td></tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <button type="button" class="button" id="css_minify_save">Save settings</button>
                        <button type="button" class="button css_minify_start">Start <span class="clean-css-logo">clean-css</span></button>
                        <span class="spinner"></span>
                        <span class="status"></span>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>