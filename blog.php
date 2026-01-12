<?php
/**
 * Plugin Name: Blog PDA
 * Plugin URI: https://github.com/pereira-lui/blog
 * Description: Plugin de Blog personalizado para WordPress. Cria um Custom Post Type "Blog" com templates personalizados, suporte a importação e atualização automática via GitHub.
 * Version: 2.4.1
 * Author: Lui
 * Author URI: https://github.com/pereira-lui
 * Text Domain: blog-pda
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/pereira-lui/blog
 * GitHub Branch: main
 * Update URI: https://github.com/pereira-lui/blog
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('BLOG_PDA_VERSION', '2.4.1');
define('BLOG_PDA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BLOG_PDA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLOG_PDA_PLUGIN_FILE', __FILE__);

/**
 * Main Blog PDA Class
 */
final class Blog_PDA {

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Singleton Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Include GitHub updater
        $this->includes();
        
        // Register CPT and taxonomy
        add_action('init', [$this, 'register_blog_post_type']);
        add_action('init', [$this, 'register_blog_taxonomy']);
        
        // Admin menu icon
        add_action('admin_head', [$this, 'admin_menu_icon']);
        
        // Flush rewrite rules on activation/deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Ensure correct permalinks structure
        add_filter('post_type_link', [$this, 'custom_post_type_link'], 10, 2);
        
        // Add rewrite rules for blog posts
        add_action('init', [$this, 'add_rewrite_rules']);
        
        // Handle query vars
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // Modify main query for blog archive
        add_action('pre_get_posts', [$this, 'modify_blog_archive_query']);
        
        // Add admin notice for permalink flush
        add_action('admin_notices', [$this, 'admin_notice_flush_permalinks']);
        
        // Add settings page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Enqueue frontend styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Register custom templates
        add_filter('template_include', [$this, 'load_custom_templates']);
        
        // AJAX handler for load more
        add_action('wp_ajax_blog_pda_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_blog_pda_load_more', [$this, 'ajax_load_more']);
        
        // AJAX handlers for CSV import
        add_action('wp_ajax_blog_pda_upload_csv', [$this, 'ajax_upload_csv']);
        add_action('wp_ajax_blog_pda_import_row', [$this, 'ajax_import_row']);
        add_action('wp_ajax_blog_pda_cleanup_import', [$this, 'ajax_cleanup_import']);
        
        // Add featured post meta box
        add_action('add_meta_boxes', [$this, 'add_featured_meta_box']);
        add_action('add_meta_boxes', [$this, 'add_audio_meta_box']);
        add_action('save_post', [$this, 'save_featured_meta']);
        add_action('save_post', [$this, 'save_audio_meta']);
        
        // Filter content for classic editor compatibility
        add_filter('the_content', [$this, 'filter_blog_content'], 20);
        
        // Hide default WordPress posts
        add_action('admin_menu', [$this, 'hide_default_posts_menu']);
        add_action('admin_bar_menu', [$this, 'hide_default_posts_admin_bar'], 999);
        add_action('wp_dashboard_setup', [$this, 'hide_default_posts_dashboard']);
        
        // Hide Rank Math SEO from Blog post type (optional)
        add_filter('rank_math/sitemap/post_type/blog_post', '__return_true');
        
        // Register Elementor Widget
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_elementor_category']);
    }

    /**
     * Include required files
     */
    public function includes() {
        require_once BLOG_PDA_PLUGIN_DIR . 'includes/class-github-updater.php';
        new Blog_PDA_GitHub_Updater(BLOG_PDA_PLUGIN_FILE);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_blog_post_type();
        $this->register_blog_taxonomy();
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        
        // Set flag to show admin notice
        set_transient('blog_pda_activated', true, 60);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Register Blog Custom Post Type
     */
    public function register_blog_post_type() {
        $labels = [
            'name'                  => _x('Blog', 'Post type general name', 'blog-pda'),
            'singular_name'         => _x('Post do Blog', 'Post type singular name', 'blog-pda'),
            'menu_name'             => _x('Blog', 'Admin Menu text', 'blog-pda'),
            'name_admin_bar'        => _x('Post do Blog', 'Add New on Toolbar', 'blog-pda'),
            'add_new'               => __('Adicionar Novo', 'blog-pda'),
            'add_new_item'          => __('Adicionar Novo Post', 'blog-pda'),
            'new_item'              => __('Novo Post', 'blog-pda'),
            'edit_item'             => __('Editar Post', 'blog-pda'),
            'view_item'             => __('Ver Post', 'blog-pda'),
            'all_items'             => __('Todos os Posts', 'blog-pda'),
            'search_items'          => __('Buscar Posts', 'blog-pda'),
            'parent_item_colon'     => __('Post Pai:', 'blog-pda'),
            'not_found'             => __('Nenhum post encontrado.', 'blog-pda'),
            'not_found_in_trash'    => __('Nenhum post encontrado na lixeira.', 'blog-pda'),
            'featured_image'        => _x('Imagem Destacada', 'Overrides the "Featured Image" phrase', 'blog-pda'),
            'set_featured_image'    => _x('Definir imagem destacada', 'Overrides the "Set featured image" phrase', 'blog-pda'),
            'remove_featured_image' => _x('Remover imagem destacada', 'Overrides the "Remove featured image" phrase', 'blog-pda'),
            'use_featured_image'    => _x('Usar como imagem destacada', 'Overrides the "Use as featured image" phrase', 'blog-pda'),
            'archives'              => _x('Arquivo do Blog', 'The post type archive label', 'blog-pda'),
            'insert_into_item'      => _x('Inserir no post', 'Overrides the "Insert into post" phrase', 'blog-pda'),
            'uploaded_to_this_item' => _x('Enviado para este post', 'Overrides the "Uploaded to this post" phrase', 'blog-pda'),
            'filter_items_list'     => _x('Filtrar lista de posts', 'Screen reader text', 'blog-pda'),
            'items_list_navigation' => _x('Navegação da lista de posts', 'Screen reader text', 'blog-pda'),
            'items_list'            => _x('Lista de posts', 'Screen reader text', 'blog-pda'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => 'blog',
                'with_front' => false,
            ],
            'capability_type'    => 'post',
            'has_archive'        => 'blog',
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-text-page',
            'supports'           => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'revisions',
            ],
            'show_in_rest'       => true, // Enable Gutenberg editor
            'taxonomies'         => ['blog_category', 'blog_tag'],
        ];

        register_post_type('blog_post', $args);
    }

    /**
     * Register Blog Taxonomies (Categories and Tags)
     */
    public function register_blog_taxonomy() {
        // Blog Categories
        $cat_labels = [
            'name'              => _x('Categorias do Blog', 'taxonomy general name', 'blog-pda'),
            'singular_name'     => _x('Categoria', 'taxonomy singular name', 'blog-pda'),
            'search_items'      => __('Buscar Categorias', 'blog-pda'),
            'all_items'         => __('Todas as Categorias', 'blog-pda'),
            'parent_item'       => __('Categoria Pai', 'blog-pda'),
            'parent_item_colon' => __('Categoria Pai:', 'blog-pda'),
            'edit_item'         => __('Editar Categoria', 'blog-pda'),
            'update_item'       => __('Atualizar Categoria', 'blog-pda'),
            'add_new_item'      => __('Adicionar Nova Categoria', 'blog-pda'),
            'new_item_name'     => __('Nome da Nova Categoria', 'blog-pda'),
            'menu_name'         => __('Categorias', 'blog-pda'),
        ];

        register_taxonomy('blog_category', ['blog_post'], [
            'hierarchical'      => true,
            'labels'            => $cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'blog/categoria', 'with_front' => false],
            'show_in_rest'      => true,
        ]);

        // Blog Tags
        $tag_labels = [
            'name'                       => _x('Tags do Blog', 'taxonomy general name', 'blog-pda'),
            'singular_name'              => _x('Tag', 'taxonomy singular name', 'blog-pda'),
            'search_items'               => __('Buscar Tags', 'blog-pda'),
            'popular_items'              => __('Tags Populares', 'blog-pda'),
            'all_items'                  => __('Todas as Tags', 'blog-pda'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Editar Tag', 'blog-pda'),
            'update_item'                => __('Atualizar Tag', 'blog-pda'),
            'add_new_item'               => __('Adicionar Nova Tag', 'blog-pda'),
            'new_item_name'              => __('Nome da Nova Tag', 'blog-pda'),
            'separate_items_with_commas' => __('Separe as tags com vírgulas', 'blog-pda'),
            'add_or_remove_items'        => __('Adicionar ou remover tags', 'blog-pda'),
            'choose_from_most_used'      => __('Escolher das tags mais usadas', 'blog-pda'),
            'not_found'                  => __('Nenhuma tag encontrada.', 'blog-pda'),
            'menu_name'                  => __('Tags', 'blog-pda'),
        ];

        register_taxonomy('blog_tag', ['blog_post'], [
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'blog/tag', 'with_front' => false],
            'show_in_rest'          => true,
        ]);
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Rule for single blog posts: blog/post-slug
        add_rewrite_rule(
            '^blog/([^/]+)/?$',
            'index.php?blog_post=$matches[1]',
            'top'
        );
        
        // Rule for blog archive
        add_rewrite_rule(
            '^blog/?$',
            'index.php?post_type=blog_post',
            'top'
        );
        
        // Rule for blog pagination
        add_rewrite_rule(
            '^blog/page/([0-9]+)/?$',
            'index.php?post_type=blog_post&paged=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'blog_post';
        return $vars;
    }

    /**
     * Custom post type link
     */
    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type === 'blog_post') {
            return home_url('/blog/' . $post->post_name . '/');
        }
        return $post_link;
    }

    /**
     * Modify blog archive query
     */
    public function modify_blog_archive_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            if (is_post_type_archive('blog_post')) {
                $query->set('posts_per_page', 12);
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
            }
        }
    }

    /**
     * Admin menu icon CSS
     */
    public function admin_menu_icon() {
        ?>
        <style>
            #adminmenu .menu-icon-blog_post div.wp-menu-image:before {
                content: '\f330';
            }
        </style>
        <?php
    }

    /**
     * Hide default WordPress posts from admin menu
     */
    public function hide_default_posts_menu() {
        remove_menu_page('edit.php'); // Remove Posts menu
    }

    /**
     * Hide default WordPress posts from admin bar
     */
    public function hide_default_posts_admin_bar($wp_admin_bar) {
        $wp_admin_bar->remove_node('new-post'); // Remove "+ New Post" from admin bar
    }

    /**
     * Hide default posts widgets from dashboard
     */
    public function hide_default_posts_dashboard() {
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Remove Quick Draft
    }

    /**
     * Admin notice for permalink flush
     */
    public function admin_notice_flush_permalinks() {
        if (get_transient('blog_pda_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Blog PDA ativado!', 'blog-pda'); ?></strong> 
                <?php _e('As regras de permalink foram atualizadas automaticamente. Se os links não funcionarem, vá em Configurações > Links Permanentes e clique em "Salvar alterações".', 'blog-pda'); ?></p>
            </div>
            <?php
            delete_transient('blog_pda_activated');
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Importar CSV', 'blog-pda'),
            __('📥 Importar CSV', 'blog-pda'),
            'manage_options',
            'blog-pda-csv-import',
            [$this, 'csv_import_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Instruções', 'blog-pda'),
            __('Instruções', 'blog-pda'),
            'manage_options',
            'blog-pda-import',
            [$this, 'import_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Converter Posts WP', 'blog-pda'),
            __('Converter Posts WP', 'blog-pda'),
            'manage_options',
            'blog-pda-convert',
            [$this, 'convert_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Migrar Taxonomias', 'blog-pda'),
            __('Migrar Taxonomias', 'blog-pda'),
            'manage_options',
            'blog-pda-migrate',
            [$this, 'migrate_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Configurações', 'blog-pda'),
            __('Configurações', 'blog-pda'),
            'manage_options',
            'blog-pda-settings',
            [$this, 'settings_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Vídeos do YouTube', 'blog-pda'),
            __('🎬 Vídeos YouTube', 'blog-pda'),
            'manage_options',
            'blog-pda-videos',
            [$this, 'videos_page_content']
        );
        
        add_submenu_page(
            'edit.php?post_type=blog_post',
            __('Podcasts', 'blog-pda'),
            __('🎙️ Podcasts', 'blog-pda'),
            'manage_options',
            'blog-pda-podcasts',
            [$this, 'podcasts_page_content']
        );
    }

    /**
     * CSV Import page content with AJAX progress
     */
    public function csv_import_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e('📥 Importar CSV do WP Import Export', 'blog-pda'); ?></h1>
            
            <!-- Upload Form -->
            <div id="import-upload-section" class="card" style="max-width: 900px; padding: 20px;">
                <h2><?php _e('Importador Direto de CSV', 'blog-pda'); ?></h2>
                <p><?php _e('Este importador lê o arquivo CSV exportado pelo <strong>WP Import Export</strong> e importa diretamente para o Custom Post Type do blog com categorias e tags.', 'blog-pda'); ?></p>
                
                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php _e('Campos reconhecidos automaticamente:', 'blog-pda'); ?></strong></p>
                    <code>Title</code>, <code>Content</code>, <code>Excerpt</code>, <code>Slug</code>, <code>Date</code>, <code>Status</code>, <code>Categorias</code>, <code>Tags</code>, <code>Image URL</code>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file"><?php _e('Arquivo CSV', 'blog-pda'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <p class="description"><?php _e('Selecione o arquivo CSV exportado do WP Import Export.', 'blog-pda'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="update_existing"><?php _e('Posts existentes', 'blog-pda'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="update_existing" id="update_existing" value="1">
                                <?php _e('Atualizar posts existentes (baseado no slug)', 'blog-pda'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="download_images"><?php _e('Imagens', 'blog-pda'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="download_images" id="download_images" value="1" checked>
                                <?php _e('Baixar e importar imagens destacadas', 'blog-pda'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="start-import-btn" class="button button-primary button-hero">
                        <?php _e('📥 Iniciar Importação', 'blog-pda'); ?>
                    </button>
                </p>
            </div>
            
            <!-- Progress Section (hidden initially) -->
            <div id="import-progress-section" class="card" style="max-width: 900px; padding: 20px; display: none;">
                <h2><?php _e('⏳ Importando...', 'blog-pda'); ?></h2>
                
                <div style="margin: 20px 0;">
                    <div id="progress-bar-container" style="background: #e0e0e0; border-radius: 10px; height: 30px; overflow: hidden;">
                        <div id="progress-bar" style="background: linear-gradient(90deg, #0073aa, #00a0d2); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            0%
                        </div>
                    </div>
                </div>
                
                <div id="progress-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #0073aa;" id="stat-current">0</div>
                        <div style="font-size: 12px; color: #666;"><?php _e('Processando', 'blog-pda'); ?></div>
                    </div>
                    <div style="background: #d4edda; padding: 15px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #28a745;" id="stat-imported">0</div>
                        <div style="font-size: 12px; color: #666;"><?php _e('Importados', 'blog-pda'); ?></div>
                    </div>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #856404;" id="stat-skipped">0</div>
                        <div style="font-size: 12px; color: #666;"><?php _e('Ignorados', 'blog-pda'); ?></div>
                    </div>
                    <div style="background: #f8d7da; padding: 15px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #721c24;" id="stat-errors">0</div>
                        <div style="font-size: 12px; color: #666;"><?php _e('Erros', 'blog-pda'); ?></div>
                    </div>
                </div>
                
                <div id="current-post-info" style="background: #f9f9f9; padding: 10px 15px; border-left: 4px solid #0073aa; margin: 10px 0;">
                    <span id="current-post-title"><?php _e('Preparando...', 'blog-pda'); ?></span>
                </div>
                
                <div id="import-log" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; margin-top: 15px;">
                    <div class="log-entry">[<?php echo date('H:i:s'); ?>] <?php _e('Aguardando início da importação...', 'blog-pda'); ?></div>
                </div>
            </div>
            
            <!-- Results Section (hidden initially) -->
            <div id="import-results-section" class="card" style="max-width: 900px; padding: 20px; display: none;">
                <h2 id="results-title"><?php _e('✅ Importação Concluída!', 'blog-pda'); ?></h2>
                
                <div id="results-summary" style="margin: 20px 0;"></div>
                
                <div id="results-errors" style="display: none; margin: 20px 0;">
                    <h3><?php _e('⚠️ Erros encontrados:', 'blog-pda'); ?></h3>
                    <ul id="errors-list" style="background: #fff3cd; padding: 15px 15px 15px 35px; border-radius: 5px; max-height: 200px; overflow-y: auto;"></ul>
                </div>
                
                <p style="margin-top: 20px;">
                    <a href="<?php echo admin_url('edit.php?post_type=blog_post'); ?>" class="button button-primary"><?php _e('Ver Posts Importados', 'blog-pda'); ?></a>
                    <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button"><?php _e('Atualizar Permalinks', 'blog-pda'); ?></a>
                    <button type="button" id="import-again-btn" class="button"><?php _e('Importar Outro Arquivo', 'blog-pda'); ?></button>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var importData = {
                file_id: '',
                total: 0,
                current: 0,
                imported: 0,
                skipped: 0,
                errors: [],
                update_existing: false,
                download_images: true
            };
            
            function addLog(message, type) {
                var time = new Date().toLocaleTimeString();
                var color = type === 'error' ? '#ff6b6b' : (type === 'success' ? '#51cf66' : '#d4d4d4');
                $('#import-log').append('<div class="log-entry" style="color: ' + color + '">[' + time + '] ' + message + '</div>');
                $('#import-log').scrollTop($('#import-log')[0].scrollHeight);
            }
            
            function updateProgress() {
                var percent = importData.total > 0 ? Math.round((importData.current / importData.total) * 100) : 0;
                $('#progress-bar').css('width', percent + '%').text(percent + '%');
                $('#stat-current').text(importData.current + ' / ' + importData.total);
                $('#stat-imported').text(importData.imported);
                $('#stat-skipped').text(importData.skipped);
                $('#stat-errors').text(importData.errors.length);
            }
            
            function processNextRow() {
                if (importData.current >= importData.total) {
                    finishImport();
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'blog_pda_import_row',
                        file_id: importData.file_id,
                        row_index: importData.current,
                        update_existing: importData.update_existing ? 1 : 0,
                        download_images: importData.download_images ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce('blog_pda_import'); ?>'
                    },
                    success: function(response) {
                        importData.current++;
                        
                        if (response.success) {
                            if (response.data.status === 'imported') {
                                importData.imported++;
                                addLog('✓ ' + response.data.title, 'success');
                            } else if (response.data.status === 'skipped') {
                                importData.skipped++;
                                addLog('⊘ Ignorado: ' + response.data.title, '');
                            } else if (response.data.status === 'error') {
                                importData.errors.push(response.data.message);
                                addLog('✗ ' + response.data.message, 'error');
                            }
                            $('#current-post-title').text(response.data.title || 'Processando...');
                        } else {
                            importData.errors.push(response.data || 'Erro desconhecido');
                            addLog('✗ Erro: ' + (response.data || 'desconhecido'), 'error');
                        }
                        
                        updateProgress();
                        
                        // Small delay to prevent server overload
                        setTimeout(processNextRow, 100);
                    },
                    error: function(xhr, status, error) {
                        importData.current++;
                        importData.errors.push('Erro de conexão: ' + error);
                        addLog('✗ Erro de conexão: ' + error, 'error');
                        updateProgress();
                        setTimeout(processNextRow, 500);
                    }
                });
            }
            
            function finishImport() {
                addLog('Importação finalizada!', 'success');
                
                // Cleanup
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'blog_pda_cleanup_import',
                        file_id: importData.file_id,
                        nonce: '<?php echo wp_create_nonce('blog_pda_import'); ?>'
                    }
                });
                
                // Show results
                $('#import-progress-section').hide();
                
                var summaryHtml = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">';
                summaryHtml += '<div style="background: #d4edda; padding: 20px; border-radius: 5px; text-align: center;"><div style="font-size: 36px; font-weight: bold; color: #28a745;">' + importData.imported + '</div><div>Posts Importados</div></div>';
                summaryHtml += '<div style="background: #fff3cd; padding: 20px; border-radius: 5px; text-align: center;"><div style="font-size: 36px; font-weight: bold; color: #856404;">' + importData.skipped + '</div><div>Ignorados</div></div>';
                summaryHtml += '<div style="background: #f8d7da; padding: 20px; border-radius: 5px; text-align: center;"><div style="font-size: 36px; font-weight: bold; color: #721c24;">' + importData.errors.length + '</div><div>Erros</div></div>';
                summaryHtml += '</div>';
                
                $('#results-summary').html(summaryHtml);
                
                if (importData.errors.length > 0) {
                    var errorsList = '';
                    importData.errors.slice(0, 20).forEach(function(err) {
                        errorsList += '<li>' + err + '</li>';
                    });
                    if (importData.errors.length > 20) {
                        errorsList += '<li><em>... e mais ' + (importData.errors.length - 20) + ' erros</em></li>';
                    }
                    $('#errors-list').html(errorsList);
                    $('#results-errors').show();
                }
                
                if (importData.imported > 0 && importData.errors.length === 0) {
                    $('#results-title').html('✅ <?php _e('Importação Concluída com Sucesso!', 'blog-pda'); ?>');
                } else if (importData.imported > 0) {
                    $('#results-title').html('⚠️ <?php _e('Importação Concluída com Alguns Erros', 'blog-pda'); ?>');
                } else {
                    $('#results-title').html('❌ <?php _e('Importação Falhou', 'blog-pda'); ?>');
                }
                
                $('#import-results-section').show();
            }
            
            // Start import button
            $('#start-import-btn').on('click', function() {
                var fileInput = $('#csv_file')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    alert('<?php _e('Por favor, selecione um arquivo CSV.', 'blog-pda'); ?>');
                    return;
                }
                
                var formData = new FormData();
                formData.append('action', 'blog_pda_upload_csv');
                formData.append('csv_file', fileInput.files[0]);
                formData.append('nonce', '<?php echo wp_create_nonce('blog_pda_import'); ?>');
                
                // Reset state
                importData = {
                    file_id: '',
                    total: 0,
                    current: 0,
                    imported: 0,
                    skipped: 0,
                    errors: [],
                    update_existing: $('#update_existing').is(':checked'),
                    download_images: $('#download_images').is(':checked')
                };
                
                // Show progress section
                $('#import-upload-section').hide();
                $('#import-progress-section').show();
                $('#import-log').html('<div class="log-entry">[' + new Date().toLocaleTimeString() + '] <?php _e('Enviando arquivo CSV...', 'blog-pda'); ?></div>');
                
                // Upload file first
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            importData.file_id = response.data.file_id;
                            importData.total = response.data.total_rows;
                            
                            addLog('Arquivo carregado: ' + response.data.total_rows + ' posts encontrados', 'success');
                            updateProgress();
                            
                            // Start processing rows
                            processNextRow();
                        } else {
                            addLog('Erro: ' + response.data, 'error');
                            $('#current-post-title').text('Erro ao processar arquivo');
                        }
                    },
                    error: function(xhr, status, error) {
                        addLog('Erro ao enviar arquivo: ' + error, 'error');
                        $('#current-post-title').text('Erro ao enviar arquivo');
                    }
                });
            });
            
            // Import again button
            $('#import-again-btn').on('click', function() {
                $('#import-results-section').hide();
                $('#import-upload-section').show();
                $('#csv_file').val('');
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Upload CSV file
     */
    public function ajax_upload_csv() {
        check_ajax_referer('blog_pda_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'blog-pda'));
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Erro no upload do arquivo.', 'blog-pda'));
        }
        
        // Read CSV and store in transient
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            wp_send_json_error(__('Não foi possível abrir o arquivo.', 'blog-pda'));
        }
        
        // Get headers
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            wp_send_json_error(__('Arquivo CSV vazio ou inválido.', 'blog-pda'));
        }
        
        // Read all rows
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        
        if (empty($rows)) {
            wp_send_json_error(__('Nenhum dado encontrado no CSV.', 'blog-pda'));
        }
        
        // Generate unique ID and store data
        $file_id = 'blog_pda_import_' . wp_generate_password(12, false);
        set_transient($file_id . '_headers', $headers, HOUR_IN_SECONDS);
        set_transient($file_id . '_rows', $rows, HOUR_IN_SECONDS);
        
        wp_send_json_success([
            'file_id' => $file_id,
            'total_rows' => count($rows),
            'headers' => $headers
        ]);
    }

    /**
     * AJAX: Import single row
     */
    public function ajax_import_row() {
        check_ajax_referer('blog_pda_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'blog-pda'));
        }
        
        $file_id = sanitize_text_field($_POST['file_id']);
        $row_index = intval($_POST['row_index']);
        $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === '1';
        $download_images = isset($_POST['download_images']) && $_POST['download_images'] === '1';
        
        // Get stored data
        $headers = get_transient($file_id . '_headers');
        $rows = get_transient($file_id . '_rows');
        
        if ($headers === false || $rows === false) {
            wp_send_json_error(__('Dados expirados. Por favor, faça upload novamente.', 'blog-pda'));
        }
        
        if (!isset($rows[$row_index])) {
            wp_send_json_error(__('Linha não encontrada.', 'blog-pda'));
        }
        
        $row = $rows[$row_index];
        $header_map = array_flip($headers);
        
        // Get values
        $title = isset($header_map['Title']) && isset($row[$header_map['Title']]) ? trim($row[$header_map['Title']]) : '';
        $content = isset($header_map['Content']) && isset($row[$header_map['Content']]) ? $row[$header_map['Content']] : '';
        $excerpt = isset($header_map['Excerpt']) && isset($row[$header_map['Excerpt']]) ? $row[$header_map['Excerpt']] : '';
        $slug = isset($header_map['Slug']) && isset($row[$header_map['Slug']]) ? sanitize_title($row[$header_map['Slug']]) : '';
        $date = isset($header_map['Date']) && isset($row[$header_map['Date']]) ? $row[$header_map['Date']] : '';
        $status = isset($header_map['Status']) && isset($row[$header_map['Status']]) ? strtolower(trim($row[$header_map['Status']])) : 'publish';
        $categories = isset($header_map['Categorias']) && isset($row[$header_map['Categorias']]) ? $row[$header_map['Categorias']] : '';
        $tags = isset($header_map['Tags']) && isset($row[$header_map['Tags']]) ? $row[$header_map['Tags']] : '';
        $image_url = isset($header_map['Image URL']) && isset($row[$header_map['Image URL']]) ? trim($row[$header_map['Image URL']]) : '';
        
        // Skip empty titles
        if (empty($title)) {
            wp_send_json_success([
                'status' => 'skipped',
                'title' => __('Título vazio', 'blog-pda'),
                'message' => __('Título vazio, linha ignorada.', 'blog-pda')
            ]);
        }
        
        // Check if post exists
        $existing_post = null;
        if (!empty($slug)) {
            $existing_post = get_page_by_path($slug, OBJECT, 'blog_post');
        }
        
        if ($existing_post && !$update_existing) {
            wp_send_json_success([
                'status' => 'skipped',
                'title' => $title,
                'message' => sprintf(__('Post "%s" já existe.', 'blog-pda'), $title)
            ]);
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_name' => $slug,
            'post_status' => in_array($status, ['publish', 'draft', 'pending', 'private']) ? $status : 'publish',
            'post_type' => 'blog_post',
            'post_date' => !empty($date) ? $date : current_time('mysql'),
        ];
        
        // Update or insert
        if ($existing_post && $update_existing) {
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($post_id)) {
            wp_send_json_success([
                'status' => 'error',
                'title' => $title,
                'message' => sprintf(__('Erro ao criar "%s": %s', 'blog-pda'), $title, $post_id->get_error_message())
            ]);
        }
        
        // Process categories
        if (!empty($categories)) {
            $cat_names = array_map('trim', explode(',', $categories));
            $cat_ids = [];
            
            foreach ($cat_names as $cat_name) {
                if (empty($cat_name)) continue;
                
                $term = get_term_by('name', $cat_name, 'blog_category');
                if (!$term) {
                    $new_term = wp_insert_term($cat_name, 'blog_category');
                    if (!is_wp_error($new_term)) {
                        $cat_ids[] = $new_term['term_id'];
                    }
                } else {
                    $cat_ids[] = $term->term_id;
                }
            }
            
            if (!empty($cat_ids)) {
                wp_set_object_terms($post_id, $cat_ids, 'blog_category');
            }
        }
        
        // Process tags
        if (!empty($tags)) {
            $tag_names = array_map('trim', explode(',', $tags));
            $tag_ids = [];
            
            foreach ($tag_names as $tag_name) {
                if (empty($tag_name)) continue;
                
                $term = get_term_by('name', $tag_name, 'blog_tag');
                if (!$term) {
                    $new_term = wp_insert_term($tag_name, 'blog_tag');
                    if (!is_wp_error($new_term)) {
                        $tag_ids[] = $new_term['term_id'];
                    }
                } else {
                    $tag_ids[] = $term->term_id;
                }
            }
            
            if (!empty($tag_ids)) {
                wp_set_object_terms($post_id, $tag_ids, 'blog_tag');
            }
        }
        
        // Process featured image
        if ($download_images && !empty($image_url)) {
            $this->set_featured_image_from_url($post_id, $image_url);
        }
        
        wp_send_json_success([
            'status' => 'imported',
            'title' => $title,
            'post_id' => $post_id,
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    /**
     * AJAX: Cleanup import data
     */
    public function ajax_cleanup_import() {
        check_ajax_referer('blog_pda_import', 'nonce');
        
        $file_id = sanitize_text_field($_POST['file_id']);
        delete_transient($file_id . '_headers');
        delete_transient($file_id . '_rows');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        wp_send_json_success();
    }

    /**
     * Set featured image from URL
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        // Check if image already exists
        $existing_attachment = attachment_url_to_postid($image_url);
        if ($existing_attachment) {
            set_post_thumbnail($post_id, $existing_attachment);
            return true;
        }
        
        // Download image
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = [
            'name' => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        ];
        
        // Sideload the image
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return false;
        }
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return true;
    }

    /**
     * Import page content
     */
    public function import_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importar Posts do Blog', 'blog-pda'); ?></h1>
            
            <div class="card" style="max-width: 900px; padding: 20px;">
                <h2><?php _e('🚀 WP Import Export (Recomendado)', 'blog-pda'); ?></h2>
                
                <p><?php _e('Use o plugin <strong>WP Import Export</strong> para importar diretamente para o Custom Post Type do blog.', 'blog-pda'); ?></p>
                
                <h3><?php _e('Passo 1: Exportar do site original', 'blog-pda'); ?></h3>
                <ol>
                    <li><?php _e('No site original, vá em <strong>WP Imp Exp > New Export</strong>', 'blog-pda'); ?></li>
                    <li><?php _e('Selecione <strong>Posts</strong> como tipo de conteúdo', 'blog-pda'); ?></li>
                    <li><?php _e('Exporte em formato <strong>CSV</strong> ou <strong>XML</strong>', 'blog-pda'); ?></li>
                </ol>
                
                <h3><?php _e('Passo 2: Importar neste site', 'blog-pda'); ?></h3>
                <ol>
                    <li><?php _e('Vá em <strong>WP Imp Exp > New Import</strong>', 'blog-pda'); ?></li>
                    <li><?php _e('Faça upload do arquivo exportado', 'blog-pda'); ?></li>
                    <li><?php _e('Na tela de configuração, selecione:', 'blog-pda'); ?>
                        <ul style="list-style-type: none; margin-left: 20px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                            <li>📌 <strong>Post Type:</strong> <code>blog_post</code> (Post do Blog)</li>
                        </ul>
                    </li>
                </ol>
                
                <h3><?php _e('Passo 3: Mapeamento de Campos (IMPORTANTE)', 'blog-pda'); ?></h3>
                <p><?php _e('Na tela de mapeamento, configure:', 'blog-pda'); ?></p>
                
                <table class="widefat" style="margin: 15px 0;">
                    <thead>
                        <tr style="background: #0073aa; color: white;">
                            <th><?php _e('Campo do Arquivo', 'blog-pda'); ?></th>
                            <th><?php _e('Mapear Para', 'blog-pda'); ?></th>
                            <th><?php _e('Observação', 'blog-pda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>post_title</code></td>
                            <td><strong>Title</strong></td>
                            <td><?php _e('Título do post', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td><code>post_content</code></td>
                            <td><strong>Content</strong></td>
                            <td><?php _e('Conteúdo completo', 'blog-pda'); ?></td>
                        </tr>
                        <tr>
                            <td><code>post_name</code> ou <code>slug</code></td>
                            <td><strong>Slug</strong></td>
                            <td>⚠️ <?php _e('Essencial para manter URLs', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td><code>post_date</code></td>
                            <td><strong>Date</strong></td>
                            <td><?php _e('Data de publicação', 'blog-pda'); ?></td>
                        </tr>
                        <tr>
                            <td><code>post_excerpt</code></td>
                            <td><strong>Excerpt</strong></td>
                            <td><?php _e('Resumo do post', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td><code>post_status</code></td>
                            <td><strong>Status</strong></td>
                            <td><?php _e('publish, draft, etc.', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #fff3cd;">
                            <td><code>category</code> ou <code>post_category</code></td>
                            <td><strong>blog_category</strong></td>
                            <td>⚠️ <?php _e('Taxonomia de Categoria do Blog', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #fff3cd;">
                            <td><code>tags</code> ou <code>post_tag</code></td>
                            <td><strong>blog_tag</strong></td>
                            <td>⚠️ <?php _e('Taxonomia de Tag do Blog', 'blog-pda'); ?></td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td><code>featured_image</code></td>
                            <td><strong>Featured Image</strong></td>
                            <td><?php _e('Imagem destacada (URL)', 'blog-pda'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php _e('⚠️ Atenção às Taxonomias:', 'blog-pda'); ?></strong></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php _e('Mapeie <code>category</code> para <strong>blog_category</strong> (NÃO para "Category")', 'blog-pda'); ?></li>
                        <li><?php _e('Mapeie <code>tags</code> para <strong>blog_tag</strong> (NÃO para "Tags")', 'blog-pda'); ?></li>
                        <li><?php _e('Se as taxonomias não aparecerem, vá em "Add Custom Field" e selecione-as', 'blog-pda'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('📋 Após a Importação', 'blog-pda'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Passo', 'blog-pda'); ?></th>
                            <th><?php _e('Ação', 'blog-pda'); ?></th>
                            <th style="width: 150px;"><?php _e('Link', 'blog-pda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>1</strong></td>
                            <td><?php _e('Verificar se há categorias/tags para migrar', 'blog-pda'); ?></td>
                            <td><a href="<?php echo admin_url('edit.php?post_type=blog_post&page=blog-pda-migrate'); ?>" class="button button-small"><?php _e('Verificar', 'blog-pda'); ?></a></td>
                        </tr>
                        <tr>
                            <td><strong>2</strong></td>
                            <td><?php _e('Salvar links permanentes (atualiza URLs)', 'blog-pda'); ?></td>
                            <td><a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-small"><?php _e('Permalinks', 'blog-pda'); ?></a></td>
                        </tr>
                        <tr>
                            <td><strong>3</strong></td>
                            <td><?php _e('Verificar os posts importados', 'blog-pda'); ?></td>
                            <td><a href="<?php echo admin_url('edit.php?post_type=blog_post'); ?>" class="button button-small button-primary"><?php _e('Ver Posts', 'blog-pda'); ?></a></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="notice notice-success inline" style="margin-top: 20px;">
                    <p><strong><?php _e('✅ Resultado esperado:', 'blog-pda'); ?></strong><br>
                    <?php _e('URL original: <code>https://www.parquedasaves.com.br/blog/meu-post/</code>', 'blog-pda'); ?><br>
                    <?php _e('URL final: <code>' . home_url('/blog/meu-post/') . '</code>', 'blog-pda'); ?></p>
                </div>
            </div>
            
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('🔄 Método Alternativo: WordPress Importer + Conversão', 'blog-pda'); ?></h2>
                <p><?php _e('Se você já importou usando o WordPress Importer nativo (posts ficaram como "post"):', 'blog-pda'); ?></p>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=blog_post&page=blog-pda-convert'); ?>" class="button button-primary">
                        <?php _e('🔄 Converter Posts WordPress para Blog', 'blog-pda'); ?>
                    </a>
                </p>
                <p><small><?php _e('Esta ferramenta converte posts do tipo "post" para "blog_post" e migra as taxonomias automaticamente.', 'blog-pda'); ?></small></p>
            </div>
        </div>
        <?php
    }

    /**
     * Convert WP posts page content
     */
    public function convert_page_content() {
        // Handle convert action
        $message = '';
        $message_type = '';
        
        if (isset($_POST['blog_pda_convert_posts']) && wp_verify_nonce($_POST['_wpnonce'], 'blog_pda_convert')) {
            $result = $this->convert_wp_posts_to_blog();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        
        // Get conversion stats
        $stats = $this->get_conversion_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Converter Posts WordPress para Blog', 'blog-pda'); ?></h1>
            
            <?php if ($message) : ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; padding: 20px;">
                <h2><?php _e('Por que converter?', 'blog-pda'); ?></h2>
                <p><?php _e('Quando você importa posts usando o WordPress Importer nativo, os posts são importados como <strong>Posts padrão do WordPress</strong> (post type: <code>post</code>).', 'blog-pda'); ?></p>
                <p><?php _e('Este plugin usa um <strong>Custom Post Type</strong> chamado <code>blog_post</code>. Esta ferramenta converte automaticamente todos os posts importados para o formato correto.', 'blog-pda'); ?></p>
                
                <h3 style="margin-top: 20px;"><?php _e('O que será convertido:', 'blog-pda'); ?></h3>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Post type: <code>post</code> → <code>blog_post</code>', 'blog-pda'); ?></li>
                    <li><?php _e('Categorias: <code>category</code> → <code>blog_category</code>', 'blog-pda'); ?></li>
                    <li><?php _e('Tags: <code>post_tag</code> → <code>blog_tag</code>', 'blog-pda'); ?></li>
                    <li><?php _e('Todos os metadados (imagem destacada, campos personalizados, SEO)', 'blog-pda'); ?></li>
                    <li><?php _e('Comentários (mantidos)', 'blog-pda'); ?></li>
                    <li><?php _e('Autor e data de publicação (preservados)', 'blog-pda'); ?></li>
                    <li><?php _e('Slug/URL (preservado)', 'blog-pda'); ?></li>
                </ul>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Status Atual', 'blog-pda'); ?></h2>
                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th><?php _e('Tipo', 'blog-pda'); ?></th>
                            <th><?php _e('Quantidade', 'blog-pda'); ?></th>
                            <th><?php _e('Status', 'blog-pda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Posts WordPress (post)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['wp_posts']; ?></td>
                            <td><?php echo $stats['wp_posts'] > 0 ? '<span style="color: orange;">⚠️ ' . __('Precisam ser convertidos', 'blog-pda') . '</span>' : '<span style="color: green;">✓ ' . __('Nenhum', 'blog-pda') . '</span>'; ?></td>
                        </tr>
                        <tr style="background: #f0f0f1;">
                            <td><strong><?php _e('Posts do Blog (blog_post)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['blog_posts']; ?></td>
                            <td><span style="color: green;">✓ <?php _e('CPT do plugin', 'blog-pda'); ?></span></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($stats['wp_posts'] > 0) : ?>
                <div style="margin-top: 15px;">
                    <h4><?php _e('Detalhes dos posts WordPress:', 'blog-pda'); ?></h4>
                    <ul style="list-style-type: none; padding: 0;">
                        <li>📝 <?php printf(__('Publicados: %d', 'blog-pda'), $stats['wp_posts_published']); ?></li>
                        <li>📋 <?php printf(__('Rascunhos: %d', 'blog-pda'), $stats['wp_posts_draft']); ?></li>
                        <li>⏳ <?php printf(__('Pendentes: %d', 'blog-pda'), $stats['wp_posts_pending']); ?></li>
                        <li>🔒 <?php printf(__('Privados: %d', 'blog-pda'), $stats['wp_posts_private']); ?></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($stats['wp_posts'] > 0) : ?>
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Executar Conversão', 'blog-pda'); ?></h2>
                <p><?php _e('Clique no botão abaixo para converter todos os posts WordPress para o Custom Post Type do blog.', 'blog-pda'); ?></p>
                
                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php _e('Atenção:', 'blog-pda'); ?></strong> <?php _e('Esta ação irá:', 'blog-pda'); ?></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php _e('Converter o post type de <code>post</code> para <code>blog_post</code>', 'blog-pda'); ?></li>
                        <li><?php _e('Migrar categorias e tags para as taxonomias do blog', 'blog-pda'); ?></li>
                        <li><?php _e('Preservar todas as informações (slug, autor, data, meta, comentários)', 'blog-pda'); ?></li>
                        <li><?php _e('A URL mudará de <code>/yyyy/mm/slug/</code> para <code>/blog/slug/</code>', 'blog-pda'); ?></li>
                    </ul>
                </div>
                
                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php _e('Recomendação:', 'blog-pda'); ?></strong> <?php _e('Faça um backup do banco de dados antes de executar a conversão.', 'blog-pda'); ?></p>
                </div>
                
                <form method="post">
                    <?php wp_nonce_field('blog_pda_convert'); ?>
                    <p>
                        <button type="submit" name="blog_pda_convert_posts" class="button button-primary button-hero">
                            <?php _e('🔄 Converter Posts Agora', 'blog-pda'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php else : ?>
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2 style="color: green;">✓ <?php _e('Tudo Certo!', 'blog-pda'); ?></h2>
                <p><?php _e('Não há posts WordPress para converter. Todos os posts já estão usando o Custom Post Type correto.', 'blog-pda'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Fluxo de Importação Recomendado', 'blog-pda'); ?></h2>
                <ol>
                    <li><?php _e('<strong>Importar</strong> - Use o WordPress Importer para importar o arquivo .xml', 'blog-pda'); ?></li>
                    <li><?php _e('<strong>Converter</strong> - Use esta ferramenta para converter posts para blog_post', 'blog-pda'); ?></li>
                    <li><?php _e('<strong>Migrar Taxonomias</strong> - Migre categorias e tags restantes', 'blog-pda'); ?></li>
                    <li><?php _e('<strong>Atualizar Permalinks</strong> - Salve os links permanentes', 'blog-pda'); ?></li>
                </ol>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=blog_post&page=blog-pda-migrate'); ?>" class="button">
                        <?php _e('Passo 3: Migrar Taxonomias', 'blog-pda'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button">
                        <?php _e('Passo 4: Links Permanentes', 'blog-pda'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Get conversion statistics
     */
    private function get_conversion_stats() {
        // Count WP posts
        $wp_posts_count = wp_count_posts('post');
        $wp_posts_total = 0;
        $wp_posts_published = isset($wp_posts_count->publish) ? $wp_posts_count->publish : 0;
        $wp_posts_draft = isset($wp_posts_count->draft) ? $wp_posts_count->draft : 0;
        $wp_posts_pending = isset($wp_posts_count->pending) ? $wp_posts_count->pending : 0;
        $wp_posts_private = isset($wp_posts_count->private) ? $wp_posts_count->private : 0;
        
        $wp_posts_total = $wp_posts_published + $wp_posts_draft + $wp_posts_pending + $wp_posts_private;
        
        // Count blog posts
        $blog_posts_count = wp_count_posts('blog_post');
        $blog_posts_total = 0;
        if ($blog_posts_count) {
            $blog_posts_total = (isset($blog_posts_count->publish) ? $blog_posts_count->publish : 0)
                              + (isset($blog_posts_count->draft) ? $blog_posts_count->draft : 0)
                              + (isset($blog_posts_count->pending) ? $blog_posts_count->pending : 0)
                              + (isset($blog_posts_count->private) ? $blog_posts_count->private : 0);
        }
        
        return [
            'wp_posts' => $wp_posts_total,
            'wp_posts_published' => $wp_posts_published,
            'wp_posts_draft' => $wp_posts_draft,
            'wp_posts_pending' => $wp_posts_pending,
            'wp_posts_private' => $wp_posts_private,
            'blog_posts' => $blog_posts_total,
        ];
    }

    /**
     * Convert WP posts to blog_post CPT
     */
    private function convert_wp_posts_to_blog() {
        global $wpdb;
        
        $converted_posts = 0;
        $converted_categories = 0;
        $converted_tags = 0;
        $errors = [];
        
        // Get all WP posts (all statuses)
        $wp_posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future']
        ]);
        
        if (empty($wp_posts)) {
            return [
                'success' => true,
                'message' => __('Nenhum post WordPress encontrado para converter.', 'blog-pda')
            ];
        }
        
        // First, ensure all categories and tags are migrated
        $category_mapping = [];
        $tag_mapping = [];
        
        // Get all WP categories
        $all_wp_categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'orderby' => 'parent',
            'order' => 'ASC'
        ]);
        
        if (!is_wp_error($all_wp_categories)) {
            foreach ($all_wp_categories as $wp_cat) {
                if ($wp_cat->slug === 'uncategorized' || $wp_cat->slug === 'sem-categoria') {
                    continue;
                }
                
                $blog_cat = get_term_by('slug', $wp_cat->slug, 'blog_category');
                
                if (!$blog_cat) {
                    $parent_id = 0;
                    if ($wp_cat->parent > 0 && isset($category_mapping[$wp_cat->parent])) {
                        $parent_id = $category_mapping[$wp_cat->parent];
                    }
                    
                    $new_term = wp_insert_term($wp_cat->name, 'blog_category', [
                        'slug' => $wp_cat->slug,
                        'description' => $wp_cat->description,
                        'parent' => $parent_id
                    ]);
                    
                    if (!is_wp_error($new_term)) {
                        $category_mapping[$wp_cat->term_id] = $new_term['term_id'];
                        $converted_categories++;
                    }
                } else {
                    $category_mapping[$wp_cat->term_id] = $blog_cat->term_id;
                }
            }
        }
        
        // Get all WP tags
        $all_wp_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false
        ]);
        
        if (!is_wp_error($all_wp_tags)) {
            foreach ($all_wp_tags as $wp_tag) {
                $blog_tag = get_term_by('slug', $wp_tag->slug, 'blog_tag');
                
                if (!$blog_tag) {
                    $new_term = wp_insert_term($wp_tag->name, 'blog_tag', [
                        'slug' => $wp_tag->slug,
                        'description' => $wp_tag->description
                    ]);
                    
                    if (!is_wp_error($new_term)) {
                        $tag_mapping[$wp_tag->term_id] = $new_term['term_id'];
                        $converted_tags++;
                    }
                } else {
                    $tag_mapping[$wp_tag->term_id] = $blog_tag->term_id;
                }
            }
        }
        
        // Now convert each post
        foreach ($wp_posts as $post) {
            // Get current categories and tags
            $post_categories = wp_get_post_terms($post->ID, 'category', ['fields' => 'ids']);
            $post_tags = wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'ids']);
            
            // Change post type directly in database to preserve all data
            $result = $wpdb->update(
                $wpdb->posts,
                ['post_type' => 'blog_post'],
                ['ID' => $post->ID],
                ['%s'],
                ['%d']
            );
            
            if ($result === false) {
                $errors[] = sprintf(__('Erro ao converter post ID %d: %s', 'blog-pda'), $post->ID, $wpdb->last_error);
                continue;
            }
            
            // Clear post cache
            clean_post_cache($post->ID);
            
            // Assign new blog_category terms
            if (!empty($post_categories) && !is_wp_error($post_categories)) {
                $new_cat_ids = [];
                foreach ($post_categories as $cat_id) {
                    if (isset($category_mapping[$cat_id])) {
                        $new_cat_ids[] = $category_mapping[$cat_id];
                    }
                }
                if (!empty($new_cat_ids)) {
                    wp_set_object_terms($post->ID, $new_cat_ids, 'blog_category');
                }
            }
            
            // Assign new blog_tag terms
            if (!empty($post_tags) && !is_wp_error($post_tags)) {
                $new_tag_ids = [];
                foreach ($post_tags as $tag_id) {
                    if (isset($tag_mapping[$tag_id])) {
                        $new_tag_ids[] = $tag_mapping[$tag_id];
                    }
                }
                if (!empty($new_tag_ids)) {
                    wp_set_object_terms($post->ID, $new_tag_ids, 'blog_tag');
                }
            }
            
            // Remove old taxonomy associations
            wp_set_object_terms($post->ID, [], 'category');
            wp_set_object_terms($post->ID, [], 'post_tag');
            
            $converted_posts++;
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => sprintf(
                    __('Conversão parcial: %d posts convertidos, %d categorias, %d tags. Erros: %s', 'blog-pda'),
                    $converted_posts,
                    $converted_categories,
                    $converted_tags,
                    implode('; ', $errors)
                )
            ];
        }
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Conversão concluída com sucesso! %d posts convertidos, %d categorias criadas, %d tags criadas.', 'blog-pda'),
                $converted_posts,
                $converted_categories,
                $converted_tags
            )
        ];
    }

    /**
     * Migrate taxonomies page content
     */
    public function migrate_page_content() {
        // Handle migration action
        $message = '';
        $message_type = '';
        
        if (isset($_POST['blog_pda_migrate_taxonomies']) && wp_verify_nonce($_POST['_wpnonce'], 'blog_pda_migrate')) {
            $result = $this->migrate_taxonomies();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        
        // Get stats
        $stats = $this->get_taxonomy_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Migrar Categorias e Tags', 'blog-pda'); ?></h1>
            
            <?php if ($message) : ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; padding: 20px;">
                <h2><?php _e('Por que migrar?', 'blog-pda'); ?></h2>
                <p><?php _e('Quando você importa posts usando o WP Import Export ou WordPress Importer, as categorias e tags são importadas usando as taxonomias padrão do WordPress (<code>category</code> e <code>post_tag</code>).', 'blog-pda'); ?></p>
                <p><?php _e('Este plugin usa taxonomias personalizadas (<code>blog_category</code> e <code>blog_tag</code>). Esta ferramenta migra automaticamente as taxonomias dos posts importados.', 'blog-pda'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Status Atual', 'blog-pda'); ?></h2>
                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th><?php _e('Taxonomia', 'blog-pda'); ?></th>
                            <th><?php _e('Quantidade', 'blog-pda'); ?></th>
                            <th><?php _e('Status', 'blog-pda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Total Categorias WP (category)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['wp_categories_total']; ?></td>
                            <td><?php echo $stats['wp_categories_total'] > 0 ? '<span style="color: orange;">⚠️ ' . __('Serão copiadas', 'blog-pda') . '</span>' : '<span style="color: green;">✓ ' . __('Nenhuma', 'blog-pda') . '</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Total Tags WP (post_tag)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['wp_tags_total']; ?></td>
                            <td><?php echo $stats['wp_tags_total'] > 0 ? '<span style="color: orange;">⚠️ ' . __('Serão copiadas', 'blog-pda') . '</span>' : '<span style="color: green;">✓ ' . __('Nenhuma', 'blog-pda') . '</span>'; ?></td>
                        </tr>
                        <tr style="background: #f0f0f1;">
                            <td><strong><?php _e('Categorias Blog (blog_category)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['blog_categories']; ?></td>
                            <td><span style="color: green;">✓ <?php _e('Taxonomia do plugin', 'blog-pda'); ?></span></td>
                        </tr>
                        <tr style="background: #f0f0f1;">
                            <td><strong><?php _e('Tags Blog (blog_tag)', 'blog-pda'); ?></strong></td>
                            <td><?php echo $stats['blog_tags']; ?></td>
                            <td><span style="color: green;">✓ <?php _e('Taxonomia do plugin', 'blog-pda'); ?></span></td>
                        </tr>
                    </tbody>
                </table>
                
                <p style="margin-top: 15px;">
                    <strong><?php _e('Posts do blog:', 'blog-pda'); ?></strong> <?php echo $stats['total_posts']; ?><br>
                    <strong><?php _e('Posts com categorias WP:', 'blog-pda'); ?></strong> <?php echo $stats['posts_with_wp_categories']; ?><br>
                    <strong><?php _e('Posts com tags WP:', 'blog-pda'); ?></strong> <?php echo $stats['posts_with_wp_tags']; ?>
                </p>
            </div>
            
            <?php if ($stats['wp_categories_total'] > 0 || $stats['wp_tags_total'] > 0 || $stats['posts_with_wp_categories'] > 0 || $stats['posts_with_wp_tags'] > 0) : ?>
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Executar Migração', 'blog-pda'); ?></h2>
                <p><?php _e('Clique no botão abaixo para migrar todas as categorias e tags dos posts do blog para as taxonomias do plugin.', 'blog-pda'); ?></p>
                
                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php _e('Atenção:', 'blog-pda'); ?></strong> <?php _e('Esta ação irá:', 'blog-pda'); ?></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php _e('Criar as categorias/tags equivalentes nas taxonomias do blog', 'blog-pda'); ?></li>
                        <li><?php _e('Associar os posts às novas taxonomias', 'blog-pda'); ?></li>
                        <li><?php _e('Remover as associações com taxonomias padrão do WordPress', 'blog-pda'); ?></li>
                    </ul>
                </div>
                
                <form method="post">
                    <?php wp_nonce_field('blog_pda_migrate'); ?>
                    <p>
                        <button type="submit" name="blog_pda_migrate_taxonomies" class="button button-primary button-hero">
                            <?php _e('🔄 Migrar Categorias e Tags Agora', 'blog-pda'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php else : ?>
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2 style="color: green;">✓ <?php _e('Tudo Certo!', 'blog-pda'); ?></h2>
                <p><?php _e('Não há categorias ou tags para migrar. Todos os posts do blog já estão usando as taxonomias corretas.', 'blog-pda'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get taxonomy statistics
     */
    private function get_taxonomy_stats() {
        global $wpdb;
        
        // Get blog post IDs
        $blog_post_ids = get_posts([
            'post_type' => 'blog_post',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any'
        ]);
        
        // Count ALL WP categories (not just from blog posts)
        $all_wp_categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false
        ]);
        $wp_categories_total = !is_wp_error($all_wp_categories) ? count($all_wp_categories) : 0;
        
        // Count ALL WP tags (not just from blog posts)
        $all_wp_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false
        ]);
        $wp_tags_total = !is_wp_error($all_wp_tags) ? count($all_wp_tags) : 0;
        
        // Count WP categories used by blog posts
        $wp_categories = 0;
        $posts_with_wp_categories = 0;
        $wp_tags = 0;
        $posts_with_wp_tags = 0;
        
        if (!empty($blog_post_ids)) {
            foreach ($blog_post_ids as $post_id) {
                $cats = wp_get_post_terms($post_id, 'category');
                if (!empty($cats) && !is_wp_error($cats)) {
                    $posts_with_wp_categories++;
                    $wp_categories += count($cats);
                }
                
                $tags = wp_get_post_terms($post_id, 'post_tag');
                if (!empty($tags) && !is_wp_error($tags)) {
                    $posts_with_wp_tags++;
                    $wp_tags += count($tags);
                }
            }
        }
        
        // Count blog taxonomies
        $blog_categories = wp_count_terms(['taxonomy' => 'blog_category', 'hide_empty' => false]);
        $blog_tags = wp_count_terms(['taxonomy' => 'blog_tag', 'hide_empty' => false]);
        
        return [
            'total_posts' => count($blog_post_ids),
            'wp_categories' => $wp_categories,
            'wp_tags' => $wp_tags,
            'wp_categories_total' => $wp_categories_total,
            'wp_tags_total' => $wp_tags_total,
            'posts_with_wp_categories' => $posts_with_wp_categories,
            'posts_with_wp_tags' => $posts_with_wp_tags,
            'blog_categories' => is_wp_error($blog_categories) ? 0 : $blog_categories,
            'blog_tags' => is_wp_error($blog_tags) ? 0 : $blog_tags,
        ];
    }

    /**
     * Migrate taxonomies from WP default to blog custom
     */
    private function migrate_taxonomies() {
        $migrated_categories = 0;
        $migrated_tags = 0;
        $migrated_posts = 0;
        $category_mapping = []; // old_term_id => new_term_id
        $tag_mapping = []; // old_term_id => new_term_id
        
        // STEP 1: Copy ALL WordPress categories to blog_category
        $all_wp_categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'orderby' => 'parent', // Process parents first
            'order' => 'ASC'
        ]);
        
        if (!is_wp_error($all_wp_categories) && !empty($all_wp_categories)) {
            foreach ($all_wp_categories as $wp_cat) {
                // Skip "Uncategorized" if it's the default
                if ($wp_cat->slug === 'uncategorized' || $wp_cat->slug === 'sem-categoria') {
                    continue;
                }
                
                // Check if blog_category with same slug exists
                $blog_cat = get_term_by('slug', $wp_cat->slug, 'blog_category');
                
                if (!$blog_cat) {
                    // Determine parent for hierarchy
                    $parent_id = 0;
                    if ($wp_cat->parent > 0 && isset($category_mapping[$wp_cat->parent])) {
                        $parent_id = $category_mapping[$wp_cat->parent];
                    }
                    
                    // Create new blog_category
                    $new_term = wp_insert_term($wp_cat->name, 'blog_category', [
                        'slug' => $wp_cat->slug,
                        'description' => $wp_cat->description,
                        'parent' => $parent_id
                    ]);
                    
                    if (!is_wp_error($new_term)) {
                        $category_mapping[$wp_cat->term_id] = $new_term['term_id'];
                        $migrated_categories++;
                    }
                } else {
                    $category_mapping[$wp_cat->term_id] = $blog_cat->term_id;
                }
            }
        }
        
        // STEP 2: Copy ALL WordPress tags to blog_tag
        $all_wp_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false
        ]);
        
        if (!is_wp_error($all_wp_tags) && !empty($all_wp_tags)) {
            foreach ($all_wp_tags as $wp_tag) {
                // Check if blog_tag with same slug exists
                $blog_tag = get_term_by('slug', $wp_tag->slug, 'blog_tag');
                
                if (!$blog_tag) {
                    // Create new blog_tag
                    $new_term = wp_insert_term($wp_tag->name, 'blog_tag', [
                        'slug' => $wp_tag->slug,
                        'description' => $wp_tag->description
                    ]);
                    
                    if (!is_wp_error($new_term)) {
                        $tag_mapping[$wp_tag->term_id] = $new_term['term_id'];
                        $migrated_tags++;
                    }
                } else {
                    $tag_mapping[$wp_tag->term_id] = $blog_tag->term_id;
                }
            }
        }
        
        // STEP 3: Associate posts with new taxonomies
        $blog_posts = get_posts([
            'post_type' => 'blog_post',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($blog_posts as $post) {
            $post_updated = false;
            
            // Migrate categories for this post
            $wp_categories = wp_get_post_terms($post->ID, 'category');
            if (!empty($wp_categories) && !is_wp_error($wp_categories)) {
                $new_cat_ids = [];
                foreach ($wp_categories as $wp_cat) {
                    if (isset($category_mapping[$wp_cat->term_id])) {
                        $new_cat_ids[] = $category_mapping[$wp_cat->term_id];
                    }
                }
                
                if (!empty($new_cat_ids)) {
                    wp_set_post_terms($post->ID, $new_cat_ids, 'blog_category', true);
                }
                
                // Remove WP categories from post
                wp_set_post_terms($post->ID, [], 'category');
                $post_updated = true;
            }
            
            // Migrate tags for this post
            $wp_tags = wp_get_post_terms($post->ID, 'post_tag');
            if (!empty($wp_tags) && !is_wp_error($wp_tags)) {
                $new_tag_ids = [];
                foreach ($wp_tags as $wp_tag) {
                    if (isset($tag_mapping[$wp_tag->term_id])) {
                        $new_tag_ids[] = $tag_mapping[$wp_tag->term_id];
                    }
                }
                
                if (!empty($new_tag_ids)) {
                    wp_set_post_terms($post->ID, $new_tag_ids, 'blog_tag', true);
                }
                
                // Remove WP tags from post
                wp_set_post_terms($post->ID, [], 'post_tag');
                $post_updated = true;
            }
            
            if ($post_updated) {
                $migrated_posts++;
            }
        }
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Migração concluída! %d categorias criadas, %d tags criadas, %d posts atualizados.', 'blog-pda'),
                $migrated_categories,
                $migrated_tags,
                $migrated_posts
            )
        ];
    }

    /**
     * Settings page content
     */
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações do Blog PDA', 'blog-pda'); ?></h1>
            
            <div class="card" style="max-width: 800px; padding: 20px;">
                <h2><?php _e('Informações do Plugin', 'blog-pda'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Versão', 'blog-pda'); ?></th>
                        <td><code><?php echo BLOG_PDA_VERSION; ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Post Type', 'blog-pda'); ?></th>
                        <td><code>blog_post</code></td>
                    </tr>
                    <tr>
                        <th><?php _e('URL Base', 'blog-pda'); ?></th>
                        <td><code><?php echo home_url('/blog/'); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Arquivo do Blog', 'blog-pda'); ?></th>
                        <td><a href="<?php echo get_post_type_archive_link('blog_post'); ?>" target="_blank"><?php echo get_post_type_archive_link('blog_post'); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total de Posts', 'blog-pda'); ?></th>
                        <td><?php echo wp_count_posts('blog_post')->publish; ?> <?php _e('publicados', 'blog-pda'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Atualizações Automáticas', 'blog-pda'); ?></h2>
                <p><?php _e('Este plugin se atualiza automaticamente via GitHub. Quando uma nova versão for publicada no repositório, você será notificado aqui no painel.', 'blog-pda'); ?></p>
                <p>
                    <strong><?php _e('Repositório:', 'blog-pda'); ?></strong> 
                    <a href="https://github.com/pereira-lui/blog-pda" target="_blank">github.com/pereira-lui/blog-pda</a>
                </p>
                <p>
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="button">
                        <?php _e('Verificar Atualizações', 'blog-pda'); ?>
                    </a>
                </p>
            </div>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Estrutura de URLs', 'blog-pda'); ?></h2>
                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php _e('Tipo', 'blog-pda'); ?></th>
                            <th><?php _e('URL', 'blog-pda'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Arquivo do Blog', 'blog-pda'); ?></td>
                            <td><code>/blog/</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Post Individual', 'blog-pda'); ?></td>
                            <td><code>/blog/slug-do-post/</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Categoria', 'blog-pda'); ?></td>
                            <td><code>/blog/categoria/nome-categoria/</code></td>
                        </tr>
                        <tr>
                            <td><?php _e('Tag', 'blog-pda'); ?></td>
                            <td><code>/blog/tag/nome-tag/</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Videos page content
     */
    public function videos_page_content() {
        // Save videos
        if (isset($_POST['blog_pda_save_videos']) && check_admin_referer('blog_pda_videos_nonce')) {
            $videos = [];
            if (!empty($_POST['video_url']) && is_array($_POST['video_url'])) {
                foreach ($_POST['video_url'] as $index => $url) {
                    $url = esc_url_raw(trim($url));
                    if (!empty($url)) {
                        $title = isset($_POST['video_title'][$index]) ? sanitize_text_field($_POST['video_title'][$index]) : '';
                        $videos[] = [
                            'url' => $url,
                            'title' => $title,
                            'thumbnail' => $this->get_youtube_thumbnail($url)
                        ];
                    }
                }
            }
            update_option('blog_pda_videos', $videos);
            echo '<div class="notice notice-success"><p>' . __('Vídeos salvos com sucesso!', 'blog-pda') . '</p></div>';
        }
        
        $videos = get_option('blog_pda_videos', []);
        ?>
        <div class="wrap">
            <h1><?php _e('🎬 Vídeos do YouTube', 'blog-pda'); ?></h1>
            <p><?php _e('Adicione vídeos do YouTube para exibir como slider na página do blog e nos posts.', 'blog-pda'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('blog_pda_videos_nonce'); ?>
                
                <div class="card" style="max-width: 900px; padding: 20px;">
                    <h2><?php _e('Lista de Vídeos', 'blog-pda'); ?></h2>
                    <p class="description"><?php _e('Cole a URL do vídeo do YouTube. A miniatura será gerada automaticamente.', 'blog-pda'); ?></p>
                    
                    <div id="videos-list" style="margin-top: 20px;">
                        <?php if (!empty($videos)) : ?>
                            <?php foreach ($videos as $index => $video) : ?>
                            <div class="video-item" style="display: flex; gap: 15px; margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px; align-items: center;">
                                <?php if (!empty($video['thumbnail'])) : ?>
                                <div style="flex: 0 0 120px;">
                                    <img src="<?php echo esc_url($video['thumbnail']); ?>" alt="" style="width: 120px; height: 68px; object-fit: cover; border-radius: 4px;">
                                </div>
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <input type="text" name="video_title[]" value="<?php echo esc_attr($video['title']); ?>" placeholder="<?php _e('Título do vídeo (opcional)', 'blog-pda'); ?>" class="regular-text" style="width: 100%; margin-bottom: 8px;">
                                    <input type="url" name="video_url[]" value="<?php echo esc_url($video['url']); ?>" placeholder="https://www.youtube.com/watch?v=..." class="regular-text" style="width: 100%;">
                                </div>
                                <button type="button" class="button remove-video" style="color: #d63638;"><?php _e('Remover', 'blog-pda'); ?></button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" id="add-video" class="button button-secondary">
                            <?php _e('+ Adicionar Vídeo', 'blog-pda'); ?>
                        </button>
                    </div>
                </div>
                
                <p style="margin-top: 20px;">
                    <input type="submit" name="blog_pda_save_videos" class="button button-primary button-hero" value="<?php _e('💾 Salvar Vídeos', 'blog-pda'); ?>">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add new video
            $('#add-video').on('click', function() {
                var html = '<div class="video-item" style="display: flex; gap: 15px; margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px; align-items: center;">' +
                    '<div style="flex: 1;">' +
                        '<input type="text" name="video_title[]" placeholder="<?php _e('Título do vídeo (opcional)', 'blog-pda'); ?>" class="regular-text" style="width: 100%; margin-bottom: 8px;">' +
                        '<input type="url" name="video_url[]" placeholder="https://www.youtube.com/watch?v=..." class="regular-text" style="width: 100%;">' +
                    '</div>' +
                    '<button type="button" class="button remove-video" style="color: #d63638;"><?php _e('Remover', 'blog-pda'); ?></button>' +
                '</div>';
                $('#videos-list').append(html);
            });
            
            // Remove video
            $(document).on('click', '.remove-video', function() {
                $(this).closest('.video-item').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Podcasts page content
     */
    public function podcasts_page_content() {
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Save podcasts
        if (isset($_POST['blog_pda_save_podcasts']) && check_admin_referer('blog_pda_podcasts_nonce')) {
            $podcasts = [];
            if (!empty($_POST['podcast_title']) && is_array($_POST['podcast_title'])) {
                foreach ($_POST['podcast_title'] as $index => $title) {
                    $title = sanitize_text_field(trim($title));
                    if (!empty($title)) {
                        $podcasts[] = [
                            'title' => $title,
                            'subtitle' => isset($_POST['podcast_subtitle'][$index]) ? sanitize_text_field($_POST['podcast_subtitle'][$index]) : '',
                            'audio_url' => isset($_POST['podcast_audio_url'][$index]) ? esc_url_raw($_POST['podcast_audio_url'][$index]) : '',
                            'link_url' => isset($_POST['podcast_link_url'][$index]) ? esc_url_raw($_POST['podcast_link_url'][$index]) : '',
                            'duration' => isset($_POST['podcast_duration'][$index]) ? sanitize_text_field($_POST['podcast_duration'][$index]) : ''
                        ];
                    }
                }
            }
            update_option('blog_pda_podcasts', $podcasts);
            echo '<div class="notice notice-success"><p>' . __('Podcasts salvos com sucesso!', 'blog-pda') . '</p></div>';
        }
        
        $podcasts = get_option('blog_pda_podcasts', []);
        ?>
        <div class="wrap">
            <h1><?php _e('🎙️ Podcasts', 'blog-pda'); ?></h1>
            <p><?php _e('Cadastre episódios de podcast com áudio para exibir na seção "Veja também" do blog.', 'blog-pda'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('blog_pda_podcasts_nonce'); ?>
                
                <div class="card" style="max-width: 1000px; padding: 20px;">
                    <h2><?php _e('Episódios de Podcast', 'blog-pda'); ?></h2>
                    <p class="description"><?php _e('Adicione o título, subtítulo (episódio), e o áudio do podcast. Você pode fazer upload de um arquivo MP3 ou colar uma URL externa.', 'blog-pda'); ?></p>
                    
                    <div id="podcasts-list" style="margin-top: 20px;">
                        <?php if (!empty($podcasts)) : ?>
                            <?php foreach ($podcasts as $index => $podcast) : ?>
                            <div class="podcast-item" style="margin-bottom: 20px; padding: 20px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #9B2D9B;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Título do Podcast', 'blog-pda'); ?></label>
                                        <input type="text" name="podcast_title[]" value="<?php echo esc_attr($podcast['title']); ?>" placeholder="<?php _e('Ex: PODCAST das Aves', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Subtítulo / Episódio', 'blog-pda'); ?></label>
                                        <input type="text" name="podcast_subtitle[]" value="<?php echo esc_attr($podcast['subtitle']); ?>" placeholder="<?php _e('Ex: Episódio 1', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('URL do Áudio (MP3)', 'blog-pda'); ?></label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="url" name="podcast_audio_url[]" value="<?php echo esc_url($podcast['audio_url'] ?? ''); ?>" placeholder="<?php _e('https://... ou clique em Upload', 'blog-pda'); ?>" class="regular-text podcast-audio-url" style="flex: 1;">
                                            <button type="button" class="button upload-audio-btn"><?php _e('📁 Upload', 'blog-pda'); ?></button>
                                        </div>
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Duração', 'blog-pda'); ?></label>
                                        <input type="text" name="podcast_duration[]" value="<?php echo esc_attr($podcast['duration'] ?? ''); ?>" placeholder="<?php _e('Ex: 15:30', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                                    </div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Link Externo (opcional)', 'blog-pda'); ?></label>
                                    <input type="url" name="podcast_link_url[]" value="<?php echo esc_url($podcast['link_url'] ?? ''); ?>" placeholder="<?php _e('Link para Spotify, Apple Podcasts, etc.', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                                </div>
                                <?php if (!empty($podcast['audio_url'])) : ?>
                                <div style="background: #fff; padding: 10px; border-radius: 4px;">
                                    <audio controls style="width: 100%;">
                                        <source src="<?php echo esc_url($podcast['audio_url']); ?>" type="audio/mpeg">
                                    </audio>
                                </div>
                                <?php endif; ?>
                                <div style="text-align: right; margin-top: 10px;">
                                    <button type="button" class="button remove-podcast" style="color: #d63638;"><?php _e('🗑️ Remover Episódio', 'blog-pda'); ?></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <button type="button" id="add-podcast" class="button button-secondary button-large">
                            <?php _e('➕ Adicionar Novo Episódio', 'blog-pda'); ?>
                        </button>
                    </div>
                </div>
                
                <p style="margin-top: 20px;">
                    <input type="submit" name="blog_pda_save_podcasts" class="button button-primary button-hero" value="<?php _e('💾 Salvar Podcasts', 'blog-pda'); ?>">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add new podcast
            $('#add-podcast').on('click', function() {
                var html = `
                <div class="podcast-item" style="margin-bottom: 20px; padding: 20px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #9B2D9B;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Título do Podcast', 'blog-pda'); ?></label>
                            <input type="text" name="podcast_title[]" placeholder="<?php _e('Ex: PODCAST das Aves', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Subtítulo / Episódio', 'blog-pda'); ?></label>
                            <input type="text" name="podcast_subtitle[]" placeholder="<?php _e('Ex: Episódio 1', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('URL do Áudio (MP3)', 'blog-pda'); ?></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="url" name="podcast_audio_url[]" placeholder="<?php _e('https://... ou clique em Upload', 'blog-pda'); ?>" class="regular-text podcast-audio-url" style="flex: 1;">
                                <button type="button" class="button upload-audio-btn"><?php _e('📁 Upload', 'blog-pda'); ?></button>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Duração', 'blog-pda'); ?></label>
                            <input type="text" name="podcast_duration[]" placeholder="<?php _e('Ex: 15:30', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php _e('Link Externo (opcional)', 'blog-pda'); ?></label>
                        <input type="url" name="podcast_link_url[]" placeholder="<?php _e('Link para Spotify, Apple Podcasts, etc.', 'blog-pda'); ?>" class="regular-text" style="width: 100%;">
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="button remove-podcast" style="color: #d63638;"><?php _e('🗑️ Remover Episódio', 'blog-pda'); ?></button>
                    </div>
                </div>`;
                $('#podcasts-list').append(html);
            });
            
            // Remove podcast
            $(document).on('click', '.remove-podcast', function() {
                if (confirm('<?php _e('Tem certeza que deseja remover este episódio?', 'blog-pda'); ?>')) {
                    $(this).closest('.podcast-item').remove();
                }
            });
            
            // Upload audio using WordPress Media Library
            $(document).on('click', '.upload-audio-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var inputField = button.siblings('.podcast-audio-url');
                
                var mediaUploader = wp.media({
                    title: '<?php _e('Selecionar Áudio do Podcast', 'blog-pda'); ?>',
                    button: {
                        text: '<?php _e('Usar este áudio', 'blog-pda'); ?>'
                    },
                    library: {
                        type: 'audio'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    inputField.val(attachment.url);
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Get YouTube thumbnail from URL
     */
    private function get_youtube_thumbnail($url) {
        $video_id = '';
        
        // youtube.com/watch?v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        // youtu.be/VIDEO_ID
        elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        // youtube.com/embed/VIDEO_ID
        elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        
        if ($video_id) {
            return 'https://img.youtube.com/vi/' . $video_id . '/mqdefault.jpg';
        }
        
        return '';
    }

    /**
     * Get YouTube video ID from URL
     */
    public static function get_youtube_video_id($url) {
        $video_id = '';
        
        // Limpa a URL
        $url = trim($url);
        
        // Padrão youtube.com/watch?v=VIDEO_ID (pode ter parâmetros extras como &t=123)
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $video_id = $matches[1];
        } 
        // Padrão youtu.be/VIDEO_ID
        elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $video_id = $matches[1];
        } 
        // Padrão youtube.com/embed/VIDEO_ID
        elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $video_id = $matches[1];
        }
        // Padrão youtube.com/v/VIDEO_ID
        elseif (preg_match('/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $video_id = $matches[1];
        }
        // Padrão youtube.com/shorts/VIDEO_ID
        elseif (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $video_id = $matches[1];
        }
        
        return $video_id;
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('blog_pda_settings', 'blog_pda_related_links');
        register_setting('blog_pda_settings', 'blog_pda_videos');
        register_setting('blog_pda_settings', 'blog_pda_podcasts');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_singular('blog_post') || is_post_type_archive('blog_post') || is_tax('blog_category') || is_tax('blog_tag')) {
            // Swiper Slider
            wp_enqueue_style(
                'swiper-css',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                [],
                '11.0.0'
            );
            
            wp_enqueue_script(
                'swiper-js',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                [],
                '11.0.0',
                true
            );
            
            // Plugin styles
            wp_enqueue_style(
                'blog-pda-style',
                BLOG_PDA_PLUGIN_URL . 'assets/css/blog-style.css',
                ['swiper-css'],
                BLOG_PDA_VERSION
            );
            
            wp_enqueue_script(
                'blog-pda-script',
                BLOG_PDA_PLUGIN_URL . 'assets/js/blog-script.js',
                ['swiper-js'],
                BLOG_PDA_VERSION,
                true
            );
            
            wp_localize_script('blog-pda-script', 'blogPdaVars', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('blog_pda_nonce')
            ]);
        }
    }

    /**
     * Load custom templates
     */
    public function load_custom_templates($template) {
        // Archive template
        if (is_post_type_archive('blog_post') || is_tax('blog_category') || is_tax('blog_tag')) {
            $custom_template = BLOG_PDA_PLUGIN_DIR . 'templates/archive-blog_post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // Single template
        if (is_singular('blog_post')) {
            $custom_template = BLOG_PDA_PLUGIN_DIR . 'templates/single-blog_post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * AJAX load more posts
     */
    public function ajax_load_more() {
        check_ajax_referer('blog_pda_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 9;
        $exclude = isset($_POST['exclude']) ? $_POST['exclude'] : '';
        $color_start = isset($_POST['color_start']) ? intval($_POST['color_start']) : 0;
        
        $args = [
            'post_type' => 'blog_post',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if (!empty($exclude)) {
            $exclude_ids = array_map('intval', explode(',', $exclude));
            $args['post__not_in'] = $exclude_ids;
        }
        
        $query = new WP_Query($args);
        
        // Cores do Parque das Aves
        $pda_colors = ['#702F8A', '#E87722', '#009BB5', '#00A94F', '#ED1164', '#FFC20E'];
        $color_index = $color_start;
        
        $left_html = '';
        $right_html = '';
        $post_index = 0;
        $left_index = 0;
        $right_index = 0;
        
        // Calcular base do order para novos posts
        $order_base = $color_start;
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $current_color = $pda_colors[$color_index % count($pda_colors)];
                $color_index++;
                
                if ($post_index % 2 === 0) {
                    // Coluna esquerda (posts pares)
                    $mobile_order = $order_base + $left_index * 2;
                    ob_start();
                    ?>
                    <article class="blog-post-card blog-masonry-card" style="--mobile-order: <?php echo $mobile_order; ?>;">
                        <a href="<?php the_permalink(); ?>" class="blog-post-card-link">
                            <div class="blog-post-card-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('large'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="blog-post-card-overlay" style="background-color: <?php echo $current_color; ?>;">
                                <h3 class="blog-post-card-title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </article>
                    <?php
                    $left_html .= ob_get_clean();
                    $left_index++;
                } else {
                    // Coluna direita (posts ímpares)
                    $mobile_order = $order_base + $right_index * 2 + 1;
                    ob_start();
                    ?>
                    <article class="blog-post-card blog-masonry-card" style="--mobile-order: <?php echo $mobile_order; ?>;">
                        <a href="<?php the_permalink(); ?>" class="blog-post-card-link">
                            <div class="blog-post-card-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('large'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="blog-post-card-overlay" style="background-color: <?php echo $current_color; ?>;">
                                <h3 class="blog-post-card-title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </article>
                    <?php
                    $right_html .= ob_get_clean();
                    $right_index++;
                }
                $post_index++;
            }
        }
        
        wp_reset_postdata();
        
        $total_pages = $query->max_num_pages;
        $has_more = $page < $total_pages;
        
        wp_send_json_success([
            'leftHtml' => $left_html,
            'rightHtml' => $right_html,
            'hasMore' => $has_more,
            'nextColorStart' => $color_index
        ]);
    }

    /**
     * Add featured post meta box
     */
    public function add_featured_meta_box() {
        add_meta_box(
            'blog_pda_featured',
            __('Post em Destaque', 'blog-pda'),
            [$this, 'featured_meta_box_callback'],
            'blog_post',
            'side',
            'high'
        );
    }

    /**
     * Featured meta box callback
     */
    public function featured_meta_box_callback($post) {
        wp_nonce_field('blog_pda_featured_nonce', 'blog_pda_featured_nonce');
        $featured = get_post_meta($post->ID, '_blog_featured', true);
        ?>
        <label>
            <input type="checkbox" name="blog_featured" value="1" <?php checked($featured, '1'); ?>>
            <?php _e('Marcar como post em destaque na página do blog', 'blog-pda'); ?>
        </label>
        <?php
    }

    /**
     * Add audio meta box for "Ouvir a Notícia"
     */
    public function add_audio_meta_box() {
        add_meta_box(
            'blog_pda_audio',
            __('Áudio da Notícia', 'blog-pda'),
            [$this, 'audio_meta_box_callback'],
            'blog_post',
            'side',
            'default'
        );
    }

    /**
     * Audio meta box callback
     */
    public function audio_meta_box_callback($post) {
        wp_nonce_field('blog_pda_audio_nonce', 'blog_pda_audio_nonce');
        $audio_url = get_post_meta($post->ID, '_blog_post_audio_url', true);
        ?>
        <p class="description"><?php _e('Adicione um arquivo de áudio MP3 para o player "Ouvir a Notícia". Se vazio, será usado Text-to-Speech.', 'blog-pda'); ?></p>
        <div style="margin-top: 10px;">
            <input type="url" id="blog_post_audio_url" name="blog_post_audio_url" value="<?php echo esc_url($audio_url); ?>" placeholder="<?php _e('URL do áudio MP3', 'blog-pda'); ?>" style="width: 100%;">
            <button type="button" class="button blog-upload-audio-btn" style="margin-top: 8px;">
                <?php _e('Upload Áudio', 'blog-pda'); ?>
            </button>
        </div>
        <?php if (!empty($audio_url)) : ?>
        <div style="margin-top: 10px;">
            <audio controls style="width: 100%;">
                <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
            </audio>
        </div>
        <?php endif; ?>
        <script>
        jQuery(document).ready(function($) {
            $('.blog-upload-audio-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var frame = wp.media({
                    title: '<?php _e('Selecionar Áudio', 'blog-pda'); ?>',
                    button: { text: '<?php _e('Usar este áudio', 'blog-pda'); ?>' },
                    library: { type: 'audio' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#blog_post_audio_url').val(attachment.url);
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Save audio meta
     */
    public function save_audio_meta($post_id) {
        if (!isset($_POST['blog_pda_audio_nonce']) || !wp_verify_nonce($_POST['blog_pda_audio_nonce'], 'blog_pda_audio_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['blog_post_audio_url'])) {
            $audio_url = esc_url_raw($_POST['blog_post_audio_url']);
            if (!empty($audio_url)) {
                update_post_meta($post_id, '_blog_post_audio_url', $audio_url);
            } else {
                delete_post_meta($post_id, '_blog_post_audio_url');
            }
        }
    }

    /**
     * Save featured meta
     */
    public function save_featured_meta($post_id) {
        // Save featured meta
        if (isset($_POST['blog_pda_featured_nonce']) && wp_verify_nonce($_POST['blog_pda_featured_nonce'], 'blog_pda_featured_nonce')) {
            if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
                if (current_user_can('edit_post', $post_id)) {
                    // If this post is being set as featured, remove featured from other posts
                    if (isset($_POST['blog_featured']) && $_POST['blog_featured'] === '1') {
                        global $wpdb;
                        $wpdb->delete($wpdb->postmeta, ['meta_key' => '_blog_featured', 'meta_value' => '1']);
                        update_post_meta($post_id, '_blog_featured', '1');
                    } else {
                        delete_post_meta($post_id, '_blog_featured');
                    }
                }
            }
        }
    }

    /**
     * Filter blog content for classic editor compatibility
     * Makes embeds responsive and fixes common issues
     */
    public function filter_blog_content($content) {
        // Only apply to blog posts
        if (!is_singular('blog_post') && !is_post_type_archive('blog_post')) {
            return $content;
        }
        
        // Make YouTube embeds responsive
        $content = preg_replace(
            '/<iframe[^>]+src=["\']https?:\/\/(www\.)?(youtube\.com|youtu\.be)[^"\']+["\'][^>]*><\/iframe>/i',
            '<div class="video-container">$0</div>',
            $content
        );
        
        // Make Vimeo embeds responsive
        $content = preg_replace(
            '/<iframe[^>]+src=["\']https?:\/\/(www\.)?vimeo\.com[^"\']+["\'][^>]*><\/iframe>/i',
            '<div class="video-container">$0</div>',
            $content
        );
        
        // Make generic video iframes responsive (if not already wrapped)
        $content = preg_replace(
            '/(?<!<div class="video-container">)<iframe[^>]+(?:width|height)[^>]*><\/iframe>/i',
            '<div class="video-container">$0</div>',
            $content
        );
        
        // Fix empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        $content = preg_replace('/<p>&nbsp;<\/p>/', '', $content);
        
        // Fix multiple br tags
        $content = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $content);
        
        // Add loading lazy to images that don't have it
        $content = preg_replace(
            '/<img((?!loading)[^>]*)>/i',
            '<img$1 loading="lazy">',
            $content
        );
        
        return $content;
    }

    /**
     * Register Elementor Widget Category
     */
    public function register_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'blog-pda',
            [
                'title' => __('Blog PDA', 'blog-pda'),
                'icon' => 'fa fa-newspaper',
            ]
        );
    }

    /**
     * Register Elementor Widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        // Include widget file
        require_once BLOG_PDA_PLUGIN_DIR . 'includes/class-elementor-widget.php';
        
        // Register widget
        $widgets_manager->register(new \Blog_PDA_Posts_Widget());
    }
}

/**
 * Calculate reading time
 */
function blog_pda_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed
    
    if ($reading_time < 1) {
        $reading_time = 1;
    }
    
    return sprintf(_n('%d min de leitura', '%d min de leitura', $reading_time, 'blog-pda'), $reading_time);
}

/**
 * Initialize the plugin
 */
function blog_pda_init() {
    return Blog_PDA::instance();
}

// Start the plugin
blog_pda_init();
