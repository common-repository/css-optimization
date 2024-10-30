<?php
namespace O10n;

/**
 * CSS Optimization Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Css extends Controller implements Controller_Interface
{
    // module key refereces
    private $client_modules = array(
        'css'
    );

    // automatically load dependencies
    private $client_module_dependencies = array();

    private $minifier; // minifier

    private $replace = null; // replace in CSS
    private $stylesheet_cdn; // stylesheet CDN config
    private $http2_push; // HTTP/2 Server Push config

    private $diff_hash_prefix; // diff based hash prefix
    private $last_used_minifier; // last used minifier

    // extracted CSS elements
    private $css_elements = array();

    // load/render position
    private $load_position;
    private $load_timing;
    private $render_timing;
    private $rel_preload = false; // default rel="preload"
    private $noscript = false; // default <noscript>

    private $async_filter; // filter for stylesheets
    private $async_filterType;

    private $localStorage = false; // default localStorage config

    private $rebase_uris = false;
    private $process_import = false;
    private $process_import_filter = false;
    private $process_import_filterType;

    private $debug_mode = false;

    // CssMin.php settings
    private $cssmin_minify_filters = null;
    private $cssmin_minify_plugins = null;

    // YUI instance
    private $YUI = null;

    // Compressor.php instance
    private $Compressor = null;

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
            'options',
            'url',
            'env',
            'file',
            'http',
            'cache',
            'client',
            'json',
            'output',
            'tools',
            'proxy',
            'output'
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

        // debug modus
        $this->debug_mode = (defined('O10N_DEBUG') && O10N_DEBUG);

        // add module definitions
        $this->client->add_module_definitions($this->client_modules, $this->client_module_dependencies);

        // optimize CSS?
        if ($this->options->bool(['css.minify','css.async','css.proxy'])) {
            if ($this->options->bool('css.minify.enabled')) {
                $this->minifier = $this->options->get('css.minify.minifier', 'cssmin');
            }

            $this->rebase_uris = $this->options->bool('css.minify.rebase.enabled');
            $this->process_import = $this->options->bool('css.minify.import.enabled');
            if ($this->process_import && $this->options->bool('css.minify.import.filter.enabled')) {
                $this->process_import_filterType = $this->options->get('css.minify.import.filter.type');
                $this->process_import_filter = $this->options->get('css.minify.import.filter.' . $this->process_import_filterType);
                if (is_null($this->process_import_filter)) {
                    $this->process_import_filter = array();
                }
            }

            // load responsive module
            $this->client->load_module('responsive');

            if ($this->options->bool('css.proxy')) {
                
                // load proxy module
                $this->client->load_module('proxy');
            }

            // add CSS optimization client module
            $this->client->load_module('css', O10N_CORE_VERSION, $this->core->modules('css')->dir_path());

            // async loading
            if ($this->options->bool('css.async')) {
                $this->client->set_config('css', 'async', true);

                // rel="preload" based loading
                // @link https://www.w3.org/TR/2015/WD-preload-20150721/
                if ($this->options->bool('css.async.rel_preload')) {
                    $this->rel_preload = true;
                }

                // <noscript> fallback
                if ($this->options->bool('css.async.noscript')) {
                    $this->noscript = true;
                }

                // async download position
                $this->load_position = $this->options->get('css.async.load_position', 'header');
                if ($this->load_position === 'footer') {
                    // set load position
                    $this->client->set_config('css', 'load_position', $this->client->config_index('key', 'footer'));
                } elseif ($this->load_position === 'timing') {

                    // add timed exec module
                    $this->client->load_module('timed-exec');

                    // set load position
                    $this->client->set_config('css', 'load_position', $this->client->config_index('key', 'timing'));

                    // timing type
                    $timing_type = $this->options->get('css.async.load_timing.type');
                    switch ($timing_type) {
                        case "media":

                            // add responsive exec module
                            $this->client->load_module('responsive');
                        break;
                        case "inview":

                            // add inview exec module
                            $this->client->load_module('inview');
                        break;
                    }

                    // timing config
                    $this->load_timing = $this->timing_config($this->options->get('css.async.load_timing.*'));
                    if ($this->load_timing) {
                        $this->client->set_config('css', 'load_timing', $this->load_timing);
                    }
                }

                if ($this->options->bool('css.async.render_timing.enabled')) {
                        
                    // add timed exec module
                    $this->client->load_module('timed-exec');

                    // timing type
                    $timing_type = $this->options->get('css.async.render_timing.type');
                    switch ($timing_type) {
                        case "requestAnimationFrame":
                            
                        break;
                        case "media":

                            // add responsive exec module
                            $this->client->load_module('responsive');
                        break;
                        case "inview":

                            // add inview exec module
                            $this->client->load_module('inview');
                        break;
                    }

                    // timing config
                    $this->render_timing = $this->timing_config($this->options->get('css.async.render_timing.*'));
                    if ($this->render_timing) {
                        $this->client->set_config('css', 'render_timing', $this->render_timing);
                    }
                }

                // localStorage cache
                if ($this->options->bool('css.async.localStorage')) {

                    // load client module
                    $this->client->load_module('localstorage');

                    // set enabled state
                    $this->client->set_config('css', 'localStorage', true);

                    // localStorage config
                    $this->localStorage = array();

                    $config_keys = array('max_size','expire','update_interval');
                    foreach ($config_keys as $key) {
                        $this->localStorage[$key] = $this->options->get('css.async.localStorage.' . $key);
                        if ($this->localStorage[$key]) {
                            $this->client->set_config('css', 'localStorage_' . $key, $this->localStorage[$key]);
                        }
                    }

                    if ($this->options->bool('css.async.localStorage.head_update')) {
                        $this->localStorage['head_update'] = 1;
                        $this->client->set_config('css', 'localStorage_head_update', 1);
                    }
                }
            }

            // CDN enabled
            if ($this->options->bool('css.cdn')) {

                // CDN config
                $this->stylesheet_cdn = array(
                    $this->options->get('css.cdn.url'),
                    $this->options->get('css.cdn.mask')
                );
            } else {
                $this->stylesheet_cdn = false;
            }

            // apply CDN to pushed assets
            $this->http2_push_cdn = $this->options->bool('css.cdn.http2_push');

            // HTTP/2 Server Push enabled
            if ($this->options->bool('css.http2_push.enabled') && $this->core->module_loaded('http2')) {
                if (!$this->options->bool('css.http2_push.filter')) {
                    $this->http2_push = true;
                } else {
                    $filterType = $this->options->get('css.http2_push.filter.type');
                    $filterConfig = ($filterType) ? $this->options->get('css.http2_push.filter.' . $filterType) : false;

                    if (!$filterConfig) {
                        $this->http2_push = false;
                    } else {
                        $this->http2_push = array($filterType, $filterConfig);
                    }
                }
            } else {
                $this->http2_push = false;
            }

            // CSS Search & Replace config
            $replace = $this->options->get('css.replace');
            if (!$replace || empty($replace)) {
                $this->replace = false;
            } else {
                $this->replace = array(
                    'search' => array(),
                    'replace' => array(),
                    'search_regex' => array(),
                    'replace_regex' => array()
                );
                
                foreach ($replace as $object) {
                    if (!isset($object['search']) || trim($object['search']) === '') {
                        continue;
                    }

                    if (isset($object['regex']) && $object['regex']) {
                        $this->replace['search_regex'][] = $object['search'];
                        $this->replace['replace_regex'][] = $object['replace'];
                    } else {
                        $this->replace['search'][] = $object['search'];
                        $this->replace['replace'][] = $object['replace'];
                    }
                }
            }

            // add filter for HTML output
            add_filter('o10n_html_pre', array( $this, 'process_html' ), 10, 1);
        }
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @param  string $HTML Reference to HTML to process
     * @return string Filtered HTML
     */
    final public function process_html($HTML)
    {
        // verify if empty
        if ($HTML === '' || !$this->env->is_optimization()) {
            return $HTML; // no HTML
        }

        // extract <link> and <style> elements from HTML
        $this->extract($HTML);

        // no CSS elements, skip
        if (empty($this->css_elements)) {
            return $HTML;
        }

        // sheet urls
        $sheet_urls = array();

        // client config
        $async_sheets = array();

        // load async
        $async = $this->options->bool('css.async');
        if ($async) {

            // async load position
            $async_position = ($this->options->get('css.async.position') === 'footer') ? 'foot' : 'critical-css';
        }

        // concatenation
        $concat = $this->options->bool('css.minify') && $this->options->bool('css.minify.concat');

        // rel="preload"
        if ($this->rel_preload) {
            
            // rel="preload" position
            $this->rel_preload_position = ($async_position) ? $async_position : 'critical-css';
        }

        // concatenation settings
        if ($concat) {
            
            // concatenate filter
            if ($this->options->bool('css.minify.concat.filter')) {
                $concat_filter = $this->sanitize_filter($this->options->get('css.minify.concat.filter.config'));
            } else {
                $concat_filter = false;
            }

            // concatenate and merge mediaQueries (media="" to @mediaQuery {})
            $concat_media_queries = $this->options->bool('css.minify.concat.mediaqueries');
            if ($concat_media_queries && $this->options->bool('css.minify.concat.mediaqueries.filter')) {
                $concat_mq_filter_type = $this->options->get('css.minify.concat.mediaqueries.filter.type');
                $concat_mq_filter = $this->options->get('css.minify.concat.mediaqueries.filter.' . $concat_mq_filter_type);
                if (empty($concat_mq_filter)) {
                    $concat_mq_filter = false;
                }
            } else {
                $concat_mq_filter = false;
            }

            // concatenate
            $concat_groups = array();
            $concat_group_settings = array();
        }

        // walk css elements
        foreach ($this->css_elements as $sheet) {

            // concatenate
            if ($concat && (
                (isset($sheet['minified']) && $sheet['minified']) // minified source
            )) {
                // concat group filter
                if ($concat_filter) {

                    // set to false (skip concat) if concatenation is excluded by default
                    $concat_group = ($this->options->get('css.minify.concat.filter.type', 'include') !== 'include') ? false : 'global';

                    // apply group filter
                    $this->apply_filter($concat_group, $concat_group_settings, $sheet['tag'], $concat_filter);
                } else {
                    $concat_group = 'global';
                }

                // include stylesheet in concatenation
                if ($concat_group) {
                    $merge_media = false;

                    // merge media queries
                    if ($concat_media_queries && $sheet['media'] && $sheet['media'] !== 'all') {
                        if ($concat_mq_filter) {

                            // match filter list
                            $match = $this->tools->filter_list_match($sheet['tag'], $concat_mq_filter_type, $concat_mq_filter);

                            // excluded or not included
                            if (!$match) {
                                $concat_group .= '@media' . $sheet['media'];
                            } else {
                                $merge_media = true;
                            }
                        } else {
                            $merge_media = true;
                        }
                    } elseif ($sheet['media'] && $sheet['media'] !== 'all') {

                        // separate group for media queries
                        $concat_group .= '@media' . $sheet['media'];
                    }

                    if (!isset($concat_groups[$concat_group])) {

                        // stylesheets in group
                        $concat_groups[$concat_group] = array();

                        // group settings
                        if (!isset($concat_group_settings[$concat_group])) {
                            $concat_group_settings[$concat_group] = array();
                        }

                        $concat_group_key = (isset($concat_group_settings[$concat_group]['group']) && isset($concat_group_settings[$concat_group]['group']['key'])) ? $concat_group_settings[$concat_group]['group']['key'] : 'global';

                        // load async by default
                        if (!isset($concat_group_settings[$concat_group]['async'])) {
                            $concat_group_settings[$concat_group]['async'] = true;
                        }

                        // apply async filter
                        if (!empty($this->async_filter)) {

                            // apply filter to key
                            $asyncConfig = $this->tools->filter_config_match($concat_group_key, $this->async_filter, $this->async_filterType);

                            // filter config object
                            if ($asyncConfig && is_array($asyncConfig)) {

                                // async enabled by filter
                                if (!isset($asyncConfig['async']) || $asyncConfig['async']) {
                                    $concat_group_settings[$concat_group]['async'] = true;

                                    // custom load position
                                    if (isset($asyncConfig['load_position']) && $asyncConfig['load_position'] !== $this->load_position) {
                                        $concat_group_settings[$concat_group]['load_position'] = $asyncConfig['load_position'];
                                    }

                                    // load timing
                                    if (isset($asyncConfig['load_position']) && $asyncConfig['load_position'] === 'timing' && isset($asyncConfig['load_timing'])) {
                                        $concat_group_settings[$concat_group]['load_timing'] = $asyncConfig['load_timing'];
                                    }

                                    // custom render position
                                    if (isset($asyncConfig['render_timing']) && $asyncConfig['render_timing'] !== $this->load_position) {
                                        $concat_group_settings[$concat_group]['render_timing'] = $asyncConfig['render_timing'];
                                    }

                                    // custom rel_preload
                                    if (isset($asyncConfig['rel_preload']) && $asyncConfig['rel_preload'] !== $this->rel_preload) {
                                        $concat_group_settings[$concat_group]['rel_preload'] = $asyncConfig['rel_preload'];
                                    }

                                    // custom <noscript>
                                    if (isset($asyncConfig['noscript']) && $asyncConfig['noscript'] !== $this->noscript) {
                                        $concat_group_settings[$concat_group]['noscript'] = $asyncConfig['noscript'];
                                    }

                                    // custom media query
                                    if (isset($asyncConfig['media'])) {
                                        $concat_group_settings[$concat_group]['media'] = $asyncConfig['media'];
                                    }

                                    // custom localStorage
                                    if (isset($asyncConfig['localStorage'])) {
                                        if ($asyncConfig['localStorage'] === false) {
                                            $concat_group_settings[$concat_group]['localStorage'] = false;
                                        } elseif ($asyncConfig['localStorage'] === true && $this->localStorage) {
                                            $concat_group_settings[$concat_group]['localStorage'] = $this->localStorage;
                                        } else {
                                            $concat_group_settings[$concat_group]['localStorage'] = $asyncConfig['localStorage'];
                                        }
                                    }
                                    
                                    // custom minifier
                                    if (isset($asyncConfig['minifier'])) {
                                        $concat_group_settings[$concat_group]['minifier'] = $asyncConfig['minifier'];
                                    }
                                } else {

                                    // do not load async
                                    $concat_group_settings[$concat_group]['async'] = false;
                                }
                            } elseif ($asyncConfig === true) {

                                // include by default
                                $concat_group_settings[$concat_group]['async'] = true;
                            }
                        }
                    }

                    // inline <style>
                    if (isset($sheet['inline'])) {
                        $concat_groups[$concat_group][] = array(
                            'inline' => true,
                            'hash' => md5($sheet['css']),
                            'media' => $sheet['media'],
                            'merge_media' => $merge_media,
                            'tag' => $sheet['tag'],
                            'css' => $sheet['css'],
                            'position' => count($async_sheets)
                        );
                    } else {
                        // minified stylesheet
                        $concat_groups[$concat_group][] = array(
                            'hash' => $sheet['minified'],
                            'media' => $sheet['media'],
                            'merge_media' => $merge_media,
                            'tag' => $sheet['tag'],
                            'href' => $sheet['href'],
                            'position' => count($async_sheets)
                        );
                    }

                    // remove sheet from HTML
                    $this->output->add_search_replace($sheet['tag'], '');

                    // maintain position index
                    $async_sheets[] = false;

                    // maintain position index
                    $sheet_urls[] = false;

                    continue 1; // next sheet
                }
            } // concat end

            // inline <style> without concatenation, ignore
            if (isset($sheet['inline'])) {
                continue 1; // next sheet
            }

            // load async
            if ($async && $sheet['async']) {

                // rel="preload" and <noscript> config
                $rel_preload = (isset($sheet['rel_preload'])) ? $sheet['rel_preload'] : $this->rel_preload;
                $noscript = (isset($sheet['noscript'])) ? $sheet['noscript'] : $this->noscript;
                $load_position = (isset($sheet['load_position'])) ? $sheet['load_position'] : $this->load_position;
                $load_timing = (isset($sheet['load_timing'])) ? $sheet['load_timing'] : $this->load_timing;
                $render_timing = (isset($sheet['render_timing'])) ? $sheet['render_timing'] : $this->render_timing;

                // minified sheet
                if (isset($sheet['minified']) && $sheet['minified']) {

                    // hash type
                    $sheet_type = 'src';

                    // stylesheet path
                    $sheet_hash = str_replace('/', '', $this->cache->hash_path($sheet['minified']) . substr($sheet['minified'], 6));

                    // stylesheet url
                    $sheet_url = $this->url_filter($this->cache->url('css', 'src', $sheet['minified']));
                } else {

                    // proxy hash
                    if (isset($sheet['proxy']) && $sheet['proxy']) {

                        // hash type
                        $sheet_type = 'proxy';

                        // stylesheet path
                        $sheet_hash = str_replace('/', '', $this->cache->hash_path($sheet['proxy']) . substr($sheet['proxy'], 6));

                        // stylesheet url
                        $sheet_url = $this->url_filter($sheet['href']);
                    } else {

                        // hash type
                        $sheet_type = 'url';

                        // stylesheet url
                        $sheet_hash = $sheet_url = $this->url_filter($sheet['href']);
                    }
                }

                // add sheet to async list
                $async_sheet = array(
                    'type' => $sheet_type,
                    'url' => $sheet_hash,
                    'original_url' => $sheet['href'],
                    'media' => $sheet['media'],
                    'load_position' => $load_position,
                    'load_timing' => $load_timing,
                    'render_timing' => $render_timing
                );
                if (isset($sheet['localStorage'])) {
                    $async_sheet['localStorage'] = $sheet['localStorage'];
                }
                $async_sheets[] = $async_sheet;

                // rel="preload" or <noscript>
                if ($rel_preload || $noscript) {

                    // add sheet to url list
                    $sheet_urls[] = array(
                        'url' => $sheet_url,
                        'media' => $sheet['media'],
                        'rel_preload' => $rel_preload,
                        'noscript' => $noscript,
                        'load_position' => $load_position,
                        'load_timing' => $load_timing,
                        'render_timing' => $render_timing
                    );
                } else {
                    $sheet_urls[] = false;
                }

                // remove sheet from HTML
                $this->output->add_search_replace($sheet['tag'], '');
            } else {
                if (isset($sheet['minified']) && $sheet['minified']) {

                    // minified URL
                    $sheet['href'] = $this->cache->url('css', 'src', $sheet['minified']);
                    $sheet['replaceHref'] = true;
                }

                // apply CDN
                $filteredHref = $this->url_filter($sheet['href']);
                if ($filteredHref !== $sheet['href']) {
                    $sheet['href'] = $filteredHref;
                    $sheet['replaceHref'] = true;
                }

                // replace href in HTML
                if (isset($sheet['replaceHref'])) {

                    // replace href in tag
                    $this->output->add_search_replace($sheet['tag'], $this->href_regex($sheet['tag'], $sheet['href']));
                }
            }
        }

        // process concatenated stylesheets
        if ($concat) {

            // use minify?
            $concat_minify = $this->options->bool('css.minify.concat.minify');

            foreach ($concat_groups as $concat_group => $stylesheets) {

                // media query
                if (isset($concat_group_settings[$concat_group]['media']) && $concat_group_settings[$concat_group]['media'] !== 'all') {
                    $media = $concat_group_settings[$concat_group]['media'];
                } else {
                    $media = false;
                }

                // position to load concatenated stylesheet
                $async_insert_position = 0;

                // stylesheet hashes
                $concat_hashes = array();

                // add group key to hash
                if ($concat_group_settings && isset($concat_group_settings[$concat_group]['group']) && isset($concat_group_settings[$concat_group]['group']['key'])) {
                    $concat_hashes[] = $concat_group_settings[$concat_group]['group']['key'];
                }

                // add stylesheet hashes
                foreach ($stylesheets as $sheet) {
                    $concat_hashes[] = $sheet['hash'];
                    if ($sheet['position'] > $async_insert_position) {
                        $async_insert_position = $sheet['position'];
                    }
                }

                // insert after last sheet in concatenated group
                $async_insert_position++;

                // calcualte hash from source files
                $urlhash = md5(implode('|', $concat_hashes));

                // load from cache
                if ($this->cache->exists('css', 'concat', $urlhash)) {

                    // preserve cache file based on access
                    $this->cache->preserve('css', 'concat', $urlhash, (time() - 3600));

                    $concat_original_urls = array();
                    foreach ($stylesheets as $sheet) {
                        if (isset($sheet['inline'])) {
                            $sheet_filename = 'inline-' . $sheet['hash'];
                            $concat_original_urls[] = $sheet_filename;
                        } else {
                            $concat_original_urls[] = $sheet['href'];
                        }
                    }
                } else {

                    // concatenate stylesheets
                    $concat_sources = array();
                    $concat_original_urls = array();
                    foreach ($stylesheets as $sheet) {
                        if (isset($sheet['inline'])) {
                            // get source
                            $source = $sheet['css'];
                            $sheet_filename = 'inline-' . $sheet['hash'];
                            $concat_original_urls[] = $sheet_filename;
                        } else {
                            // get source from cache
                            $source = $this->cache->get('css', 'src', $sheet['hash']);
                            $sheet_filename = $this->extract_filename($sheet['href']);
                            $concat_original_urls[] = $sheet['href'];
                        }

                        // empty, ignore
                        if (!$source) {
                            continue 1;
                        }

                        // concat source config
                        $concat_sources[$sheet_filename] = array();

                        // remove sourceMap references
                        $sourcemapIndex = strpos($source, '/*# sourceMappingURL');
                        while ($sourcemapIndex !== false) {
                            $sourcemapEndIndex = strpos($source, '*/', $sourcemapIndex);
                            $source = substr_replace($source, '', $sourcemapIndex, (($sourcemapEndIndex - $sourcemapIndex) + 2));
                            $sourcemapIndex = strpos($source, '/*# sourceMappingURL');
                        }

                        // CSS source
                        $concat_sources[$sheet_filename]['css'] = $source;

                        // merge media query
                        if ($sheet['merge_media']) {
                            $concat_sources[$sheet_filename]['media'] = $sheet['media'];
                        }

                        // create source map
                        if ($this->options->bool('css.clean-css.sourceMap')) {
                            $map = $this->cache->get('css', 'src', $sheet['hash'], false, false, '.css.map');
                            $concat_sources[$sheet_filename]['map'] = $map;
                        }
                    }

                    // use minify?
                    $concat_group_minify = (isset($concat_group_settings[$concat_group]['minify'])) ? $concat_group_settings[$concat_group]['minify'] : $concat_minify;
                    $concat_group_minifier = (isset($concat_group_settings[$concat_group]['minifier'])) ? $concat_group_settings[$concat_group]['minifier'] : $this->minifier;
                    $concat_group_key = (isset($concat_group_settings[$concat_group]['group']) && isset($concat_group_settings[$concat_group]['group']['key'])) ? $concat_group_settings[$concat_group]['group']['key'] : false;

                    // concatenate using minify
                    if ($concat_group_minify) {

                        // target src cache dir of concatenated stylesheets for URL rebasing
                        $base_href = $this->file->directory_url('css/0/1/', 'cache', false);

                        // create concatenated file using minifier
                        try {
                            $minified = $this->minify($concat_sources, $base_href, $concat_group_minifier);
                        } catch (Exception $err) {
                            $minified = false;
                        }
                    } else {
                        $minified = false;
                    }

                    if ($minified) {

                        // store cache file
                        $cache_file_path = $this->cache->put('css', 'concat', $urlhash, $minified['css'], $concat_group_key);

                        // add link to source map
                        if (isset($minified['sourcemap'])) {

                            // add link to CSS
                            $minified['css'] .= "\n/*# sourceMappingURL=".basename($cache_file_path).".map */";

                            // update stylesheet cache
                            try {
                                $this->file->put_contents($cache_file_path, $minified['css']);
                            } catch (\Exception $e) {
                                throw new Exception('Failed to store stylesheet ' . $this->file->safe_path($cache_file_path) . ' <pre>'.$e->getMessage().'</pre>', 'config');
                            }

                            // apply filters
                            $minified['sourcemap'] = $this->minified_sourcemap_filter($minified['sourcemap']);

                            // store source map
                            try {
                                $this->file->put_contents($cache_file_path . '.map', $minified['sourcemap']);
                            } catch (\Exception $e) {
                                throw new Exception('Failed to store stylesheet source map ' . $this->file->safe_path($cache_file_path . '.map') . ' <pre>'.$e->getMessage().'</pre>', 'config');
                            }
                        }
                    } else {

                        // minification failed, simply join files

                        $CSS = array();
                        foreach ($concat_sources as $source) {
                            $CSS[] = $source['css'];
                        }

                        // store cache file
                        $this->cache->put('css', 'concat', $urlhash, implode(' ', $CSS), $concat_group_key);
                    }
                }

                // load async?
                $concat_group_async = (isset($concat_group_settings[$concat_group]['async'])) ? $concat_group_settings[$concat_group]['async'] : true;

                // rel="preload" and <noscript> config
                $rel_preload = (isset($concat_group_settings[$concat_group]['rel_preload'])) ? $concat_group_settings[$concat_group]['rel_preload'] : $this->rel_preload;
                $noscript = (isset($concat_group_settings[$concat_group]['noscript'])) ? $concat_group_settings[$concat_group]['noscript'] : $this->noscript;

                // load / render position
                $load_position = (isset($concat_group_settings[$concat_group]['load_position'])) ? $concat_group_settings[$concat_group]['load_position'] : $this->load_position;
                if ($load_position === 'timing') {
                    $load_timing = (isset($concat_group_settings[$concat_group]['load_timing'])) ? $concat_group_settings[$concat_group]['load_timing'] : $this->load_timing;
                } else {
                    $load_timing = false;
                }
                $render_timing = (isset($concat_group_settings[$concat_group]['render_timing'])) ? $concat_group_settings[$concat_group]['render_timing'] : $this->render_timing;

                // custom media
                $media = (isset($concat_group_settings[$concat_group]['media'])) ? $concat_group_settings[$concat_group]['media'] : $media;
                
                // concat URL
                $sheet_url = $this->url_filter($this->cache->url('css', 'concat', $urlhash));

                // load async (concatenated stylesheet)
                if ($async && $concat_group_async) {

                    // add sheet to async list
                    $async_sheet = array(
                        'type' => 'concat',
                        'url' => $this->async_hash_path($urlhash),
                        'original_url' => $concat_original_urls,
                        'media' => $media,
                        'load_position' => $load_position,
                        'load_timing' => $load_timing,
                        'render_timing' => $render_timing
                    );
                    if (isset($concat_group_settings[$concat_group]['localStorage'])) {
                        $async_sheet['localStorage'] = $concat_group_settings[$concat_group]['localStorage'];
                    }

                    // add to position of last stylesheet in concatenated stylesheet
                    array_splice($async_sheets, $async_insert_position, 0, array($async_sheet));

                    // rel="preload" or <noscript>
                    if ($rel_preload || $noscript) {

                        // add to position of last stylesheet in concatenated stylesheet
                        array_splice($sheet_urls, $async_insert_position, 0, array(array(
                            'url' => $sheet_url,
                            'media' => $media,
                            'rel_preload' => $rel_preload,
                            'noscript' => $noscript,
                            'load_position' => $load_position,
                            'load_timing' => $load_timing,
                            'render_timing' => $render_timing
                        )));
                    }
                } else {
                    // position in document
                    $position = ($load_position === 'footer') ? 'footer' : 'critical-css';

                    // include stylesheet link in HTML
                    $this->client->after($position, '<link rel="stylesheet" href="'.esc_url($sheet_url).'"'.(($media && $media !== 'all') ? ' media="'.esc_attr($media).'"' : '').'>');

                    // add <noscript>
                    if ($noscript) {

                        // add to position of last stylesheet in concatenated stylesheet
                        array_splice($sheet_urls, $async_insert_position, 0, array(array(
                            'url' => $sheet_url,
                            'media' => $media,
                            'rel_preload' => false,
                            'noscript' => $noscript,
                            'load_position' => $load_position,
                            'load_timing' => $load_timing,
                            'render_timing' => $render_timing
                        )));
                    }
                }
            }
        }

        // load async
        if ($async) {
            if (!empty($async_sheets)) {

                // async list
                $async_list = array();
                $async_ref_list = array(); // debug ref list

                // concat index list
                $concat_index = array();

                // type prefixes
                $hash_type_prefixes = array(
                    'url' => 1,
                    'proxy' => 2
                );
                
                foreach ($async_sheets as $sheet) {
                    if (!$sheet) {
                        continue;
                    }

                    // load position
                    $load_position = ($sheet['load_position'] && $sheet['load_position'] !== $this->load_position) ? $sheet['load_position'] : false;
                    if ($load_position) {
                        $load_position = ($load_position === 'timing') ? $this->client->config_index('key', 'timing') : 0;
                    }
                    if ($sheet['load_position'] && $sheet['load_position'] === 'timing') {
                        $load_timing = ($sheet['load_timing'] && $sheet['load_timing'] !== $this->load_timing) ? $sheet['load_timing'] : false;
                    } else {
                        $load_timing = false;
                    }

                    // render timing
                    $render_timing = ($sheet['render_timing'] && $sheet['render_timing'] !== $this->render_timing) ? $sheet['render_timing'] : false;

                    // hash type prefix
                    $hash_type_prefix = (isset($hash_type_prefixes[$sheet['type']])) ? $hash_type_prefixes[$sheet['type']] : false;

                    // add concat index position
                    if ($sheet['type'] === 'concat') {
                        $concat_index[] = count($async_list);
                    }

                    // async sheet object
                    $async_sheet = array();

                    // add hash prefix
                    if ($hash_type_prefix) {
                        $async_sheet[] = $hash_type_prefix;
                    }

                    // sheet URL or hash
                    $async_sheet[] = $sheet['url'];

                    $index = count($async_sheet);
                    $async_sheet[] = null; // media
                    $async_sheet[] = null; // load position
                    $async_sheet[] = null; // localStorage
                    $async_sheet[] = null; // custom CDN

                    // sheet media
                    $media_set = $load_set = $render_set = false;
                    if (isset($sheet['media']) && $sheet['media'] !== 'all') {
                        $async_sheet[$index] = $sheet['media'];
                        $media_set = true;
                    }

                    // load config
                    if ($load_position !== false || $render_timing !== false) {
                        if ($render_timing !== false) {
                            $async_sheet[($index + 1)] = array($load_position, $this->timing_config($load_timing), $this->timing_config($render_timing));
                        } elseif ($load_timing !== false) {
                            $async_sheet[($index + 1)] = array($load_position, $this->timing_config($load_timing));
                        } else {
                            $async_sheet[($index + 1)] = $load_position;
                        }
                    }

                    // custom localStorage config
                    if (isset($sheet['localStorage'])) {
                        if (is_array($sheet['localStorage'])) {
                            $localStorage = array();
                            $config_keys = array('max_size','expire','update_interval');
                            foreach ($config_keys as $key) {
                                $localStorage[$this->client->config_index('css', 'localStorage_'.$key)] = $sheet['localStorage'][$key];
                            }

                            if ($sheet['localStorage']['head_update']) {
                                $localStorage[$this->client->config_index('css', 'localStorage_head_update')] = $sheet['localStorage']['head_update'];
                            }
 
                            $async_sheet[($index + 2)] = $localStorage;
                        } else {
                            $async_sheet[($index + 2)] = ($sheet['localStorage']) ? 1 : 0;
                        }
                        
                        // load client module
                        $this->client->load_module('localstorage');
                    }

                    // custom CDN
                    if (isset($sheet['cdn']) && is_array($sheet['cdn']) && isset($sheet['cdn']['url'])) {
                        $async_sheet[($index + 3)] = array($sheet['cdn']['url']);
                        if (isset($sheet['cdn']['mask']) && $sheet['cdn']['mask']) {
                            $async_sheet[($index + 3)][] = $sheet['cdn']['mask'];
                        }
                    }

                    $value_set = false;
                    for ($i = count($async_sheet); $i >= $index; $i--) {
                        if ($async_sheet[$i] !== null) {
                            $value_set = true;
                        } else {
                            if (!$value_set) {
                                unset($async_sheet[$i]);
                            } else {
                                $async_sheet[$i] = '__O10N_NULL__';
                            }
                        }
                    }

                    // add to async list
                    $async_list[] = $async_sheet;

                    if ($this->debug_mode) {
                        $async_ref_list[$sheet['url']] = $sheet['original_url'];
                    }
                }

                // add async list to client
                $this->client->set_config('css', 'async', $async_list);

                // add CDN config to client
                if ($this->stylesheet_cdn) {
                    $cdn_config = array();
                    $cdn_config[$this->client->config_index('key', 'url')] = rtrim($this->stylesheet_cdn[0], '/ ');
                    if (isset($this->stylesheet_cdn[1]) && $this->stylesheet_cdn[1]) {
                        $cdn_config[$this->client->config_index('key', 'mask')] = $this->stylesheet_cdn[1];
                    }
                    $this->client->set_config('css', 'cdn', $cdn_config);
                }

                // add references
                if ($this->debug_mode) {
                    $this->client->set_config('css', 'debug_ref', $async_ref_list);
                }

                // add concat index to client
                if (count($async_list) === count($concat_index)) {
                    $this->client->set_config('css', 'concat', 1); // concat only
                } elseif (!empty($concat_index)) {
                    $this->client->set_config('css', 'concat', $concat_index); // concat indexes
                }
            }
        }

        // add rel="preload" and <noscript>

        // <noscript> sheets
        $noscript_sheets = array(
            'header' => '',
            'footer' => ''
        );

        foreach ($sheet_urls as $sheet) {
            if (!$sheet) {
                continue;
            }

            // rel="preload" as="css"
            if (isset($sheet['rel_preload']) && $sheet['rel_preload']) {

                // position in document
                $position = ($sheet['load_position'] === 'footer') ? 'footer' : 'critical-css';

                $this->client->after($position, '<link rel="preload" as="style" href="'.esc_url($sheet['url']).'"'.((isset($sheet['media']) && $sheet['media'] && $sheet['media'] !== 'all') ? ' media="'.esc_attr($sheet['media']).'"' : '').' onload="this.rel=\'stylesheet\';">');
            }

            // add sheet to <noscript>
            if (isset($sheet['noscript']) && $sheet['noscript']) {
                $noscript_sheets[$sheet['load_position']] .= '<link rel="stylesheet" href="'.esc_url($sheet['url']).'"'.((isset($sheet['media']) && $sheet['media'] && $sheet['media'] !== 'all') ? ' media="'.esc_attr($sheet['media']).'"' : '').'>';
            }
        }

        // add <noscript> to HTML
        foreach ($noscript_sheets as $noscript_position => $noscript) {
            if ($noscript) {

                // position in document
                $position = ($noscript_position === 'footer') ? 'footer' : 'header';

                $this->client->after($position, '<noscript>' . $noscript . '</noscript>');
            }
        }

        return $HTML;
    }

    /**
     * Search and replace strings in CSS
     *
     * To enable different minification settings per page, any settings that modify the CSS before minification should be used in the hash.
     *
     * @param  string $CSS CSS to filter
     * @return string Modified CSS
     */
    final public function css_filters($CSS, $base_href)
    {

        // apply search & replace filter
        if ($this->replace) {

            // apply string search & replace
            if (!empty($this->replace['search'])) {
                $CSS = str_replace($this->replace['search'], $this->replace['replace'], $CSS);
            }

            // apply regular expression search & replace
            if (!empty($this->replace['search_regex'])) {
                try {
                    $CSS = @preg_replace($this->replace['search_regex'], $this->replace['replace_regex'], $CSS);
                } catch (\Exception $err) {
                    // @todo log error
                }
            }
        }

        // rebase relative links
        if ($this->rebase_uris) {

            // rebase relative links in CSS
            if (strpos($CSS, 'url') !== false) {
                if (preg_match_all('/url\s*\(\s*("|\')?\s*(?!data:)([^\)\s\'"]+)\s*("|\')?\s*\)/i', $CSS, $out)) {
                    $s = $r = array();
                    foreach ($out[2] as $n => $url) {
                        $translated_url = $this->url->rebase($url, $base_href);
                        if ($translated_url !== $url) {
                            $s[] = $out[0][$n];
                            $r[] = str_replace($url, $translated_url, $out[0][$n]);
                            //$CSS = str_replace($out[0][$n], str_replace($url, $translated_url, $out[0][$n]), $CSS);
                        }
                    }
                    $CSS = str_replace($s, $r, $CSS);
                }
            }
        }

        // process @import links
        if ($this->process_import) {
            $CSS = $this->process_import_links($CSS, $base_href);
        }

        return $CSS;
    }

    /**
     * Process @import links
     */
    final private function process_import_links($CSS, $base_href)
    {
        // remove comments to prevent importing commented our CSS
        $nocomment_css = preg_replace('#/\*.*\*/#Us', '', $CSS);
        
        // check if CSS contains imports
        if (stripos($nocomment_css, '@import') !== false) {
            if (preg_match_all('/(?:@import)\s(?:url\()?\s?["\'](.*?)["\']\s?\)?(?:[^;]*);?/mi', $nocomment_css, $matches)) {

                // process import links
                foreach ($matches[1] as $n => $import) {
                    // sanitize url
                    $url = trim(preg_replace('#^.*((?:https?:|ftp:)?//.*\.css).*$#', '$1', trim($import)), " \t\n\r\0\x0B\"'");
                    // apply filter
                    if ($this->process_import_filter !== false) {
                        if (!$this->tools->filter_list_match($url, $this->process_import_filterType, $this->process_import_filter)) {
                            continue 1;
                        }
                    }

                    // translate relative url
                    $url = $this->url->rebase($url, $base_href);


                    // detect local URL
                    $local = $this->url->is_local($url);
                    if ($local) {
                        $cssText = file_get_contents($local);
                    } else {

                        // import external stylesheet
                        if (!$this->url->valid_protocol($url)) {
                            continue 1;
                        }

                        // download stylesheet
                        try {
                            $sheetData = $this->proxy->proxify('css', $url, 'filedata');
                        } catch (HTTPException $err) {
                            $sheetData = false;
                        } catch (Exception $err) {
                            $sheetData = false;
                        }

                        // failed to download file or file is empty
                        if (!$sheetData) {
                            continue 1;
                        }

                        // css text
                        $cssText = $sheetData[0];
                    }

                    // apply CSS filters before processing
                    $cssText = $this->css_filters($cssText, $url);

                    // remove import rule from CSS
                    $CSS = str_replace($matches[0][$n], $cssText, $CSS);
                }
            }
        }

        return $CSS;
    }

    /**
     * Extract stylesheets from HTML
     *
     * @param  string $HTML HTML source
     * @return array  Extracted stylesheets
     */
    final private function extract($HTML)
    {

        // extracted CSS elements
        $this->css_elements = array();

        // minify
        $minify = $this->options->bool('css.minify');

        // async
        $async = $this->options->bool('css.async');

        // proxy
        $proxy = $this->options->bool('css.proxy');

        // concat
        $concat = $minify && $this->options->bool('css.minify.concat');

        // replace href
        $replaceHref = false;

        // pre url filter
        if ($this->options->bool('css.url_filter')) {
            $url_filter = $this->options->get('css.url_filter.config');
            if (empty($url_filter)) {
                $url_filter = false;
            }
        } else {
            $url_filter = false;
        }

        // minify filter
        if ($minify && $this->options->bool('css.minify.filter')) {
            $minify_filterType = $this->options->get('css.minify.filter.type');
            $minify_filter = $this->options->get('css.minify.filter.' . $minify_filterType, array());
        } else {
            $minify_filter = false;
        }

        // async filter
        if ($async && $this->options->bool('css.async.filter')) {
            $this->async_filterType = $this->options->get('css.async.filter.type');
            $this->async_filter = $this->options->get('css.async.filter.config', array());
        } else {
            $this->async_filter = false;
        }

        // proxy filter
        if ($proxy) {
            $proxy_filter = $this->options->get('css.proxy.include', array());
        } else {
            $proxy_filter = false;
        }

        // stylesheet regex
        $stylesheet_regex = '#(<\!--\[if[^>]+>\s*)?<link[^>]+>#Usmi';

        if (preg_match_all($stylesheet_regex, $HTML, $out)) {
            foreach ($out[0] as $n => $stylesheet) {

                // conditional, skip
                if (trim($out[1][$n]) !== '') {
                    continue 1;
                }

                // no rel="stylesheet", skip
                if (strpos($stylesheet, 'stylesheet') === false) {
                    continue 1;
                }

                // verify if tag contains href
                $href = strpos($stylesheet, 'href');
                if ($href === false) {
                    continue 1;
                }

                // extract href using regular expression
                $href = $this->href_regex($stylesheet);
                if (!$href) {
                    continue 1;
                }

                // stylesheet
                $sheet = array(
                    'href' => $href,
                    'tag' => $stylesheet,
                    'minify' => $minify,
                    'async' => $async
                );

                // extract media query
                $media = $this->parse_media_attr($stylesheet);
                $sheet['media'] = $media;

                // apply pre url filter
                if ($url_filter) {
                    foreach ($url_filter as $rule) {
                        if (!is_array($rule)) {
                            continue 1;
                        }

                        // match
                        $match = true;
                        if (isset($rule['regex']) && $rule['regex']) {
                            try {
                                if (!preg_match($rule['url'], $href)) {
                                    $match = false;
                                }
                            } catch (\Exception $err) {
                                $match = false;
                            }
                        } else {
                            if (strpos($href, $rule['url']) === false) {
                                $match = false;
                            }
                        }
                        if (!$match) {
                            continue 1;
                        }

                        // ignore stylesheet
                        if (isset($rule['ignore'])) {
                            continue 2; // next stylesheet
                        }

                        // delete stylesheet
                        if (isset($rule['delete'])) {
                            
                            // delete from HTML
                            $this->output->add_search_replace($stylesheet, '');
                            continue 2; // next stylesheet
                        }

                        // replace stylesheet
                        if (isset($rule['replace'])) {
                            $sheet['href'] = $rule['replace'];
                            $sheet['replaceHref'] = true;
                        }
                    }
                }

                // apply custom stylesheet filter pre processing
                $filteredHref = apply_filters('o10n_stylesheet_pre', $href, $stylesheet);

                // ignore stylesheet
                if ($filteredHref === 'ignore') {
                    continue 1;
                }

                // delete stylesheet
                if ($filteredHref === 'delete') {

                    // delete from HTML
                    $this->output->add_search_replace($stylesheet, '');
                    continue 1;
                }

                // replace href
                if ($filteredHref !== $sheet['href']) {
                    $sheet['href'] = $filteredHref;
                    $sheet['replaceHref'] = true;
                }

                // apply stylesheet minify filter
                if ($minify && $minify_filter) {
                    $sheet['minify'] = $this->tools->filter_list_match($stylesheet, $minify_filterType, $minify_filter);
                }

                // apply stylesheet async filter
                if ($async && $this->async_filter !== false) {

                    // apply filter
                    $asyncConfig = $this->tools->filter_config_match($stylesheet, $this->async_filter, $this->async_filterType);

                    // filter config object
                    if ($asyncConfig && is_array($asyncConfig)) {

                        // async enabled by filter
                        if (!isset($asyncConfig['async']) || $asyncConfig['async']) {
                            $sheet['async'] = true;

                            // custom load position
                            if (isset($asyncConfig['load_position']) && $asyncConfig['load_position'] !== $this->load_position) {
                                $sheet['load_position'] = $asyncConfig['load_position'];
                            }

                            if (isset($asyncConfig['load_position']) && $asyncConfig['load_position'] === 'timing' && isset($asyncConfig['load_timing'])) {
                                $sheet['load_timing'] = $asyncConfig['load_timing'];
                            }

                            // custom render position
                            if (isset($asyncConfig['render_timing']) && $asyncConfig['render_timing'] !== $this->load_position) {
                                $sheet['render_timing'] = $asyncConfig['render_timing'];
                            }

                            // custom rel_preload
                            if (isset($asyncConfig['rel_preload']) && $asyncConfig['rel_preload'] !== $this->rel_preload) {
                                $sheet['rel_preload'] = $asyncConfig['rel_preload'];
                            }

                            // custom <noscript>
                            if (isset($asyncConfig['noscript']) && $asyncConfig['noscript'] !== $this->noscript) {
                                $sheet['noscript'] = $asyncConfig['noscript'];
                            }

                            // custom media query
                            if (isset($asyncConfig['media'])) {
                                $sheet['media'] = $asyncConfig['media'];
                            }

                            // custom localStorage
                            if (isset($asyncConfig['localStorage'])) {
                                $sheet['localStorage'] = $asyncConfig['localStorage'];
                            }

                            // custom minify
                            if (isset($asyncConfig['minify'])) {
                                $sheet['minify'] = $asyncConfig['minify'];
                            }
                            
                            // custom minifier
                            if (isset($asyncConfig['minifier'])) {
                                $sheet['minifier'] = $asyncConfig['minifier'];
                            }
                        } elseif (!$asyncConfig['async']) {
                            // include by default
                            $sheet['async'] = false;
                        }
                    } elseif ($asyncConfig === true) {

                        // include by default
                        $sheet['async'] = true;
                    } else {
                        // include by default
                        $sheet['async'] = false;
                    }
                }

                // apply stylesheet proxy filter
                if (!$sheet['minify'] && $proxy && !$this->url->is_local($sheet['href'], false, false)) {

                    // apply filter
                    $sheet_proxy = ($proxy_filter) ? $this->tools->filter_list_match($stylesheet, 'include', $proxy_filter) : $proxy;

                    // proxy stylesheet
                    if ($sheet_proxy) {

                        // proxify URL
                        $proxyResult = $this->proxy->proxify('css', $sheet['href']);

                        // proxy href
                        if ($proxyResult[0] && $proxyResult[1] !== $sheet['href']) {
                            $sheet['href'] = $proxyResult[1];
                            $sheet['proxy'] = $proxyResult[0];
                            $sheet['replaceHref'] = true;
                        }
                    }
                }

                $this->css_elements[] = $sheet;
            }
        }

        // extract inline styles for concatenation
        if ($concat && $this->options->bool('css.minify.concat.inline')) {

            // filter
            if ($this->options->bool('css.minify.concat.inline.filter')) {
                $inlineConcat_filterType = $this->options->get('css.minify.concat.inline.filter.type');
                $inlineConcat_filter = $this->options->get('css.minify.concat.inline.filter.' . $inlineConcat_filterType);
                if (empty($inlineConcat_filter)) {
                    $inlineConcat_filter = false;
                }
            } else {
                $inlineConcat_filter = false;
            }
            
            // <style> regex
            $style_regex = '#<style(.*)>(.*)</style>#Usmi';

            if (preg_match_all($style_regex, $HTML, $out)) {
                foreach ($out[0] as $n => $style) {
                    $css = trim($out[2][$n]);

                    // strip CDATA
                    if (stripos($css, 'cdata') !== false) {
                        $css = preg_replace('#^.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*$#smi', '$1', $css);
                    }

                    // ignore empty styles
                    if ($css === '') {

                        // delete from HTML
                        $this->output->add_search_replace($style, '');
                        continue 1;
                    }

                    // apply css file filter pre processing
                    $filteredCSS = apply_filters('o10n_style_pre', $css, $style);

                    // ignore style
                    if ($filteredCSS === 'ignore') {
                        continue 1;
                    }

                    // delete style
                    if ($filteredCSS === 'delete') {
                        
                        // delete from HTML
                        $this->output->add_search_replace($style, '');
                        continue 1;
                    }

                    // replace CSS
                    if ($filteredCSS !== $css) {
                        $css = $filteredCSS;
                    }

                    // apply inline filter
                    if ($inlineConcat_filter) {
                        $concat = $this->tools->filter_list_match($style, $inlineConcat_filterType, $inlineConcat_filter);
                        if (!$concat) {
                            continue 1;
                        }
                    }

                    // extract media query
                    $media = $this->parse_media_attr($style);

                    $sheet = array(
                        'inline' => true,
                        'css' => $css,
                        'tag' => $style,
                        'media' => $media,
                        'minify' => $minify
                    );

                    // apply stylesheet minify filter
                    if ($minify && $minify_filter) {
                        $sheet['minify'] = $this->tools->filter_list_match($style, $minify_filterType, $minify_filter);
                    }

                    // result
                    $this->css_elements[] = $sheet;
                }
            }
        }

        // minify stylesheets
        if (!empty($this->css_elements) && $minify) {
            $this->minify_stylesheets();
        }
    }

    /**
     * Minify extracted stylesheets
     */
    final private function minify_stylesheets()
    {

        // walk extracted CSS elements
        foreach ($this->css_elements as $n => $sheet) {

            // skip inline <style>
            if (isset($sheet['inline']) && $sheet['inline']) {
                $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $base_href = $url;
                if (substr($base_href, -1) === '/') {
                    $base_href .= 'page.html';
                }
                $urlhash = md5($url . $sheet['css']);
                $file_hash = md5($sheet['css']);
                $cssText = $sheet['css'];
                $local = false;
            } else {

                // minify hash
                $urlhash = $this->minify_hash($sheet['href']);
                $base_href = $sheet['href'];

                // detect local URL
                $local = $this->url->is_local($sheet['href']);

                $cache_file_hash = $proxy_file_meta = false;

                // local URL, verify change based on content hash
                if ($local) {

                    // get local file hash
                    $file_hash = md5_file($local);
                } else {
                    // remote URL

                    // invalid prefix
                    if (!$this->url->valid_protocol($sheet['href'])) {
                        continue 1;
                    }

                    // try cache
                    if ($this->cache->exists('css', 'src', $urlhash) && (!$this->options->bool('css.clean-css.sourceMap') || $this->cache->exists('css', 'src', $urlhash, false, '.css.map'))) {

                        // verify content
                        $proxy_file_meta = $this->proxy->meta('css', $sheet['href']);
                        $cache_file_hash = $this->cache->meta('css', 'src', $urlhash, true);

                        if ($proxy_file_meta && $cache_file_hash && $proxy_file_meta[2] === $cache_file_hash) {

                            // preserve cache file based on access
                            $this->cache->preserve('css', 'src', $urlhash, (time() - 3600));
                           
                            // add minified path
                            $this->css_elements[$n]['minified'] = $urlhash;

                            // update content in background using proxy (conditionl HEAD request)
                            $this->proxy->proxify('css', $sheet['href']);
                            continue 1;
                        }
                    }

                    // download stylesheet
                    try {
                        $sheetData = $this->proxy->proxify('css', $sheet['href'], 'filedata');
                    } catch (HTTPException $err) {
                        $sheetData = false;
                    } catch (Exception $err) {
                        $sheetData = false;
                    }

                    // failed to download file or file is empty
                    if (!$sheetData) {
                        continue 1;
                    }

                    // file hash
                    $file_hash = $sheetData[1][2];
                    $cssText = $sheetData[0];
                }
            }

            // get content hash
            $cache_hash = $this->cache->meta('css', 'src', $urlhash, true);

            if ($cache_hash === $file_hash) {

                // preserve cache file based on access
                $this->cache->preserve('css', 'src', $urlhash, (time() - 3600));

                // add minified path
                $this->css_elements[$n]['minified'] = $urlhash;

                continue 1;
            }
            
            // load CSS source from local file
            if ($local) {
                $cssText = trim(file_get_contents($local));
                if ($cssText === '') {

                    // file is empty, remove
                    $this->output->add_search_replace($sheet['tag'], '');

                    // store stylesheet
                    $this->cache->put(
                        'css',
                        'src',
                        $urlhash,
                        '',
                        false, // suffix
                        false, // gzip
                        false, // opcache
                        $file_hash, // meta
                        true // meta opcache
                    );

                    continue 1;
                }
            }

            // minify disabled
            if (!isset($sheet['minify']) || !$sheet['minify']) {

                // entry
                $this->css_elements[$n]['minified'] = $urlhash;

                // store stylesheet
                $cache_file_path = $this->cache->put(
                    'css',
                    'src',
                    $urlhash,
                    $cssText,
                    false, // suffix
                    false, // gzip
                    false, // opcache
                    $file_hash, // meta
                    true // meta opcache
                );
                continue 1;
            }

            // target src cache dir
            //$target_src_dir = $this->file->directory_url('css/src/' . $this->cache->hash_path($urlhash), 'cache', true);

            //$sheet['href'] = $this->extract_filename($sheet['href']);

            try {
                $href = (isset($sheet['href'])) ? $sheet['href'] : (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $minified = $this->minify(array(array('css' => $cssText)), $href, ((isset($sheet['minifier'])) ? $sheet['minifier'] : $this->minifier));
            } catch (Exception $err) {
                // @todo
                // handle minify failure, prevent overload
                $minified = false;
            } catch (\Exception $err) {
                // @todo
                // handle minify failure, prevent overload
                $minified = false;
            }

            // minified CSS
            if ($minified) {

                // footer
                //$minified['css'] .= "\n/* @src ".$sheet['href']." */";

                if (isset($sheet['inline']) && $sheet['inline']) {
                    $this->css_elements[$n]['css'] = $minified['css'];
                }

                // store stylesheet
                $cache_file_path = $this->cache->put(
                    'css',
                    'src',
                    $urlhash,
                    $minified['css'],
                    false, // suffix
                    false, // gzip
                    false, // opcache
                    $file_hash, // meta
                    true // meta opcache
                );

                // add link to source map
                if (isset($minified['sourcemap'])) {
                    
                    // add link to CSS
                    $minified['css'] .= "\n/*# sourceMappingURL=".basename($cache_file_path).".map */";

                    // update stylesheet cache
                    try {
                        $this->file->put_contents($cache_file_path, $minified['css']);
                    } catch (\Exception $e) {
                        throw new Exception('Failed to store stylesheet ' . $this->file->safe_path($cache_file_path) . ' <pre>'.$e->getMessage().'</pre>', 'config');
                    }

                    // apply filters
                    $minified['sourcemap'] = $this->minified_sourcemap_filter($minified['sourcemap']);

                    // store source map
                    try {
                        $this->file->put_contents($cache_file_path . '.map', $minified['sourcemap']);
                    } catch (\Exception $e) {
                        throw new Exception('Failed to store stylesheet source map ' . $this->file->safe_path($cache_file_path . '.map') . ' <pre>'.$e->getMessage().'</pre>', 'config');
                    }
                }

                // entry
                $this->css_elements[$n]['minified'] = $urlhash;
            } else {

                // minification failed
                $this->css_elements[$n]['minified'] = false;
            }
        }
    }

    /**
     * Minify stylesheets
     */
    final private function minify($sources, $base_href, $minifier)
    {
        $this->last_used_minifier = $minifier;

        // concat sources
        $CSS = '';
        foreach ($sources as $source) {
            $CSS .= ' ' . $source['css'];
        }

        // apply CSS filters, search/replace, rebase relative links
        $CSS = $this->css_filters($CSS, $base_href);

        // minified CSS
        $minified = '';

        // select minifier
        switch ($minifier) {
            case "cssmin":

                // setup css minifier settings
                if (is_null($this->cssmin_minify_filters)) {
                    $this->cssmin_minify_filters = array(
                        "ImportImports" => (($this->options->bool('css.minify.cssmin.filters.ImportImports.enabled')) ? array('base_href' => '') : false),
                        "RemoveComments" => (($this->options->bool('css.minify.cssmin.filters.RemoveComments.enabled')) ? array('whitelist' => $this->options->get('css.minify.cssmin.filters.RemoveComments.whitelist')) : false),

                        // @todo create rebase filter for CssMin.php
                        //"RebaseURLs" => $this->options->bool('css.minify.cssmin.filters.RebaseURLs'),

                        "RemoveEmptyRulesets" => $this->options->bool('css.minify.cssmin.filters.RemoveEmptyRulesets'),
                        "RemoveEmptyAtBlocks" => $this->options->bool('css.minify.cssmin.filters.RemoveEmptyAtBlocks'),
                        "ConvertLevel3Properties" => $this->options->bool('css.minify.cssmin.filters.ConvertLevel3Properties'),
                        "ConvertLevel3AtKeyframes" => $this->options->bool('css.minify.cssmin.filters.ConvertLevel3AtKeyframes'),
                        "Variables" => $this->options->bool('css.minify.cssmin.filters.Variables'),
                        "RemoveLastDelarationSemiColon" => $this->options->bool('css.minify.cssmin.filters.RemoveLastDelarationSemiColon')
                    );
                    if ($this->cssmin_minify_filters['ImportImports']) {
                        if ($this->options->bool('css.minify.cssmin.filters.ImportImports.filter.enabled')) {
                            $type = $this->options->get('css.minify.cssmin.filters.ImportImports.filter.type');
                            $this->cssmin_minify_filters['ImportImports']['filter'] = array(
                                'type' => $type,
                                'list' => $this->options->get('css.minify.cssmin.filters.ImportImports.filter.' . $type)
                            );
                        }
                    }

                    $this->cssmin_minify_plugins = array(
                        "Variables" => $this->options->bool('css.minify.cssmin.plugins.Variables'),
                        "ConvertFontWeight" => $this->options->bool('css.minify.cssmin.plugins.ConvertFontWeight'),
                        "ConvertHslColors" => $this->options->bool('css.minify.cssmin.plugins.ConvertHslColors'),
                        "ConvertRgbColors" => $this->options->bool('css.minify.cssmin.plugins.ConvertRgbColors'),
                        "ConvertNamedColors" => $this->options->bool('css.minify.cssmin.plugins.ConvertNamedColors'),
                        "CompressColorValues" => $this->options->bool('css.minify.cssmin.plugins.CompressColorValues'),
                        "CompressUnitValues" => $this->options->bool('css.minify.cssmin.plugins.CompressUnitValues'),
                        "CompressExpressionValues" => $this->options->bool('css.minify.cssmin.plugins.CompressExpressionValues')
                    );
                }

                // load library
                if (!class_exists('O10n\CssMin')) {
                    require_once $this->core->modules('css')->dir_path() . 'lib/CssMin.php';
                }

                $filters = $this->cssmin_minify_filters;
                if (isset($filters['ImportImports']) && $filters['ImportImports']) {
                    $filters['ImportImports']['base_href'] = $base_href;
                    if (!$base_href) {
                        exit;
                    }
                }
         
                // minify
                try {
                    $minified = CssMin::minify($CSS, $filters, $this->cssmin_minify_plugins);
                } catch (\Exception $err) {
                    throw new Exception('PHP CssMin failed: ' . $err->getMessage(), 'css');
                }

                if (!$this->options->bool('css.minify.ignore_errors.enabled') && CssMin::hasErrors()) {
                    throw new Exception('PHP CssMin failed: <ul><li>' . implode("</li><li>", CssMin::getErrors()) . '</li></ul>', 'css');
                }

                if (!$minified && $minified !== '') {
                    if (CssMin::hasErrors()) {
                        throw new Exception('PHP CssMin failed: <ul><li>' . implode("</li><li>", CssMin::getErrors()) . '</li></ul>', 'css');
                    } else {
                        throw new Exception('PHP CssMin failed: unknown error', 'css');
                    }
                }

            break;
            case "yui":

                // load library
                if (!class_exists('tubalmartin\CssMin\Minifier')) {
                    try {
                        require_once $this->core->modules('css')->dir_path() . 'lib/YUI_Utils.php';
                        require_once $this->core->modules('css')->dir_path() . 'lib/YUI_Colors.php';
                        require_once $this->core->modules('css')->dir_path() . 'lib/YUI_Minifier.php';
                    } catch (\Exception $err) {
                        throw new Exception('YUI CSS Compressor failed to load: ' . $err->getMessage(), 'css');
                    }
                }
                if (is_null($this->YUI)) {
                    $this->YUI = new \tubalmartin\CssMin\Minifier;

                    // set options
                    if ($this->options->bool('css.minify.yui.options.keepSourceMapComment')) {
                        $this->YUI->keepSourceMapComment(true);
                    }

                    if ($this->options->bool('css.minify.yui.options.removeImportantComments')) {
                        $this->YUI->removeImportantComments(true);
                    }

                    if ($this->options->bool('css.minify.yui.options.setLinebreakPosition.enabled')) {
                        $this->YUI->setLinebreakPosition($this->options->get('css.minify.yui.options.setLinebreakPosition.position'));
                    }

                    if ($this->options->bool('css.minify.yui.options.setMaxExecutionTime.enabled')) {
                        $this->YUI->setMaxExecutionTime($this->options->get('css.minify.yui.options.setMaxExecutionTime.value'));
                    }

                    if ($this->options->bool('css.minify.yui.options.setMemoryLimit.enabled')) {
                        $this->YUI->setMemoryLimit($this->options->get('css.minify.yui.options.setMemoryLimit.value'));
                    }

                    if ($this->options->bool('css.minify.yui.options.setPcreBacktrackLimit.enabled')) {
                        $this->YUI->setPcreBacktrackLimit($this->options->get('css.minify.yui.options.setPcreBacktrackLimit.value'));
                    }

                    if ($this->options->bool('css.minify.yui.options.setPcreRecursionLimit.enabled')) {
                        $this->YUI->setPcreRecursionLimit($this->options->get('css.minify.yui.options.setPcreRecursionLimit.value'));
                    }
                }

                // minify
                try {
                    $minified = $this->YUI->run($CSS);
                } catch (\Exception $err) {
                    throw new Exception('YUI CSS Compressor failed: ' . $err->getMessage(), 'css');
                }

                if (!$minified && $minified !== '') {
                    throw new Exception('YUI CSS Compressor failed: unknown error', 'css');
                }

            break;
            case "custom":

                // minify
                try {
                    $minified = apply_filters('o10n_css_custom_minify', $CSS, $base_href);
                } catch (\Exception $err) {
                    throw new Exception('Custom CSS minifier failed: ' . $err->getMessage(), 'css');
                }

                if (!$minified && $minified !== '') {
                    throw new Exception('Custom CSS minifier failed: unknown error', 'css');
                }

            break;
            case "regex":
            default:

                // load library
                if (!class_exists('O10n\Minify_CSS_Compressor')) {
                    try {
                        require_once $this->core->modules('css')->dir_path() . 'lib/Compressor.php';
                    } catch (\Exception $err) {
                        throw new Exception('CSS Compressor.php failed to load: ' . $err->getMessage(), 'css');
                    }
                }
                if (is_null($this->Compressor)) {
                    $this->Compressor = new Minify_CSS_Compressor(null);
                }

                // minify
                try {
                    $minified = $this->Compressor->process($CSS);
                } catch (\Exception $err) {
                    throw new Exception('CSS Compressor.php failed: ' . $err->getMessage(), 'css');
                }

                if (!$minified && $minified !== '') {
                    throw new Exception('CSS Compressor.php failed: unknown error', 'css');
                }

            break;
        }


        return array('css' => $minified);
    }

    /**
     * Parse media attribute
     *
     * @param  string $tag Stylesheet tag
     * @return string Media query extracted from the tag.
     */
    final private function parse_media_attr($tag)
    {
        $media = false;
        $mediapos = strpos($tag, 'media');
        if ($mediapos !== false) {

            // regex
            $char = substr($tag, ($mediapos + 6), 1);
            if ($char === '"' || $char === '\'') {
                $char = preg_quote($char);
                $regex = '#media\s*=\s*'.$char.'([^'.$char.']+)'.$char.'#Usmi';
            } elseif ($char === ' ' || $char === "\n") {
                $regex = '#media\s*=\s*["|\']([^"|\']+)["|\']#Usmi';
            } else {
                $regex = '#media\s*=([^\s]+)\s#Usmi';
            }

            // match
            if (preg_match($regex, $tag, $mediaOut)) {
                $media = trim(strtolower($mediaOut[1]));
            }
        }
        if (!$media) {
            $media = 'all';
        }

        return $media;
    }

    /**
     * Return filename
     */
    final private function extract_filename($href)
    {
        //$basename = basename($href);
        $basename = str_replace('http://abtf.local', '', $href);
        if (strpos($basename, '?') !== false) {
            return explode('?', $basename)[0];
        }

        return $basename;
    }

    /**
     * Extract href from tag
     *
     * @param  string $tag     HTML tag
     * @param  string $replace href value to replace
     * @return string href or modified tag
     */
    final private function href_regex($tag, $replace = false)
    {

        // detect if tag has href
        $hrefpos = strpos($tag, 'href');
        if ($hrefpos !== false) {

            // regex
            $char = substr($tag, ($hrefpos + 5), 1);
            if ($char === '"' || $char === '\'') {
                $char = preg_quote($char);
                $regex = '#(href\s*=\s*'.$char.')([^'.$char.']+)('.$char.')#Usmi';
            } elseif ($char === ' ' || $char === "\n") {
                $regex = '#(href\s*=\s*["|\'])([^"|\']+)(["|\'])#Usmi';
            } else {
                $regex = '#(href\s*=)([^\s]+)(\s)#Usmi';
            }

            // return href
            if (!$replace) {

                // match href
                if (!preg_match($regex, $tag, $out)) {
                    return false;
                }

                return ($out[2]) ? $this->url->translate_protocol($out[2]) : $out[2];
            }

            // replace href in tag
            $tag = preg_replace($regex, '$1' . $replace . '$3', $tag);
        }

        return ($replace) ? $tag : false;
    }

    /**
     * Apply stylesheet CDN or HTTP/@ Server Push to url
     *
     * @param  string $url Stylesheet URL
     * @return string href or modified tag
     */
    final private function url_filter($url)
    {
        // apply HTTP/2 Server Push
        if ($this->http2_push) {

            // apply stylesheet CDN
            $cdn_url = false;
            if ($this->http2_push_cdn) {
                $cdn_url = $this->url->cdn($url, $this->stylesheet_cdn);
                if ($cdn_url === $url) {
                    $cdn_url = false;
                } else {
                    $url = $cdn_url;
                }
            }

            if (Core::get('http2')->push($url, 'style', false, $this->http2_push, ($cdn_url ? null : true))) {

                // return original URL that has been pushed
                return $url;
            }

            // return CDN url
            if ($this->http2_push_cdn) {
                return $url;
            }
        }

        // apply stylesheet CDN
        return $this->url->cdn($url, $this->stylesheet_cdn);
    }

    /**
     * Apply filters to minified sourcemap
     *
     * @param  string $json Sourcemap JSON
     * @return string Filtered sourcemap JSON
     */
    final private function minified_sourcemap_filter($json)
    {

        // fix relative paths
        if (strpos($json, '../') !== false || strpos($json, '"wp-') !== false) {
            $json = preg_replace('#"(\../)*wp-(includes|admin|content)/#s', '"'.$this->url->root_path().'wp-$2/', $json);
        }

        return $json;
    }

    /**
     * Return resource minification hash
     *
     * To enable different minification settings per page, any settings that modify the CSS before minification should be used in the hash.
     *
     * @param  string $resource Resource
     * @return string MD5 hash for resource
     */
    final public function minify_hash($resource)
    {

        // return default hash
        return md5($resource);
    }

    /**
     * Sanitize group filter
     */
    final public function sanitize_filter($concat_filter)
    {
        if (!is_array($concat_filter) || empty($concat_filter)) {
            $concat_filter = false;
        }

        // sanitize groups by key reference
        $sanitized_groups = array();
        foreach ($concat_filter as $filter) {
            if (!isset($filter['match']) || empty($filter['match'])) {
                continue;
            }

            if (isset($filter['group']) && isset($filter['group']['key'])) {
                $sanitized_groups[$filter['group']['key']] = $filter;
            } else {
                $sanitized_groups[] = $filter;
            }
        }

        return $sanitized_groups;
    }

    /**
     * Apply filter
     */
    final public function apply_filter(&$concat_group, &$concat_group_settings, $tag, $concat_filter)
    {
        if (!is_array($concat_filter)) {
            throw new Exception('Concat group filter not array.', 'core');
        }

        $filter_set = false; // group set flag
        
        // match group filter list
        foreach ($concat_filter as $key => $filter) {

            // verify filter config
            if (!is_array($filter) || empty($filter) || (!isset($filter['match']) && !isset($filter['match_regex']))) {
                continue 1;
            }

            // exclude rule
            $exclude_filter = (isset($filter['exclude']) && $filter['exclude']);

            // string based match
            if (isset($filter['match']) && !empty($filter['match'])) {
                foreach ($filter['match'] as $match_string) {
                    $exclude = false;
                    $regex = false;

                    // filter config
                    if (is_array($match_string)) {
                        $exclude = (isset($match_string['exclude'])) ? $match_string['exclude'] : false;
                        $regex = (isset($match_string['regex'])) ? $match_string['regex'] : false;
                        $match_string = $match_string['string'];
                    }

                    // group set, just apply exclude filters
                    if ($filter_set && !$exclude && !$exclude_filter) {
                        continue 1;
                    }

                    if ($regex) {
                        $match = false;
                        try {
                            if (@preg_match($match_string, $tag)) {

                                // exclude filter
                                if ($exclude || $exclude_filter) {
                                    $concat_group = false;

                                    return;
                                }

                                $match = true;
                            }
                        } catch (\Exception $err) {
                            $match = false;
                        }

                        if ($match) {

                            // match, assign to group
                            $concat_group = md5(json_encode($filter));
                            if (!isset($concat_group_settings[$concat_group])) {
                                $concat_group_settings[$concat_group] = array();
                            }
                            $concat_group_settings[$concat_group] = array_merge($filter, $concat_group_settings[$concat_group]);
                            
                            $filter_set = true;
                        }
                    } else {
                        if (strpos($tag, $match_string) !== false) {

                            // exclude filter
                            if ($exclude || $exclude_filter) {
                                $concat_group = false;

                                return;
                            }

                            // match, assign to group
                            $concat_group = md5(json_encode($filter));
                            if (!isset($concat_group_settings[$concat_group])) {
                                $concat_group_settings[$concat_group] = array();
                            }
                            $concat_group_settings[$concat_group] = array_merge($filter, $concat_group_settings[$concat_group]);

                            $filter_set = true;
                        }
                    }
                }
            }
        }
    }

    /**
     * Return concat hash path for async list
     *
     * @param  string $hash Hash key for concat stylesheet
     * @return string Hash path for async list.
     */
    final public function async_hash_path($hash)
    {
        // get index id
        $index_id = $this->cache->index_id('css', 'concat', $hash);

        if (!$index_id) {
            throw new Exception('Failed to retrieve concat hash index ID.', 'text');
        }
        if (is_array($index_id)) {
            $suffix = $index_id[1];
            $index_id = $index_id[0];
        } else {
            $suffix = false;
        }

        // return hash path
        return str_replace('/', '|', $this->cache->index_path($index_id)) . $index_id . (($suffix) ? ':' . $suffix : '');
    }

    /**
     * Return timing config
     *
     * @param   array   Timing config
     * @return array Client compressed timing config
     */
    final private function timing_config($config)
    {
        if (!$config || !is_array($config) || !isset($config['type'])) {
            return false;
        }


        // init config with type index
        $timing_config = array($this->client->config_index('key', $config['type']));

        // timing config
        switch (strtolower($config['type'])) {
            case "requestanimationframe":
                
                // frame
                $frame = (isset($config['frame']) && is_numeric($config['frame'])) ? $config['frame'] : 1;
                if ($frame > 1) {
                    $timing_config[1] = array();
                    $timing_config[1][$this->client->config_index('key', 'frame')] = $frame;
                }
            break;
            case "requestidlecallback":
                
                // timeout
                $timeout = (isset($config['timeout']) && is_numeric($config['timeout'])) ? $config['timeout'] : '';
                if ($timeout) {
                    $timing_config[1] = array();
                    $timing_config[1][$this->client->config_index('key', 'timeout')] = $timeout;
                }

                // setTimeout fallback
                $setTimeout = (isset($config['setTimeout']) && is_numeric($config['setTimeout'])) ? $config['setTimeout'] : '';
                if ($setTimeout) {
                    if (!isset($timing_config[1])) {
                        $timing_config[1] = array();
                    }
                    $timing_config[1][$this->client->config_index('key', 'setTimeout')] = $setTimeout;
                }
            break;
            case "inview":

                // selector
                $selector = (isset($config['selector'])) ? trim($config['selector']) : '';
                if ($selector !== '') {
                    $timing_config[1] = array();
                    $timing_config[1][$this->client->config_index('key', 'selector')] = $selector;
                }

                // offset
                $offset = (isset($config['offset']) && is_numeric($config['offset'])) ? $config['offset'] : 0;
                if ($offset > 0) {
                    if (!isset($timing_config[1])) {
                        $timing_config[1] = array();
                    }
                    $timing_config[1][$this->client->config_index('key', 'offset')] = $offset;
                }
            break;
            case "media":

                // media query
                $media = (isset($config['media'])) ? trim($config['media']) : '';
                if ($media !== '') {
                    $timing_config[1] = array();
                    $timing_config[1][$this->client->config_index('key', 'media')] = $media;
                }
            break;
        }

        return $timing_config;
    }
}
