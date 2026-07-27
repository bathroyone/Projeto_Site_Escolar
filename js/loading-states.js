// Sistema de Loading States
class LoadingManager {
  constructor() {
    this.loadingElements = new Map();
  }

  // Adiciona loading state a um elemento
  addLoading(element, options = {}) {
    const {
      text = 'Carregando...',
      icon = 'fa-spinner',
      originalContent = element.innerHTML
    } = options;

    // Salva conteúdo original
    this.loadingElements.set(element, {
      originalContent,
      disabled: element.disabled
    });

    // Desabilita elemento
    element.disabled = true;
    element.dataset.loading = 'true';

    // Adiciona conteúdo de loading
    element.innerHTML = `
      <i class="fas ${icon} fa-spin mr-2"></i>
      <span>${text}</span>
    `;

    // Adiciona classe de loading
    element.classList.add('loading');
  }

  // Remove loading state de um elemento
  removeLoading(element) {
    const saved = this.loadingElements.get(element);
    
    if (saved) {
      element.innerHTML = saved.originalContent;
      element.disabled = saved.disabled;
      element.removeAttribute('data-loading');
      element.classList.remove('loading');
      this.loadingElements.delete(element);
    }
  }

  // Adiciona loading a um botão
  buttonLoading(button, text = 'Processando...') {
    this.addLoading(button, { text });
  }

  // Remove loading de um botão
  buttonRemoveLoading(button) {
    this.removeLoading(button);
  }

  // Adiciona overlay de loading a um container
  addOverlay(container, options = {}) {
    const {
      text = 'Carregando...',
      background = 'rgba(255, 255, 255, 0.8)'
    } = options;

    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: ${background};
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      backdrop-filter: blur(2px);
    `;

    overlay.innerHTML = `
      <div class="loading-content text-center">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
        <p class="text-gray-700 font-semibold">${text}</p>
      </div>
    `;

    // Posiciona o container como relative se não for
    if (getComputedStyle(container).position === 'static') {
      container.style.position = 'relative';
    }

    container.appendChild(overlay);
    this.loadingElements.set(container, { overlay });
  }

  // Remove overlay de loading
  removeOverlay(container) {
    const saved = this.loadingElements.get(container);
    
    if (saved && saved.overlay) {
      saved.overlay.remove();
      this.loadingElements.delete(container);
    }
  }

  // Adiciona loading global
  showGlobalLoading(text = 'Carregando...') {
    let globalOverlay = document.getElementById('global-loading');
    
    if (!globalOverlay) {
      globalOverlay = document.createElement('div');
      globalOverlay.id = 'global-loading';
      globalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
      `;

      globalOverlay.innerHTML = `
        <div class="bg-white rounded-2xl p-8 text-center shadow-2xl">
          <i class="fas fa-spinner fa-spin text-5xl text-blue-600 mb-4"></i>
          <p class="text-gray-700 font-semibold text-lg">${text}</p>
        </div>
      `;

      document.body.appendChild(globalOverlay);
      document.body.style.overflow = 'hidden';
    }
  }

  // Remove loading global
  hideGlobalLoading() {
    const globalOverlay = document.getElementById('global-loading');
    
    if (globalOverlay) {
      globalOverlay.remove();
      document.body.style.overflow = '';
    }
  }

  // Wrapper para promises com loading
  async withLoading(element, promise, options = {}) {
    try {
      this.addLoading(element, options);
      const result = await promise;
      return result;
    } finally {
      this.removeLoading(element);
    }
  }

  // Wrapper para overlay com promise
  async withOverlay(container, promise, options = {}) {
    try {
      this.addOverlay(container, options);
      const result = await promise;
      return result;
    } finally {
      this.removeOverlay(container);
    }
  }

  // Wrapper para loading global com promise
  async withGlobalLoading(promise, text = 'Carregando...') {
    try {
      this.showGlobalLoading(text);
      const result = await promise;
      return result;
    } finally {
      this.hideGlobalLoading();
    }
  }
}

// Instância global
const loadingManager = new LoadingManager();

// Funções helper globais
window.showLoading = (element, options) => loadingManager.addLoading(element, options);
window.hideLoading = (element) => loadingManager.removeLoading(element);
window.showButtonLoading = (button, text) => loadingManager.buttonLoading(button, text);
window.hideButtonLoading = (button) => loadingManager.buttonRemoveLoading(button);
window.showOverlay = (container, options) => loadingManager.addOverlay(container, options);
window.hideOverlay = (container) => loadingManager.removeOverlay(container);
window.showGlobalLoading = (text) => loadingManager.showGlobalLoading(text);
window.hideGlobalLoading = () => loadingManager.hideGlobalLoading();

// Auto-loading para formulários
document.addEventListener('DOMContentLoaded', () => {
  // Adiciona loading a formulários com data-loading="true"
  document.querySelectorAll('form[data-loading="true"]').forEach(form => {
    form.addEventListener('submit', (e) => {
      const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      if (submitBtn) {
        const loadingText = submitBtn.dataset.loadingText || 'Enviando...';
        loadingManager.buttonLoading(submitBtn, loadingText);
      }
    });
  });

  // Adiciona loading a links com data-loading="true"
  document.querySelectorAll('a[data-loading="true"]').forEach(link => {
    link.addEventListener('click', (e) => {
      if (link.target !== '_blank') {
        e.preventDefault();
        const loadingText = link.dataset.loadingText || 'Carregando...';
        loadingManager.showGlobalLoading(loadingText);
        setTimeout(() => {
          window.location.href = link.href;
        }, 100);
      }
    });
  });
});

export default LoadingManager;
