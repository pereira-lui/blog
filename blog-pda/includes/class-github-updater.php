<?php
/**
 * GitHub Plugin Updater
 * 
 * Permite atualização automática do plugin via GitHub
 * Mantém histórico de versões no repositório
 *
 * @package Blog_PDA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Blog_PDA_GitHub_Updater {

    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_response;
    private $plugin_activated;
    private $plugin_folder;

    /**
     * Constructor
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->username = 'pereira-lui';
        $this->repo = 'blog-pda';
        $this->plugin_folder = 'blog-pda';
        $this->slug = $this->plugin_folder . '/' . basename($plugin_file);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_folder'], 10, 4);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        
        // Add settings link
        add_filter('plugin_action_links_' . $this->slug, [$this, 'plugin_settings_link']);
        
        // Add version info row
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
    }

    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (!empty($this->plugin_data)) {
            return;
        }
        
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($this->plugin_file);
    }

    /**
     * Get repository info from GitHub
     */
    private function get_repository_info() {
        if (!empty($this->github_response)) {
            return;
        }

        // Check cache first
        $cache_key = 'blog_pda_github_response';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            $this->github_response = $cached;
            return;
        }

        // First try to get the latest release
        $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repo);
        
        $response = wp_remote_get($request_uri, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
            ],
            'timeout' => 10,
        ]);

        $use_tags = false;
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $use_tags = true;
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $release_data = json_decode($response_body);
            
            // Check if we need to use tags instead (if tag is newer than release)
            $tags_response = $this->get_latest_tag();
            if ($tags_response && !empty($tags_response->name)) {
                $release_version = isset($release_data->tag_name) ? ltrim($release_data->tag_name, 'v') : '0.0.0';
                $tag_version = ltrim($tags_response->name, 'v');
                
                if (version_compare($tag_version, $release_version, '>')) {
                    $use_tags = true;
                } else {
                    $this->github_response = $release_data;
                }
            } else {
                $this->github_response = $release_data;
            }
        }
        
        // If release not found or tag is newer, get latest tag
        if ($use_tags) {
            $tag_data = $this->get_latest_tag();
            if ($tag_data) {
                $this->github_response = (object) [
                    'tag_name' => $tag_data->name,
                    'zipball_url' => sprintf('https://api.github.com/repos/%s/%s/zipball/%s', $this->username, $this->repo, $tag_data->name),
                    'published_at' => $tag_data->commit->committer->date ?? date('c'),
                    'body' => '',
                    'assets' => [],
                ];
            }
        }
        
        if (!empty($this->github_response)) {
            // Cache for 1 hour
            set_transient($cache_key, $this->github_response, 1 * HOUR_IN_SECONDS);
        }
    }
    
    /**
     * Get latest tag from GitHub
     */
    private function get_latest_tag() {
        $request_uri = sprintf('https://api.github.com/repos/%s/%s/tags', $this->username, $this->repo);
        
        $response = wp_remote_get($request_uri, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $response_body = wp_remote_retrieve_body($response);
        $tags = json_decode($response_body);
        
        if (!empty($tags) && is_array($tags)) {
            // Sort tags by version number
            usort($tags, function($a, $b) {
                return version_compare(
                    ltrim($b->name, 'v'),
                    ltrim($a->name, 'v')
                );
            });
            
            return $tags[0];
        }
        
        return null;
    }

    /**
     * Check for updates
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->get_plugin_data();
        $this->get_repository_info();

        if (empty($this->github_response)) {
            return $transient;
        }

        $github_version = ltrim($this->github_response->tag_name, 'v');
        $current_version = $this->plugin_data['Version'];

        // Check if a new version is available
        if (version_compare($github_version, $current_version, '>')) {
            $package = $this->github_response->zipball_url;
            
            // Check for direct download asset
            if (!empty($this->github_response->assets)) {
                foreach ($this->github_response->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $package = $asset->browser_download_url;
                        break;
                    }
                }
            }

            $transient->response[$this->slug] = (object) [
                'slug'         => $this->plugin_folder,
                'plugin'       => $this->slug,
                'new_version'  => $github_version,
                'url'          => $this->plugin_data['PluginURI'],
                'package'      => $package,
                'icons'        => [],
                'banners'      => [],
                'tested'       => '',
                'requires_php' => '7.4',
            ];
        }

        return $transient;
    }

    /**
     * Plugin info for the API
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_folder) {
            return $result;
        }

        $this->get_plugin_data();
        $this->get_repository_info();

        if (empty($this->github_response)) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = $this->plugin_data['Name'];
        $plugin_info->slug = $this->plugin_folder;
        $plugin_info->version = ltrim($this->github_response->tag_name, 'v');
        $plugin_info->author = $this->plugin_data['AuthorName'];
        $plugin_info->author_profile = $this->plugin_data['AuthorURI'];
        $plugin_info->requires = '5.0';
        $plugin_info->tested = get_bloginfo('version');
        $plugin_info->requires_php = '7.4';
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $this->github_response->published_at;
        $plugin_info->sections = [
            'description' => $this->plugin_data['Description'],
            'changelog'   => nl2br($this->github_response->body) ?: __('Consulte o GitHub para ver as alterações.', 'blog-pda'),
        ];
        $plugin_info->download_link = $this->github_response->zipball_url;
        
        if (!empty($this->github_response->assets)) {
            foreach ($this->github_response->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    $plugin_info->download_link = $asset->browser_download_url;
                    break;
                }
            }
        }

        return $plugin_info;
    }

    /**
     * Fix source folder name after download
     */
    public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        // Check if it's our plugin being updated
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $source;
        }

        // GitHub zipball creates folder like "username-repo-hash"
        // We need to rename it to our plugin folder name
        $corrected_source = trailingslashit($remote_source) . $this->plugin_folder . '/';

        if ($source !== $corrected_source) {
            if ($wp_filesystem->move($source, $corrected_source)) {
                return $corrected_source;
            }
        }

        return $source;
    }

    /**
     * After install
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Check if it's our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $result;
        }

        // Make sure plugin is activated after update
        $this->plugin_activated = is_plugin_active($this->slug);

        // Move plugin to correct location
        $plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_folder;
        $wp_filesystem->move($result['destination'], $plugin_dir);
        $result['destination'] = $plugin_dir;

        // Re-activate plugin if it was active before
        if ($this->plugin_activated) {
            activate_plugin($this->slug);
        }

        // Clear cache
        delete_transient('blog_pda_github_response');

        return $result;
    }

    /**
     * Add settings link to plugins page
     */
    public function plugin_settings_link($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=blog_post&page=blog-pda-settings') . '">' . __('Configurações', 'blog-pda') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function plugin_row_meta($links, $file) {
        if ($file === $this->slug) {
            $links[] = '<a href="https://github.com/' . $this->username . '/' . $this->repo . '/releases" target="_blank">' . __('Histórico de Versões', 'blog-pda') . '</a>';
            $links[] = '<a href="https://github.com/' . $this->username . '/' . $this->repo . '" target="_blank">' . __('GitHub', 'blog-pda') . '</a>';
        }
        return $links;
    }

    /**
     * Clear update cache
     */
    public static function clear_cache() {
        delete_transient('blog_pda_github_response');
        delete_site_transient('update_plugins');
    }
}
