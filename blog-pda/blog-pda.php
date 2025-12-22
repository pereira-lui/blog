<?php
/**
 * Plugin Name: Blog PDA
 * Plugin URI: https://github.com/pereira-lui/blog-pda
 * Description: Plugin de Blog personalizado para WordPress. Cria um Custom Post Type "Blog" com suporte a importação e atualização automática via GitHub.
 * Version: 1.0.0
 * Author: Lui
 * Author URI: https://github.com/pereira-lui
 * Text Domain: blog-pda
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/pereira-lui/blog-pda
 * GitHub Branch: main
 * Update URI: https://github.com/pereira-lui/blog-pda
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('BLOG_PDA_VERSION', '1.0.0');
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
            'menu_icon'          => 'dashicons-admin-post',
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
                content: '\f109';
            }
        </style>
        <?php
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
            
            <div class="card" style="max-width: 800px; padding: 20px;">
                <h2><?php _e('Instruções para Importação', 'blog-pda'); ?></h2>
                
                <p><?php _e('Para importar posts do outro site mantendo os slugs originais, siga os passos abaixo:', 'blog-pda'); ?></p>
                
                <h3><?php _e('Método 1: WP Import Export (Recomendado)', 'blog-pda'); ?></h3>
                <ol>
                    <li><?php _e('Instale o plugin <strong>WP Import Export</strong> neste site', 'blog-pda'); ?></li>
                    <li><?php _e('Vá em <strong>WP Imp Exp > New Import</strong>', 'blog-pda'); ?></li>
                    <li><?php _e('Faça upload do arquivo exportado (.csv ou .xml)', 'blog-pda'); ?></li>
                    <li><?php _e('Na configuração de mapeamento:', 'blog-pda'); ?>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li><?php _e('Selecione <strong>Post Type: blog_post</strong>', 'blog-pda'); ?></li>
                            <li><?php _e('Mapeie o campo "slug" ou "post_name" para manter a URL original', 'blog-pda'); ?></li>
                            <li><?php _e('Mapeie todos os campos necessários (título, conteúdo, data, autor, etc.)', 'blog-pda'); ?></li>
                        </ul>
                    </li>
                    <li><?php _e('Execute a importação', 'blog-pda'); ?></li>
                </ol>
                
                <h3><?php _e('Método 2: WordPress Importer Nativo', 'blog-pda'); ?></h3>
                <ol>
                    <li><?php _e('Vá em <strong>Ferramentas > Importar</strong>', 'blog-pda'); ?></li>
                    <li><?php _e('Selecione <strong>WordPress</strong> e instale o importador se necessário', 'blog-pda'); ?></li>
                    <li><?php _e('Faça upload do arquivo .xml exportado', 'blog-pda'); ?></li>
                    <li><?php _e('Configure as opções de importação', 'blog-pda'); ?></li>
                </ol>
                
                <div class="notice notice-warning inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Importante:', 'blog-pda'); ?></strong> 
                    <?php _e('Os slugs dos posts serão preservados automaticamente durante a importação. A URL final será: <code>/blog/slug-do-post/</code>', 'blog-pda'); ?></p>
                </div>
                
                <div class="notice notice-info inline" style="margin-top: 10px;">
                    <p><strong><?php _e('Exemplo de URL:', 'blog-pda'); ?></strong><br>
                    <?php _e('Original: <code>https://www.parquedasaves.com.br/blog/trio-em-foz-do-iguacu/</code>', 'blog-pda'); ?><br>
                    <?php _e('Após importação: <code>' . home_url('/blog/trio-em-foz-do-iguacu/') . '</code>', 'blog-pda'); ?></p>
                </div>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Após a Importação', 'blog-pda'); ?></h2>
                <ol>
                    <li><?php _e('Vá em <strong>Configurações > Links Permanentes</strong>', 'blog-pda'); ?></li>
                    <li><?php _e('Clique em <strong>Salvar alterações</strong> (sem mudar nada)', 'blog-pda'); ?></li>
                    <li><?php _e('Isso irá atualizar as regras de rewrite', 'blog-pda'); ?></li>
                </ol>
                
                <p>
                    <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-primary">
                        <?php _e('Ir para Links Permanentes', 'blog-pda'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=blog_post'); ?>" class="button">
                        <?php _e('Ver Posts do Blog', 'blog-pda'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
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
}

/**
 * Initialize the plugin
 */
function blog_pda_init() {
    return Blog_PDA::instance();
}

// Start the plugin
blog_pda_init();
