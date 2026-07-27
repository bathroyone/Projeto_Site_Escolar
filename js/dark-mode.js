// Sistema de Dark Mode
class DarkModeManager {
  constructor() {
    this.storageKey = 'darkMode';
    this.darkMode = this.loadPreference();
    this.init();
  }

  init() {
    // Aplicar tema salvo
    this.applyTheme();

    // Adicionar botão de toggle
    this.addToggleButton();

    // Detectar preferência do sistema
    this.detectSystemPreference();
  }

  loadPreference() {
    const saved = localStorage.getItem(this.storageKey);
    if (saved !== null) {
      return saved === 'true';
    }
    return false;
  }

  savePreference(isDark) {
    localStorage.setItem(this.storageKey, isDark);
  }

  detectSystemPreference() {
    if (localStorage.getItem(this.storageKey) === null) {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.setDarkMode(prefersDark);
    }

    // Ouvir mudanças na preferência do sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (localStorage.getItem(this.storageKey) === null) {
        this.setDarkMode(e.matches);
      }
    });
  }

  setDarkMode(isDark) {
    this.darkMode = isDark;
    this.savePreference(isDark);
    this.applyTheme();
  }

  toggle() {
    this.setDarkMode(!this.darkMode);
  }

  applyTheme() {
    if (this.darkMode) {
      document.documentElement.classList.add('dark');
      document.body.classList.add('dark-mode');
    } else {
      document.documentElement.classList.remove('dark');
      document.body.classList.remove('dark-mode');
    }

    // Atualizar ícone do botão
    this.updateToggleButton();
  }

  addToggleButton() {
    const header = document.querySelector('header nav') || document.querySelector('header .flex');
    if (!header) return;

    const button = document.createElement('button');
    button.id = 'dark-mode-toggle';
    button.className = 'p-2 rounded-full hover:bg-white/10 transition-colors';
    button.setAttribute('aria-label', 'Alternar tema escuro/claro');
    button.innerHTML = `
      <i class="fas fa-moon text-white text-xl"></i>
    `;

    button.addEventListener('click', () => this.toggle());
    header.appendChild(button);
  }

  updateToggleButton() {
    const button = document.getElementById('dark-mode-toggle');
    if (!button) return;

    const icon = button.querySelector('i');
    if (this.darkMode) {
      icon.className = 'fas fa-sun text-yellow-400 text-xl';
      button.setAttribute('aria-label', 'Alternar para tema claro');
    } else {
      icon.className = 'fas fa-moon text-white text-xl';
      button.setAttribute('aria-label', 'Alternar para tema escuro');
    }
  }
}

// Inicializar dark mode
document.addEventListener('DOMContentLoaded', () => {
  window.darkModeManager = new DarkModeManager();
});

// Função helper global
window.toggleDarkMode = () => {
  if (window.darkModeManager) {
    window.darkModeManager.toggle();
  }
};
