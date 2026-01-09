<?php
/**
 * Template para Arquivo do Blog
 * Layout baseado no wireframe
 * 
 * @package Blog_PDA
 */

get_header();

// Configurações
$posts_per_page = 6;

// Buscar posts para o hero (1 grande + 3 pequenos)
$hero_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
];

// Verificar se há post em destaque
$featured_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 1,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_blog_featured',
            'value' => '1',
            'compare' => '='
        ]
    ]
];
$featured_query = new WP_Query($featured_args);
$featured_id = null;

if ($featured_query->have_posts()) {
    $featured_query->the_post();
    $featured_id = get_the_ID();
    wp_reset_postdata();
}

$hero_query = new WP_Query($hero_args);
$hero_posts = [];
if ($hero_query->have_posts()) {
    while ($hero_query->have_posts()) {
        $hero_query->the_post();
        $hero_posts[] = get_the_ID();
    }
    wp_reset_postdata();
}

// Se tiver post em destaque, ele vai primeiro
if ($featured_id && !in_array($featured_id, $hero_posts)) {
    array_unshift($hero_posts, $featured_id);
    $hero_posts = array_slice($hero_posts, 0, 4);
}

// Buscar posts mais lidos (ordenado por visualizações)
$popular_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'meta_key' => 'blog_post_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
];
$popular_query = new WP_Query($popular_args);

// Fallback: se não tiver posts com views, busca os mais recentes
if ($popular_query->post_count < 3) {
    $popular_args = [
        'post_type' => 'blog_post',
        'posts_per_page' => 10,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    $popular_query = new WP_Query($popular_args);
}
?>

<!-- Cabeçalho do Blog -->
<header class="blog-pda-header">
    <div class="blog-header-container">
        <img src="<?php echo BLOG_PDA_PLUGIN_URL; ?>assets/imgs/Cabecalho-1.webp" alt="Parque das Aves - Nossa Mata Atlântica" class="blog-header-logo">
    </div>
</header>

<main id="blog-main" class="blog-pda-archive">
    
    <!-- Hero Section -->
    <section class="blog-hero">
        <div class="blog-container">
            <h1 class="blog-hero-title"><?php _e('O blog mais querido da Mata Atlântica', 'blog-pda'); ?></h1>
            
            <?php if (!empty($hero_posts)) : ?>
            <div class="blog-hero-grid">
                <!-- Post Principal (Grande) -->
                <?php 
                $main_post_id = $hero_posts[0];
                $main_post = get_post($main_post_id);
                $main_categories = get_the_terms($main_post_id, 'blog_category');
                ?>
                <article class="blog-hero-main">
                    <a href="<?php echo get_permalink($main_post_id); ?>" class="blog-hero-main-link">
                        <div class="blog-hero-main-image">
                            <?php if (has_post_thumbnail($main_post_id)) : ?>
                                <?php echo get_the_post_thumbnail($main_post_id, 'large'); ?>
                            <?php endif; ?>
                        </div>
                        <div class="blog-hero-main-content">
                            <?php if ($main_categories && !is_wp_error($main_categories)) : ?>
                                <span class="blog-category-tag"><?php echo esc_html($main_categories[0]->name); ?></span>
                            <?php endif; ?>
                            <h2 class="blog-hero-main-title"><?php echo get_the_title($main_post_id); ?></h2>
                            <p class="blog-hero-main-excerpt"><?php echo wp_trim_words(get_the_excerpt($main_post_id), 20); ?></p>
                        </div>
                    </a>
                </article>
                
                <!-- Posts Secundários (3 pequenos) -->
                <div class="blog-hero-sidebar">
                    <?php 
                    $sidebar_posts = array_slice($hero_posts, 1, 3);
                    foreach ($sidebar_posts as $post_id) : 
                        $categories = get_the_terms($post_id, 'blog_category');
                    ?>
                    <article class="blog-hero-sidebar-item">
                        <a href="<?php echo get_permalink($post_id); ?>" class="blog-hero-sidebar-link">
                            <div class="blog-hero-sidebar-image">
                                <?php if (has_post_thumbnail($post_id)) : ?>
                                    <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="blog-hero-sidebar-content">
                                <?php if ($categories && !is_wp_error($categories)) : ?>
                                    <span class="blog-category-tag-small"><?php echo esc_html($categories[0]->name); ?></span>
                                <?php endif; ?>
                                <h3 class="blog-hero-sidebar-title"><?php echo get_the_title($post_id); ?></h3>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Os 10 Artigos Mais Lidos -->
    <?php if ($popular_query->have_posts()) : ?>
    <section class="blog-popular-section">
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Os 10 artigos mais lidos', 'blog-pda'); ?></h2>
            <div class="blog-popular-slider">
                <button class="blog-slider-prev" aria-label="<?php _e('Anterior', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>
                </button>
                <div class="blog-popular-track">
                    <?php while ($popular_query->have_posts()) : $popular_query->the_post(); ?>
                    <div class="blog-popular-item">
                        <a href="<?php the_permalink(); ?>" class="blog-popular-link">
                            <div class="blog-popular-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else : ?>
                                    <div class="blog-popular-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <h3 class="blog-popular-title"><?php the_title(); ?></h3>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button class="blog-slider-next" aria-label="<?php _e('Próximo', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,6 15,12 9,18"></polyline></svg>
                </button>
            </div>
        </div>
    </section>
    <?php wp_reset_postdata(); endif; ?>

    <!-- Todas as Publicações -->
    <section class="blog-all-posts-section">
        <div class="blog-all-posts-header">
            <div class="blog-container">
                <h2 class="blog-section-title-white">
                    <?php _e('Todas', 'blog-pda'); ?><br>
                    <?php _e('as publicações', 'blog-pda'); ?>
                </h2>
            </div>
        </div>
        <div class="blog-container">
            <div class="blog-posts-grid" id="blog-posts-grid">
                <?php
                $exclude_ids = $hero_posts;
                $all_posts_args = [
                    'post_type' => 'blog_post',
                    'posts_per_page' => $posts_per_page,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post__not_in' => $exclude_ids
                ];
                $all_posts_query = new WP_Query($all_posts_args);
                
                if ($all_posts_query->have_posts()) :
                    while ($all_posts_query->have_posts()) : $all_posts_query->the_post();
                        $categories = get_the_terms(get_the_ID(), 'blog_category');
                ?>
                <article class="blog-post-card">
                    <a href="<?php the_permalink(); ?>" class="blog-post-card-link">
                        <div class="blog-post-card-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large'); ?>
                            <?php endif; ?>
                            <?php if ($categories && !is_wp_error($categories)) : ?>
                            <span class="blog-post-card-category"><?php echo esc_html($categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="blog-post-card-content">
                            <h3 class="blog-post-card-title"><?php the_title(); ?></h3>
                        </div>
                    </a>
                </article>
                <?php 
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
            </div>
            
            <?php 
            $total_posts = wp_count_posts('blog_post')->publish;
            $shown_posts = count($hero_posts) + $posts_per_page;
            if ($total_posts > $shown_posts) : 
            ?>
            <div class="blog-load-more-wrapper">
                <button class="blog-load-more-btn" 
                        data-page="1" 
                        data-per-page="<?php echo $posts_per_page; ?>"
                        data-exclude="<?php echo implode(',', $exclude_ids); ?>">
                    <?php _e('Carregar mais', 'blog-pda'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Veja Também (Podcasts) -->
    <?php 
    $podcasts = get_option('blog_pda_podcasts', []);
    if (!empty($podcasts)) : 
    ?>
    <section class="blog-podcasts-section">
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Veja também', 'blog-pda'); ?></h2>
            <div class="blog-podcasts-grid">
                <?php 
                foreach ($podcasts as $index => $podcast) :
                    $audio_url = isset($podcast['audio_url']) ? $podcast['audio_url'] : '';
                    $link_url = isset($podcast['link_url']) ? $podcast['link_url'] : '';
                    $duration = isset($podcast['duration']) ? $podcast['duration'] : '';
                    $has_audio = !empty($audio_url);
                ?>
                <div class="blog-podcast-card" <?php if ($has_audio) : ?>data-audio="true"<?php endif; ?>>
                    <div class="blog-podcast-icon">
                        <?php if ($has_audio) : ?>
                        <button class="blog-podcast-play-btn" aria-label="<?php _e('Reproduzir', 'blog-pda'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                        </button>
                        <?php else : ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="blog-podcast-content">
                        <h4 class="blog-podcast-title"><?php echo esc_html($podcast['title']); ?></h4>
                        <p class="blog-podcast-subtitle">
                            <?php echo esc_html($podcast['subtitle']); ?>
                            <?php if ($duration) : ?>
                            <span class="blog-podcast-duration"><?php echo esc_html($duration); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($link_url) : ?>
                    <a href="<?php echo esc_url($link_url); ?>" class="blog-podcast-external" target="_blank" aria-label="<?php _e('Abrir em plataforma externa', 'blog-pda'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($has_audio) : ?>
                    <audio class="blog-podcast-audio" preload="none">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                    </audio>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Vídeos -->
    <?php 
    $videos = get_option('blog_pda_videos', []);
    if (!empty($videos)) : 
    ?>
    <section class="blog-videos-section">
        <div class="blog-container blog-container-videos">
            <h2 class="blog-section-title"><?php _e('Vídeos', 'blog-pda'); ?></h2>
        </div>
        <div class="blog-videos-wrapper">
            <div class="blog-videos-track">
                <?php foreach ($videos as $video) : 
                    $video_id = Blog_PDA::get_youtube_video_id($video['url']);
                ?>
                <div class="blog-video-card" data-video-id="<?php echo esc_attr($video_id); ?>">
                    <?php if (!empty($video['thumbnail'])) : ?>
                    <img src="<?php echo esc_url($video['thumbnail']); ?>" alt="<?php echo esc_attr($video['title'] ?? 'Video'); ?>" class="blog-video-thumbnail">
                    <?php else : ?>
                    <div class="blog-video-placeholder"></div>
                    <?php endif; ?>
                    <button class="blog-video-play" aria-label="<?php _e('Reproduzir vídeo', 'blog-pda'); ?>">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <?php if (!empty($video['title'])) : ?>
                    <span class="blog-video-title"><?php echo esc_html($video['title']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="blog-videos-next" aria-label="<?php _e('Próximo', 'blog-pda'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,6 15,12 9,18"></polyline></svg>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Modal de Vídeo -->
    <div id="blog-video-modal" class="blog-video-modal">
        <div class="blog-video-modal-overlay"></div>
        <div class="blog-video-modal-content">
            <button class="blog-video-modal-close" aria-label="<?php _e('Fechar', 'blog-pda'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="blog-video-modal-iframe">
                <iframe id="blog-video-iframe" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>

</main>

<?php get_footer(); ?>
