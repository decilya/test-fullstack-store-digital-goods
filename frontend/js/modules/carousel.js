/**
 * Модуль карусели.
 * 
 * ЛОГИКА:
 * - Показывает один активный слайд, остальные скрыты
 * - Автоматическое переключение каждые 5 секунд
 * - При наведении мыши автопереключение приостанавливается
 * - Точки-индикаторы создаются динамически
 */

const AUTO_PLAY_INTERVAL = 5000;

export function initCarousel() {
    const slides = document.querySelectorAll('.carousel__slide');
    const dotsContainer = document.getElementById('carouselDots');
    const prevBtn = document.getElementById('prevSlide');
    const nextBtn = document.getElementById('nextSlide');
    const carousel = document.getElementById('heroCarousel');
    
    if (!slides.length || !dotsContainer) {
        console.warn('Карусель: элементы не найдены');
        return;
    }
    
    let currentIndex = 0;
    let autoPlayTimer = null;
    
    function createDots() {
        slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.className = 'carousel__dot' + (index === 0 ? ' carousel__dot--active' : '');
            dot.setAttribute('aria-label', `Слайд ${index + 1}`);
            dot.setAttribute('role', 'tab');
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });
    }
    
    function goToSlide(index) {
        slides[currentIndex].classList.remove('carousel__slide--active');
        if (dotsContainer.children[currentIndex]) {
            dotsContainer.children[currentIndex].classList.remove('carousel__dot--active');
        }
        
        currentIndex = (index + slides.length) % slides.length;
        
        slides[currentIndex].classList.add('carousel__slide--active');
        if (dotsContainer.children[currentIndex]) {
            dotsContainer.children[currentIndex].classList.add('carousel__dot--active');
        }
        
        resetAutoPlay();
    }
    
    function nextSlide() { goToSlide(currentIndex + 1); }
    function prevSlide() { goToSlide(currentIndex - 1); }
    
    function startAutoPlay() {
        autoPlayTimer = setInterval(nextSlide, AUTO_PLAY_INTERVAL);
    }
    
    function stopAutoPlay() {
        if (autoPlayTimer) {
            clearInterval(autoPlayTimer);
            autoPlayTimer = null;
        }
    }
    
    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }
    
    createDots();
    startAutoPlay();
    
    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);
    
    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);
}