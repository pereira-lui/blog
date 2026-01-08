/**
 * Blog PDA - JavaScript
 * 
 * @package Blog_PDA
 */

(function() {
    'use strict';

    // Wait for DOM
    document.addEventListener('DOMContentLoaded', function() {
        initSliders();
        initVideoSliders();
        initLoadMore();
        initCopyLink();
        initVideoModal();
    });

    /**
     * Initialize Popular Posts Sliders
     */
    function initSliders() {
        const sliders = document.querySelectorAll('.blog-popular-slider');
        
        sliders.forEach(function(slider) {
            const track = slider.querySelector('.blog-popular-track');
            const prevBtn = slider.querySelector('.blog-slider-prev');
            const nextBtn = slider.querySelector('.blog-slider-next');
            
            if (!track) return;
            
            const scrollAmount = 220; // item width + gap
            
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    track.scrollBy({
                        left: -scrollAmount * 2,
                        behavior: 'smooth'
                    });
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    track.scrollBy({
                        left: scrollAmount * 2,
                        behavior: 'smooth'
                    });
                });
            }
            
            // Update button visibility based on scroll position
            function updateButtons() {
                if (prevBtn) {
                    prevBtn.style.opacity = track.scrollLeft <= 0 ? '0.5' : '1';
                }
                if (nextBtn) {
                    const maxScroll = track.scrollWidth - track.clientWidth;
                    nextBtn.style.opacity = track.scrollLeft >= maxScroll - 10 ? '0.5' : '1';
                }
            }
            
            track.addEventListener('scroll', updateButtons);
            updateButtons();
            
            initDragScroll(track);
        });
    }

    /**
     * Initialize Video Sliders
     */
    function initVideoSliders() {
        const sliders = document.querySelectorAll('.blog-videos-slider');
        
        sliders.forEach(function(slider) {
            const track = slider.querySelector('.blog-videos-track');
            const prevBtn = slider.querySelector('.blog-videos-prev');
            const nextBtn = slider.querySelector('.blog-videos-next');
            
            if (!track) return;
            
            const scrollAmount = 216; // item width + gap
            
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    track.scrollBy({
                        left: -scrollAmount * 2,
                        behavior: 'smooth'
                    });
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    track.scrollBy({
                        left: scrollAmount * 2,
                        behavior: 'smooth'
                    });
                });
            }
            
            // Update button visibility based on scroll position
            function updateButtons() {
                if (prevBtn) {
                    prevBtn.style.opacity = track.scrollLeft <= 0 ? '0.5' : '1';
                }
                if (nextBtn) {
                    const maxScroll = track.scrollWidth - track.clientWidth;
                    nextBtn.style.opacity = track.scrollLeft >= maxScroll - 10 ? '0.5' : '1';
                }
            }
            
            track.addEventListener('scroll', updateButtons);
            updateButtons();
            
            initDragScroll(track);
        });
    }

    /**
     * Initialize Drag Scroll for tracks
     */
    function initDragScroll(track) {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        track.addEventListener('mousedown', function(e) {
            isDown = true;
            track.style.cursor = 'grabbing';
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
        });
        
        track.addEventListener('mouseleave', function() {
            isDown = false;
            track.style.cursor = 'grab';
        });
        
        track.addEventListener('mouseup', function() {
            isDown = false;
            track.style.cursor = 'grab';
        });
        
        track.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            const walk = (x - startX) * 2;
            track.scrollLeft = scrollLeft - walk;
        });
        
        track.style.cursor = 'grab';
    }

    /**
     * Initialize Video Modal
     */
    function initVideoModal() {
        const modal = document.getElementById('blog-video-modal');
        if (!modal) return;
        
        const iframe = document.getElementById('blog-video-iframe');
        const overlay = modal.querySelector('.blog-video-modal-overlay');
        const closeBtn = modal.querySelector('.blog-video-modal-close');
        
        // Click on video play buttons
        document.querySelectorAll('.blog-video-card').forEach(function(card) {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const videoId = this.dataset.videoId;
                if (!videoId) return;
                
                // Set iframe src
                iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
                
                // Show modal
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close modal
        function closeModal() {
            modal.style.display = 'none';
            iframe.src = '';
            document.body.style.overflow = '';
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }
        
        // Close with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
    }

    /**
     * Initialize Load More
     */
    function initLoadMore() {
        const loadMoreBtn = document.querySelector('.blog-load-more-btn');
        const postsGrid = document.getElementById('blog-posts-grid');
        
        if (!loadMoreBtn || !postsGrid) return;
        
        loadMoreBtn.addEventListener('click', function() {
            const btn = this;
            const page = parseInt(btn.dataset.page) + 1;
            const perPage = parseInt(btn.dataset.perPage);
            const exclude = btn.dataset.exclude;
            
            // Add loading state
            btn.classList.add('loading');
            btn.disabled = true;
            
            // AJAX request
            const formData = new FormData();
            formData.append('action', 'blog_pda_load_more');
            formData.append('page', page);
            formData.append('per_page', perPage);
            formData.append('exclude', exclude);
            formData.append('nonce', blogPdaVars.nonce);
            
            fetch(blogPdaVars.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data.html) {
                    // Append new posts
                    postsGrid.insertAdjacentHTML('beforeend', data.data.html);
                    
                    // Update page number
                    btn.dataset.page = page;
                    
                    // Hide button if no more posts
                    if (!data.data.hasMore) {
                        btn.style.display = 'none';
                    }
                } else {
                    btn.style.display = 'none';
                }
            })
            .catch(function(error) {
                console.error('Error loading more posts:', error);
            })
            .finally(function() {
                btn.classList.remove('loading');
                btn.disabled = false;
            });
        });
    }

    /**
     * Initialize Copy Link Button
     */
    function initCopyLink() {
        const copyBtns = document.querySelectorAll('.blog-share-copy');
        
        copyBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function() {
                        showCopyFeedback(btn);
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-9999px';
                    document.body.appendChild(textArea);
                    textArea.select();
                    
                    try {
                        document.execCommand('copy');
                        showCopyFeedback(btn);
                    } catch (err) {
                        console.error('Failed to copy:', err);
                    }
                    
                    document.body.removeChild(textArea);
                }
            });
        });
    }

    /**
     * Show copy feedback
     */
    function showCopyFeedback(btn) {
        const originalBg = btn.style.backgroundColor;
        btn.style.backgroundColor = '#4CAF50';
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        
        setTimeout(function() {
            btn.style.backgroundColor = originalBg;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>';
        }, 2000);
    }

})();
