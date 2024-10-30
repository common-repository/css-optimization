<?php
namespace O10n;

/**
 * CSS delivery optimization admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}


// print form header
$this->form_start(__('CSS Delivery Optimization', 'o10n'), 'css');

?>


<table class="form-table">
    <tr valign="top">
        <th scope="row">Async Loading <a href="https://github.com/o10n-x/wordpress-css-optimization/tree/master/docs" target="_blank" title="Recommendations by Google"><span class="dashicons dashicons-editor-help"></span></a></th>
        <td>
            <p class="poweredby">Powered by <a href="https://github.com/filamentgroup/loadCSS" target="_blank">loadCSS</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/filamentgroup/loadCSS" data-icon="octicon-star" data-show-count="true" aria-label="Star filamentgroup/loadCSS on GitHub">Star</a></span></p>
            <label><input type="checkbox" name="o10n[css.async.enabled]" data-json-ns="1" value="1"<?php $checked('css.async.enabled'); ?> /> Enabled</label>
            <p class="description">When enabled, stylesheets are loaded asynchronously via <a href="https://github.com/filamentgroup/loadCSS" target="_blank">loadCSS</a> enhanced with <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content" target="_blank">responsive loading</a>.</p>

            <p data-ns="css.async"<?php $visible('css.async'); ?>>
                <label><input type="checkbox" name="o10n[css.async.rel_preload]" data-json-ns="1" value="1"<?php $checked('css.async.rel_preload'); ?> /> Load stylesheets via <code>&lt;link rel="preload" as="style"&gt;</code> (<a href="https://www.w3.org/TR/preload/" target="_blank">W3C Spec</a>) with loadCSS as polyfill.</label>
            </p>
            <p data-ns="css.async"<?php $visible('css.async'); ?>>
                <label><input type="checkbox" name="o10n[css.async.noscript]" value="1"<?php $checked('css.async.noscript'); ?> /> Add fallback stylesheets via <code>&lt;noscript&gt;</code> for browsers without javascript support.</label>
            </p>
            <div class="suboption" data-ns="css.async"<?php $visible('css.async'); ?>>
                <label><input type="checkbox" value="1" name="o10n[css.async.filter.enabled]" data-json-ns="1"<?php $checked('css.async.filter.enabled'); ?> /> Enable config filter</label>
                <span data-ns="css.async.filter"<?php $visible('css.async.filter'); ?>>
                    <select name="o10n[css.async.filter.type]" data-ns-change="css.async.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.async.filter.type', 'include'); ?>>Include by default</option>
                        <option value="exclude"<?php $selected('css.async.filter.type', 'exclude'); ?>>Exclude by default</option>
                    </select>
                </span>
                <p class="description">The config filter enables to include or exclude stylesheets from async loading or to apply custom async load configuration to individual files or concat groups.</p>
            </div>
        </td>
    </tr>
    <tr valign="top" data-ns="css.async.filter"<?php $visible('css.async.filter'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Async Config Filter</h5>
            <div id="css-async-filter-config"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'o10n'); ?></div></div>
            <input type="hidden" class="json" name="o10n[css.async.filter.config]" data-json-type="json-array" data-json-editor-height="auto" data-json-editor-init="1" value="<?php print esc_attr($json('css.async.filter.config')); ?>" />
            <p class="description">Enter a JSON array with objects. (<a href="javascript:void(0);" onclick="jQuery('#concat_group_example').fadeToggle();">show example</a>)</p>
            <div class="info_yellow" id="concat_group_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">{
    "match": "/concat-group-(x|y)/",
    "regex": true,
    "async": true,
    "media": "all",
    "noscript": true,
    "rel_preload": true,
    "load_position": "timing",
    "load_timing": {
        "type": "media",
        "media": "screen and (max-width: 700px)"
    },
    "render_timing": {
        "type": "requestAnimationFrame",
        "frame": 1
    },
    "localStorage": {
        "max_size": 10000,
        "update_interval": 3600,
        "expire": 86400,
        "head_update": true
    }
}</pre></div>
        </td>
    </tr>
    <tr valign="top" data-ns="css.async"<?php $visible('css.async'); ?>>
        <th scope="row">Load Position</th>
        <td>
            <select name="o10n[css.async.load_position]" data-ns-change="css.async">
                <option value="header"<?php $selected('css.async.load_position', 'header'); ?>>Header</option>
                <option value="footer"<?php $selected('css.async.load_position', 'footer'); ?>>Footer</option>
                <option value="timing"<?php $selected('css.async.load_position', 'timing'); ?>>Timed</option>
            </select>
            <p class="description">Select the position of the HTML document where the downloading of CSS will start.</p>


            <div class="suboption" data-ns="css.async""<?php $visible('css.async', ($get('css.async.load_position') === 'timing'));  ?> data-ns-condition="css.async.load_position==timing">
                <h5 class="h">&nbsp;Load Timing Method</h5>
                <select name="o10n[css.async.load_timing.type]" data-ns-change="css.async" data-json-default="<?php print esc_attr(json_encode('domReady')); ?>">
                    <option value="domReady"<?php $selected('css.async.load_timing.type', 'domReady'); ?>>domReady</option>
                    <option value="requestAnimationFrame"<?php $selected('css.async.load_timing.type', 'requestAnimationFrame'); ?>>requestAnimationFrame (on paint)</option>
                    <option value="requestIdleCallback"<?php $selected('css.async.load_timing.type', 'requestIdleCallback'); ?>>requestIdleCallback</option>
                    <option value="inview"<?php $selected('css.async.load_timing.type', 'inview'); ?>>element in view (on scroll)</option>
                    <option value="media"<?php $selected('css.async.load_timing.type', 'media'); ?>>responsive (Media Query)</option>
                </select>
                <p class="description">Select the timing method for async stylesheet loading. This option is also available per individual stylesheet in the filter config.</p>

                <div class="suboption" data-ns="css.async"<?php $visible('css.async', ($get('css.async.load_timing.type') === 'requestAnimationFrame'));  ?> data-ns-condition="css.async.load_timing.type==requestAnimationFrame">
                    <h5 class="h">&nbsp;Frame number</h5>
                    <input type="number" style="width:60px;" min="1" name="o10n[css.async.load_timing.frame]" value="<?php $value('css.async.load_timing.frame'); ?>" />
                    <p class="description">Optionally, select the frame number to start loading stylesheets. <code>requestAnimationFrame</code> will be called this many times before the stylesheets are loaded.</p>
                </div>


                <div class="suboption" data-ns="css.async"<?php $visible('css.async', ($get('css.async.load_timing.type') === 'requestIdleCallback'));  ?> data-ns-condition="css.async.load_timing.type==requestIdleCallback">

                    <h5 class="h">&nbsp;Timeout</h5>
                    <input type="number" style="width:60px;" min="1" name="o10n[css.async.load_timing.timeout]" value="<?php $value('css.async.load_timing.timeout'); ?>" />
                    <p class="description">Enter a timeout after which the stylesheet should be forced to render.</p>
                
                    <div class="suboption">
                        <h5 class="h">&nbsp;setTimeout fallback</h5>
                        <input type="number" style="width:60px;" min="1" name="o10n[css.async.load_timing.setTimeout]" value="<?php $value('css.async.load_timing.setTimeout'); ?>" />
                        <p class="description">Optionally, enter a timeout in milliseconds for browsers that don't support requestIdleCallback. Leave blank to disable async rendering for those browsers.</p>
                    </div>
                </div>

                <div class="suboption" data-ns="css.async"<?php $visible('css.async', ($get('css.async.load_timing.type') === 'inview'));  ?> data-ns-condition="css.async.load_timing.type==inview">
                    <p class="poweredby">Powered by <a href="https://github.com/camwiegert/in-view" target="_blank">in-view.js</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/camwiegert/in-view" data-icon="octicon-star" data-show-count="true" aria-label="Star camwiegert/in-view on GitHub">Star</a></span></p>
                    <h5 class="h">&nbsp;CSS selector</h5>
                    <input type="text" name="o10n[css.async.load_timing.selector]" value="<?php $value('css.async.load_timing.selector'); ?>" />
                    <p class="description">Enter the <a href="https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelector" target="_blank">CSS selector</a> of the element to watch.</p>
                    
                    <div class="suboption">
                        <h5 class="h">&nbsp;Offset</h5>
                        <input type="number" style="width:60px;" name="o10n[css.async.load_timing.offset]" value="<?php $value('css.async.load_timing.offset'); ?>" />
                        <p class="description">Optionally, enter an offset from the edge of the element to start stylesheet loading.</p>
                    </div>
                </div>

                <div class="suboption" data-ns="css.async"<?php $visible('css.async', ($get('css.async.load_timing.type') === 'media'));  ?> data-ns-condition="css.async.load_timing.type==media">
                    <h5 class="h">&nbsp;Media Query</h5>
                    <input type="text" name="o10n[css.async.load_timing.media]" value="<?php $value('css.async.load_timing.media'); ?>" style="width:400px;max-width:100%;" />
                    <p class="description">Enter a <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Using_media_queries" target="_blank">Media Query</a> for conditional stylesheet loading, e.g. omit stylesheets on mobile devices or load or disable a stylesheet on orientation change.</p>
                </div>
            </div>

            <div class="suboption" data-ns="css.async"<?php $visible('css.async'); ?>>
                <h5 class="h">&nbsp;Timed Render</h5>
                <label><input type="checkbox" name="o10n[css.async.render_timing.enabled]" data-json-ns="1" data-ns-change="css.async.render_timing" value="1"<?php $checked('css.async.render_timing.enabled'); ?> /> Enabled</label>
                <p class="description">When enabled, stylesheets are rendered asynchronously using a timing method.</p>

                <div class="suboption" data-ns="css.async.render_timing"<?php $visible('css.async.render_timing'); ?>>
                    
                        <h5 class="h">&nbsp;Render Timing Method</h5>
                        <select name="o10n[css.async.render_timing.type]" data-ns-change="css.async" data-json-default="<?php print esc_attr(json_encode('domReady')); ?>">
                            <option value="domReady"<?php $selected('css.async.render_timing.type', 'domReady'); ?>>domReady</option>
                            <option value="requestAnimationFrame"<?php $selected('css.async.render_timing.type', 'requestAnimationFrame'); ?>>requestAnimationFrame (on paint)</option>
                            <option value="requestIdleCallback"<?php $selected('css.async.render_timing.type', 'requestIdleCallback'); ?>>requestIdleCallback</option>
                            <option value="inview"<?php $selected('css.async.render_timing.type', 'inview'); ?>>element in view (on scroll)</option>
                            <option value="media"<?php $selected('css.async.render_timing.type', 'media'); ?>>responsive (Media Query)</option>
                        </select>
                        <p class="description">Select the timing method for async stylesheet rendering. This option is also available per individual stylesheet in the filter config.</p>

                        <div class="suboption" data-ns="css.async.render_timing"<?php $visible('css.async.render_timing', ($get('css.async.render_timing.type') === 'requestAnimationFrame'));  ?> data-ns-condition="css.async.render_timing.type==requestAnimationFrame">
                            <h5 class="h">&nbsp;Frame number</h5>
                            <input type="number" style="width:60px;" min="1" name="o10n[css.async.render_timing.frame]" value="<?php $value('css.async.render_timing.frame'); ?>" />
                            <p class="description">Optionally, select the frame number to start stylesheet rendering. <code>requestAnimationFrame</code> will be called this many times before the stylesheets are rendered.</p>
                        </div>

                        <div class="suboption" data-ns="css.async.render_timing"<?php $visible('css.async.render_timing', ($get('css.async.render_timing.type') === 'requestIdleCallback'));  ?> data-ns-condition="css.async.render_timing.type==requestIdleCallback">
        
                            <h5 class="h">&nbsp;Timeout</h5>
                            <input type="number" style="width:60px;" min="1" name="o10n[css.async.render_timing.timeout]" value="<?php $value('css.async.render_timing.timeout'); ?>" />
                            <p class="description">Enter a timeout after which the stylesheet should be forced to render.</p>
                        
                            <div class="suboption">
                                <h5 class="h">&nbsp;setTimeout fallback</h5>
                                <input type="number" style="width:60px;" min="1" name="o10n[css.async.render_timing.setTimeout]" value="<?php $value('css.async.render_timing.setTimeout'); ?>" />
                                <p class="description">Optionally, enter a timeout in milliseconds for browsers that don't support requestIdleCallback. Leave blank to disable async rendering for those browsers.</p>
                            </div>
                        </div>

                        <div class="suboption" data-ns="css.async.render_timing"<?php $visible('css.async.render_timing', ($get('css.async.render_timing.type') === 'inview'));  ?> data-ns-condition="css.async.render_timing.type==inview">
                            <p class="poweredby">Powered by <a href="https://github.com/camwiegert/in-view" target="_blank">in-view.js</a><span class="star"><a class="github-button" data-manual="1" href="https://github.com/camwiegert/in-view" data-icon="octicon-star" data-show-count="true" aria-label="Star camwiegert/in-view on GitHub">Star</a></span></p>
                            <h5 class="h">&nbsp;CSS selector</h5>
                            <input type="text" name="o10n[css.async.render_timing.selector]" value="<?php $value('css.async.render_timing.selector'); ?>" />
                            <p class="description">Enter the <a href="https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelector" target="_blank">CSS selector</a> of the element to watch.</p>
                            
                            <div class="suboption">
                                <h5 class="h">&nbsp;Offset</h5>
                                <input type="number" style="width:60px;" name="o10n[css.async.render_timing.offset]" value="<?php $value('css.async.render_timing.offset'); ?>" />
                                <p class="description">Optionally, enter an offset from the edge of the element to start stylesheet rendering.</p>
                            </div>
                        </div>

                        <div class="suboption" data-ns="css.async.render_timing"<?php $visible('css.async.render_timing', ($get('css.async.render_timing.type') === 'media'));  ?> data-ns-condition="css.async.render_timing.type==media">
                            <h5 class="h">&nbsp;Media Query</h5>
                            <input type="text" name="o10n[css.async.render_timing.media]" value="<?php $value('css.async.render_timing.media'); ?>" style="width:400px;max-width:100%;" />
                            <p class="description">Enter a <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Using_media_queries" target="_blank">Media Query</a> for conditional stylesheet rendering, e.g. render or disable a stylesheet on mobile device orientation change.</p>
                        </div>

                </div>
            </div>
        </td>
     </tr>
    <tr valign="top" data-ns="css.async"<?php $visible('css.async'); ?>>
        <td colspan="2" style="padding:0px;">
<?php
submit_button(__('Save'), 'primary large', 'is_submit', false);
?>
<br />
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">HTTP/2 Server Push</th>
        <td>
        <?php if (!$module_loaded('http2')) {
    ?>
<p class="description">Install the <a href="<?php print esc_url(add_query_arg(array('s' => 'o10n', 'tab' => 'search', 'type' => 'author'), admin_url('plugin-install.php'))); ?>">HTTP/2 Optimization</a> plugin to use this feature.</p>
<?php
} else {
        ?>
            <label><input type="checkbox" name="o10n[css.http2_push.enabled]" data-json-ns="1" value="1"<?php $checked('css.http2_push.enabled'); ?> /> Enabled</label>
            <p class="description">When enabled, stylesheets are pushed using <a href="https://developers.google.com/web/fundamentals/performance/http2/#server_push" target="_blank">HTTP/2 Server Push</a>.</p>

            <div data-ns="css.http2_push"<?php $visible('css.http2_push'); ?>>
                <?php
                    if (!$this->env->is_ssl()) {
                        print '<div class="warning_red">HTTP/2 Server Push requires SSL</div>';
                    } elseif (!$this->options->bool('http2.push.enabled')) {
                        print '<div class="warning_red">HTTP/2 Server Push is disabled in <a href="'.add_query_arg(array( 'page' => 'o10n-http2', 'tab' => 'push' ), admin_url('admin.php')).'">HTTP/2 Server Push Settings</a></div>';
                    } ?>
            
                <label><input type="checkbox" value="1" name="o10n[css.http2_push.filter.enabled]" data-json-ns="1"<?php $checked('css.http2_push.filter.enabled'); ?> /> Enable filter</label>
                <span data-ns="css.http2_push.filter"<?php $visible('css.http2_push.filter'); ?>>
                    <select name="o10n[css.http2_push.filter.type]" data-ns-change="css.http2_push.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('css.http2_push.filter.type', 'include'); ?>>Include List</option>
                        <option value="exclude"<?php $selected('css.http2_push.filter.type', 'exclude'); ?>>Exclude List</option>
                    </select>
                </span>
            </div>
<?php
    }
?>
        </td>
    </tr>
    <tr valign="top" data-ns="css.http2_push.filter"<?php $visible('css.http2_push.filter', ($get('css.http2_push.filter.type') === 'include')); ?> data-ns-condition="css.http2_push.filter.type==include">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;HTTP/2 Server Push Include List</h5>
            <textarea class="json-array-lines" name="o10n[css.http2_push.filter.include]" data-json-type="json-array-lines" placeholder="Leave blank to push all stylesheets..."><?php $line_array('css.http2_push.filter.include'); ?></textarea>
            <p class="description">Enter (parts of) stylesheet <code>&lt;link&gt;</code> elements to push, e.g. <code>bootstrap.min.css</code> or <code>id="stylesheet"</code>. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.http2_push.filter"<?php $visible('css.http2_push.filter', ($get('css.http2_push.filter.type') === 'exclude')); ?> data-ns-condition="css.http2_push.filter.type==exclude">
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;HTTP/2 Server Push Exclude List</h5>
            <textarea class="json-array-lines" name="o10n[css.http2_push.filter.exclude]" data-json-type="json-array-lines"><?php $line_array('css.http2_push.filter.exclude'); ?></textarea>
            <p class="description">Enter (parts of) stylesheet <code>&lt;link&gt;</code> elements to exclude from being pushed. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.async"<?php $visible('css.async'); ?>>
        <th scope="row">LocalStorage Cache</th>
        <td>
            <label><input type="checkbox" name="o10n[css.async.localStorage.enabled]" data-json-ns="1" value="1"<?php $checked('css.async.localStorage.enabled'); ?> /> Enabled</label>
            <p class="description">When enabled, stylesheets are cached using <a href="https://developer.mozilla.org/docs/Web/API/Window/localStorage" target="_blank">localStorage</a>, a technique that is <a href="https://addyosmani.com/basket.js/" target="_blank">used by Google</a> to improve performance on mobile devices.</p>

            <p class="info_yellow" data-ns="css.async.rel_preload"<?php $visible('css.async.rel_preload');  ?>>
                When using <code>rel="preload" as="style"</code> the localStorage cache is not used in <a href="https://caniuse.com/#feat=link-rel-preload" target="_blank">browsers that support it</a>. localStorage will still be used in older browsers.
            </p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.async.localStorage"<?php $visible('css.async.localStorage');  ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Maximum stylesheet size</h5>
            <input type="number" size="20" style="width:100px;" name="o10n[css.async.localStorage.max_size]" min="1" placeholder="No limit..." value="<?php $value('css.async.localStorage.max_size'); ?>" /> 
            <p class="description">Enter a maximum file size in bytes to store in cache. LocalStorage has a total limit of 5-10MB.</p>

            <div style="padding-top:5px;">
            <h5 class="h">&nbsp;Expire time</h5>
            <input type="number" size="20" style="width:100px;" name="o10n[css.async.localStorage.expire]" min="1" placeholder="86400" value="<?php $value('css.async.localStorage.expire'); ?>" />
            <p class="description">Enter a expire time in seconds.</p>
            </div>

            <div style="padding-top:5px;">
            <h5 class="h">&nbsp;Update interval</h5>
            <input type="number" size="20" style="width:100px;" name="o10n[css.async.localStorage.update_interval]" min="0" placeholder="Always..." value="<?php $value('css.async.localStorage.update_interval'); ?>" />
            <p class="description">Enter a interval in seconds to update the cache in the background.</p>
            </div>

            <div style="padding-top:5px;">
            <label><input type="checkbox" name="o10n[css.async.localStorage.head_update]" value="1"<?php $checked('css.async.localStorage.head_update'); ?> /> HTTP HEAD request based update</label>
            <p class="description">Use a HTTP HEAD request and <code>etag</code> and/or <code>last-modified</code> header verification to update the cache.</p>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">Proxy</th>
        <td>
            <label><input type="checkbox" name="o10n[css.proxy.enabled]" data-json-ns="1" value="1"<?php $checked('css.proxy.enabled'); ?> /> Enabled</label>
            <p class="description">Proxy external stylesheets to pass the <a href="https://developers.google.com/speed/docs/insights/LeverageBrowserCaching?hl=" target="_blank">Leverage browser caching</a> rule from Google PageSpeed Insights.</p>

        </td>
    </tr>
    <tr valign="top" data-ns="css.proxy"<?php $visible('css.proxy'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Proxy List</h5>
            <textarea class="json-array-lines" name="o10n[css.proxy.include]" data-json-type="json-array-lines" placeholder="Leave blank to proxy all external stylesheets..."><?php $line_array('css.proxy.include'); ?></textarea>
            <p class="description">Enter (parts of) stylesheet URI's to proxy, e.g. <code>bootstrap.min.css</code>. One match string per line.</p>
        </td>
    </tr>
    <tr valign="top" data-ns="css.proxy"<?php $visible('css.proxy'); ?>>
        <th scope="row">Proxy Capture</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.proxy.capture.enabled]" data-json-ns="1"<?php $checked('css.proxy.capture.enabled'); ?> /> Capture script-injected stylesheets</label>
            <p class="description">When enabled, dynamically via javascript inserted stylesheets are captured and processed by the proxy.</p>
        </td>
    </tr>

    <tr valign="top" data-ns="css.proxy.capture"<?php $visible('css.proxy.capture'); ?>>
        <th scope="row">&nbsp;</th> 
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Proxy Capture List</h5>
            <div id="css-proxy-capture-list"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'o10n'); ?></div></div>
            <input type="hidden" class="json" name="o10n[css.proxy.capture.list]" data-json-type="json-array" data-json-editor-init="1" value="<?php print esc_attr($json('css.proxy.capture.list')); ?>" />
            <p class="description">Enter a JSON array with stylesheet URL strings or JSON objects with a regular expression match.</p>
            <div class="info_yellow"><strong>Example:</strong> <code id="css_proxy_capture_example" class="clickselect" data-example-text="show string" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;">["https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css", "https://domain.com/style.css"]</code> (<a href="javascript:void(0);" data-example="css_proxy_capture_example" data-example-html="<?php print esc_attr(__('[{"match": "jquery\\\\.mobile\\\\.min\\\\.css\\\\?[0-9]+/i", "regex": true, "url": "https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css" }]', 'optimization')); ?>">show regular expression</a>)</div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row">CDN</th>
        <td>
            <label><input type="checkbox" value="1" name="o10n[css.cdn.enabled]" data-json-ns="1"<?php $checked('css.cdn.enabled'); ?> /> Enabled</label>
            <p class="description">When enabled, stylesheets are loaded via a Content Delivery Network (CDN).</p>

            <div data-ns="css.cdn"<?php $visible('css.cdn'); ?>>
                <p data-ns="css.http2_push"<?php $visible('css.http2_push'); ?>>
                    <label><input type="checkbox" name="o10n[css.cdn.http2_push]" value="1"<?php $checked('css.cdn.http2_push'); ?> /> Apply CDN to HTTP/2 pushed stylesheets. This will add <code>crossorigin;</code> to the HTTP/2 push header.</label>
                </p>
            </div>
        </td>
    </tr>
    <tr valign="top" data-ns="css.cdn"<?php $visible('css.cdn'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;CDN URL</h5>
            <input type="url" name="o10n[css.cdn.url]" value="<?php $value('css.cdn.url'); ?>" style="width:500px;max-width:100%;" placeholder="https://cdn.yourdomain.com/" />
            <p class="description">Enter a CDN URL for stylesheets, e.g. <code>https://css.domain.com/</code></p>
            
            <br />
            <h5 class="h">&nbsp;CDN Mask</h5>
            <input type="text" name="o10n[css.cdn.mask]" value="<?php $value('css.cdn.mask'); ?>" style="width:500px;max-width:100%;" placeholder="/" />
            <p class="description">Optionally, enter a CDN mask to apply to the stylesheet path, e.g. <code>/wp-content/cache/o10n/css/</code> to access stylesheets from the root of the CDN domain. The CDN mask enables to shorten CDN URLs.</p>
        </td>
    </tr>
</table>

<p class="suboption info_yellow"><strong><span class="dashicons dashicons-lightbulb"></span></strong> You can enable debug modus by adding <code>define('O10N_DEBUG', true);</code> to wp-config.php. The browser console will show details about CSS loading and a <a href="https://developer.mozilla.org/nl/docs/Web/API/Performance" target="_blank" rel="noopener">Performance API</a> result for each step of the loading and rendering process.</p>

<hr />
<?php
    submit_button(__('Save'), 'primary large', 'is_submit', false);

// print form header
$this->form_end();
