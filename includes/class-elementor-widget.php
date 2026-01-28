<?php
/**
 * Blog PDA - Elementor Widget
 * 
 * Widget para exibir últimos posts do blog com hover de imagem
 *
 * @package Blog_PDA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Blog Posts List Widget
 */
class Blog_PDA_Posts_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'blog_pda_posts';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Blog PDA - Lista de Posts', 'blog-pda');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-post-list';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['blog-pda'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['blog', 'posts', 'lista', 'news', 'notícias'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Conteúdo', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => __('Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Blog', 'blog-pda'),
                'placeholder' => __('Digite o título', 'blog-pda'),
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __('Número de Posts', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 20,
            ]
        );

        $this->add_control(
            'show_button',
            [
                'label' => __('Mostrar Botão "Mais Publicações"', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Texto do Botão', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Mais publicações', 'blog-pda'),
                'condition' => [
                    'show_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'category',
            [
                'label' => __('Categoria', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_blog_categories(),
                'multiple' => true,
                'label_block' => true,
                'description' => __('Deixe vazio para mostrar de todas as categorias', 'blog-pda'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Title
        $this->start_controls_section(
            'style_title_section',
            [
                'label' => __('Título Principal', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Cor', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1a1a1a',
                'selectors' => [
                    '{{WRAPPER}} .blog-pda-widget-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .blog-pda-widget-title',
            ]
        );

        $this->end_controls_section();

        // Style Section - Posts
        $this->start_controls_section(
            'style_posts_section',
            [
                'label' => __('Posts', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'post_color',
            [
                'label' => __('Cor do Texto', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1a1a1a',
                'selectors' => [
                    '{{WRAPPER}} .blog-pda-post-item a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'post_typography',
                'selector' => '{{WRAPPER}} .blog-pda-post-item a',
            ]
        );

        $this->add_control(
            'separator_color',
            [
                'label' => __('Cor da Linha Separadora', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .blog-pda-post-item' => 'border-bottom-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Colors
        $this->start_controls_section(
            'style_colors_section',
            [
                'label' => __('Cores de Destaque', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'color_1',
            [
                'label' => __('Cor 1 (Verde)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#00AC50',
            ]
        );

        $this->add_control(
            'color_2',
            [
                'label' => __('Cor 2 (Vermelho)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#EF3340',
            ]
        );

        $this->add_control(
            'color_3',
            [
                'label' => __('Cor 3 (Laranja)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#E87722',
            ]
        );

        $this->add_control(
            'color_4',
            [
                'label' => __('Cor 4 (Rosa)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#DF6286',
            ]
        );

        $this->add_control(
            'color_5',
            [
                'label' => __('Cor 5 (Azul)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#009CB6',
            ]
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'style_button_section',
            [
                'label' => __('Botão', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Cor de Fundo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .blog-pda-widget-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Cor do Texto', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .blog-pda-widget-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Image
        $this->start_controls_section(
            'style_image_section',
            [
                'label' => __('Imagem Flutuante', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_width',
            [
                'label' => __('Largura', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 150,
                        'max' => 500,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 280,
                ],
            ]
        );

        $this->add_control(
            'image_height',
            [
                'label' => __('Altura', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 400,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 220,
                ],
            ]
        );

        $this->add_control(
            'border_width',
            [
                'label' => __('Largura da Borda Colorida', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 45,
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get blog categories
     */
    private function get_blog_categories() {
        $categories = get_terms([
            'taxonomy' => 'blog_category',
            'hide_empty' => false,
        ]);

        $options = [];
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->term_id] = $category->name;
            }
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $args = [
            'post_type' => 'blog_post',
            'posts_per_page' => $settings['posts_count'],
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Filter by category if selected
        if (!empty($settings['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'blog_category',
                    'field' => 'term_id',
                    'terms' => $settings['category'],
                ],
            ];
        }

        $query = new \WP_Query($args);
        
        // Get colors
        $colors = [
            $settings['color_1'],
            $settings['color_2'],
            $settings['color_3'],
            $settings['color_4'],
            $settings['color_5'],
        ];
        
        $widget_id = $this->get_id();
        $image_width = $settings['image_width']['size'];
        $image_height = $settings['image_height']['size'];
        $border_width = $settings['border_width']['size'];
        
        ?>
        <div class="blog-pda-widget" id="blog-pda-widget-<?php echo esc_attr($widget_id); ?>">
            
            <!-- Área fixa da imagem (esquerda) -->
            <div class="blog-pda-image-area" id="blog-pda-image-area-<?php echo esc_attr($widget_id); ?>">
                <img class="blog-pda-preview-img" id="blog-pda-preview-<?php echo esc_attr($widget_id); ?>" src="" alt="">
            </div>
            
            <!-- Área do conteúdo (direita) -->
            <div class="blog-pda-content-area">
                <?php if (!empty($settings['title'])) : ?>
                <h2 class="blog-pda-widget-title"><?php echo esc_html($settings['title']); ?></h2>
                <?php endif; ?>
                
                <div class="blog-pda-posts-list">
                    <?php 
                    $index = 0;
                    if ($query->have_posts()) : 
                        while ($query->have_posts()) : $query->the_post();
                            $color_index = $index % 5;
                            $accent_color = $colors[$color_index];
                            $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                    ?>
                    <div class="blog-pda-post-item" 
                         data-color="<?php echo esc_attr($accent_color); ?>"
                         data-image="<?php echo esc_url($thumbnail); ?>">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </div>
                    <?php 
                            $index++;
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
                
                <?php if ($settings['show_button'] === 'yes') : ?>
                <div class="blog-pda-widget-button-wrap">
                    <a href="<?php echo esc_url(home_url('/blog')); ?>" class="blog-pda-widget-button">
                        <?php echo esc_html($settings['button_text']); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> {
                position: relative !important;
                font-family: "Neurial Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 40px !important;
                align-items: stretch !important;
            }
            
            /* Área fixa da imagem (esquerda) */
            #blog-pda-image-area-<?php echo esc_attr($widget_id); ?> {
                flex: 0 0 <?php echo $image_width; ?>px !important;
                width: <?php echo $image_width; ?>px !important;
                position: relative !important;
            }
            
            /* Área do conteúdo (direita) */
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-content-area {
                flex: 1 !important;
                min-width: 0 !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-widget-title {
                font-size: 32px !important;
                font-weight: 700 !important;
                margin: 0 0 20px !important;
                padding-bottom: 15px !important;
                border-bottom: 1px solid #0000004f !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-posts-list {
                display: flex !important;
                flex-direction: column !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-post-item {
                padding: 16px 0 !important;
                border-bottom: 1px solid #e0e0e0 !important;
                transition: all 0.18s ease !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-post-item a {
                text-decoration: none !important;
                color: inherit !important;
                font-weight: 600 !important;
                font-size: 18px !important;
                line-height: 1.5 !important;
                transition: color 0.18s ease !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-post-item:hover a,
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-post-item.blog-pda-active a {
                color: var(--blog-pda-accent) !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-widget-button-wrap {
                margin-top: 30px !important;
                text-align: right !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-widget-button {
                display: inline-block !important;
                padding: 12px 24px !important;
                font-size: 14px !important;
                text-decoration: none !important;
                border-radius: 4px !important;
                transition: opacity 0.2s ease !important;
            }
            
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> .blog-pda-widget-button:hover {
                opacity: 0.9 !important;
            }
            
            /* Imagem de preview - posicionada na área fixa */
            #blog-pda-preview-<?php echo esc_attr($widget_id); ?> {
                position: absolute !important;
                top: 0;
                left: 0 !important;
                width: 100% !important;
                height: <?php echo $image_height; ?>px !important;
                object-fit: cover !important;
                border-left: <?php echo $border_width; ?>px solid var(--blog-pda-accent, #00AC50) !important;
                opacity: 0 !important;
                transition: opacity 0.25s ease, top 0.2s ease !important;
                pointer-events: none !important;
            }
            
            #blog-pda-preview-<?php echo esc_attr($widget_id); ?>.blog-pda-active {
                opacity: 1 !important;
            }
            
            @media (max-width: 1024px) {
                #blog-pda-widget-<?php echo esc_attr($widget_id); ?> {
                    flex-direction: column !important;
                }
                
                #blog-pda-image-area-<?php echo esc_attr($widget_id); ?> {
                    display: none !important;
                }
            }
        </style>
        
        <script>
        (function(){
            const widget = document.getElementById('blog-pda-widget-<?php echo esc_js($widget_id); ?>');
            const previewImg = document.getElementById('blog-pda-preview-<?php echo esc_js($widget_id); ?>');
            const imageArea = document.getElementById('blog-pda-image-area-<?php echo esc_js($widget_id); ?>');
            const postsList = widget.querySelector('.blog-pda-posts-list');
            
            if (!widget || !previewImg || !postsList || !imageArea) return;
            
            let activeItem = null;
            
            // Pré-carregar todas as imagens no cache do navegador
            function warmCache() {
                postsList.querySelectorAll('.blog-pda-post-item').forEach(function(item) {
                    const url = item.dataset.image;
                    if (url) {
                        const img = new Image();
                        img.src = url;
                    }
                });
            }
            warmCache();
            
            function positionImage(item) {
                // Calcular posição do item em relação à imageArea (onde a imagem está posicionada)
                const itemRect = item.getBoundingClientRect();
                const imageAreaRect = imageArea.getBoundingClientRect();
                
                // Posicionar a imagem alinhada com o topo do item
                // Como a imagem é position:absolute dentro de imageArea, usamos imageArea como referência
                const topOffset = itemRect.top - imageAreaRect.top;
                previewImg.style.top = topOffset + 'px';
            }
            
            function showImage(item) {
                if (!item) return;
                
                const imageUrl = item.dataset.image;
                const color = item.dataset.color;
                
                if (!imageUrl) return;
                
                // Limpar item anterior se existir
                if (activeItem && activeItem !== item) {
                    activeItem.classList.remove('blog-pda-active');
                    activeItem.style.removeProperty('--blog-pda-accent');
                }
                
                // Posicionar imagem alinhada com o item
                positionImage(item);
                
                previewImg.style.setProperty('--blog-pda-accent', color);
                item.style.setProperty('--blog-pda-accent', color);
                
                if (previewImg.src !== imageUrl) {
                    previewImg.classList.remove('blog-pda-active');
                    const loader = new Image();
                    loader.onload = function() {
                        previewImg.src = imageUrl;
                        previewImg.classList.add('blog-pda-active');
                    };
                    loader.src = imageUrl;
                } else {
                    previewImg.classList.add('blog-pda-active');
                }
                
                item.classList.add('blog-pda-active');
                activeItem = item;
            }
            
            function hideImage() {
                previewImg.classList.remove('blog-pda-active');
                if (activeItem) {
                    activeItem.classList.remove('blog-pda-active');
                    activeItem.style.removeProperty('--blog-pda-accent');
                    activeItem = null;
                }
            }
            
            // Event listeners
            postsList.querySelectorAll('.blog-pda-post-item').forEach(function(item) {
                item.addEventListener('mouseenter', function(e) {
                    showImage(this);
                });
            });
            
            postsList.addEventListener('mouseleave', hideImage);
        })();
        </script>
        <?php
    }
}

/**
 * Blog Posts Grid Widget - Para selecionar posts específicos
 */
class Blog_PDA_Posts_Grid_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'blog_pda_posts_grid';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Blog PDA - Grid de Posts', 'blog-pda');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['blog-pda', 'general'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['blog', 'posts', 'grid', 'cards', 'news', 'artigos', 'selecionar'];
    }

    /**
     * Get script dependencies
     */
    public function get_script_depends() {
        return ['swiper'];
    }

    /**
     * Get style dependencies
     */
    public function get_style_depends() {
        return ['swiper'];
    }

    /**
     * Get all blog posts for selection
     */
    private function get_blog_posts_options() {
        $posts = get_posts([
            'post_type' => 'blog_post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $options = [];
        foreach ($posts as $post) {
            $date = get_the_date('d/m/Y', $post->ID);
            $options[$post->ID] = $post->post_title . ' (' . $date . ')';
        }

        return $options;
    }

    /**
     * Get blog categories
     */
    private function get_blog_categories() {
        $terms = get_terms([
            'taxonomy' => 'blog_category',
            'hide_empty' => false,
        ]);

        $options = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
        }

        return $options;
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // ========================================
        // Content Section - Posts Selection
        // ========================================
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Seleção de Posts', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'selection_type',
            [
                'label' => __('Tipo de Seleção', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'manual',
                'options' => [
                    'manual' => __('Selecionar Posts Manualmente', 'blog-pda'),
                    'category' => __('Por Categoria', 'blog-pda'),
                    'recent' => __('Posts Mais Recentes', 'blog-pda'),
                    'popular' => __('Posts Mais Lidos', 'blog-pda'),
                ],
            ]
        );

        $this->add_control(
            'selected_posts',
            [
                'label' => __('Buscar e Selecionar Posts', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_blog_posts_options(),
                'default' => [],
                'label_block' => true,
                'condition' => [
                    'selection_type' => 'manual',
                ],
                'description' => __('Digite para buscar posts pelo título. A ordem de seleção será respeitada.', 'blog-pda'),
            ]
        );

        $this->add_control(
            'posts_search_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #f0f0f1; padding: 10px; border-radius: 4px; font-size: 12px;"><strong>💡 Dica:</strong> Digite parte do título do post no campo acima para filtrar a lista.</div>',
                'content_classes' => 'elementor-descriptor',
                'condition' => [
                    'selection_type' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'selected_categories',
            [
                'label' => __('Selecionar Categorias', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_blog_categories(),
                'default' => [],
                'label_block' => true,
                'condition' => [
                    'selection_type' => 'category',
                ],
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __('Quantidade de Posts', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 20,
                'condition' => [
                    'selection_type!' => 'manual',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Content Section - Carrossel
        // ========================================
        $this->start_controls_section(
            'carousel_section',
            [
                'label' => __('Carrossel', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'carousel_space_between',
            [
                'label' => __('Espaço entre Slides', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
            ]
        );

        $this->add_control(
            'carousel_loop',
            [
                'label' => __('Loop Infinito', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => '',
            ]
        );

        $this->add_control(
            'carousel_autoplay',
            [
                'label' => __('Autoplay', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => '',
            ]
        );

        $this->add_control(
            'carousel_autoplay_speed',
            [
                'label' => __('Velocidade do Autoplay (ms)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5000,
                'min' => 1000,
                'max' => 10000,
                'step' => 500,
                'condition' => [
                    'carousel_autoplay' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Content Section - Título da Seção
        // ========================================
        $this->start_controls_section(
            'title_section',
            [
                'label' => __('Título da Seção', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_section_title',
            [
                'label' => __('Mostrar Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Os 10 artigos mais lidos', 'blog-pda'),
                'label_block' => true,
                'condition' => [
                    'show_section_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_tag',
            [
                'label' => __('Tag HTML', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'p' => 'P',
                ],
                'condition' => [
                    'show_section_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_title_line',
            [
                'label' => __('Mostrar Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
                'condition' => [
                    'show_section_title' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Content Section - Exibição
        // ========================================
        $this->start_controls_section(
            'display_section',
            [
                'label' => __('Exibição', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'image_ratio',
            [
                'label' => __('Proporção da Imagem', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '1-1',
                'options' => [
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1 (Quadrado)',
                    '3-4' => '3:4',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Título
        // ========================================
        $this->start_controls_section(
            'style_title_section',
            [
                'label' => __('Estilo do Título', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_section_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'section_title_color',
            [
                'label' => __('Cor do Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1F1F1F',
                'selectors' => [
                    '{{WRAPPER}} .bpw-section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'section_title_typography',
                'label' => __('Tipografia', 'blog-pda'),
                'selector' => '{{WRAPPER}} .bpw-section-title',
            ]
        );

        $this->add_control(
            'section_title_margin',
            [
                'label' => __('Margem Inferior', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 32,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-section-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'title_line_heading',
            [
                'label' => __('Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'show_title_line' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_line_color',
            [
                'label' => __('Cor da Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-title-line' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_title_line' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_line_width',
            [
                'label' => __('Largura da Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 60,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-title-line' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_title_line' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_line_height',
            [
                'label' => __('Espessura da Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-title-line' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_title_line' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_line_spacing',
            [
                'label' => __('Espaço entre Título e Linha', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 12,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-title-line' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_title_line' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Carrossel
        // ========================================
        $this->start_controls_section(
            'style_carousel_section',
            [
                'label' => __('Estilo do Carrossel', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius dos Cards', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-overlay' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bpw-card-image' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bpw-card-overlay-content' => 'border-radius: 0 0 {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_nav_color',
            [
                'label' => __('Cor do Botão Próximo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#E87722',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-next' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Cor do Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-overlay .bpw-card-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get posts based on settings
     */
    private function get_posts($settings) {
        $selection_type = $settings['selection_type'];
        
        $args = [
            'post_type' => 'blog_post',
            'post_status' => 'publish',
        ];

        switch ($selection_type) {
            case 'manual':
                $selected_posts = $settings['selected_posts'];
                if (empty($selected_posts)) {
                    return [];
                }
                $args['post__in'] = $selected_posts;
                $args['orderby'] = 'post__in';
                $args['posts_per_page'] = count($selected_posts);
                break;

            case 'category':
                $selected_categories = $settings['selected_categories'];
                if (!empty($selected_categories)) {
                    $args['tax_query'] = [
                        [
                            'taxonomy' => 'blog_category',
                            'field' => 'term_id',
                            'terms' => $selected_categories,
                        ],
                    ];
                }
                $args['posts_per_page'] = $settings['posts_count'];
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;

            case 'recent':
                $args['posts_per_page'] = $settings['posts_count'];
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;

            case 'popular':
                $args['posts_per_page'] = $settings['posts_count'];
                $args['meta_key'] = 'blog_post_views';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }

        return get_posts($args);
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $posts = $this->get_posts($settings);
        
        if (empty($posts)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="bpw-no-posts" style="padding: 40px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #666;">';
                echo '<p style="margin: 0; font-size: 16px;">' . __('Nenhum post selecionado ou encontrado.', 'blog-pda') . '</p>';
                echo '<p style="margin: 10px 0 0; font-size: 14px; opacity: 0.7;">' . __('Configure o widget para exibir posts do blog.', 'blog-pda') . '</p>';
                echo '</div>';
            }
            return;
        }

        $image_ratio = $settings['image_ratio'] ?? '1-1';
        
        $widget_id = $this->get_id();
        $space_between = $settings['carousel_space_between']['size'] ?? 24;
        $loop = $settings['carousel_loop'] === 'yes';
        $autoplay = $settings['carousel_autoplay'] === 'yes';
        $autoplay_speed = $settings['carousel_autoplay_speed'] ?? 5000;
        $posts_count = count($posts);
        
        // Configurações do título
        $show_title = $settings['show_section_title'] === 'yes';
        $section_title = $settings['section_title'] ?? '';
        $title_tag = $settings['title_tag'] ?? 'h2';
        $show_line = $settings['show_title_line'] === 'yes';
        ?>
        
        <div class="bpw-widget bpw-widget-carousel">
            <?php if ($show_title && !empty($section_title)) : ?>
            <div class="bpw-section-header">
                <<?php echo esc_html($title_tag); ?> class="bpw-section-title"><?php echo esc_html($section_title); ?></<?php echo esc_html($title_tag); ?>>
                <?php if ($show_line) : ?>
                <div class="bpw-title-line"></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="bpw-carousel-wrapper">
                <div class="swiper bpw-carousel" id="bpw-carousel-<?php echo esc_attr($widget_id); ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($posts as $post) : ?>
                        <div class="swiper-slide">
                            <article class="bpw-card bpw-card-overlay">
                                <a href="<?php echo get_permalink($post->ID); ?>" class="bpw-card-link">
                                    <?php if (has_post_thumbnail($post->ID)) : ?>
                                    <div class="bpw-card-image bpw-ratio-<?php echo esc_attr($image_ratio); ?>">
                                        <?php echo get_the_post_thumbnail($post->ID, 'medium_large'); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="bpw-card-overlay-content">
                                        <h3 class="bpw-card-title"><?php echo esc_html($post->post_title); ?></h3>
                                    </div>
                                </a>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button class="bpw-carousel-next" id="bpw-next-<?php echo esc_attr($widget_id); ?>" aria-label="<?php _e('Próximo', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            
            <script>
            (function() {
                function initBPWCarousel<?php echo esc_js($widget_id); ?>() {
                    if (typeof Swiper === 'undefined') {
                        setTimeout(initBPWCarousel<?php echo esc_js($widget_id); ?>, 100);
                        return;
                    }
                    
                    var swiperEl = document.getElementById('bpw-carousel-<?php echo esc_js($widget_id); ?>');
                    var nextBtn = document.getElementById('bpw-next-<?php echo esc_js($widget_id); ?>');
                    
                    var swiper = new Swiper('#bpw-carousel-<?php echo esc_js($widget_id); ?>', {
                        slidesPerView: 'auto',
                        spaceBetween: <?php echo intval($space_between); ?>,
                        loop: <?php echo $loop ? 'true' : 'false'; ?>,
                        <?php if ($autoplay) : ?>
                        autoplay: {
                            delay: <?php echo intval($autoplay_speed); ?>,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true,
                        },
                        <?php endif; ?>
                        navigation: {
                            nextEl: '#bpw-next-<?php echo esc_js($widget_id); ?>',
                        },
                        on: {
                            init: function() {
                                checkScrollable(this);
                            },
                            resize: function() {
                                checkScrollable(this);
                            }
                        }
                    });
                    
                    function checkScrollable(swiperInstance) {
                        if (!swiperInstance || !swiperInstance.wrapperEl) return;
                        
                        var wrapper = swiperInstance.wrapperEl;
                        var container = swiperInstance.el;
                        
                        // Calcular se há mais slides do que cabem na tela
                        var totalSlidesWidth = 0;
                        var slides = swiperInstance.slides;
                        if (slides && slides.length > 0) {
                            for (var i = 0; i < slides.length; i++) {
                                totalSlidesWidth += slides[i].offsetWidth + <?php echo intval($space_between); ?>;
                            }
                        }
                        
                        var containerWidth = container.clientWidth;
                        var isScrollable = totalSlidesWidth > containerWidth;
                        
                        console.log('BPW Carousel Debug:', {
                            totalSlidesWidth: totalSlidesWidth,
                            containerWidth: containerWidth,
                            isScrollable: isScrollable,
                            slidesCount: slides ? slides.length : 0
                        });
                        
                        if (nextBtn) {
                            nextBtn.style.display = isScrollable ? 'flex' : 'none';
                            nextBtn.style.visibility = isScrollable ? 'visible' : 'hidden';
                        }
                    }
                }
                
                // Inicializar imediatamente e também no load para garantir
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    setTimeout(initBPWCarousel<?php echo esc_js($widget_id); ?>, 50);
                } else {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(initBPWCarousel<?php echo esc_js($widget_id); ?>, 50);
                    });
                }
                // Backup: também no window load
                window.addEventListener('load', function() {
                    setTimeout(initBPWCarousel<?php echo esc_js($widget_id); ?>, 100);
                });
            })();
            </script>
        </div>
        
        <style>
        /* Blog Posts Widget - Carrossel */
        .bpw-widget {
            width: 100%;
        }
        
        /* Section Title Styles */
        .bpw-section-header {
            padding-left: calc((100% - 1200px) / 2 + 24px);
            padding-right: 24px;
            margin-bottom: 32px;
        }
        
        .bpw-section-title {
            margin: 0;
            padding: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.3;
            color: #1F1F1F;
        }
        
        .bpw-title-line {
            width: 60px;
            height: 4px;
            background-color: #702F8A;
            margin-top: 12px;
            border-radius: 2px;
        }
        
        /* Carousel Styles - Grudado à direita */
        .bpw-carousel-wrapper {
            position: relative;
            padding-left: calc((100% - 1200px) / 2 + 24px);
            padding-right: 0;
            overflow: visible;
        }
        
        .bpw-carousel {
            overflow: visible;
            padding: 10px 0;
        }
        
        .bpw-carousel .swiper-wrapper {
            align-items: stretch;
        }
        
        .bpw-carousel .swiper-slide {
            height: auto;
            width: auto;
        }
        
        /* Overlay Card Style for Carousel */
        .bpw-card-overlay {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            width: 280px;
            min-width: 260px;
            max-width: 320px;
        }
        
        .bpw-card-overlay .bpw-card-link {
            display: block;
            position: relative;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        
        .bpw-card-overlay .bpw-card-link:hover {
            transform: translateY(-4px);
        }
        
        .bpw-card-overlay .bpw-card-image {
            position: relative;
            width: 100%;
            height: 280px;
            border-radius: 16px;
            overflow: hidden;
            background: #F5F5F5;
        }
        
        .bpw-card-overlay .bpw-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .bpw-card-overlay:hover .bpw-card-image img {
            transform: scale(1.05);
        }
        
        /* Image Ratios */
        .bpw-ratio-16-9 {
            aspect-ratio: 16 / 9;
            height: auto !important;
        }
        
        .bpw-ratio-4-3 {
            aspect-ratio: 4 / 3;
            height: auto !important;
        }
        
        .bpw-ratio-1-1 {
            aspect-ratio: 1 / 1;
            height: auto !important;
        }
        
        .bpw-ratio-3-4 {
            aspect-ratio: 3 / 4;
            height: auto !important;
        }
        
        .bpw-card-overlay-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 60px 16px 16px 16px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            border-radius: 0 0 16px 16px;
        }
        
        .bpw-card-overlay .bpw-card-title {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .bpw-carousel-next {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E87722;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .bpw-carousel-next:hover {
            background: #D06A1D;
            transform: translateY(-50%) scale(1.05);
        }
        
        .bpw-carousel-next svg {
            width: 24px;
            height: 24px;
            stroke-width: 2.5;
        }
        
        @media (max-width: 1248px) {
            .bpw-carousel-wrapper {
                padding-left: 24px;
            }
        }
        
        @media (max-width: 767px) {
            .bpw-carousel-wrapper {
                padding-left: 16px;
            }
            
            .bpw-carousel-next {
                width: 40px;
                height: 40px;
                right: 10px;
            }
            
            .bpw-carousel-next svg {
                width: 20px;
                height: 20px;
            }
            
            .bpw-card-overlay {
                width: 240px;
                min-width: 220px;
            }
            
            .bpw-card-overlay .bpw-card-image {
                height: 240px;
            }
            
            .bpw-card-overlay .bpw-card-title {
                font-size: 13px;
            }
            
            .bpw-card-overlay-content {
                padding: 50px 12px 12px 12px;
            }
        }
        </style>
        <?php
    }
}
