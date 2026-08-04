<?php
$pageTitle = 'Biblioteca Virtual';
require_once 'portal/config.php';

// Buscar livros do banco de dados
$livros = [];
$categorias = [];
$termoBusca = isset($_GET['busca']) ? sanitizeInput($_GET['busca']) : '';
$catFiltro   = isset($_GET['cat'])   ? sanitizeInput($_GET['cat'])   : '';

try {
    $pdo = getDBConnection();

    // Buscar categorias
    $catStmt = $pdo->query("SELECT DISTINCT categoria FROM biblioteca_categorias ORDER BY categoria");
    $categorias = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    // Buscar livros
    $sql = "SELECT bl.*, bc.categoria as nome_categoria FROM biblioteca_livros bl
            LEFT JOIN biblioteca_categorias bc ON bl.categoria_id = bc.id WHERE 1=1";
    $params = [];
    if ($termoBusca) { $sql .= " AND (bl.titulo LIKE ? OR bl.autor LIKE ? OR bl.descricao LIKE ?)"; $params = array_merge($params, ["%$termoBusca%", "%$termoBusca%", "%$termoBusca%"]); }
    if ($catFiltro)  { $sql .= " AND bc.categoria = ?"; $params[] = $catFiltro; }
    $sql .= " ORDER BY bl.id DESC LIMIT 24";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $livros = [];
    $categorias = ['Literatura', 'Ciências', 'Matemática', 'História', 'Artes', 'Tecnologia', 'Filosofia', 'Geografia'];
}

// Mock books if DB is empty
$mockBooks = [
    ['titulo'=>'O Pequeno Príncipe','autor'=>'Antoine de Saint-Exupéry','categoria'=>'Literatura','cor'=>'from-rose-500 to-pink-600','icon'=>'fa-crown'],
    ['titulo'=>'Uma Breve História do Tempo','autor'=>'Stephen Hawking','categoria'=>'Ciências','cor'=>'from-cyan-500 to-blue-600','icon'=>'fa-atom'],
    ['titulo'=>'O Homem que Calculava','autor'=>'Malba Tahan','categoria'=>'Matemática','cor'=>'from-violet-500 to-purple-600','icon'=>'fa-infinity'],
    ['titulo'=>'Crônicas de Nárnia','autor'=>'C.S. Lewis','categoria'=>'Literatura','cor'=>'from-amber-500 to-orange-600','icon'=>'fa-dragon'],
    ['titulo'=>'Dom Casmurro','autor'=>'Machado de Assis','categoria'=>'Literatura','cor'=>'from-teal-500 to-emerald-600','icon'=>'fa-feather'],
    ['titulo'=>'Cosmos','autor'=>'Carl Sagan','categoria'=>'Ciências','cor'=>'from-indigo-500 to-blue-700','icon'=>'fa-globe'],
    ['titulo'=>'O Alquimista','autor'=>'Paulo Coelho','categoria'=>'Literatura','cor'=>'from-yellow-500 to-amber-600','icon'=>'fa-star'],
    ['titulo'=>'Sapiens','autor'=>'Yuval Noah Harari','categoria'=>'História','cor'=>'from-slate-500 to-gray-700','icon'=>'fa-landmark'],
];
?>
<?php require_once 'includes/header.php'; ?>

<style>
/* ─── LUXURY LIBRARY STYLES ───────────────────────────── */
* { font-family: 'Outfit', sans-serif; }

/* Hero */
.lib-hero {
  min-height: 56vh;
  background: #050c1a;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
}
.lib-hero-bg {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
  background-size: 50px 50px;
}
.lib-glow-1 { position:absolute; top:-20%; left:10%; width:600px; height:600px; border-radius:50%; background:radial-gradient(circle,rgba(139,92,246,0.2) 0%,transparent 70%); filter:blur(60px); pointer-events:none; }
.lib-glow-2 { position:absolute; bottom:-20%; right:5%; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle,rgba(59,130,246,0.18) 0%,transparent 70%); filter:blur(50px); pointer-events:none; }
.lib-glow-3 { position:absolute; top:30%; right:30%; width:250px; height:250px; border-radius:50%; background:radial-gradient(circle,rgba(245,158,11,0.08) 0%,transparent 70%); filter:blur(40px); pointer-events:none; }

.lib-eyebrow {
  display:inline-flex; align-items:center; gap:8px;
  font-size:0.72rem; font-weight:700; letter-spacing:0.14em;
  text-transform:uppercase; color:rgba(139,92,246,0.9);
  margin-bottom:14px;
}
.lib-hero-title {
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 800; color: white;
  line-height: 1.1; letter-spacing:-0.02em;
  margin-bottom: 16px;
}
.lib-hero-title .grad {
  background: linear-gradient(135deg, #a78bfa, #60a5fa, #34d399);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.lib-hero-sub {
  font-size: 1rem; color: rgba(255,255,255,0.45);
  line-height: 1.7; max-width: 480px; margin-bottom: 36px;
}

/* Search */
.lib-search-wrap {
  display: flex; gap: 0;
  background: rgba(255,255,255,0.08);
  border: 1.5px solid rgba(255,255,255,0.12);
  border-radius: 16px;
  backdrop-filter: blur(14px);
  overflow: hidden;
  max-width: 560px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.lib-search-wrap:focus-within {
  border-color: rgba(139,92,246,0.6);
  box-shadow: 0 0 0 3px rgba(139,92,246,0.12);
}
.lib-search-input {
  flex:1; padding:15px 18px; background:transparent;
  border:none; outline:none; color:white;
  font-size:0.95rem; font-family:'Outfit',sans-serif;
}
.lib-search-input::placeholder { color: rgba(255,255,255,0.35); }
.lib-search-btn {
  padding: 15px 22px;
  background: linear-gradient(135deg,#7c3aed,#4f46e5);
  color: white; border:none; cursor:pointer;
  font-weight:700; font-size:0.9rem;
  display:flex; align-items:center; gap:8px;
  transition: opacity 0.2s;
  font-family:'Outfit',sans-serif;
}
.lib-search-btn:hover { opacity:0.9; }

/* Stats */
.lib-stat {
  text-align: center;
}
.lib-stat-num {
  font-size: 1.8rem; font-weight: 800; color: white;
  line-height: 1;
}
.lib-stat-label {
  font-size: 0.72rem; color: rgba(255,255,255,0.35);
  font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
  margin-top: 4px;
}

/* Category chips */
.cat-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 100px;
  font-size: 0.82rem; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: all 0.25s ease;
  white-space: nowrap;
}
.cat-chip.active {
  background: linear-gradient(135deg,#7c3aed,#4f46e5);
  color: white; box-shadow: 0 4px 16px rgba(124,58,237,0.35);
}
.cat-chip:not(.active) {
  background: white; color: #374151;
  border: 1.5px solid #e5e7eb;
}
.cat-chip:not(.active):hover {
  background: #f0fdf4; border-color: #86efac; color: #15803d;
  transform: translateY(-1px);
}

/* Book cards */
.book-card {
  border-radius: 20px;
  overflow: hidden;
  background: white;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
  text-decoration: none;
  display: block;
}
.book-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 50px rgba(0,0,0,0.12);
  border-color: transparent;
}
.book-cover {
  height: 180px;
  display: flex; align-items: center; justify-content: center;
  position: relative; overflow: hidden;
}
.book-cover-icon { font-size: 3rem; color: white; opacity: 0.9; position: relative; z-index: 1; }
.book-cover::before {
  content: '';
  position: absolute; top: -20px; right: -20px;
  width: 100px; height: 100px; border-radius: 50%;
  background: rgba(255,255,255,0.1);
}
.book-cover::after {
  content: '';
  position: absolute; bottom: -30px; left: -15px;
  width: 120px; height: 120px; border-radius: 50%;
  background: rgba(255,255,255,0.07);
}
.book-body { padding: 18px 20px 20px; }
.book-badge {
  display: inline-block;
  padding: 3px 10px; border-radius: 100px;
  font-size: 0.68rem; font-weight: 700;
  letter-spacing: 0.05em; text-transform: uppercase;
  margin-bottom: 8px;
}
.book-title {
  font-size: 0.95rem; font-weight: 700;
  color: #0f172a; line-height: 1.3; margin-bottom: 4px;
}
.book-author {
  font-size: 0.8rem; color: #94a3b8; margin-bottom: 14px;
}
.book-read-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 0.78rem; font-weight: 700;
  color: #7c3aed; text-decoration: none;
  transition: gap 0.2s;
}
.book-card:hover .book-read-btn { gap: 10px; }

/* Featured section */
.featured-card {
  border-radius: 24px;
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  padding: 36px; overflow: hidden; position: relative;
  border: 1px solid rgba(139,92,246,0.2);
}
.featured-card::before {
  content: '';
  position: absolute; top: -40px; right: -40px;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(139,92,246,0.12);
}

/* Reveal */
.reveal { opacity:0; transform:translateY(28px); transition:opacity 0.65s ease,transform 0.65s ease; }
.reveal.visible { opacity:1; transform:translateY(0); }
</style>

<!-- ══════════ HERO ══════════ -->
<section class="lib-hero">
  <div class="lib-hero-bg"></div>
  <div class="lib-glow-1"></div>
  <div class="lib-glow-2"></div>
  <div class="lib-glow-3"></div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20" style="position:relative;z-index:10;">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- Left -->
      <div>
        <div class="lib-eyebrow"><i class="fas fa-book-open"></i> Colégio de Excelência</div>
        <h1 class="lib-hero-title">
          Biblioteca<br>
          <span class="grad">Virtual de Luxo</span>
        </h1>
        <p class="lib-hero-sub">
          Acesse nosso acervo completo com milhares de títulos, recursos digitais e materiais pedagógicos — disponíveis 24h para toda a comunidade escolar.
        </p>

        <form method="GET" action="biblioteca.php">
          <div class="lib-search-wrap">
            <input type="text" name="busca" class="lib-search-input"
                   placeholder="Buscar livros, autores ou temas..."
                   value="<?= htmlspecialchars($termoBusca) ?>">
            <button type="submit" class="lib-search-btn">
              <i class="fas fa-search"></i> Buscar
            </button>
          </div>
        </form>
      </div>

      <!-- Right — Stats Panel -->
      <div class="hidden lg:block">
        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:32px;backdrop-filter:blur(16px);">
          <div style="border-top:2px solid rgba(139,92,246,0.5);margin-bottom:24px;"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:28px;">
            <div class="lib-stat"><div class="lib-stat-num">4.800+</div><div class="lib-stat-label">Títulos</div></div>
            <div class="lib-stat"><div class="lib-stat-num">320</div><div class="lib-stat-label">Revistas</div></div>
            <div class="lib-stat"><div class="lib-stat-num">24h</div><div class="lib-stat-label">Disponível</div></div>
          </div>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <?php
            $feats = [
              ['fas fa-download','Download de PDFs','Baixe materiais para leitura offline'],
              ['fas fa-headphones','Audiobooks','Ouça enquanto estuda ou se desloca'],
              ['fas fa-bookmark','Sua Estante','Organize seus livros favoritos'],
            ];
            foreach($feats as $f):
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:14px;background:rgba(255,255,255,0.04);border-radius:14px;border:1px solid rgba(255,255,255,0.06);">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(139,92,246,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="<?= $f[0] ?>" style="color:#a78bfa;font-size:0.9rem;"></i>
              </div>
              <div>
                <div style="font-size:0.88rem;font-weight:700;color:white;margin-bottom:2px;"><?= $f[1] ?></div>
                <div style="font-size:0.75rem;color:rgba(255,255,255,0.35);"><?= $f[2] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════ CATEGORIES ══════════ -->
<section style="background:#f8fafc;padding:28px 0;border-bottom:1px solid #e2e8f0;position:sticky;top:76px;z-index:50;">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div style="display:flex;align-items:center;gap:10px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none;" class="no-scrollbar">
      <a href="biblioteca.php" class="cat-chip <?= !$catFiltro ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Todos
      </a>
      <?php
      $catIcons = ['Literatura'=>'fa-book','Ciências'=>'fa-flask','Matemática'=>'fa-calculator','História'=>'fa-landmark','Artes'=>'fa-palette','Tecnologia'=>'fa-laptop-code','Filosofia'=>'fa-lightbulb','Geografia'=>'fa-globe'];
      $displayCats = !empty($categorias) ? $categorias : array_keys($catIcons);
      foreach($displayCats as $cat):
        $icon = $catIcons[$cat] ?? 'fa-book';
        $isActive = $catFiltro === $cat;
      ?>
      <a href="biblioteca.php?cat=<?= urlencode($cat) ?>" class="cat-chip <?= $isActive ? 'active' : '' ?>">
        <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($cat) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════ FEATURED + BOOKS ══════════ -->
<section style="background:#f8fafc;padding:56px 0;">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Featured strip -->
    <?php if (!$termoBusca && !$catFiltro): ?>
    <div class="featured-card reveal" style="margin-bottom:56px;">
      <div style="display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;" class="grid-featured">
        <div style="position:relative;z-index:1;">
          <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(167,139,250,0.8);margin-bottom:10px;">
            <i class="fas fa-fire-alt" style="margin-right:4px;"></i> Mais lido da semana
          </div>
          <h2 style="font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:white;line-height:1.2;margin-bottom:10px;">
            O Pequeno Príncipe
          </h2>
          <p style="font-size:0.9rem;color:rgba(255,255,255,0.45);margin-bottom:6px;">Antoine de Saint-Exupéry · Literatura Infantojuvenil</p>
          <p style="font-size:0.85rem;color:rgba(255,255,255,0.35);max-width:500px;line-height:1.7;margin-bottom:24px;">
            Um clássico universal que fala sobre amizade, amor e a essência da vida através dos olhos de uma criança. Disponível em português, inglês e espanhol.
          </p>
          <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="#" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:white;font-weight:700;font-size:0.9rem;text-decoration:none;box-shadow:0 6px 20px rgba(124,58,237,0.4);">
              <i class="fas fa-book-reader"></i> Ler agora
            </a>
            <a href="#" style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:12px;background:rgba(255,255,255,0.07);border:1.5px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.8);font-weight:600;font-size:0.9rem;text-decoration:none;">
              <i class="fas fa-download"></i> Baixar PDF
            </a>
          </div>
        </div>
        <div class="hidden md:flex" style="width:160px;height:220px;background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:12px;align-items:center;justify-content:center;box-shadow:0 20px 50px rgba(124,58,237,0.4);flex-shrink:0;position:relative;z-index:1;">
          <i class="fas fa-crown" style="font-size:4rem;color:rgba(255,255,255,0.8);"></i>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Section heading -->
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:32px;" class="reveal">
      <div>
        <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#7c3aed;margin-bottom:6px;">
          <?= $termoBusca ? 'Resultados para "'.htmlspecialchars($termoBusca).'"' : ($catFiltro ? htmlspecialchars($catFiltro) : 'Acervo Completo') ?>
        </div>
        <h2 style="font-family:'Outfit',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;color:#0f172a;line-height:1.2;margin:0;">
          <?= $termoBusca ? 'Encontrados na Biblioteca' : 'Livros em Destaque' ?>
        </h2>
      </div>
      <?php if (!$termoBusca): ?>
      <a href="biblioteca.php?busca=" style="font-size:0.85rem;font-weight:700;color:#7c3aed;text-decoration:none;display:flex;align-items:center;gap:6px;">
        Ver todo acervo <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
      </a>
      <?php endif; ?>
    </div>

    <!-- Books grid -->
    <?php
    $displayBooks = !empty($livros) ? $livros : $mockBooks;
    $gradients = [
      'from-rose-500 to-pink-600', 'from-cyan-500 to-blue-600',
      'from-violet-500 to-purple-600', 'from-amber-500 to-orange-600',
      'from-teal-500 to-emerald-600', 'from-indigo-500 to-blue-700',
      'from-yellow-500 to-amber-600', 'from-slate-500 to-gray-700',
    ];
    $gradCss = [
      'linear-gradient(135deg,#f43f5e,#ec4899)',
      'linear-gradient(135deg,#06b6d4,#3b82f6)',
      'linear-gradient(135deg,#8b5cf6,#7c3aed)',
      'linear-gradient(135deg,#f59e0b,#ea580c)',
      'linear-gradient(135deg,#14b8a6,#10b981)',
      'linear-gradient(135deg,#6366f1,#1d4ed8)',
      'linear-gradient(135deg,#eab308,#d97706)',
      'linear-gradient(135deg,#64748b,#374151)',
    ];
    $icons = ['fa-crown','fa-atom','fa-infinity','fa-dragon','fa-feather','fa-globe','fa-star','fa-landmark','fa-book','fa-flask','fa-calculator','fa-palette'];
    $badgeColors = [
      'background:#fce7f3;color:#9d174d','background:#dbeafe;color:#1e40af',
      'background:#ede9fe;color:#5b21b6','background:#fef3c7;color:#92400e',
      'background:#d1fae5;color:#065f46','background:#e0e7ff;color:#3730a3',
      'background:#fef9c3;color:#854d0e','background:#f1f5f9;color:#1e293b',
    ];
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;" class="reveal">
      <?php foreach($displayBooks as $idx => $book):
        $titulo   = $book['titulo']   ?? $book['titulo']   ?? 'Sem título';
        $autor    = $book['autor']    ?? $book['autor']    ?? 'Autor desconhecido';
        $cat      = $book['nome_categoria'] ?? $book['categoria'] ?? 'Geral';
        $icon     = $book['icon']     ?? $icons[$idx % count($icons)];
        $grad     = $gradCss[$idx % count($gradCss)];
        $badge    = $badgeColors[$idx % count($badgeColors)];
      ?>
      <a href="#" class="book-card">
        <div class="book-cover" style="background:<?= $grad ?>;">
          <i class="fas <?= $icon ?> book-cover-icon"></i>
        </div>
        <div class="book-body">
          <span class="book-badge" style="<?= $badge ?>"><?= htmlspecialchars($cat) ?></span>
          <div class="book-title"><?= htmlspecialchars($titulo) ?></div>
          <div class="book-author"><?= htmlspecialchars($autor) ?></div>
          <span class="book-read-btn">Acessar <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></span>
        </div>
      </a>
      <?php endforeach; ?>

      <?php if (empty($displayBooks)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;">
        <i class="fas fa-search" style="font-size:3rem;margin-bottom:16px;display:block;opacity:0.4;"></i>
        <p style="font-size:1.1rem;font-weight:600;">Nenhum livro encontrado.</p>
        <a href="biblioteca.php" style="color:#7c3aed;font-weight:700;text-decoration:none;">Limpar busca</a>
      </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- ══════════ ACCESS CTA ══════════ -->
<section style="background:white;padding:64px 0;">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 reveal">
    <div style="background:linear-gradient(135deg,#050c1a 0%,#1e1b4b 60%,#2e1065 100%);border-radius:24px;padding:52px;text-align:center;position:relative;overflow:hidden;">
      <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:rgba(139,92,246,0.12);"></div>
      <div style="position:absolute;bottom:-30px;left:-30px;width:150px;height:150px;border-radius:50%;background:rgba(59,130,246,0.08);"></div>
      <div style="position:relative;">
        <i class="fas fa-university" style="font-size:2.5rem;color:rgba(167,139,250,0.7);margin-bottom:16px;display:block;"></i>
        <h2 style="font-family:'Outfit',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;color:white;margin-bottom:12px;">
          Acesso completo para alunos
        </h2>
        <p style="color:rgba(255,255,255,0.45);font-size:0.95rem;max-width:460px;margin:0 auto 28px;">
          Entre com suas credenciais para acessar o acervo completo, baixar materiais e salvar seus livros favoritos.
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
          <button onclick="document.getElementById('acesso-sistema-btn').click()" style="display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:white;font-weight:700;font-size:0.95rem;border:none;cursor:pointer;box-shadow:0 8px 24px rgba(124,58,237,0.4);">
            <i class="fas fa-lock"></i> Fazer login
          </button>
          <a href="pre_matricula.php" style="display:inline-flex;align-items:center;gap:10px;padding:13px 26px;border-radius:14px;background:rgba(255,255,255,0.07);border:1.5px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.8);font-weight:600;font-size:0.95rem;text-decoration:none;">
            <i class="fas fa-user-plus"></i> Matricule-se
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const obs = new IntersectionObserver(e => e.forEach(el => { if(el.isIntersecting){ el.target.classList.add('visible'); obs.unobserve(el.target); } }), {threshold:0.1});
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
</script>

<style>
.no-scrollbar::-webkit-scrollbar { display:none; }
@media(max-width:768px){
  .grid-featured { grid-template-columns:1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
