<?php
/**
 * Template para Post Individual do Blog
 * 
 * @package Blog_PDA
 */

get_header();

// Incrementar contador de visualizações
if (!is_admin() && !current_user_can('edit_posts')) {
    $views = (int) get_post_meta(get_the_ID(), 'blog_post_views', true);
    update_post_meta(get_the_ID(), 'blog_post_views', $views + 1);
}

// Dados do post
$categories = get_the_terms(get_the_ID(), 'blog_category');
$tags = get_the_terms(get_the_ID(), 'blog_tag');

// Posts relacionados
$related_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 3,
    'post_status' => 'publish',
    'post__not_in' => [get_the_ID()],
    'orderby' => 'rand'
];

if ($categories && !is_wp_error($categories)) {
    $cat_ids = wp_list_pluck($categories, 'term_id');
    $related_args['tax_query'] = [
        [
            'taxonomy' => 'blog_category',
            'field' => 'term_id',
            'terms' => $cat_ids
        ]
    ];
}
$related_query = new WP_Query($related_args);

// Posts mais lidos
$popular_args = [
    'post_type' => 'blog_post',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'post__not_in' => [get_the_ID()],
    'meta_key' => 'blog_post_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
];
$popular_query = new WP_Query($popular_args);

if ($popular_query->post_count < 5) {
    $popular_args = [
        'post_type' => 'blog_post',
        'posts_per_page' => 10,
        'post_status' => 'publish',
        'post__not_in' => [get_the_ID()],
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    $popular_query = new WP_Query($popular_args);
}
?>

<main id="blog-main" class="blog-pda-single">
    
    <?php while (have_posts()) : the_post(); ?>
    
    <!-- Header do Post -->
    <article class="blog-single-article">
        <header class="blog-single-header">
            <div class="blog-container">
                <!-- Breadcrumb -->
                <nav class="blog-breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php _e('Home', 'blog-pda'); ?></a>
                    <span class="blog-breadcrumb-sep">›</span>
                    <a href="<?php echo get_post_type_archive_link('blog_post'); ?>"><?php _e('Blog', 'blog-pda'); ?></a>
                    <span class="blog-breadcrumb-sep">›</span>
                    <span class="blog-breadcrumb-current"><?php the_title(); ?></span>
                </nav>
                
                <!-- Categorias -->
                <?php if ($categories && !is_wp_error($categories)) : ?>
                <div class="blog-single-categories">
                    <?php foreach ($categories as $cat) : ?>
                        <a href="<?php echo get_term_link($cat); ?>" class="blog-category-tag"><?php echo esc_html($cat->name); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Título -->
                <h1 class="blog-single-title"><?php the_title(); ?></h1>
                
                <!-- Meta -->
                <div class="blog-single-meta">
                    <span class="blog-meta-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <?php echo get_the_date('d \d\e F \d\e Y'); ?>
                    </span>
                    <span class="blog-meta-author">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <?php _e('Por', 'blog-pda'); ?> <?php the_author(); ?>
                    </span>
                    <span class="blog-meta-reading">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <?php echo blog_pda_reading_time(get_the_content()); ?>
                    </span>
                </div>
            </div>
        </header>
        
        <!-- Imagem Destacada -->
        <?php if (has_post_thumbnail()) : ?>
        <div class="blog-single-featured-image">
            <div class="blog-container-wide">
                <?php the_post_thumbnail('full'); ?>
                <p class="blog-featured-image-caption"><?php echo wp_strip_all_tags(get_the_excerpt()); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Player: Ouvir a Notícia (Text-to-Speech) -->
        <div class="blog-listen-section">
            <div class="blog-container blog-container-narrow">
                <div class="blog-listen-player" id="blog-tts-player">
                    <div class="blog-listen-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                    </div>
                    <div class="blog-listen-content">
                        <h4 class="blog-listen-title"><?php _e('Ouvir a notícia:', 'blog-pda'); ?> <?php the_title(); ?></h4>
                        <div class="blog-listen-controls">
                            <button class="blog-listen-play-btn" id="blog-tts-play" aria-label="<?php _e('Reproduzir', 'blog-pda'); ?>">
                                <svg class="play-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <svg class="pause-icon" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                                    <rect x="6" y="4" width="4" height="16"></rect>
                                    <rect x="14" y="4" width="4" height="16"></rect>
                                </svg>
                            </button>
                            <span class="blog-listen-time">
                                <span class="blog-listen-current" id="blog-tts-current">00:00</span> / <span class="blog-listen-duration" id="blog-tts-duration">--:--</span>
                            </span>
                        </div>
                        <div class="blog-listen-progress" id="blog-tts-progress-container">
                            <div class="blog-listen-progress-bar" id="blog-tts-progress"></div>
                            <div class="blog-listen-progress-handle"></div>
                        </div>
                    </div>
                </div>
                <!-- Hidden content for TTS -->
                <div id="blog-tts-content" style="display: none;">
                    <?php echo wp_strip_all_tags(get_the_content()); ?>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="blog-single-content">
            <div class="blog-container blog-container-narrow">
                <div class="blog-content-body">
                    <?php the_content(); ?>
                </div>
                
                <!-- Tags -->
                <?php if ($tags && !is_wp_error($tags)) : ?>
                <div class="blog-single-tags">
                    <span class="blog-tags-label"><?php _e('Tags:', 'blog-pda'); ?></span>
                    <?php foreach ($tags as $tag) : ?>
                        <a href="<?php echo get_term_link($tag); ?>" class="blog-tag"><?php echo esc_html($tag->name); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Compartilhar -->
                <div class="blog-single-share">
                    <span class="blog-share-label"><?php _e('Compartilhar:', 'blog-pda'); ?></span>
                    <div class="blog-share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                           target="_blank" class="blog-share-btn blog-share-facebook" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                           target="_blank" class="blog-share-btn blog-share-twitter" aria-label="Twitter">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                           target="_blank" class="blog-share-btn blog-share-whatsapp" aria-label="WhatsApp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" 
                           target="_blank" class="blog-share-btn blog-share-linkedin" aria-label="LinkedIn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                        </a>
                        <button class="blog-share-btn blog-share-copy" data-url="<?php echo get_permalink(); ?>" aria-label="<?php _e('Copiar link', 'blog-pda'); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Posts Relacionados -->
        <?php if ($related_query->have_posts()) : ?>
        <section class="blog-related-posts-section">
            <div class="blog-container">
                <h2 class="blog-section-title"><?php _e('Veja também', 'blog-pda'); ?></h2>
                <div class="blog-related-posts-grid">
                    <?php while ($related_query->have_posts()) : $related_query->the_post(); 
                        $rel_categories = get_the_terms(get_the_ID(), 'blog_category');
                    ?>
                    <article class="blog-post-card">
                        <a href="<?php the_permalink(); ?>" class="blog-post-card-link">
                            <?php if (has_post_thumbnail()) : ?>
                            <div class="blog-post-card-image">
                                <?php the_post_thumbnail('medium_large'); ?>
                                <?php if ($rel_categories && !is_wp_error($rel_categories)) : ?>
                                <div class="blog-post-card-categories">
                                    <?php foreach (array_slice($rel_categories, 0, 1) as $cat) : ?>
                                        <span class="blog-category-tag"><?php echo esc_html($cat->name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="blog-post-card-content">
                                <h3 class="blog-post-card-title"><?php the_title(); ?></h3>
                                <p class="blog-post-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 12); ?></p>
                            </div>
                        </a>
                    </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </article>
    
    <?php endwhile; ?>

    <!-- Veja Também - Links Externos -->
    <?php 
    $links = get_option('blog_pda_related_links', []);
    if (!empty($links)) : 
    ?>
    <section class="blog-external-links-section">
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
            <div class="blog-videos-slider">
                <button class="blog-videos-prev" aria-label="<?php _e('Anterior', 'blog-pda'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>
                </button>
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
        </div>
    </section>
    <?php endif; ?>

    <!-- Os 10 Artigos Mais Lidos -->
    <?php if ($popular_query->have_posts()) : ?>
    <section class="blog-popular-section blog-popular-section-bottom">
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
                    <?php endwhile; wp_reset_postdata(); ?>
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
    <?php endif; ?>

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

    <!-- Modal de Vídeo -->
    <div id="blog-video-modal" class="blog-video-modal" style="display: none;">
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
