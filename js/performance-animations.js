// Sistema de Animações Otimizadas para Performance
class PerformanceAnimations {
  constructor() {
    this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.init();
  }

  init() {
    this.optimizeScrollAnimations();
    this.optimizeCarousel();
    this.optimizeHoverEffects();
    this.setupIntersectionObserver();
    this.enableGPUAcceleration();
  }

  // Otimizar animações de scroll
  optimizeScrollAnimations() {
    if (this.prefersReducedMotion) {
      // Desabilitar animações se usuário prefere redução de movimento
      document.querySelectorAll('.animate-on-scroll').forEach(el => {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }

    // Usar IntersectionObserver com threshold otimizado
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '50px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Usar requestAnimationFrame para melhor performance
          requestAnimationFrame(() => {
            entry.target.classList.add('animate-fade-in-up');
            observer.unobserve(entry.target);
          });
        }
      });
    }, observerOptions);

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
      observer.observe(el);
    });
  }

  // Otimizar carrossel
  optimizeCarousel() {
    const carousel = document.getElementById('hero-carousel');
    if (!carousel) return;

    // Usar transform em vez de opacity para melhor performance
    const slides = carousel.querySelectorAll('.carousel-slide');
    
    slides.forEach((slide, index) => {
      slide.style.willChange = 'transform, opacity';
      slide.style.transform = 'translate3d(0, 0, 0)';
    });

    // Limpar will-change após animação
    carousel.addEventListener('transitionend', () => {
      slides.forEach(slide => {
        slide.style.willChange = 'auto';
      });
    });
  }

  // Otimizar efeitos hover
  optimizeHoverEffects() {
    // Usar transform em vez de animações complexas
    const hoverElements = document.querySelectorAll('.card-hover, .gallery-card');
    
    hoverElements.forEach(el => {
      el.style.willChange = 'transform';
      
      el.addEventListener('mouseenter', () => {
        requestAnimationFrame(() => {
          el.style.transform = 'translateY(-8px)';
        });
      });
      
      el.addEventListener('mouseleave', () => {
        requestAnimationFrame(() => {
          el.style.transform = 'translateY(0)';
        });
      });
      
      el.addEventListener('transitionend', () => {
        el.style.willChange = 'auto';
      });
    });
  }

  // Setup Intersection Observer otimizado
  setupIntersectionObserver() {
    // Lazy loading de imagens
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            
            if (img.dataset.src) {
              img.src = img.dataset.src;
              img.classList.add('loaded');
              observer.unobserve(img);
            }
          }
        });
      }, {
        rootMargin: '50px 0px',
        threshold: 0.01
      });

      document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
      });
    }
  }

  // Habilitar aceleração GPU
  enableGPUAcceleration() {
    // Adicionar transform 3d para elementos animados
    const animatedElements = document.querySelectorAll(
      '.carousel-slide, .card-hover, .gallery-card, .animate-on-scroll'
    );
    
    animatedElements.forEach(el => {
      el.style.transform = 'translate3d(0, 0, 0)';
      el.style.backfaceVisibility = 'hidden';
      el.style.perspective = '1000px';
    });
  }

  // Debounce para eventos de scroll
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Throttle para eventos frequentes
  throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  // Otimizar contador animado
  optimizeCounter() {
    const counters = document.querySelectorAll('[data-count]');
    
    if (this.prefersReducedMotion) {
      // Mostrar valores imediatamente se usuário prefere redução de movimento
      counters.forEach(counter => {
        counter.textContent = counter.dataset.count;
      });
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          const target = parseInt(counter.dataset.count);
          const duration = 2000;
          const startTime = performance.now();
          
          const animateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function para animação suave
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(target * easeOutQuart);
            
            counter.textContent = current;
            
            if (progress < 1) {
              requestAnimationFrame(animateCounter);
            } else {
              counter.textContent = target;
            }
          };
          
          requestAnimationFrame(animateCounter);
          observer.unobserve(counter);
        }
      });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
  }
}

// Inicializar otimizações de animações
document.addEventListener('DOMContentLoaded', () => {
  window.performanceAnimations = new PerformanceAnimations();
  
  // Otimizar scroll events
  let ticking = false;
  
  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        // Header scroll effect
        const header = document.getElementById('main-header');
        if (header) {
          if (window.scrollY > 50) {
            header.classList.add('glass-dark');
          } else {
            header.classList.remove('glass-dark');
          }
        }
        
        // Back to top button
        const backToTop = document.getElementById('back-to-top');
        if (backToTop) {
          if (window.scrollY > 500) {
            backToTop.classList.remove('opacity-0', 'invisible');
          } else {
            backToTop.classList.add('opacity-0', 'invisible');
          }
        }
        
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });
  
  // Otimizar resize events
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      // Ações de resize que precisam ser executadas
    }, 250);
  });
});

// Detectar se usuário prefere redução de movimento
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.documentElement.classList.add('reduced-motion');
  
  // Adicionar CSS para reduzir animações
  const style = document.createElement('style');
  style.textContent = `
    .reduced-motion *,
    .reduced-motion *::before,
    .reduced-motion *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
      scroll-behavior: auto !important;
    }
  `;
  document.head.appendChild(style);
}
