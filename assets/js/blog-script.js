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
                
                // URL do embed do YouTube com parâmetros corretos
                iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
                
                // Adicionar atributos que fazem funcionar
                iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                
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
     * Initialize Load More Button
     */
    function initLoadMore() {
        const postsGrid = document.getElementById('blog-posts-grid');
        const loadMoreBtn = document.querySelector('.blog-load-more-btn');
        const leftColumn = postsGrid ? postsGrid.querySelector('.blog-masonry-left') : null;
        const rightColumn = postsGrid ? postsGrid.querySelector('.blog-masonry-right') : null;
        
        if (!postsGrid || !loadMoreBtn || !leftColumn || !rightColumn) return;
        
        // Configurações
        let colorStart = postsGrid.querySelectorAll('.blog-masonry-card').length;
        
        loadMoreBtn.addEventListener('click', function() {
            const btn = this;
            const page = parseInt(btn.dataset.page) + 1;
            const perPage = parseInt(btn.dataset.perPage) || 10;
            const exclude = btn.dataset.exclude || '';
            
            // Add loading state
            btn.classList.add('loading');
            btn.disabled = true;
            
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
                if (data.success && (data.data.leftHtml || data.data.rightHtml)) {
                    // Inserir nas colunas corretas
                    if (data.data.leftHtml) {
                        leftColumn.insertAdjacentHTML('beforeend', data.data.leftHtml);
                    }
                    if (data.data.rightHtml) {
                        rightColumn.insertAdjacentHTML('beforeend', data.data.rightHtml);
                    }
                    
                    // Atualizar página
                    btn.dataset.page = page;
                    
                    // Atualizar colorStart
                    if (data.data.nextColorStart) {
                        colorStart = data.data.nextColorStart;
                    }
                    
                    // Esconder botão se não há mais posts
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
     * Initialize Listen Player (Ouvir a Notícia) - Audio or Text-to-Speech
     */
    function initListenPlayer() {
        const player = document.getElementById('blog-tts-player');
        const contentEl = document.getElementById('blog-tts-content');
        const audioElement = document.getElementById('blog-audio-element');
        
        if (!player) return;
        
        const playBtn = document.getElementById('blog-tts-play');
        if (!playBtn) return;
        
        const playIcon = playBtn.querySelector('.play-icon');
        const pauseIcon = playBtn.querySelector('.pause-icon');
        const currentTimeEl = document.getElementById('blog-tts-current');
        const durationEl = document.getElementById('blog-tts-duration');
        const progressBar = document.getElementById('blog-tts-progress');
        const progressHandle = document.getElementById('blog-tts-handle');
        const progressContainer = document.getElementById('blog-tts-progress-container');
        const speedBtn = document.getElementById('blog-tts-speed');
        
        // Speed options
        const speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
        let currentSpeedIndex = 2; // Default 1x
        
        // Format time helper
        function formatTime(seconds) {
            if (isNaN(seconds) || seconds === Infinity || seconds < 0) return '00:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }
        
        // Update progress bar and handle
        function updateProgressUI(percent) {
            if (progressBar) progressBar.style.width = percent + '%';
            if (progressHandle) progressHandle.style.left = percent + '%';
        }
        
        // Check if we have a real audio file
        if (audioElement && audioElement.querySelector('source')) {
            // ==================== REAL AUDIO MODE ====================
            console.log('Audio player: Real audio mode');
            let isPlaying = false;
            let isDragging = false;
            
            // Set duration when metadata is loaded
            audioElement.addEventListener('loadedmetadata', function() {
                durationEl.textContent = formatTime(audioElement.duration);
            });
            
            // Also try to get duration if already loaded
            if (audioElement.duration && !isNaN(audioElement.duration)) {
                durationEl.textContent = formatTime(audioElement.duration);
            }
            
            // Update progress during playback
            audioElement.addEventListener('timeupdate', function() {
                if (!isDragging && audioElement.duration) {
                    const percent = (audioElement.currentTime / audioElement.duration) * 100;
                    updateProgressUI(percent);
                    currentTimeEl.textContent = formatTime(audioElement.currentTime);
                }
            });
            
            // Handle audio end
            audioElement.addEventListener('ended', function() {
                isPlaying = false;
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
                updateProgressUI(0);
                currentTimeEl.textContent = '00:00';
                audioElement.currentTime = 0;
            });
            
            // Play/Pause toggle
            playBtn.addEventListener('click', function() {
                if (isPlaying) {
                    audioElement.pause();
                    isPlaying = false;
                    playIcon.style.display = 'block';
                    pauseIcon.style.display = 'none';
                } else {
                    audioElement.play().catch(function(e) {
                        console.error('Play error:', e);
                    });
                    isPlaying = true;
                    playIcon.style.display = 'none';
                    pauseIcon.style.display = 'block';
                }
            });
            
            // Seek function
            function seekToPosition(e) {
                if (!progressContainer || !audioElement.duration) return;
                
                const rect = progressContainer.getBoundingClientRect();
                let percent = (e.clientX - rect.left) / rect.width;
                percent = Math.max(0, Math.min(1, percent));
                
                const newTime = percent * audioElement.duration;
                if (!isNaN(newTime) && isFinite(newTime)) {
                    audioElement.currentTime = newTime;
                    updateProgressUI(percent * 100);
                    currentTimeEl.textContent = formatTime(newTime);
                }
            }
            
            // Click on progress bar to seek
            if (progressContainer) {
                progressContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    seekToPosition(e);
                });
                
                // Drag to seek - Mouse events
                progressContainer.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    isDragging = true;
                    seekToPosition(e);
                    document.body.style.userSelect = 'none';
                });
                
                document.addEventListener('mousemove', function(e) {
                    if (isDragging) {
                        e.preventDefault();
                        seekToPosition(e);
                    }
                });
                
                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        document.body.style.userSelect = '';
                    }
                });
                
                // Touch events for mobile
                progressContainer.addEventListener('touchstart', function(e) {
                    isDragging = true;
                    const touch = e.touches[0];
                    seekToPosition({ clientX: touch.clientX });
                }, { passive: true });
                
                document.addEventListener('touchmove', function(e) {
                    if (isDragging && e.touches[0]) {
                        seekToPosition({ clientX: e.touches[0].clientX });
                    }
                }, { passive: true });
                
                document.addEventListener('touchend', function() {
                    isDragging = false;
                });
            }
            
            // Speed control
            if (speedBtn) {
                speedBtn.addEventListener('click', function() {
                    currentSpeedIndex = (currentSpeedIndex + 1) % speeds.length;
                    const speed = speeds[currentSpeedIndex];
                    audioElement.playbackRate = speed;
                    speedBtn.textContent = speed + 'x';
                });
            }
            
        } else if (contentEl && 'speechSynthesis' in window) {
            // ==================== TEXT-TO-SPEECH MODE ====================
            console.log('Audio player: TTS mode initialized');
            
            // Get text content
            let fullText = contentEl.textContent.trim();
            fullText = fullText.replace(/\s+/g, ' ').trim();
            
            // Split text into words for seeking
            const words = fullText.split(/\s+/);
            const totalWords = words.length;
            
            let utterance = null;
            let isPlaying = false;
            let isPaused = false;
            let startTime = 0;
            let currentWordIndex = 0;
            let estimatedDuration = 0;
            let progressInterval = null;
            let currentSpeed = 1;
            let isDraggingTTS = false;
            
            // Estimate duration based on average speech rate (150 words per minute at 1x speed)
            estimatedDuration = Math.ceil((totalWords / 150) * 60);
            durationEl.textContent = formatTime(estimatedDuration);
            console.log('TTS: Total words:', totalWords, 'Estimated duration:', estimatedDuration + 's');
            
            // Calculate current progress percentage
            function getCurrentProgress() {
                if (!isPlaying || isPaused) return (currentWordIndex / totalWords) * 100;
                const elapsed = (Date.now() - startTime) / 1000;
                const wordsSpoken = Math.floor((elapsed * 150 * currentSpeed) / 60);
                const totalSpoken = currentWordIndex + wordsSpoken;
                return Math.min((totalSpoken / totalWords) * 100, 100);
            }
            
            // Calculate current time in seconds
            function getCurrentTime() {
                const progress = getCurrentProgress() / 100;
                return progress * estimatedDuration;
            }
            
            // Update progress display
            function updateTTSProgress() {
                if (!isPlaying || isPaused || isDraggingTTS) return;
                const percent = getCurrentProgress();
                updateProgressUI(percent);
                currentTimeEl.textContent = formatTime(getCurrentTime());
                
                if (percent >= 100) {
                    resetTTSPlayer();
                }
            }
            
            // Get Portuguese voice
            function getPortugueseVoice() {
                const voices = speechSynthesis.getVoices();
                let voice = voices.find(v => v.lang === 'pt-BR');
                if (!voice) voice = voices.find(v => v.lang.startsWith('pt'));
                return voice || voices[0];
            }
            
            // Start speaking from a specific word index
            function speakFromIndex(wordIndex) {
                // Cancel any ongoing speech
                speechSynthesis.cancel();
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
                
                // Get remaining text from word index
                currentWordIndex = Math.max(0, Math.min(wordIndex, totalWords - 1));
                const remainingText = words.slice(currentWordIndex).join(' ');
                
                if (!remainingText.trim()) {
                    resetTTSPlayer();
                    return;
                }
                
                console.log('TTS: Speaking from word', currentWordIndex, 'of', totalWords);
                
                utterance = new SpeechSynthesisUtterance(remainingText);
                utterance.lang = 'pt-BR';
                utterance.rate = currentSpeed;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;
                
                const voice = getPortugueseVoice();
                if (voice) utterance.voice = voice;
                
                utterance.onstart = function() {
                    console.log('TTS: Started speaking at speed', currentSpeed);
                    isPlaying = true;
                    isPaused = false;
                    startTime = Date.now();
                    progressInterval = setInterval(updateTTSProgress, 100);
                    playIcon.style.display = 'none';
                    pauseIcon.style.display = 'block';
                };
                
                utterance.onend = function() {
                    console.log('TTS: Finished speaking');
                    resetTTSPlayer();
                };
                
                utterance.onerror = function(event) {
                    if (event.error !== 'interrupted' && event.error !== 'canceled') {
                        console.error('TTS Error:', event.error);
                        resetTTSPlayer();
                    }
                };
                
                speechSynthesis.speak(utterance);
            }
            
            // Reset TTS player
            function resetTTSPlayer() {
                speechSynthesis.cancel();
                isPlaying = false;
                isPaused = false;
                currentWordIndex = 0;
                startTime = 0;
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
                updateProgressUI(0);
                currentTimeEl.textContent = '00:00';
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            }
            
            // Seek to position (percentage)
            function seekToPercent(percent) {
                percent = Math.max(0, Math.min(1, percent));
                const targetWordIndex = Math.floor(percent * totalWords);
                
                console.log('TTS: Seeking to', Math.round(percent * 100) + '%', 'word', targetWordIndex);
                
                // Update visual immediately
                updateProgressUI(percent * 100);
                currentTimeEl.textContent = formatTime(percent * estimatedDuration);
                
                // If playing, restart from new position
                if (isPlaying && !isPaused) {
                    speakFromIndex(targetWordIndex);
                } else {
                    currentWordIndex = targetWordIndex;
                }
            }
            
            // Handle click/drag on progress bar
            function handleProgressInteraction(clientX) {
                if (!progressContainer) return;
                
                const rect = progressContainer.getBoundingClientRect();
                const percent = (clientX - rect.left) / rect.width;
                seekToPercent(percent);
            }
            
            // Progress bar interactions
            if (progressContainer) {
                console.log('TTS: Setting up progress bar events');
                progressContainer.style.cursor = 'pointer';
                
                progressContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleProgressInteraction(e.clientX);
                });
                
                progressContainer.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    isDraggingTTS = true;
                    handleProgressInteraction(e.clientX);
                    document.body.style.userSelect = 'none';
                });
                
                document.addEventListener('mousemove', function(e) {
                    if (isDraggingTTS) {
                        e.preventDefault();
                        handleProgressInteraction(e.clientX);
                    }
                });
                
                document.addEventListener('mouseup', function() {
                    if (isDraggingTTS) {
                        isDraggingTTS = false;
                        document.body.style.userSelect = '';
                    }
                });
                
                // Touch events
                progressContainer.addEventListener('touchstart', function(e) {
                    isDraggingTTS = true;
                    if (e.touches[0]) {
                        handleProgressInteraction(e.touches[0].clientX);
                    }
                }, { passive: true });
                
                document.addEventListener('touchmove', function(e) {
                    if (isDraggingTTS && e.touches[0]) {
                        handleProgressInteraction(e.touches[0].clientX);
                    }
                }, { passive: true });
                
                document.addEventListener('touchend', function() {
                    isDraggingTTS = false;
                });
            }
            
            // Play/Pause toggle
            playBtn.addEventListener('click', function() {
                console.log('TTS: Play button clicked, isPlaying:', isPlaying, 'isPaused:', isPaused);
                
                if (!isPlaying && !isPaused) {
                    // Start from beginning or current position
                    speakFromIndex(currentWordIndex);
                } else if (isPlaying && !isPaused) {
                    // Pause
                    speechSynthesis.pause();
                    isPaused = true;
                    // Save current position
                    const elapsed = (Date.now() - startTime) / 1000;
                    const wordsSpoken = Math.floor((elapsed * 150 * currentSpeed) / 60);
                    currentWordIndex = Math.min(currentWordIndex + wordsSpoken, totalWords);
                    if (progressInterval) clearInterval(progressInterval);
                    playIcon.style.display = 'block';
                    pauseIcon.style.display = 'none';
                } else if (isPaused) {
                    // Resume
                    speechSynthesis.resume();
                    isPaused = false;
                    startTime = Date.now();
                    progressInterval = setInterval(updateTTSProgress, 100);
                    playIcon.style.display = 'none';
                    pauseIcon.style.display = 'block';
                }
            });
            
            // Speed control - restart from current position with new speed
            if (speedBtn) {
                speedBtn.addEventListener('click', function() {
                    // Calculate current word position
                    let targetWordIndex = currentWordIndex;
                    if (isPlaying && !isPaused) {
                        const elapsed = (Date.now() - startTime) / 1000;
                        const wordsSpoken = Math.floor((elapsed * 150 * currentSpeed) / 60);
                        targetWordIndex = Math.min(currentWordIndex + wordsSpoken, totalWords);
                    }
                    
                    // Change speed
                    currentSpeedIndex = (currentSpeedIndex + 1) % speeds.length;
                    currentSpeed = speeds[currentSpeedIndex];
                    speedBtn.textContent = currentSpeed + 'x';
                    
                    console.log('TTS: Speed changed to', currentSpeed + 'x, continuing from word', targetWordIndex);
                    
                    // Restart from current position with new speed
                    if (isPlaying && !isPaused) {
                        speakFromIndex(targetWordIndex);
                    } else {
                        currentWordIndex = targetWordIndex;
                    }
                });
            }
            
            // Load voices
            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = function() {
                    console.log('TTS: Voices loaded');
                };
            }
            
            // Stop speech when leaving page
            window.addEventListener('beforeunload', function() {
                speechSynthesis.cancel();
            });
            
        } else {
            // No audio and no TTS support
            player.style.display = 'none';
        }
    }

})();
