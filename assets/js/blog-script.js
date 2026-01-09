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
        initPodcastPlayers();
        initListenPlayer();
    });

    /**
     * Initialize Popular Posts Sliders (Swiper)
     */
    function initSliders() {
        // Swiper para popular posts
        const popularSwipers = document.querySelectorAll('.blog-popular-swiper');
        
        popularSwipers.forEach(function(swiperEl) {
            const wrapper = swiperEl.closest('.blog-popular-wrapper');
            const nextBtn = wrapper ? wrapper.querySelector('.blog-popular-next') : null;
            
            new Swiper(swiperEl, {
                slidesPerView: 'auto',
                spaceBetween: 20,
                freeMode: true,
                grabCursor: true,
                navigation: nextBtn ? {
                    nextEl: nextBtn
                } : false
            });
        });
    }

    /**
     * Initialize Video Sliders (Swiper)
     */
    function initVideoSliders() {
        // Swiper para vídeos
        const videoSwipers = document.querySelectorAll('.blog-videos-swiper');
        
        videoSwipers.forEach(function(swiperEl) {
            const wrapper = swiperEl.closest('.blog-videos-wrapper');
            const nextBtn = wrapper ? wrapper.querySelector('.blog-videos-next') : null;
            
            new Swiper(swiperEl, {
                slidesPerView: 'auto',
                spaceBetween: 20,
                freeMode: true,
                grabCursor: true,
                navigation: nextBtn ? {
                    nextEl: nextBtn
                } : false
            });
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
                
                // Set iframe src with all necessary parameters
                // origin parameter helps with cross-origin issues
                const origin = encodeURIComponent(window.location.origin);
                iframe.src = 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0&modestbranding=1&enablejsapi=1&origin=' + origin;
                
                // Show modal
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close modal
        function closeModal() {
            modal.classList.remove('active');
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
            if (e.key === 'Escape' && modal.classList.contains('active')) {
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
        
        // Contador de cores - começa com quantidade de cards já exibidos
        let colorStart = postsGrid.querySelectorAll('.blog-masonry-card').length;
        
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
            formData.append('color_start', colorStart);
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
                    
                    // Update color start for next load
                    if (data.data.nextColorStart) {
                        colorStart = data.data.nextColorStart;
                    }
                    
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

    /**
     * Initialize Podcast Players
     */
    function initPodcastPlayers() {
        const podcastCards = document.querySelectorAll('.blog-podcast-card[data-audio]');
        let currentlyPlaying = null;
        
        podcastCards.forEach(function(card) {
            const playBtn = card.querySelector('.blog-podcast-play-btn');
            const audio = card.querySelector('.blog-podcast-audio');
            
            if (!playBtn || !audio) return;
            
            // Play icon SVG
            const playIcon = '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
            // Pause icon SVG
            const pauseIcon = '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
            
            playBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (audio.paused) {
                    // Stop any currently playing audio
                    if (currentlyPlaying && currentlyPlaying !== audio) {
                        currentlyPlaying.pause();
                        currentlyPlaying.currentTime = 0;
                        
                        // Reset previous card
                        const prevCard = currentlyPlaying.closest('.blog-podcast-card');
                        if (prevCard) {
                            prevCard.classList.remove('playing');
                            const prevBtn = prevCard.querySelector('.blog-podcast-play-btn');
                            if (prevBtn) prevBtn.innerHTML = playIcon;
                        }
                    }
                    
                    // Play this audio
                    audio.play();
                    currentlyPlaying = audio;
                    card.classList.add('playing');
                    playBtn.innerHTML = pauseIcon;
                } else {
                    // Pause this audio
                    audio.pause();
                    card.classList.remove('playing');
                    playBtn.innerHTML = playIcon;
                }
            });
            
            // Reset when audio ends
            audio.addEventListener('ended', function() {
                card.classList.remove('playing');
                playBtn.innerHTML = playIcon;
                currentlyPlaying = null;
            });
        });
        
        // Handle external link clicks (don't trigger play)
        document.querySelectorAll('.blog-podcast-external').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    }

    /**
     * Initialize Listen Player (Ouvir a Notícia) - Text-to-Speech
     */
    function initListenPlayer() {
        const player = document.getElementById('blog-tts-player');
        const contentEl = document.getElementById('blog-tts-content');
        
        if (!player || !contentEl) return;
        
        // Check if Speech Synthesis is supported
        if (!('speechSynthesis' in window)) {
            player.style.display = 'none';
            console.log('Text-to-Speech não suportado neste navegador');
            return;
        }
        
        const playBtn = document.getElementById('blog-tts-play');
        const playIcon = playBtn.querySelector('.play-icon');
        const pauseIcon = playBtn.querySelector('.pause-icon');
        const currentTimeEl = document.getElementById('blog-tts-current');
        const durationEl = document.getElementById('blog-tts-duration');
        const progressBar = document.getElementById('blog-tts-progress');
        const progressHandle = player.querySelector('.blog-listen-progress-handle');
        const progressContainer = document.getElementById('blog-tts-progress-container');
        
        // Get text content
        let text = contentEl.textContent.trim();
        // Clean up text - remove extra whitespace
        text = text.replace(/\s+/g, ' ').trim();
        
        let utterance = null;
        let isPlaying = false;
        let isPaused = false;
        let startTime = 0;
        let elapsedTime = 0;
        let estimatedDuration = 0;
        let progressInterval = null;
        
        // Estimate duration based on average speech rate (150 words per minute)
        const wordCount = text.split(/\s+/).length;
        estimatedDuration = Math.ceil((wordCount / 150) * 60); // in seconds
        
        // Format time helper
        function formatTime(seconds) {
            if (isNaN(seconds) || seconds === Infinity || seconds < 0) return '00:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }
        
        // Set initial duration estimate
        durationEl.textContent = formatTime(estimatedDuration);
        
        // Update progress
        function updateProgress() {
            if (!isPlaying || isPaused) return;
            
            elapsedTime = (Date.now() - startTime) / 1000;
            const percent = Math.min((elapsedTime / estimatedDuration) * 100, 100);
            progressBar.style.width = percent + '%';
            if (progressHandle) progressHandle.style.left = percent + '%';
            currentTimeEl.textContent = formatTime(elapsedTime);
        }
        
        // Get Portuguese voice
        function getPortugueseVoice() {
            const voices = speechSynthesis.getVoices();
            // Try to find Brazilian Portuguese first
            let voice = voices.find(v => v.lang === 'pt-BR');
            // Fallback to any Portuguese
            if (!voice) voice = voices.find(v => v.lang.startsWith('pt'));
            // Fallback to default
            return voice || voices[0];
        }
        
        // Create and configure utterance
        function createUtterance() {
            utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'pt-BR';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;
            
            const voice = getPortugueseVoice();
            if (voice) utterance.voice = voice;
            
            utterance.onstart = function() {
                isPlaying = true;
                isPaused = false;
                startTime = Date.now();
                progressInterval = setInterval(updateProgress, 100);
            };
            
            utterance.onend = function() {
                resetPlayer();
            };
            
            utterance.onerror = function(event) {
                console.error('TTS Error:', event.error);
                resetPlayer();
            };
        }
        
        // Reset player to initial state
        function resetPlayer() {
            isPlaying = false;
            isPaused = false;
            elapsedTime = 0;
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
            progressBar.style.width = '0%';
            if (progressHandle) progressHandle.style.left = '0%';
            currentTimeEl.textContent = '00:00';
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
        }
        
        // Play/Pause toggle
        playBtn.addEventListener('click', function() {
            if (!isPlaying && !isPaused) {
                // Start new speech
                createUtterance();
                speechSynthesis.speak(utterance);
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            } else if (isPlaying && !isPaused) {
                // Pause
                speechSynthesis.pause();
                isPaused = true;
                if (progressInterval) clearInterval(progressInterval);
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
            } else if (isPaused) {
                // Resume
                speechSynthesis.resume();
                isPaused = false;
                startTime = Date.now() - (elapsedTime * 1000);
                progressInterval = setInterval(updateProgress, 100);
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            }
        });
        
        // Load voices (they load async in some browsers)
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = function() {
                // Voices loaded
            };
        }
        
        // Stop speech when leaving page
        window.addEventListener('beforeunload', function() {
            speechSynthesis.cancel();
        });
    }

})();
