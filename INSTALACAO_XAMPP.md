# Passo a Passo - Instalação no XAMPP 8.2.12

## 1. Preparação do XAMPP

### 1.1. Verificar se o XAMPP está instalado
- Abra o XAMPP Control Panel
- Verifique se Apache e MySQL estão rodando
- Se não estiverem, inicie ambos os serviços

### 1.2. Configurar o PHP
- No XAMPP Control Panel, clique em "Config" ao lado de Apache
- Selecione "php.ini"
- Verifique se as seguintes extensões estão habilitadas (remover o ; se estiver presente):
  ```
  extension=pdo_mysql
  extension=mbstring
  extension=fileinfo
  ```
- Salve e reinicie o Apache

## 2. Configurar o Banco de Dados MySQL

### 2.1. Acessar o phpMyAdmin
- Abra o navegador e acesse: `http://localhost/phpmyadmin`
- Faça login (geralmente usuário: root, senha em branco)

### 2.2. Criar o banco de dados manualmente
- Clique em "Novo" no menu lateral
- Nome do banco: `ceaa_escola`
- Collation: `utf8mb4_unicode_ci`
- Clique em "Criar"

### 2.3. Importar o schema.sql
- Selecione o banco `ceaa_escola`
- Clique na aba "Importar"
- Clique em "Escolher arquivo"
- Navegue até: `g:\Meus Sites\Alameda Argentina\ceaacolegiobrasil.com.br\database\schema.sql`
- Clique em "Importar"
- Aguarde a conclusão da importação

### 2.4. Verificar se os usuários foram criados
- Clique na tabela `usuarios`
- Verifique se existem 3 usuários:
  - Administrador (usuario_login: admin)
  - Professor Teste (matricula: PRO2026001)
  - Aluno Teste (cpf: 123.456.789-00)

## 3. Configurar o Sistema

### 3.1. Configurar o arquivo config.php
- Abra o arquivo: `g:\Meus Sites\Alameda Argentina\ceaacolegiobrasil.com.br\portal\config.php`
- Verifique as configurações do banco de dados:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'ceaa_escola');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  ```
- Se sua senha do MySQL for diferente, altere `DB_PASS`
- Configure o URL do site:
  ```php
  define('SITE_URL', 'http://localhost/ceaacolegiobrasil.com.br/portal');
  ```

### 3.2. Criar diretório de uploads
- Navegue até: `g:\Meus Sites\Alameda Argentina\ceaacolegiobrasil.com.br\portal`
- Crie a pasta `uploads`
- Dentro de `uploads`, crie a pasta `arquivos`

## 4. Testar o Sistema

### 4.1. Acessar o site principal
- Abra o navegador e acesse: `http://localhost/ceaacolegiobrasil.com.br`
- Verifique se o site carrega corretamente

### 4.2. Testar o modal de login
- Clique no botão "Acesso ao Sistema" no menu
- O modal deve abrir com 3 tabs: Professor, Aluno, Admin

### 4.3. Testar login como Admin
- Selecione a tab "Admin"
- Usuário: `admin`
- Senha: `admin123`
- Clique em "Entrar como Admin"
- Deve ser redirecionado para o dashboard do admin

### 4.4. Testar login como Professor
- Faça logout
- Clique novamente em "Acesso ao Sistema"
- Selecione a tab "Professor"
- Matrícula: `PRO2026001`
- Senha: `prof123`
- Clique em "Entrar como Professor"
- Deve ser redirecionado para o dashboard do professor

### 4.5. Testar login como Aluno
- Faça logout
- Clique novamente em "Acesso ao Sistema"
- Selecione a tab "Aluno"
- CPF: `123.456.789-00`
- Senha: `aluno123`
- Clique em "Entrar como Aluno"
- Deve ser redirecionado para o dashboard do aluno

## 5. Testar Funcionalidades

### 5.1. Portal do Admin
- Gerenciar usuários (adicionar, ativar/desativar, excluir)
- Gerenciar turmas (criar novas turmas)
- Gerenciar arquivos (visualizar todos, ativar/desativar)

### 5.2. Portal do Professor
- Fazer upload de arquivos
- Definir visibilidade (turma, série, público)
- Organizar por disciplina

### 5.3. Portal do Aluno
- Visualizar arquivos disponíveis
- Ver calendário de eventos
- Ver avisos dos professores

## 6. Solução de Problemas

### 6.1. Erro de conexão com o banco
- Verifique se o MySQL está rodando no XAMPP
- Verifique as credenciais no config.php
- Tente acessar o phpMyAdmin para verificar se o banco existe

### 6.2. Erro ao fazer upload de arquivos
- Verifique se o diretório `portal/uploads/arquivos` existe
- Verifique as permissões do diretório
- Verifique o tamanho máximo do arquivo (10MB)

### 6.3. Página em branco
- Verifique os logs de erro do Apache (geralmente em `C:\xampp\apache\logs\error.log`)
- Verifique se o PHP está configurado corretamente
- Habilite a exibição de erros no config.php temporariamente para debug

## 7. Segurança

### 7.1. Após testes bem-sucedidos
- Altere a senha do admin padrão
- Delete o arquivo `portal/install.php` se existir
- Configure HTTPS em produção
- Altere a configuração `session.cookie_secure` para 1 em produção

## 8. Credenciais de Teste

### Admin
- Usuário: `admin`
- Senha: `admin123`

### Professor
- Matrícula: `PRO2026001`
- Senha: `prof123`

### Aluno
- CPF: `123.456.789-00`
- Senha: `aluno123`

---

**Nota:** Este passo a passo assume que o XAMPP está instalado em `C:\xampp` e o site está em `g:\Meus Sites\Alameda Argentina\ceaacolegiobrasil.com.br`. Ajuste os caminhos conforme necessário.
