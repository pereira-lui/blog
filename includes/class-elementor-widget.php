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
            
            <!-- Floating Image -->
            <img class="blog-pda-preview-img" id="blog-pda-preview-<?php echo esc_attr($widget_id); ?>" src="" alt="">
        </div>
        
        <style>
            #blog-pda-widget-<?php echo esc_attr($widget_id); ?> {
                position: relative !important;
                font-family: "Neurial Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
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
            
            #blog-pda-preview-<?php echo esc_attr($widget_id); ?> {
                position: fixed !important;
                width: <?php echo $image_width; ?>px !important;
                height: <?php echo $image_height; ?>px !important;
                object-fit: cover !important;
                border-left: <?php echo $border_width; ?>px solid var(--blog-pda-accent, #00AC50) !important;
                opacity: 0 !important;
                transition: opacity 0.15s ease !important;
                pointer-events: none !important;
                z-index: 99999 !important;
            }
            
            #blog-pda-preview-<?php echo esc_attr($widget_id); ?>.blog-pda-active {
                opacity: 1 !important;
            }
            
            @media (max-width: 1024px) {
                #blog-pda-preview-<?php echo esc_attr($widget_id); ?> {
                    display: none !important;
                }
            }
        </style>
        
        <script>
        (function(){
            const widget = document.getElementById('blog-pda-widget-<?php echo esc_js($widget_id); ?>');
            const previewImg = document.getElementById('blog-pda-preview-<?php echo esc_js($widget_id); ?>');
            const postsList = widget.querySelector('.blog-pda-posts-list');
            
            if (!widget || !previewImg || !postsList) return;
            
            const imageWidth = <?php echo $image_width; ?>;
            const imageHeight = <?php echo $image_height; ?>;
            const offsetX = 20;
            const offsetY = 20;
            
            let activeItem = null;
            let mouseX = 0;
            let mouseY = 0;
            
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
            
            function positionImage(e) {
                if (e) {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                }
                
                // Calcular posição - à esquerda do cursor
                let left = mouseX - imageWidth - offsetX;
                let top = mouseY - (imageHeight / 2);
                
                // Se não couber à esquerda, mostra à direita
                if (left < 10) {
                    left = mouseX + offsetX;
                }
                
                // Manter dentro da tela verticalmente
                if (top < 10) top = 10;
                if (top + imageHeight > window.innerHeight - 10) {
                    top = window.innerHeight - imageHeight - 10;
                }
                
                previewImg.style.left = left + 'px';
                previewImg.style.top = top + 'px';
            }
            
            function showImage(item, e) {
                if (!item) return;
                
                const imageUrl = item.dataset.image;
                const color = item.dataset.color;
                
                if (!imageUrl) return;
                
                // Limpar item anterior se existir
                if (activeItem && activeItem !== item) {
                    activeItem.classList.remove('blog-pda-active');
                    activeItem.style.removeProperty('--blog-pda-accent');
                }
                
                previewImg.style.setProperty('--blog-pda-accent', color);
                item.style.setProperty('--blog-pda-accent', color);
                
                if (previewImg.src !== imageUrl) {
                    previewImg.classList.remove('blog-pda-active');
                    const loader = new Image();
                    loader.onload = function() {
                        previewImg.src = imageUrl;
                        positionImage(e);
                        previewImg.classList.add('blog-pda-active');
                    };
                    loader.src = imageUrl;
                } else {
                    positionImage(e);
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
                    showImage(this, e);
                });
                
                item.addEventListener('mousemove', function(e) {
                    if (activeItem === this) {
                        positionImage(e);
                    }
                });
            });
            
            postsList.addEventListener('mouseleave', hideImage);
        })();
        </script>
        <?php
    }
}
