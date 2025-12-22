<?php
/**
 * Template para Arquivo do Blog
 * 
 * @package Blog_PDA
 */

get_header();

// Configurações
$posts_per_page = 9;
$featured_post = null;

// Buscar post em destaque (mais recente ou fixado)
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

if (!$featured_query->have_posts()) {
    // Se não houver post fixado, pegar o mais recente
    $featured_args = [
        'post_type' => 'blog_post',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    $featured_query = new WP_Query($featured_args);
}

if ($featured_query->have_posts()) {
    $featured_query->the_post();
    $featured_post = get_the_ID();
    wp_reset_postdata();
}

// Buscar posts mais lidos
$popular_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'meta_key' => 'blog_post_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
];
$popular_query = new WP_Query($popular_args);

// Se não houver views suficientes, ordenar por data
if ($popular_query->post_count < 5) {
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

<main id="blog-main" class="blog-pda-archive">
    
    <!-- Hero Section -->
    <section class="blog-hero">
        <div class="blog-container">
            <h1 class="blog-hero-title"><?php _e('O blog mais querido da Mata Atlântica', 'blog-pda'); ?></h1>
            
            <?php if ($featured_post) : 
                $post = get_post($featured_post);
                setup_postdata($post);
                $categories = get_the_terms($featured_post, 'blog_category');
            ?>
            <div class="blog-featured-post">
                <div class="blog-featured-image">
                    <?php if (has_post_thumbnail($featured_post)) : ?>
                        <a href="<?php echo get_permalink($featured_post); ?>">
                            <?php echo get_the_post_thumbnail($featured_post, 'large'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="blog-featured-content">
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                        <div class="blog-featured-categories">
                            <?php foreach ($categories as $cat) : ?>
                                <a href="<?php echo get_term_link($cat); ?>" class="blog-category-tag"><?php echo esc_html($cat->name); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="blog-featured-title">
                        <a href="<?php echo get_permalink($featured_post); ?>"><?php echo get_the_title($featured_post); ?></a>
                    </h2>
                    <div class="blog-featured-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt($featured_post), 30); ?>
                    </div>
                    <div class="blog-featured-meta">
                        <span class="blog-meta-date"><?php echo get_the_date('d/m/Y', $featured_post); ?></span>
                        <span class="blog-meta-author"><?php _e('Por', 'blog-pda'); ?> <?php echo get_the_author_meta('display_name', get_post_field('post_author', $featured_post)); ?></span>
                    </div>
                    <a href="<?php echo get_permalink($featured_post); ?>" class="blog-featured-link"><?php _e('Leia mais', 'blog-pda'); ?></a>
                </div>
            </div>
            <?php wp_reset_postdata(); endif; ?>
        </div>
    </section>

    <!-- Os 10 Artigos Mais Lidos -->
    <?php if ($popular_query->have_posts()) : ?>
    <section class="blog-popular-section">
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Os 10 artigos mais lidos', 'blog-pda'); ?></h2>
            <div class="blog-popular-slider">
                <div class="blog-popular-track">
                    <?php while ($popular_query->have_posts()) : $popular_query->the_post(); ?>
                    <div class="blog-popular-item">
                        <a href="<?php the_permalink(); ?>" class="blog-popular-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="blog-popular-image">
                                    <?php the_post_thumbnail('medium'); ?>
                                </div>
                            <?php endif; ?>
                            <h3 class="blog-popular-title"><?php the_title(); ?></h3>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button class="blog-slider-prev" aria-label="<?php _e('Anterior', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>
                </button>
                <button class="blog-slider-next" aria-label="<?php _e('Próximo', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,6 15,12 9,18"></polyline></svg>
                </button>
            </div>
        </div>
    </section>
    <?php wp_reset_postdata(); endif; ?>

    <!-- Todas as Publicações -->
    <section class="blog-all-posts-section">
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Todas as publicações', 'blog-pda'); ?></h2>
            
            <div class="blog-posts-grid" id="blog-posts-grid">
                <?php
                $all_posts_args = [
                    'post_type' => 'blog_post',
                    'posts_per_page' => $posts_per_page,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post__not_in' => $featured_post ? [$featured_post] : []
                ];
                $all_posts_query = new WP_Query($all_posts_args);
                
                if ($all_posts_query->have_posts()) :
                    while ($all_posts_query->have_posts()) : $all_posts_query->the_post();
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
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
            </div>
            
            <?php 
            $total_posts = wp_count_posts('blog_post')->publish;
            $shown_posts = $featured_post ? $posts_per_page + 1 : $posts_per_page;
            if ($total_posts > $shown_posts) : 
            ?>
            <div class="blog-load-more-wrapper">
                <button class="blog-load-more-btn" 
                        data-page="1" 
                        data-per-page="<?php echo $posts_per_page; ?>"
                        data-exclude="<?php echo $featured_post ? $featured_post : ''; ?>">
                    <?php _e('Carregar mais', 'blog-pda'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Veja Também -->
    <?php 
    $links = get_option('blog_pda_related_links', []);
    if (!empty($links)) : 
    ?>
    <section class="blog-related-section">
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Veja também', 'blog-pda'); ?></h2>
            <div class="blog-related-links">
                <?php foreach ($links as $link) : ?>
                <a href="<?php echo esc_url($link['url']); ?>" class="blog-related-link" target="_blank">
                    <?php echo esc_html($link['label']); ?>
                </a>
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
        <div class="blog-container">
            <h2 class="blog-section-title"><?php _e('Vídeos', 'blog-pda'); ?></h2>
            <div class="blog-videos-grid">
                <?php foreach ($videos as $video) : ?>
                <div class="blog-video-item">
                    <div class="blog-video-wrapper">
                        <?php echo wp_oembed_get($video['url']); ?>
                    </div>
                    <?php if (!empty($video['title'])) : ?>
                    <h3 class="blog-video-title"><?php echo esc_html($video['title']); ?></h3>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
