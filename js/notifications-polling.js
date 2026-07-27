// Sistema de Notificações em Tempo Real (Polling)
class NotificationPoller {
  constructor(options = {}) {
    this.pollInterval = options.pollInterval || 30000; // 30 segundos padrão
    this.apiEndpoint = options.apiEndpoint || '/portal/api/notifications.php';
    this.onNewNotification = options.onNewNotification || (() => {});
    this.onError = options.onError || (() => {});
    this.isPolling = false;
    this.lastCheck = Date.now();
    this.pollTimer = null;
  }

  start() {
    if (this.isPolling) return;
    
    this.isPolling = true;
    this.poll();
    
    console.log('Notification polling started');
  }

  stop() {
    if (!this.isPolling) return;
    
    this.isPolling = false;
    if (this.pollTimer) {
      clearTimeout(this.pollTimer);
      this.pollTimer = null;
    }
    
    console.log('Notification polling stopped');
  }

  async poll() {
    if (!this.isPolling) return;

    try {
      const response = await fetch(`${this.apiEndpoint}?last_check=${Math.floor(this.lastCheck / 1000)}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        this.lastCheck = data.timestamp * 1000;
        
        if (data.new_count > 0) {
          this.onNewNotification(data);
        }
      } else {
        throw new Error(data.error || 'Erro desconhecido');
      }

    } catch (error) {
      console.error('Error polling notifications:', error);
      this.onError(error);
    }

    // Agendar próximo poll
    if (this.isPolling) {
      this.pollTimer = setTimeout(() => this.poll(), this.pollInterval);
    }
  }

  setPollInterval(interval) {
    this.pollInterval = interval;
  }
}

// Sistema de UI para notificações
class NotificationUI {
  constructor() {
    this.container = null;
    this.badge = null;
    this.dropdown = null;
    this.notifications = [];
  }

  init() {
    // Criar container de notificações no header
    this.createNotificationButton();
    this.createNotificationDropdown();
  }

  createNotificationButton() {
    const header = document.querySelector('header nav') || document.querySelector('header .flex');
    if (!header) return;

    const button = document.createElement('button');
    button.className = 'relative p-2 rounded-full hover:bg-white/10 transition-colors';
    button.id = 'notification-button';
    button.innerHTML = `
      <i class="fas fa-bell text-white text-xl"></i>
      <span id="notification-badge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center hidden">0</span>
    `;

    header.appendChild(button);
    this.badge = button.querySelector('#notification-badge');

    button.addEventListener('click', () => this.toggleDropdown());
  }

  createNotificationDropdown() {
    const dropdown = document.createElement('div');
    dropdown.id = 'notification-dropdown';
    dropdown.className = 'absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl hidden z-50';
    dropdown.innerHTML = `
      <div class="p-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-800">Notificações</h3>
      </div>
      <div id="notification-list" class="max-h-96 overflow-y-auto">
        <div class="p-4 text-center text-gray-500">
          <i class="fas fa-bell-slash text-3xl mb-2"></i>
          <p>Nenhuma notificação</p>
        </div>
      </div>
      <div class="p-4 border-t border-gray-100">
        <button id="mark-all-read" class="w-full py-2 text-sm text-blue-600 hover:text-blue-700 transition-colors">
          Marcar todas como lidas
        </button>
      </div>
    `;

    document.body.appendChild(dropdown);
    this.dropdown = dropdown;
    this.container = dropdown.querySelector('#notification-list');

    // Marcar todas como lidas
    dropdown.querySelector('#mark-all-read').addEventListener('click', () => {
      this.markAllAsRead();
    });

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && !document.getElementById('notification-button').contains(e.target)) {
        this.hideDropdown();
      }
    });
  }

  toggleDropdown() {
    this.dropdown.classList.toggle('hidden');
  }

  hideDropdown() {
    this.dropdown.classList.add('hidden');
  }

  updateBadge(count) {
    if (count > 0) {
      this.badge.textContent = count > 9 ? '9+' : count;
      this.badge.classList.remove('hidden');
      this.badge.classList.add('animate-pulse');
    } else {
      this.badge.classList.add('hidden');
      this.badge.classList.remove('animate-pulse');
    }
  }

  addNotification(notification) {
    this.notifications.unshift(notification);
    this.renderNotifications();
    this.updateBadge(this.notifications.length);
  }

  addNotifications(notifications) {
    this.notifications = [...notifications, ...this.notifications];
    this.renderNotifications();
    this.updateBadge(this.notifications.length);
  }

  renderNotifications() {
    if (this.notifications.length === 0) {
      this.container.innerHTML = `
        <div class="p-4 text-center text-gray-500">
          <i class="fas fa-bell-slash text-3xl mb-2"></i>
          <p>Nenhuma notificação</p>
        </div>
      `;
      return;
    }

    this.container.innerHTML = this.notifications.map(notification => `
      <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer notification-item" data-id="${notification.id}">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <i class="fas ${this.getNotificationIcon(notification.tipo_notificacao)} text-blue-600"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 text-sm truncate">${notification.titulo}</p>
            <p class="text-gray-600 text-xs truncate">${notification.mensagem}</p>
            <p class="text-gray-400 text-xs mt-1">${this.formatTime(notification.data_criacao)}</p>
          </div>
          ${!notification.lida ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>' : ''}
        </div>
      </div>
    `).join('');

    // Adicionar click handlers
    this.container.querySelectorAll('.notification-item').forEach(item => {
      item.addEventListener('click', () => {
        this.markAsRead(item.dataset.id);
        if (this.notifications.find(n => n.id === item.dataset.id)?.link) {
          window.location.href = this.notifications.find(n => n.id === item.dataset.id).link;
        }
      });
    });
  }

  getNotificationIcon(type) {
    const icons = {
      'arquivo': 'fa-file',
      'aviso': 'fa-exclamation-circle',
      'evento': 'fa-calendar',
      'sistema': 'fa-cog'
    };
    return icons[type] || 'fa-bell';
  }

  formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return 'Agora';
    if (diff < 3600000) return `${Math.floor(diff / 60000)} min`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)} h`;
    if (diff < 604800000) return `${Math.floor(diff / 86400000)} d`;
    
    return date.toLocaleDateString('pt-BR');
  }

  async markAsRead(notificationId) {
    try {
      await fetch('/portal/api/mark-notification-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: notificationId })
      });

      this.notifications = this.notifications.filter(n => n.id !== notificationId);
      this.renderNotifications();
      this.updateBadge(this.notifications.length);
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  }

  async markAllAsRead() {
    try {
      await fetch('/portal/api/mark-all-read.php', { method: 'POST' });
      this.notifications = [];
      this.renderNotifications();
      this.updateBadge(0);
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  }
}

// Inicializar sistema de notificações
document.addEventListener('DOMContentLoaded', () => {
  // Verificar se estamos no portal
  if (window.location.pathname.includes('/portal/')) {
    const notificationUI = new NotificationUI();
    notificationUI.init();

    const poller = new NotificationPoller({
      pollInterval: 30000, // 30 segundos
      onNewNotification: (data) => {
        notificationUI.addNotifications(data.notifications);
        
        // Mostrar toast para novas notificações
        if (data.new_count > 0) {
          showToast(`${data.new_count} nova(s) notificação(ões)`);
        }
      },
      onError: (error) => {
        console.error('Notification polling error:', error);
      }
    });

    // Iniciar polling
    poller.start();

    // Parar polling quando a página for oculta
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        poller.stop();
      } else {
        poller.start();
      }
    });
  }
});

// Função helper para mostrar toast
function showToast(message) {
  const toast = document.createElement('div');
  toast.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-up';
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}
