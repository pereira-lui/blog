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
                'label' => __('Selecionar Posts', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_blog_posts_options(),
                'default' => [],
                'label_block' => true,
                'condition' => [
                    'selection_type' => 'manual',
                ],
                'description' => __('Selecione os posts que deseja exibir. A ordem de seleção será respeitada.', 'blog-pda'),
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
                'default' => 3,
                'min' => 1,
                'max' => 12,
                'condition' => [
                    'selection_type!' => 'manual',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Content Section - Layout
        // ========================================
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Layout', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout_type',
            [
                'label' => __('Tipo de Layout', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Grid', 'blog-pda'),
                    'carousel' => __('Carrossel', 'blog-pda'),
                    'list' => __('Lista', 'blog-pda'),
                    'featured' => __('Destaque + Grid', 'blog-pda'),
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Colunas', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
                'condition' => [
                    'layout_type' => ['grid', 'featured'],
                ],
            ]
        );

        $this->add_responsive_control(
            'slides_per_view',
            [
                'label' => __('Slides por Visualização', 'blog-pda'),
                'description' => __('Use valores decimais (ex: 1.5, 2.5) para mostrar parte do próximo slide', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'min' => 1,
                'max' => 6,
                'step' => 0.1,
                'condition' => [
                    'layout_type' => 'carousel',
                ],
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
                'condition' => [
                    'layout_type' => 'carousel',
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
                'default' => 'yes',
                'condition' => [
                    'layout_type' => 'carousel',
                ],
            ]
        );

        $this->add_control(
            'carousel_autoplay',
            [
                'label' => __('Autoplay', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
                'condition' => [
                    'layout_type' => 'carousel',
                ],
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
                    'layout_type' => 'carousel',
                    'carousel_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'carousel_navigation',
            [
                'label' => __('Setas de Navegação', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
                'condition' => [
                    'layout_type' => 'carousel',
                ],
            ]
        );

        $this->add_control(
            'carousel_pagination',
            [
                'label' => __('Paginação (Dots)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
                'condition' => [
                    'layout_type' => 'carousel',
                ],
            ]
        );

        $this->add_control(
            'show_image',
            [
                'label' => __('Mostrar Imagem', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'image_ratio',
            [
                'label' => __('Proporção da Imagem', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '16-9',
                'options' => [
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1',
                    '3-4' => '3:4',
                ],
                'condition' => [
                    'show_image' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_category',
            [
                'label' => __('Mostrar Categoria', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_date',
            [
                'label' => __('Mostrar Data', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label' => __('Mostrar Resumo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label' => __('Tamanho do Resumo (palavras)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 15,
                'min' => 5,
                'max' => 50,
                'condition' => [
                    'show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_read_more',
            [
                'label' => __('Mostrar Botão "Leia Mais"', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'no',
            ]
        );

        $this->add_control(
            'read_more_text',
            [
                'label' => __('Texto do Botão', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Leia Mais', 'blog-pda'),
                'condition' => [
                    'show_read_more' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Content Section - Header
        // ========================================
        $this->start_controls_section(
            'header_section',
            [
                'label' => __('Cabeçalho', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_header',
            [
                'label' => __('Mostrar Cabeçalho', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'no',
            ]
        );

        $this->add_control(
            'header_title',
            [
                'label' => __('Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Posts do Blog', 'blog-pda'),
                'label_block' => true,
                'condition' => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_view_all',
            [
                'label' => __('Mostrar "Ver Todos"', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'blog-pda'),
                'label_off' => __('Não', 'blog-pda'),
                'default' => 'yes',
                'condition' => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'view_all_text',
            [
                'label' => __('Texto "Ver Todos"', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Ver todos os posts', 'blog-pda'),
                'condition' => [
                    'show_header' => 'yes',
                    'show_view_all' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Card
        // ========================================
        $this->start_controls_section(
            'style_card_section',
            [
                'label' => __('Card', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg_color',
            [
                'label' => __('Cor de Fundo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'blog-pda'),
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
                    '{{WRAPPER}} .bpw-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bpw-card-image' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'label' => __('Sombra', 'blog-pda'),
                'selector' => '{{WRAPPER}} .bpw-card',
            ]
        );

        $this->add_control(
            'card_gap',
            [
                'label' => __('Espaçamento entre Cards', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bpw-grid' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bpw-list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Typography
        // ========================================
        $this->start_controls_section(
            'style_typography_section',
            [
                'label' => __('Tipografia', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Cor do Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1F1F1F',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'card_title_typography',
                'label' => __('Tipografia do Título', 'blog-pda'),
                'selector' => '{{WRAPPER}} .bpw-card-title',
            ]
        );

        $this->add_control(
            'excerpt_color',
            [
                'label' => __('Cor do Resumo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'date_color',
            [
                'label' => __('Cor da Data', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#999999',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-date' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_color',
            [
                'label' => __('Cor da Categoria', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-category' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_bg_color',
            [
                'label' => __('Fundo da Categoria', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(112, 47, 138, 0.1)',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-category' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Header
        // ========================================
        $this->start_controls_section(
            'style_header_section',
            [
                'label' => __('Cabeçalho', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'header_title_color',
            [
                'label' => __('Cor do Título', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1F1F1F',
                'selectors' => [
                    '{{WRAPPER}} .bpw-header-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'header_title_typography',
                'label' => __('Tipografia do Título', 'blog-pda'),
                'selector' => '{{WRAPPER}} .bpw-header-title',
            ]
        );

        $this->add_control(
            'view_all_color',
            [
                'label' => __('Cor "Ver Todos"', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-view-all' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Read More
        // ========================================
        $this->start_controls_section(
            'style_read_more_section',
            [
                'label' => __('Botão Leia Mais', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'read_more_color',
            [
                'label' => __('Cor do Texto', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-read-more' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'read_more_bg_color',
            [
                'label' => __('Cor de Fundo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .bpw-card-read-more' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========================================
        // Style Section - Carousel
        // ========================================
        $this->start_controls_section(
            'style_carousel_section',
            [
                'label' => __('Carrossel', 'blog-pda'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'layout_type' => 'carousel',
                ],
            ]
        );

        $this->add_control(
            'carousel_nav_color',
            [
                'label' => __('Cor das Setas', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-prev, {{WRAPPER}} .bpw-carousel-next' => 'color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_nav_bg_color',
            [
                'label' => __('Fundo das Setas', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-prev, {{WRAPPER}} .bpw-carousel-next' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_nav_hover_color',
            [
                'label' => __('Cor das Setas (Hover)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-prev:hover, {{WRAPPER}} .bpw-carousel-next:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_nav_hover_bg_color',
            [
                'label' => __('Fundo das Setas (Hover)', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-prev:hover, {{WRAPPER}} .bpw-carousel-next:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots_color',
            [
                'label' => __('Cor dos Dots', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#E0E0E0',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots_active_color',
            [
                'label' => __('Cor do Dot Ativo', 'blog-pda'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#702F8A',
                'selectors' => [
                    '{{WRAPPER}} .bpw-carousel-pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
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

        $layout_type = $settings['layout_type'];
        $show_image = $settings['show_image'] === 'yes';
        $show_category = $settings['show_category'] === 'yes';
        $show_date = $settings['show_date'] === 'yes';
        $show_excerpt = $settings['show_excerpt'] === 'yes';
        $excerpt_length = $settings['excerpt_length'];
        $show_read_more = $settings['show_read_more'] === 'yes';
        $read_more_text = $settings['read_more_text'];
        $image_ratio = $settings['image_ratio'];
        
        $show_header = $settings['show_header'] === 'yes';
        $header_title = $settings['header_title'];
        $show_view_all = $settings['show_view_all'] === 'yes';
        $view_all_text = $settings['view_all_text'];
        $view_all_link = get_post_type_archive_link('blog_post');
        ?>
        
        <div class="bpw-widget" data-layout="<?php echo esc_attr($layout_type); ?>">
            
            <?php if ($show_header) : ?>
            <header class="bpw-header">
                <h2 class="bpw-header-title"><?php echo esc_html($header_title); ?></h2>
                <?php if ($show_view_all && $view_all_link) : ?>
                <a href="<?php echo esc_url($view_all_link); ?>" class="bpw-view-all">
                    <?php echo esc_html($view_all_text); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                <?php endif; ?>
            </header>
            <?php endif; ?>
            
            <?php if ($layout_type === 'featured' && count($posts) > 0) : 
                $featured_post = array_shift($posts);
                $featured_categories = get_the_terms($featured_post->ID, 'blog_category');
            ?>
            <div class="bpw-featured">
                <article class="bpw-card bpw-card-featured">
                    <a href="<?php echo get_permalink($featured_post->ID); ?>" class="bpw-card-link">
                        <?php if ($show_image && has_post_thumbnail($featured_post->ID)) : ?>
                        <div class="bpw-card-image bpw-ratio-<?php echo esc_attr($image_ratio); ?>">
                            <?php echo get_the_post_thumbnail($featured_post->ID, 'large'); ?>
                            <?php if ($show_category && $featured_categories && !is_wp_error($featured_categories)) : ?>
                            <span class="bpw-card-category"><?php echo esc_html($featured_categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="bpw-card-content">
                            <h3 class="bpw-card-title"><?php echo esc_html($featured_post->post_title); ?></h3>
                            <?php if ($show_date) : ?>
                            <span class="bpw-card-date"><?php echo get_the_date('d \d\e F \d\e Y', $featured_post->ID); ?></span>
                            <?php endif; ?>
                            <?php if ($show_excerpt) : ?>
                            <p class="bpw-card-excerpt"><?php echo wp_trim_words(get_the_excerpt($featured_post->ID), $excerpt_length * 2); ?></p>
                            <?php endif; ?>
                            <?php if ($show_read_more) : ?>
                            <span class="bpw-card-read-more"><?php echo esc_html($read_more_text); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($posts)) : ?>
            
            <?php if ($layout_type === 'carousel') : 
                $widget_id = $this->get_id();
                $slides_per_view = $settings['slides_per_view'] ?? '3';
                $slides_per_view_tablet = $settings['slides_per_view_tablet'] ?? '2';
                $slides_per_view_mobile = $settings['slides_per_view_mobile'] ?? '1';
                $space_between = $settings['carousel_space_between']['size'] ?? 24;
                $loop = $settings['carousel_loop'] === 'yes';
                $autoplay = $settings['carousel_autoplay'] === 'yes';
                $autoplay_speed = $settings['carousel_autoplay_speed'] ?? 5000;
                $show_nav = $settings['carousel_navigation'] === 'yes';
                $show_dots = $settings['carousel_pagination'] === 'yes';
            ?>
            <div class="bpw-carousel-wrapper">
                <div class="swiper bpw-carousel" id="bpw-carousel-<?php echo esc_attr($widget_id); ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($posts as $post) : 
                            $post_categories = get_the_terms($post->ID, 'blog_category');
                        ?>
                        <div class="swiper-slide">
                            <article class="bpw-card">
                                <a href="<?php echo get_permalink($post->ID); ?>" class="bpw-card-link">
                                    <?php if ($show_image && has_post_thumbnail($post->ID)) : ?>
                                    <div class="bpw-card-image bpw-ratio-<?php echo esc_attr($image_ratio); ?>">
                                        <?php echo get_the_post_thumbnail($post->ID, 'medium_large'); ?>
                                        <?php if ($show_category && $post_categories && !is_wp_error($post_categories)) : ?>
                                        <span class="bpw-card-category"><?php echo esc_html($post_categories[0]->name); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="bpw-card-content">
                                        <h3 class="bpw-card-title"><?php echo esc_html($post->post_title); ?></h3>
                                        <?php if ($show_date) : ?>
                                        <span class="bpw-card-date"><?php echo get_the_date('d \d\e F \d\e Y', $post->ID); ?></span>
                                        <?php endif; ?>
                                        <?php if ($show_excerpt) : ?>
                                        <p class="bpw-card-excerpt"><?php echo wp_trim_words(get_the_excerpt($post->ID), $excerpt_length); ?></p>
                                        <?php endif; ?>
                                        <?php if ($show_read_more) : ?>
                                        <span class="bpw-card-read-more"><?php echo esc_html($read_more_text); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($show_nav) : ?>
                <button class="bpw-carousel-prev" id="bpw-prev-<?php echo esc_attr($widget_id); ?>" aria-label="<?php _e('Anterior', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="bpw-carousel-next" id="bpw-next-<?php echo esc_attr($widget_id); ?>" aria-label="<?php _e('Próximo', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 6 15 12 9 18"></polyline>
                    </svg>
                </button>
                <?php endif; ?>
                
                <?php if ($show_dots) : ?>
                <div class="bpw-carousel-pagination" id="bpw-pagination-<?php echo esc_attr($widget_id); ?>"></div>
                <?php endif; ?>
            </div>
            
            <script>
            (function() {
                function initBPWCarousel() {
                    if (typeof Swiper === 'undefined') {
                        setTimeout(initBPWCarousel, 100);
                        return;
                    }
                    
                    new Swiper('#bpw-carousel-<?php echo esc_js($widget_id); ?>', {
                        slidesPerView: <?php echo floatval($slides_per_view_mobile); ?>,
                        spaceBetween: <?php echo intval($space_between); ?>,
                        loop: <?php echo $loop ? 'true' : 'false'; ?>,
                        <?php if ($autoplay) : ?>
                        autoplay: {
                            delay: <?php echo intval($autoplay_speed); ?>,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true,
                        },
                        <?php endif; ?>
                        <?php if ($show_nav) : ?>
                        navigation: {
                            nextEl: '#bpw-next-<?php echo esc_js($widget_id); ?>',
                            prevEl: '#bpw-prev-<?php echo esc_js($widget_id); ?>',
                        },
                        <?php endif; ?>
                        <?php if ($show_dots) : ?>
                        pagination: {
                            el: '#bpw-pagination-<?php echo esc_js($widget_id); ?>',
                            clickable: true,
                        },
                        <?php endif; ?>
                        breakpoints: {
                            768: {
                                slidesPerView: <?php echo floatval($slides_per_view_tablet); ?>,
                            },
                            1024: {
                                slidesPerView: <?php echo floatval($slides_per_view); ?>,
                            },
                        },
                    });
                }
                
                if (document.readyState === 'complete') {
                    initBPWCarousel();
                } else {
                    window.addEventListener('load', initBPWCarousel);
                }
            })();
            </script>
            
            <?php else : ?>
            <div class="bpw-<?php echo $layout_type === 'list' ? 'list' : 'grid'; ?>">
                <?php foreach ($posts as $post) : 
                    $post_categories = get_the_terms($post->ID, 'blog_category');
                ?>
                <article class="bpw-card">
                    <a href="<?php echo get_permalink($post->ID); ?>" class="bpw-card-link">
                        <?php if ($show_image && has_post_thumbnail($post->ID)) : ?>
                        <div class="bpw-card-image bpw-ratio-<?php echo esc_attr($image_ratio); ?>">
                            <?php echo get_the_post_thumbnail($post->ID, 'medium_large'); ?>
                            <?php if ($show_category && $post_categories && !is_wp_error($post_categories)) : ?>
                            <span class="bpw-card-category"><?php echo esc_html($post_categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="bpw-card-content">
                            <h3 class="bpw-card-title"><?php echo esc_html($post->post_title); ?></h3>
                            <?php if ($show_date) : ?>
                            <span class="bpw-card-date"><?php echo get_the_date('d \d\e F \d\e Y', $post->ID); ?></span>
                            <?php endif; ?>
                            <?php if ($show_excerpt) : ?>
                            <p class="bpw-card-excerpt"><?php echo wp_trim_words(get_the_excerpt($post->ID), $excerpt_length); ?></p>
                            <?php endif; ?>
                            <?php if ($show_read_more) : ?>
                            <span class="bpw-card-read-more"><?php echo esc_html($read_more_text); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
        </div>
        
        <style>
        /* Blog Posts Widget Grid Styles */
        .bpw-widget {
            width: 100%;
        }
        
        .bpw-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .bpw-header-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .bpw-view-all {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .bpw-view-all:hover {
            gap: 12px;
        }
        
        .bpw-view-all svg {
            transition: transform 0.3s ease;
        }
        
        .bpw-view-all:hover svg {
            transform: translateX(4px);
        }
        
        /* Grid Layout */
        .bpw-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        
        /* List Layout */
        .bpw-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .bpw-list .bpw-card {
            display: flex;
            flex-direction: row;
        }
        
        .bpw-list .bpw-card-link {
            display: flex;
            flex-direction: row;
            gap: 20px;
        }
        
        .bpw-list .bpw-card-image {
            width: 200px;
            min-width: 200px;
            border-radius: 12px;
        }
        
        .bpw-list .bpw-card-content {
            padding: 0;
        }
        
        /* Featured Layout */
        .bpw-featured {
            margin-bottom: 24px;
        }
        
        .bpw-card-featured {
            display: block;
        }
        
        .bpw-card-featured .bpw-card-image {
            height: 400px;
        }
        
        .bpw-card-featured .bpw-card-title {
            font-size: 28px;
        }
        
        .bpw-card-featured .bpw-card-excerpt {
            font-size: 16px;
        }
        
        /* Card Styles */
        .bpw-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        
        .bpw-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .bpw-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .bpw-card-image {
            position: relative;
            overflow: hidden;
            background: #f5f5f5;
        }
        
        .bpw-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .bpw-card:hover .bpw-card-image img {
            transform: scale(1.05);
        }
        
        /* Image Ratios */
        .bpw-ratio-16-9 {
            aspect-ratio: 16 / 9;
        }
        
        .bpw-ratio-4-3 {
            aspect-ratio: 4 / 3;
        }
        
        .bpw-ratio-1-1 {
            aspect-ratio: 1 / 1;
        }
        
        .bpw-ratio-3-4 {
            aspect-ratio: 3 / 4;
        }
        
        .bpw-card-category {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bpw-card-content {
            padding: 20px;
        }
        
        .bpw-card-title {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s ease;
        }
        
        .bpw-card:hover .bpw-card-title {
            color: #702F8A;
        }
        
        .bpw-card-date {
            display: block;
            font-size: 13px;
            margin-bottom: 10px;
            opacity: 0.7;
        }
        
        .bpw-card-excerpt {
            margin: 0 0 12px;
            font-size: 14px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .bpw-card-read-more {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .bpw-card:hover .bpw-card-read-more {
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .bpw-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .bpw-card-featured .bpw-card-image {
                height: 300px;
            }
        }
        
        @media (max-width: 767px) {
            .bpw-grid {
                grid-template-columns: 1fr;
            }
            
            .bpw-list .bpw-card-link {
                flex-direction: column;
            }
            
            .bpw-list .bpw-card-image {
                width: 100%;
                min-width: auto;
            }
            
            .bpw-card-featured .bpw-card-image {
                height: 200px;
            }
            
            .bpw-card-featured .bpw-card-title {
                font-size: 22px;
            }
            
            .bpw-header-title {
                font-size: 24px;
            }
        }
        
        /* Carousel Styles */
        .bpw-carousel-wrapper {
            position: relative;
            padding: 0 50px;
        }
        
        .bpw-carousel {
            overflow: hidden;
        }
        
        .bpw-carousel .swiper-slide {
            height: auto;
        }
        
        .bpw-carousel .bpw-card {
            height: 100%;
        }
        
        .bpw-carousel-prev,
        .bpw-carousel-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 2px solid #702F8A;
            border-radius: 50%;
            color: #702F8A;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .bpw-carousel-prev {
            left: 0;
        }
        
        .bpw-carousel-next {
            right: 0;
        }
        
        .bpw-carousel-prev:hover,
        .bpw-carousel-next:hover {
            background: #702F8A;
            color: #fff;
            border-color: #702F8A;
        }
        
        .bpw-carousel-prev svg,
        .bpw-carousel-next svg {
            width: 20px;
            height: 20px;
        }
        
        .bpw-carousel-pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .bpw-carousel-pagination .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: #E0E0E0;
            border-radius: 50%;
            opacity: 1;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .bpw-carousel-pagination .swiper-pagination-bullet-active {
            background: #702F8A;
            transform: scale(1.2);
        }
        
        @media (max-width: 767px) {
            .bpw-carousel-wrapper {
                padding: 0 40px;
            }
            
            .bpw-carousel-prev,
            .bpw-carousel-next {
                width: 36px;
                height: 36px;
            }
            
            .bpw-carousel-prev svg,
            .bpw-carousel-next svg {
                width: 16px;
                height: 16px;
            }
        }
        </style>
        <?php
    }
}
