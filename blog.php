<?php
/**
 * Plugin Name: Blog PDA
 * Plugin URI: https://github.com/pereira-lui/blog
 * Description: Plugin de Blog personalizado para WordPress. Cria um Custom Post Type "Blog" com templates personalizados, suporte a importação e atualização automática via GitHub.
 * Version: 1.4.1
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
define('BLOG_PDA_VERSION', '1.4.1');
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
        
        // Add featured post meta box
        add_action('add_meta_boxes', [$this, 'add_featured_meta_box']);
        add_action('save_post', [$this, 'save_featured_meta']);
        
        // Filter content for classic editor compatibility
        add_filter('the_content', [$this, 'filter_blog_content'], 20);
        
        // Hide default WordPress posts
        add_action('admin_menu', [$this, 'hide_default_posts_menu']);
        add_action('admin_bar_menu', [$this, 'hide_default_posts_admin_bar'], 999);
        add_action('wp_dashboard_setup', [$this, 'hide_default_posts_dashboard']);
        
        // Hide Rank Math SEO from Blog post type (optional)
        add_filter('rank_math/sitemap/post_type/blog_post', '__return_true');
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
                'comments',
                'trackbacks',
                'custom-fields',
                'revisions',
                'page-attributes',
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
            __('Importar Posts', 'blog-pda'),
            __('Importar Posts', 'blog-pda'),
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
            wp_enqueue_style(
                'blog-pda-style',
                BLOG_PDA_PLUGIN_URL . 'assets/css/blog-style.css',
                [],
                BLOG_PDA_VERSION
            );
            
            wp_enqueue_script(
                'blog-pda-script',
                BLOG_PDA_PLUGIN_URL . 'assets/js/blog-script.js',
                [],
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
        $exclude = isset($_POST['exclude']) ? intval($_POST['exclude']) : 0;
        
        $args = [
            'post_type' => 'blog_post',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if ($exclude) {
            $args['post__not_in'] = [$exclude];
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $categories = get_the_terms(get_the_ID(), 'blog_category');
                ?>
                <article class="blog-post-card">
                    <a href="<?php the_permalink(); ?>" class="blog-post-card-link">
                        <?php if (has_post_thumbnail()) : ?>
                        <div class="blog-post-card-image">
                            <?php the_post_thumbnail('medium_large'); ?>
                            <?php if ($categories && !is_wp_error($categories)) : ?>
                            <div class="blog-post-card-categories">
                                <?php foreach (array_slice($categories, 0, 2) as $cat) : ?>
                                    <span class="blog-category-tag"><?php echo esc_html($cat->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="blog-post-card-content">
                            <h3 class="blog-post-card-title"><?php the_title(); ?></h3>
                            <p class="blog-post-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                        </div>
                    </a>
                </article>
                <?php
            }
        }
        
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $total_pages = $query->max_num_pages;
        $has_more = $page < $total_pages;
        
        wp_send_json_success([
            'html' => $html,
            'hasMore' => $has_more
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
     * Save featured meta
     */
    public function save_featured_meta($post_id) {
        if (!isset($_POST['blog_pda_featured_nonce']) || !wp_verify_nonce($_POST['blog_pda_featured_nonce'], 'blog_pda_featured_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // If this post is being set as featured, remove featured from other posts
        if (isset($_POST['blog_featured']) && $_POST['blog_featured'] === '1') {
            global $wpdb;
            $wpdb->delete($wpdb->postmeta, ['meta_key' => '_blog_featured', 'meta_value' => '1']);
            update_post_meta($post_id, '_blog_featured', '1');
        } else {
            delete_post_meta($post_id, '_blog_featured');
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
