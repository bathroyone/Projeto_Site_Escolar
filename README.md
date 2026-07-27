# Site Institucional Moderno e Sistema de Gestão Escolar

Site institucional moderno e sistema de gestão escolar completo, desenvolvido como base para implementação em instituições educacionais.

## 📋 Visão Geral

Este projeto consiste em um sistema escolar completo com:
- **Site Institucional**: Landing page moderna e responsiva
- **Sistema de Portal**: Gestão escolar para alunos, professores e administradores
- **Biblioteca Virtual**: Acervo de livros digitais organizados por categorias
- **Galeria de Fotos**: Álbuns de fotos dos eventos institucionais
- **Sistema de Eventos**: Calendário de eventos
- **Sistema de Correções**: Área para correções de trabalhos (em desenvolvimento)

## 🚀 Tecnologias Utilizadas

### Frontend
- **HTML5**: Estrutura semântica
- **Tailwind CSS**: Framework CSS para estilização (build local)
- **GSAP**: Animações avançadas
- **Font Awesome**: Ícones
- **Google Fonts**: Inter e Poppins
- **Dark Mode**: Alternância entre temas claro/escuro
- **PWA**: Progressive Web App com service worker

### Backend
- **PHP 7.4+**: Linguagem de servidor
- **MySQL 5.7+**: Banco de dados relacional
- **PDO**: Abstração de banco de dados
- **Sistema de Autenticação**: Login via AJAX com banco de dados
- **Tratamento de Erros**: Centralizado com logging
- **Cache**: Sistema de cache para performance
- **Logs de Auditoria**: Rastreamento de ações do sistema

### Ferramentas de Desenvolvimento
- **Node.js**: Gerenciamento de pacotes
- **PostCSS**: Processamento de CSS
- **Autoprefixer**: Compatibilidade de CSS

## 📁 Estrutura do Projeto

```
Projeto_Site_Escolar/
├── index.html                  # Página principal do site
├── package.json                # Dependências do Node.js
├── tailwind.config.js          # Configuração do Tailwind CSS
├── postcss.config.js           # Configuração do PostCSS
├── INSTALACAO_XAMPP.md        # Guia de instalação do XAMPP
├── README.md                   # Este arquivo
│
├── css/                        # Estilos CSS
│   ├── globals.css            # Estilos globais
│   ├── site-moderno.css       # Estilos adicionais
│   └── font-awesome.min.css   # Font Awesome
│
├── js/                         # Scripts JavaScript
│   ├── main.js                # Funcionalidades principais
│   ├── carousel.js            # Carrossel de imagens
│   ├── form-validation.js     # Validação de formulários
│   ├── loading-states.js      # Estados de loading
│   ├── dark-mode.js           # Sistema de dark mode
│   ├── accessibility.js      # Melhorias de acessibilidade
│   ├── performance-animations.js # Animações otimizadas
│   └── notifications-polling.js # Sistema de notificações
│
├── img/                        # Imagens do site
│   └── logo.jpg               # Logo da escola
│
├── portal/                     # Sistema de gestão escolar
│   ├── config.php             # Configurações do sistema
│   ├── login.php              # Página de login
│   ├── logout.php             # Página de logout
│   ├── register.php           # Cadastro de alunos
│   ├── dashboard.php          # Dashboard principal
│   ├── install.php            # Instalação do sistema
│   ├── api/                   # API endpoints
│   │   ├── login.php         # API de login (AJAX)
│   │   ├── notifications.php  # API de notificações
│   │   └── mark-notification-read.php # Marcar notificação como lida
│   ├── admin/                 # Portal do administrador
│   │   ├── index.php         # Dashboard admin
│   │   ├── usuarios.php      # Gerenciamento de usuários
│   │   ├── turmas.php        # Gerenciamento de turmas
│   │   └── arquivos.php      # Gerenciamento de arquivos
│   ├── professor/             # Portal do professor
│   │   └── upload.php        # Upload de arquivos
│   ├── error-handler.php      # Tratamento de erros
│   ├── audit-logger.php      # Sistema de logs de auditoria
│   ├── cache-manager.php     # Sistema de cache
│   └── uploads/              # Diretório de uploads
│       └── arquivos/         # Arquivos enviados
│
├── database/                   # Banco de dados
│   └── schema.sql            # Schema do banco de dados (escola_gestao)

├── scripts/                    # Scripts de automação
│   ├── backup-database.php    # Script de backup do banco
│   └── backup-cron.bat        # Script para agendamento (Windows)

├── manifest.json               # Manifesto PWA
├── sw.js                       # Service Worker PWA
├── robots.txt                  # Diretrizes para crawlers
└── sitemap.xml                 # Sitemap do site
│
├── biblioteca_vrtual/          # Biblioteca virtual
│   ├── livro.html             # Página principal da biblioteca
│   ├── livro1.html            # Livros infantis 03-05 anos
│   ├── livro2.html            # Livros infantis 06-08 anos
│   ├── livro3.html            # Livros infantis 08-12 anos
│   ├── livro4.html            # Livros didáticos - Educação Infantil
│   ├── livro5.html            # Livros didáticos - Ensino Fundamental
│   ├── livro6.html            # Livros didáticos - Ensino Médio
│   ├── livro7.html            # Livros didáticos - ENEM
│   ├── livro8.html            # Ciências Biológicas
│   ├── livro9.html            # Ciências Exatas
│   ├── livro10.html           # Ciências Humanas
│   ├── livro11.html           # Literatura Brasileira
│   ├── livro12.html           # Literatura Estrangeira
│   ├── livro13.html           # Literatura de Cordel
│   ├── livro14.html           # Paradidáticos
│   ├── livro15.html           # Ação e Aventura
│   ├── livro16.html           # Romance
│   ├── livro17.html           # Cursos
│   ├── livro18.html           # Religioso
│   ├── livro19.html           # Receitas
│   ├── 1º_ano.html           # 1º ano
│   ├── 2º_ano.html           # 2º ano
│   ├── 3º_ano.html           # 3º ano
│   ├── 4º_ano.html           # 4º ano
│   ├── 5º_ano.html           # 5º ano
│   ├── 6º_ano.html           # 6º ano
│   ├── 7º_ano.html           # 7º ano
│   ├── 8º_ano.html           # 8º ano
│   └── 9º_ano.html           # 9º ano
│
├── album/                      # Galeria de fotos
│   ├── Album.html             # Página principal do álbum
│   ├── Album2.html            # Álbum 2
│   ├── Album3.html            # Álbum 3
│   ├── Album4.html            # Álbum 4
│   ├── Album5.html            # Álbum 5
│   ├── Album6.html            # Álbum 6
│   ├── Album7.html            # Álbum 7
│   ├── Album8.html            # Álbum 8
│   ├── Album9.html            # Álbum 9
│   ├── Album10.html           # Álbum 10
│   ├── Album11.html           # Álbum 11
│   ├── Album12.html           # Álbum 12
│   └── Album13.html           # Álbum 13
│
├── eventos/                    # Sistema de eventos
│   ├── eventos.html           # Página de eventos
│   ├── img/                   # Imagens dos eventos
│   └── css/                   # Estilos dos eventos
│
└── aviso2/                     # Sistema de correções (em desenvolvimento)
    ├── passo1.html            # Passo 1 das correções
    ├── 1_ano.html             # Correções 1º ano
    ├── 2_ano.html             # Correções 2º ano
    ├── 3_ano.html             # Correções 3º ano
    ├── 4_ano.html             # Correções 4º ano
    ├── 5_ano.html             # Correções 5º ano
    ├── 6_ano.html             # Correções 6º ano
    ├── 7_ano.html             # Correções 7º ano
    ├── 8_ano.html             # Correções 8º ano
    ├── 9_ano.html             # Correções 9º ano
    ├── css/                   # Estilos das correções
    └── img/                   # Imagens das correções
```

## 🔧 Instalação

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior / MariaDB 10.2 ou superior
- Servidor web (Apache, Nginx, etc.)
- Node.js (para desenvolvimento)
- XAMPP (recomendado para ambiente local)

### Instalação Local com XAMPP

1. **Instale o XAMPP** se ainda não tiver instalado
2. **Copie o projeto** para a pasta `htdocs` do XAMPP:
   ```
   C:\xampp\htdocs\Projeto_Site_Escolar\
   ```

3. **Configure o banco de dados**:
   - Abra o XAMPP Control Panel
   - Inicie os serviços Apache e MySQL
   - Acesse `http://localhost/phpmyadmin`
   - Importe o arquivo `database/schema.sql`
   - O banco de dados será criado com o nome `escola_gestao`
   - Ou siga o guia detalhado em `INSTALACAO_XAMPP.md`

4. **Configure o portal**:
   - Edite `portal/config.php` com suas credenciais do MySQL
   - O nome do banco deve ser `escola_gestao`
   - Crie o diretório `portal/uploads/arquivos`
   - Configure o URL do site em `SITE_URL`

5. **Instale as dependências do Node.js** (opcional, para desenvolvimento):
   ```bash
   npm install
   ```

6. **Compile o CSS** (opcional, para desenvolvimento):
   ```bash
   npm run dev    # Modo desenvolvimento
   npm run build  # Modo produção
   ```

7. **Acesse o site**:
   - Site principal: `http://localhost/Projeto_Site_Escolar`
   - Portal: `http://localhost/Projeto_Site_Escolar/portal`

## 👥 Credenciais de Acesso

### Portal do Sistema

**Administrador:**
- Usuário: `admin`
- Senha: `admin123`

**Professor:**
- Matrícula: `PRO2026001`
- Senha: `prof123`

**Aluno:**
- CPF: `123.456.789-00`
- Senha: `aluno123`

⚠️ **Importante**: Altere as senhas padrão após o primeiro acesso!

## 📱 Funcionalidades

### Site Principal
- **Hero Section**: Carrossel de imagens com chamadas para ação
- **Sobre Nós**: Informações sobre a instituição
- **Projetos**: Showcase de projetos e atividades
- **Eventos**: Calendário de eventos próximos
- **Galeria**: Preview de fotos com modal de visualização
- **Contato**: Informações de contato e redes sociais
- **Menu Mobile**: Responsivo para dispositivos móveis
- **Animações**: Efeitos de scroll e transições suaves
- **Dark Mode**: Alternância entre temas claro/escuro
- **Login Modal**: Autenticação via AJAX com banco de dados
- **SEO**: Meta tags otimizadas, sitemap e robots.txt

### Sistema de Portal

**Administrador:**
- Gerenciamento de usuários (alunos, professores, admins)
- Gerenciamento de turmas e séries
- Visualização e controle de todos os arquivos
- Estatísticas do sistema

**Professor:**
- Upload de arquivos (PDF, DOC, XLS, PPT, imagens, vídeos)
- Definição de visibilidade (turma, série, público)
- Organização por disciplina
- Notificação automática aos alunos

**Aluno:**
- Visualização de arquivos disponíveis para sua turma/série
- Acesso ao dashboard com calendário de eventos
- Recebimento de notificações em tempo real
- Visualização de avisos dos professores
- Avatar e menu de usuário personalizado

### Biblioteca Virtual
- Livros organizados por categorias
- Busca de livros por categoria ou título
- Interface responsiva e moderna
- Livros para todas as faixas etárias

### Galeria de Fotos
- Múltiplos álbuns de fotos
- Modal de visualização em tela cheia
- Navegação por teclado
- Design responsivo

## 🎨 Design e Identidade Visual

### Cores
- **Azul Principal**: `#0a2463`
- **Azul Escuro**: `#051435`
- **Azul Claro**: `#1e3a8a`
- **Amarelo Destaque**: `#ffd700`
- **Amarelo Claro**: `#ffed4a`
- **Verde Complementar**: `#2d6a4f`
- **Verde Claro**: `#40916c`

### Tipografia
- **Sans**: Inter (texto corrido)
- **Display**: Poppins (títulos)

## 🔒 Segurança

- Senhas hasheadas com `password_hash()`
- Proteção contra SQL Injection com PDO prepared statements
- Sanitização de entrada de dados
- Sessões seguras com `httponly` e `secure`
- Validação de tipos de arquivo no upload
- Limitação de tamanho de arquivo (10MB)

## 📝 Licença

Este projeto é um template/base para desenvolvimento de sites institucionais e sistemas de gestão escolar. Pode ser adaptado e utilizado por qualquer instituição educacional.

© 2026 Todos os direitos reservados.

## 👨‍💻 Desenvolvimento

### Scripts Disponíveis
```bash
npm run dev    # Inicia o watcher do Tailwind CSS
npm run build  # Compila o CSS para produção
```

### Estrutura de CSS
- `css/globals.css`: Estilos globais e utilitários
- `css/site-moderno.css`: Estilos específicos do site
- Tailwind CSS via CDN para prototipagem rápida

##  Problemas Conhecidos

- Sistema de correções (aviso2/) está em desenvolvimento
- Arquivos de correções estão vazios e precisam ser implementados
- Imagens placeholder precisam ser substituídas por fotos reais da instituição
- Diretório `img/` está vazio e precisa de imagens

## 🚧 Roadmap

### Em Desenvolvimento
- [ ] Sistema de correções completo
- [ ] Integração de imagens reais
- [ ] Sistema de notificações em tempo real
- [ ] App mobile para pais e alunos

### Futuras Melhorias
- [ ] Sistema de matrículas online
- [ ] Pagamento de mensalidades online
- [ ] Boletim escolar digital
- [ ] Sistema de chamada eletrônica
- [ ] Integração com redes sociais
- [ ] Blog institucional
