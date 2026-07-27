// Sistema de Acessibilidade
class AccessibilityManager {
  constructor() {
    this.init();
  }

  init() {
    this.improveKeyboardNavigation();
    this.addSkipLinks();
    this.improveFocusIndicators();
    this.addARIALabels();
    this.improveColorContrast();
    this.addScreenReaderSupport();
  }

  // Melhorar navegação por teclado
  improveKeyboardNavigation() {
    // Foco visível em todos os elementos interativos
    const interactiveElements = 'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])';
    
    document.querySelectorAll(interactiveElements).forEach(el => {
      el.addEventListener('keydown', (e) => {
        // Enter em elementos com role="button"
        if (e.key === 'Enter' && el.getAttribute('role') === 'button') {
          e.preventDefault();
          el.click();
        }
      });
    });

    // Trap focus em modais
    this.setupModalFocusTrap();

    // Navegação por setas em menus
    this.setupArrowKeyNavigation();
  }

  // Adicionar skip links
  addSkipLinks() {
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Pular para o conteúdo principal';
    skipLink.setAttribute('aria-label', 'Pular para o conteúdo principal');
    
    document.body.insertBefore(skipLink, document.body.firstChild);

    // Adicionar ID ao conteúdo principal
    const mainContent = document.querySelector('main') || document.querySelector('#hero-carousel');
    if (mainContent) {
      mainContent.id = 'main-content';
      mainContent.setAttribute('tabindex', '-1');
    }
  }

  // Melhorar indicadores de foco
  improveFocusIndicators() {
    const style = document.createElement('style');
    style.textContent = `
      .skip-link {
        position: absolute;
        top: -40px;
        left: 0;
        background: #000;
        color: #fff;
        padding: 8px;
        z-index: 100;
        transition: top 0.3s;
      }
      
      .skip-link:focus {
        top: 0;
      }
      
      *:focus {
        outline: 3px solid #ffd700 !important;
        outline-offset: 2px !important;
      }
      
      *:focus:not(:focus-visible) {
        outline: none !important;
      }
      
      *:focus-visible {
        outline: 3px solid #ffd700 !important;
        outline-offset: 2px !important;
      }
      
      .dark-mode *:focus-visible {
        outline: 3px solid #ffd700 !important;
        outline-offset: 2px !important;
      }
    `;
    document.head.appendChild(style);
  }

  // Adicionar labels ARIA
  addARIALabels() {
    // Labels para botões sem texto
    document.querySelectorAll('button i').forEach(icon => {
      const button = icon.closest('button');
      if (button && !button.getAttribute('aria-label') && !button.textContent.trim()) {
        const iconClass = icon.className;
        let label = '';
        
        if (iconClass.includes('fa-bars')) label = 'Abrir menu';
        else if (iconClass.includes('fa-times')) label = 'Fechar';
        else if (iconClass.includes('fa-search')) label = 'Buscar';
        else if (iconClass.includes('fa-user')) label = 'Usuário';
        else if (iconClass.includes('fa-bell')) label = 'Notificações';
        else if (iconClass.includes('fa-chevron-left')) label = 'Anterior';
        else if (iconClass.includes('fa-chevron-right')) label = 'Próximo';
        else if (iconClass.includes('fa-arrow-up')) label = 'Voltar ao topo';
        
        if (label) {
          button.setAttribute('aria-label', label);
        }
      }
    });

    // Labels para inputs sem label explícito
    document.querySelectorAll('input:not([aria-label]), textarea:not([aria-label]), select:not([aria-label])').forEach(input => {
      const label = document.querySelector(`label[for="${input.id}"]`);
      if (!label) {
        const placeholder = input.getAttribute('placeholder');
        if (placeholder) {
          input.setAttribute('aria-label', placeholder);
        }
      }
    });

    // Roles para elementos interativos não semânticos
    document.querySelectorAll('[onclick]').forEach(el => {
      if (!el.getAttribute('role')) {
        el.setAttribute('role', 'button');
        el.setAttribute('tabindex', '0');
      }
    });
  }

  // Melhorar contraste de cores
  improveColorContrast() {
    // Verificar contraste de texto
    const textElements = document.querySelectorAll('p, h1, h2, h3, h4, h5, h6, span, a, button');
    
    textElements.forEach(el => {
      const computedStyle = window.getComputedStyle(el);
      const color = computedStyle.color;
      const backgroundColor = computedStyle.backgroundColor;
      
      // Se o contraste for baixo, adicionar classe
      if (this.getContrastRatio(color, backgroundColor) < 4.5) {
        el.classList.add('low-contrast');
      }
    });
  }

  // Calcular razão de contraste
  getContrastRatio(foreground, background) {
    const lum1 = this.getLuminance(foreground);
    const lum2 = this.getLuminance(background);
    const brightest = Math.max(lum1, lum2);
    const darkest = Math.min(lum1, lum2);
    return (brightest + 0.05) / (darkest + 0.05);
  }

  // Calcular luminância
  getLuminance(color) {
    const rgb = this.colorToRGB(color);
    const [r, g, b] = rgb.map(c => {
      c = c / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }

  // Converter cor para RGB
  colorToRGB(color) {
    if (color.startsWith('#')) {
      const hex = color.slice(1);
      if (hex.length === 3) {
        return hex.split('').map(c => parseInt(c + c, 16));
      }
      return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16)];
    }
    if (color.startsWith('rgb')) {
      return color.match(/\d+/g).map(Number);
    }
    return [0, 0, 0]; // fallback
  }

  // Suporte para leitores de tela
  addScreenReaderSupport() {
    // Adicionar região live para notificações
    const liveRegion = document.createElement('div');
    liveRegion.id = 'sr-live-region';
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only';
    liveRegion.style.cssText = 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;';
    document.body.appendChild(liveRegion);

    // Função para anunciar mudanças
    window.announceToScreenReader = (message) => {
      liveRegion.textContent = message;
      setTimeout(() => liveRegion.textContent = '', 1000);
    };
  }

  // Setup trap focus em modais
  setupModalFocusTrap() {
    document.querySelectorAll('[role="dialog"], .modal').forEach(modal => {
      const focusableElements = modal.querySelectorAll(
        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      
      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
          if (e.shiftKey) {
            if (document.activeElement === firstElement) {
              e.preventDefault();
              lastElement.focus();
            }
          } else {
            if (document.activeElement === lastElement) {
              e.preventDefault();
              firstElement.focus();
            }
          }
        }
        
        // Escape para fechar modal
        if (e.key === 'Escape') {
          const closeBtn = modal.querySelector('[aria-label*="fechar"], [aria-label*="close"], .close-modal');
          if (closeBtn) closeBtn.click();
        }
      });
    });
  }

  // Setup navegação por setas
  setupArrowKeyNavigation() {
    document.querySelectorAll('[role="menu"], .dropdown-menu').forEach(menu => {
      const items = menu.querySelectorAll('[role="menuitem"], a, button');
      
      items.forEach((item, index) => {
        item.addEventListener('keydown', (e) => {
          switch (e.key) {
            case 'ArrowDown':
              e.preventDefault();
              const next = items[index + 1] || items[0];
              next.focus();
              break;
            case 'ArrowUp':
              e.preventDefault();
              const prev = items[index - 1] || items[items.length - 1];
              prev.focus();
              break;
            case 'Home':
              e.preventDefault();
              items[0].focus();
              break;
            case 'End':
              e.preventDefault();
              items[items.length - 1].focus();
              break;
          }
        });
      });
    });
  }
}

// Inicializar acessibilidade
document.addEventListener('DOMContentLoaded', () => {
  window.accessibilityManager = new AccessibilityManager();
});

// Funções helper globais
window.announceToScreenReader = (message) => {
  const liveRegion = document.getElementById('sr-live-region');
  if (liveRegion) {
    liveRegion.textContent = message;
    setTimeout(() => liveRegion.textContent = '', 1000);
  }
};
