# Portal Escolar CEAA - Sistema de Gestão Escolar

Sistema completo de gestão escolar com portais para alunos, professores e administradores.

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior / MariaDB 10.2 ou superior
- Servidor web (Apache, Nginx, etc.)
- Extensões PHP: PDO, PDO_MySQL, mbstring

## 🚀 Instalação

### 1. Configuração do Banco de Dados

1. Crie o banco de dados MySQL usando o arquivo `schema.sql`:

```bash
mysql -u root -p < database/schema.sql
```

Ou execute manualmente no MySQL:

```sql
CREATE DATABASE IF NOT EXISTS ceaa_escola CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ceaa_escola;

-- Execute o conteúdo do arquivo database/schema.sql
```

2. Configure as credenciais do banco de dados no arquivo `portal/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ceaa_escola');
define('DB_USER', 'seu_usuario_mysql');
define('DB_PASS', 'sua_senha_mysql');
```

### 2. Configuração de Diretórios

1. Crie o diretório de uploads:

```bash
mkdir -p portal/uploads/arquivos
chmod 755 portal/uploads
chmod 755 portal/uploads/arquivos
```

2. Configure o URL do site no arquivo `portal/config.php`:

```php
define('SITE_URL', 'https://seusite.com.br/portal');
```

### 3. Acesso ao Sistema

- **Login do Admin**: 
  - Usuário: `admin`
  - Senha: `admin123`
  - ⚠️ **Importante**: Altere a senha do admin após o primeiro acesso!

- **Login do Professor**: 
  - Matrícula: `PRO2026001`
  - Senha: `prof123`

- **Login do Aluno**: 
  - CPF: `123.456.789-00`
  - Senha: `aluno123`

- **Cadastro de Alunos**: Acesse `portal/register.php` para novos alunos se cadastrarem

- **Login via Modal**: No site principal (index.html), clique em "Acesso ao Sistema" para fazer login diretamente

## 📁 Estrutura de Diretórios

```
portal/
├── config.php              # Configurações do sistema
├── login.php               # Página de login
├── logout.php              # Página de logout
├── register.php            # Cadastro de alunos
├── dashboard.php           # Dashboard principal
├── admin/                  # Portal do administrador
│   ├── index.php          # Dashboard do admin
│   ├── usuarios.php       # Gerenciamento de usuários
│   ├── turmas.php         # Gerenciamento de turmas
│   └── arquivos.php       # Gerenciamento de arquivos
├── professor/              # Portal do professor
│   └── upload.php         # Upload de arquivos
├── uploads/                # Diretório de uploads
│   └── arquivos/          # Arquivos enviados
└── database/
    └── schema.sql          # Schema do banco de dados
```

## 👥 Perfis de Usuário

### Administrador
- Gerenciar usuários (alunos, professores, admins)
- Gerenciar turmas e séries
- Gerenciar todos os arquivos do sistema
- Visualizar estatísticas do sistema

### Professor
- Fazer upload de arquivos (trabalhos, correções, materiais, vídeos)
- Definir visibilidade dos arquivos (turma, série, público)
- Organizar arquivos por disciplina

### Aluno
- Visualizar arquivos disponíveis para sua turma/série
- Acessar dashboard com calendário de eventos
- Receber notificações de novos arquivos
- Visualizar avisos dos professores

## 🔐 Segurança

1. **Alterar senha do admin**: Após a instalação, altere imediatamente a senha do administrador padrão

2. **HTTPS**: Em produção, configure HTTPS e altere a configuração:
```php
ini_set('session.cookie_secure', 1);
```

3. **Permissões de arquivos**: Mantenha permissões adequadas nos diretórios de uploads

4. **Backup**: Faça backups regulares do banco de dados

## 📝 Funcionalidades

### Dashboard do Aluno
- Visualização de arquivos por turma/série
- Calendário de eventos
- Avisos dos professores
- Notificações em tempo real
- Estatísticas pessoais

### Portal do Professor
- Upload de arquivos (PDF, DOC, XLS, PPT, imagens, vídeos)
- Definição de visibilidade (turma, série, público)
- Organização por disciplina
- Notificação automática aos alunos

### Portal do Admin
- Gerenciamento completo de usuários
- Criação e gerenciamento de turmas
- Visualização e controle de todos os arquivos
- Estatísticas do sistema

## 🎨 Design

O sistema utiliza:
- **Tailwind CSS** para estilização
- **FontAwesome** para ícones
- **Google Fonts** (Inter e Poppins)
- Design responsivo e moderno
- Paleta de cores consistente com o site principal

## 🔧 Configurações Adicionais

### Tamanho Máximo de Arquivo
Em `config.php`, altere o tamanho máximo:
```php
define('MAX_FILE_SIZE', 10485760); // 10MB em bytes
```

### Extensões Permitidas
Em `config.php`, altere as extensões permitidas:
```php
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm']);
```

## 🐛 Solução de Problemas

### Erro de conexão com o banco
- Verifique as credenciais em `config.php`
- Certifique-se que o MySQL está rodando
- Verifique se o banco de dados foi criado

### Erro ao fazer upload
- Verifique as permissões do diretório `uploads/`
- Verifique o tamanho máximo do arquivo
- Verifique se a extensão é permitida

### Página em branco
- Verifique os logs de erro do PHP
- Certifique-se que todas as extensões PHP estão instaladas
- Verifique o encoding UTF-8 dos arquivos

## 📞 Suporte

Para suporte técnico, entre em contato com o desenvolvedor do sistema.

## 📄 Licença

Este sistema foi desenvolvido exclusivamente para o Centro Educacional Alameda Argentina.

---

**Desenvolvido para CEAA - Centro Educacional Alameda Argentina**
© 2026 Todos os direitos reservados
