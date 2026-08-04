<?php
$pageTitle = 'Início';
require_once 'portal/config.php';

// Buscar imagens do banner de matrícula no banco de dados
$bannerImages = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM site_imagens WHERE categoria = 'banner_matricula' AND ativo = 1 ORDER BY ordem ASC, id ASC");
    $stmt->execute();
    $bannerImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bannerImages = [];
}

// Imagens padrão caso não haja nenhuma cadastrada
$defaultImages = [
    ['url' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800&q=80', 'alt' => 'Sala de aula'],
    ['url' => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?w=800&q=80', 'alt' => 'Alunos estudando'],
    ['url' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=800&q=80', 'alt' => 'Biblioteca'],
    ['url' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&q=80', 'alt' => 'Laboratório'],
];
?>

<?php require_once 'includes/header.php'; ?>

<style>
/* ─── LUXURY HOMEPAGE STYLES ──────────────────────── */

/* Fonts */
.font-outfit { font-family: 'Outfit', sans-serif; }

/* ── HERO ── */
.hero-section {
  min-height: 90vh;
  background: #0a0f1e;
  position: relative;
  display: flex;
  align-items: center;
  overflow: hidden;
}
.hero-bg-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 60px 60px;
}
.hero-glow-1 {
  position: absolute;
  top: -20%; left: -10%;
  width: 700px; height: 700px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(29,78,216,0.25) 0%, transparent 70%);
  filter: blur(60px);
}
.hero-glow-2 {
  position: absolute;
  bottom: -20%; right: -5%;
  width: 500px; height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(109,40,217,0.18) 0%, transparent 70%);
  filter: blur(50px);
}
.hero-glow-3 {
  position: absolute;
  top: 30%; right: 15%;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245,158,11,0.1) 0%, transparent 70%);
  filter: blur(40px);
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border-radius: 100px;
  padding: 8px 20px;
  color: rgba(255,255,255,0.85);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 28px;
}
.hero-badge .dot {
  width: 6px; height: 6px;
  background: #22d3ee;
  border-radius: 50%;
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0%,100%{ opacity:1; transform:scale(1); }
  50%{ opacity:0.5; transform:scale(1.4); }
}

.hero-title {
  font-family: 'Outfit', sans-serif;
  font-size: clamp(2.8rem, 6vw, 5.2rem);
  font-weight: 800;
  color: #fff;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin-bottom: 24px;
}
.hero-title .accent {
  background: linear-gradient(135deg, #60a5fa, #a78bfa, #f472b6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-desc {
  font-size: 1.15rem;
  color: rgba(255,255,255,0.55);
  line-height: 1.75;
  max-width: 520px;
  margin-bottom: 40px;
  font-weight: 300;
}

.hero-cta-wrap { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
.btn-hero-primary {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 15px 32px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1d4ed8, #4f46e5);
  color: white;
  font-weight: 700;
  font-size: 0.95rem;
  text-decoration: none;
  box-shadow: 0 8px 28px rgba(29,78,216,0.45);
  transition: all 0.3s ease;
  border: none; cursor: pointer;
}
.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 40px rgba(29,78,216,0.55);
}
.btn-hero-secondary {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 14px 28px;
  border-radius: 14px;
  background: rgba(255,255,255,0.07);
  border: 1.5px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.85);
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}
.btn-hero-secondary:hover {
  background: rgba(255,255,255,0.13);
  border-color: rgba(255,255,255,0.3);
}

/* Hero stats */
.hero-stats {
  display: flex; flex-wrap: wrap; gap: 32px;
  margin-top: 60px;
  padding-top: 40px;
  border-top: 1px solid rgba(255,255,255,0.08);
}
.stat-item { text-align: left; }
.stat-number {
  font-family: 'Outfit', sans-serif;
  font-size: 2.2rem;
  font-weight: 800;
  color: white;
  line-height: 1;
  margin-bottom: 4px;
}
.stat-label {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.45);
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

/* Hero right card */
.hero-visual {
  position: relative;
}
.hero-card-main {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 28px;
  position: relative;
  overflow: hidden;
}
.hero-card-main::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
}

.student-photo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 20px;
}
.photo-tile {
  border-radius: 14px;
  overflow: hidden;
  aspect-ratio: 4/3;
  position: relative;
}
.photo-tile img {
  width: 100%; height: 100%;
  object-fit: cover;
}
.photo-tile.large {
  grid-row: span 2;
  aspect-ratio: auto;
}

.floating-badge {
  position: absolute;
  background: white;
  border-radius: 14px;
  padding: 12px 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
  display: flex; align-items: center; gap: 10px;
  white-space: nowrap;
}
.floating-badge.top-left {
  top: -12px; left: -20px;
}
.floating-badge.bottom-right {
  bottom: 60px; right: -20px;
}
.floating-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.floating-label { font-size: 0.75rem; font-weight: 700; color: #0f172a; }
.floating-sub { font-size: 0.68rem; color: #64748b; font-weight: 500; }

/* ── SECTION TITLES ── */
.section-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #3b82f6;
  margin-bottom: 12px;
}
.section-eyebrow::before, .section-eyebrow::after {
  content: '';
  display: block; width: 20px; height: 2px;
  background: currentColor; border-radius: 2px;
}
.section-title {
  font-family: 'Outfit', sans-serif;
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin-bottom: 16px;
}
.section-desc {
  font-size: 1rem;
  color: #64748b;
  line-height: 1.7;
  max-width: 560px;
}

/* ── LEVELS / ETAPAS ── */
.levels-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 20px; }
.level-card {
  border-radius: 20px;
  padding: 32px 28px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  text-decoration: none;
  display: block;
  transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
  border: 1px solid transparent;
}
.level-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 50px -10px var(--shadow);
}
.level-card .card-icon {
  width: 56px; height: 56px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  margin-bottom: 20px;
  background: var(--icon-bg);
  color: var(--icon-color);
}
.level-card .card-title {
  font-family: 'Outfit', sans-serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}
.level-card .card-sub {
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 20px;
}
.level-card .card-arrow {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--accent);
  transition: gap 0.2s;
}
.level-card:hover .card-arrow { gap: 10px; }

.level-card-1 { background: #f0f9ff; border-color: #bae6fd; --shadow: rgba(14,165,233,0.2); --icon-bg: #e0f2fe; --icon-color: #0284c7; --accent: #0284c7; }
.level-card-2 { background: #f0fdf4; border-color: #bbf7d0; --shadow: rgba(34,197,94,0.2); --icon-bg: #dcfce7; --icon-color: #16a34a; --accent: #16a34a; }
.level-card-3 { background: #faf5ff; border-color: #e9d5ff; --shadow: rgba(139,92,246,0.2); --icon-bg: #ede9fe; --icon-color: #7c3aed; --accent: #7c3aed; }
.level-card-4 { background: #fff7ed; border-color: #fed7aa; --shadow: rgba(249,115,22,0.2); --icon-bg: #ffedd5; --icon-color: #ea580c; --accent: #ea580c; }

/* ── FEATURES STRIP ── */
.features-strip {
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  position: relative;
  overflow: hidden;
}
.features-strip::before {
  content: '';
  position: absolute;
  top: -50%; left: -10%;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
  filter: blur(40px);
}
.feature-item {
  display: flex; align-items: flex-start; gap: 16px;
  padding: 28px 0;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.feature-item:last-child { border-bottom: none; }
.feature-dot {
  width: 44px; height: 44px; flex-shrink: 0;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.feature-item h4 {
  font-family: 'Poppins', sans-serif;
  font-size: 1rem; font-weight: 700;
  color: white; margin-bottom: 4px;
}
.feature-item p { font-size: 0.85rem; color: rgba(255,255,255,0.45); line-height: 1.6; }

/* ── NEWS ── */
.news-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transition: all 0.3s ease;
  border: 1px solid #f1f5f9;
  text-decoration: none;
  display: block;
}
.news-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 44px rgba(0,0,0,0.1);
}
.news-img { width: 100%; height: 200px; object-fit: cover; }
.news-body { padding: 22px 24px 26px; }
.news-tag {
  display: inline-block;
  padding: 4px 12px; border-radius: 100px;
  font-size: 0.72rem; font-weight: 700;
  letter-spacing: 0.06em; text-transform: uppercase;
  margin-bottom: 10px;
}
.news-title {
  font-family: 'Outfit', sans-serif;
  font-size: 1.15rem; font-weight: 700;
  color: #0f172a; line-height: 1.35;
  margin-bottom: 8px;
}
.news-meta { font-size: 0.8rem; color: #94a3b8; font-weight: 500; }

/* ── EVENTS ── */
.event-card {
  background: white;
  border-radius: 16px;
  padding: 20px 22px;
  display: flex; align-items: center; gap: 18px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  transition: all 0.25s ease;
  text-decoration: none;
}
.event-card:hover {
  transform: translateX(4px);
  box-shadow: 0 8px 28px rgba(0,0,0,0.08);
  border-color: #e0f2fe;
}
.event-date-box {
  flex-shrink: 0;
  width: 52px; text-align: center;
  background: #f0f9ff;
  border-radius: 12px;
  padding: 8px 4px;
}
.event-date-box .day {
  font-family: 'Outfit', sans-serif;
  font-size: 1.5rem; font-weight: 800; color: #1d4ed8; line-height: 1;
}
.event-date-box .month {
  font-size: 0.65rem; font-weight: 700;
  text-transform: uppercase; color: #64748b;
  letter-spacing: 0.05em;
}
.event-info h4 {
  font-size: 0.9rem; font-weight: 700;
  color: #0f172a; margin-bottom: 3px;
}
.event-info p { font-size: 0.78rem; color: #94a3b8; }
.event-arrow {
  margin-left: auto; flex-shrink: 0;
  width: 32px; height: 32px;
  border-radius: 50%;
  background: #f0f9ff;
  display: flex; align-items: center; justify-content: center;
  color: #3b82f6; font-size: 0.75rem;
  transition: background 0.2s;
}
.event-card:hover .event-arrow { background: #3b82f6; color: white; }

/* ── QUICK ACCESS ── */
.qa-card {
  display: flex; flex-direction: column; align-items: center;
  gap: 12px;
  padding: 28px 16px;
  border-radius: 20px;
  background: white;
  border: 1.5px solid #f1f5f9;
  text-decoration: none;
  transition: all 0.3s ease;
  text-align: center;
}
.qa-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.08);
  border-color: transparent;
}
.qa-icon {
  width: 56px; height: 56px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
}
.qa-label {
  font-weight: 700; font-size: 0.9rem;
  color: #0f172a;
}
.qa-sub { font-size: 0.78rem; color: #94a3b8; }

/* ── TESTIMONIALS ── */
.testimonial-card {
  background: white;
  border-radius: 20px;
  padding: 30px 28px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  position: relative;
}
.testimonial-card::before {
  content: '\201C';
  font-family: 'Outfit', sans-serif;
  position: absolute;
  top: 16px; left: 22px;
  font-size: 5rem;
  color: #eff6ff;
  line-height: 1;
  font-weight: 900;
}
.testimonial-text {
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.8;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}
.testimonial-author {
  display: flex; align-items: center; gap: 12px;
}
.avatar-circle {
  width: 42px; height: 42px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 0.85rem; color: white;
}
.author-name { font-size: 0.875rem; font-weight: 700; color: #0f172a; }
.author-role { font-size: 0.75rem; color: #94a3b8; }

/* ── CTA BANNER ── */
.cta-banner {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
  border-radius: 28px;
  padding: 64px 48px;
  position: relative;
  overflow: hidden;
  text-align: center;
}
.cta-banner::before {
  content: '';
  position: absolute; top: -60px; right: -60px;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.cta-banner::after {
  content: '';
  position: absolute; bottom: -40px; left: -40px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
}
.cta-title {
  font-family: 'Outfit', sans-serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 800;
  color: white;
  margin-bottom: 16px;
  position: relative;
}
.cta-desc {
  color: rgba(255,255,255,0.6);
  font-size: 1rem;
  margin-bottom: 36px;
  position: relative;
}

/* ── SCROLL ANIMATIONS ── */
.reveal {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}
</style>

<!-- ══════════ HERO ══════════ -->
<section class="hero-section">
  <div class="hero-bg-grid"></div>
  <div class="hero-glow-1"></div>
  <div class="hero-glow-2"></div>
  <div class="hero-glow-3"></div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20 relative" style="z-index:10;">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- Left -->
      <div>
        <div class="hero-badge">
          <span class="dot"></span>
          Matrículas 2026 abertas
        </div>
        <h1 class="hero-title">
          Educação que<br>
          <span class="accent">transforma</span><br>
          gerações
        </h1>
        <p class="hero-desc">
          Formamos líderes com excelência acadêmica, valores sólidos e visão global — preparando cada estudante para um futuro extraordinário.
        </p>
        <div class="hero-cta-wrap">
          <a href="agendar_visita.php" class="btn-hero-primary">
            <i class="fas fa-calendar-check"></i>
            Agendar Visita
          </a>
          <a href="pre_matricula.php" class="btn-hero-secondary">
            <i class="fas fa-user-graduate"></i>
            Pré-Matrícula
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <div class="stat-number">+2.500</div>
            <div class="stat-label">Alunos formados</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">98%</div>
            <div class="stat-label">Aprovação no ENEM</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">30+</div>
            <div class="stat-label">Anos de excelência</div>
          </div>
        </div>
      </div>

      <!-- Right visual — Dynamic Carousel -->
      <div class="hero-visual hidden lg:block">
        <div style="position:relative; padding: 24px 24px 24px 40px;">
          <!-- Floating badge top -->
          <div class="floating-badge" style="top:-10px; left:10px; position:absolute; z-index:20;">
            <div class="floating-icon" style="background:#fef3c7;"><i class="fas fa-trophy" style="color:#d97706;"></i></div>
            <div>
              <div class="floating-label">Premiação Nacional</div>
              <div class="floating-sub">Melhor escola 2025</div>
            </div>
          </div>

          <div class="hero-card-main" style="overflow:visible;">
            <!-- Carousel -->
            <div id="hero-carousel" style="position:relative; border-radius:18px; overflow:hidden; aspect-ratio:4/3; background:#111827; margin-bottom:14px;">
              <?php
              $slides = !empty($bannerImages) ? array_map(fn($img) => ['src'=>$img['caminho_completo'],'alt'=>$img['descricao']??$img['nome_arquivo'],'local'=>true], $bannerImages) : array_map(fn($img) => ['src'=>$img['url'],'alt'=>$img['alt'],'local'=>false], $defaultImages);
              foreach($slides as $i => $slide): ?>
              <div class="c-slide" style="position:absolute;inset:0;opacity:<?= $i===0?'1':'0' ?>;transition:opacity 0.85s ease;z-index:<?= $i===0?'2':'1' ?>;">
                <img src="<?= htmlspecialchars($slide['src']) ?>" alt="<?= htmlspecialchars($slide['alt']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,0.45) 0%,transparent 55%);"></div>
              </div>
              <?php endforeach; ?>
              <!-- Progress bar -->
              <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,0.12);z-index:10;">
                <div id="c-progress" style="height:100%;background:linear-gradient(90deg,#3b82f6,#8b5cf6);width:0;"></div>
              </div>
              <!-- Dots -->
              <div style="position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:5px;z-index:10;">
                <?php foreach($slides as $i=>$s): ?>
                <button onclick="cGoTo(<?=$i?>)" class="c-dot" style="width:<?=$i===0?'18px':'6px'?>;height:6px;border-radius:3px;border:none;background:<?=$i===0?'white':'rgba(255,255,255,0.35)'?>;cursor:pointer;transition:all 0.3s;padding:0;"></button>
                <?php endforeach; ?>
              </div>
              <!-- Arrows -->
              <button onclick="cPrev()" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(6px);color:white;cursor:pointer;font-size:0.7rem;z-index:10;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-chevron-left"></i></button>
              <button onclick="cNext()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(6px);color:white;cursor:pointer;font-size:0.7rem;z-index:10;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-chevron-right"></i></button>
            </div>
            <!-- Bottom info -->
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.45);margin-bottom:4px;">Novos alunos este ano</div>
                <div style="font-size:1.1rem;font-weight:800;color:white;">+380 matrículas</div>
              </div>
              <div style="display:flex;align-items:center;"><?php $ac=['#3b82f6','#8b5cf6','#ec4899','#10b981']; for($i=0;$i<4;$i++) echo "<div style='width:32px;height:32px;border-radius:50%;background:{$ac[$i]};border:2px solid #1a1f35;margin-left:".($i>0?'-8px':'0')."px;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:white;position:relative;z-index:".($i+1)."'>".chr(65+$i)."</div>"; ?>
              <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.1);border:2px solid #1a1f35;margin-left:-8px;display:flex;align-items:center;justify-content:center;font-size:0.6rem;color:rgba(255,255,255,0.6);z-index:5;position:relative;">+96</div></div>
            </div>
          </div>

          <!-- Floating badge bottom -->
          <div class="floating-badge" style="bottom:60px;right:-10px;position:absolute;z-index:20;">
            <div class="floating-icon" style="background:#f0fdf4;"><i class="fas fa-check-circle" style="color:#16a34a;"></i></div>
            <div><div class="floating-label">IDEB 9.8</div><div class="floating-sub">Nota máxima MEC</div></div>
          </div>
        </div>
      </div>

      <script>
      (function(){
        const N = <?= count($slides) ?>;
        let cur = 0, timer, INTERVAL = 5000;
        const sls = document.querySelectorAll('.c-slide');
        const dots = document.querySelectorAll('.c-dot');
        const bar  = document.getElementById('c-progress');
        function show(i){
          sls.forEach((s,j)=>{s.style.opacity=j===i?'1':'0';s.style.zIndex=j===i?'2':'1';});
          dots.forEach((d,j)=>{d.style.width=j===i?'18px':'6px';d.style.background=j===i?'white':'rgba(255,255,255,0.35)';});
          cur=i; startBar();
        }
        function startBar(){
          if(!bar) return;
          bar.style.transition='none'; bar.style.width='0%';
          requestAnimationFrame(()=>requestAnimationFrame(()=>{
            bar.style.transition='width '+INTERVAL+'ms linear'; bar.style.width='100%';
          }));
        }
        function go(){ show((cur+1)%N); }
        function rst(){ clearInterval(timer); timer=setInterval(go,INTERVAL); }
        window.cNext=()=>{show((cur+1)%N);rst();};
        window.cPrev=()=>{show((cur-1+N)%N);rst();};
        window.cGoTo=(i)=>{show(i);rst();};
        if(N>1){ startBar(); timer=setInterval(go,INTERVAL); }
      })();
      </script>

    </div>
  </div>
</section>

<!-- ══════════ NÍVEIS DE ENSINO ══════════ -->
<section class="py-24 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <div class="section-eyebrow" style="justify-content:center;">Etapas de Ensino</div>
      <h2 class="section-title" style="max-width:600px; margin:0 auto 16px;">Uma jornada completa de aprendizado</h2>
      <p class="section-desc" style="margin:0 auto; text-align:center;">Do berçário ao ensino médio, cada etapa é cuidadosamente planejada para o pleno desenvolvimento do seu filho.</p>
    </div>

    <div class="levels-grid reveal">
      <a href="educacao_infantil.php" class="level-card level-card-1">
        <div class="card-icon"><i class="fas fa-child"></i></div>
        <div class="card-title">Educação Infantil</div>
        <div class="card-sub">Berçário ao Pré-Escolar. Aprendizado lúdico com afetividade e estimulação do desenvolvimento integral.</div>
        <div class="card-arrow" style="color:#0284c7;">Conhecer <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i></div>
      </a>
      <a href="ensino_fundamental_i.php" class="level-card level-card-2">
        <div class="card-icon"><i class="fas fa-star"></i></div>
        <div class="card-title">Ensino Fundamental I</div>
        <div class="card-sub">1º ao 5º Ano. Construção de base sólida em alfabetização, raciocínio e valores humanos.</div>
        <div class="card-arrow" style="color:#16a34a;">Conhecer <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i></div>
      </a>
      <a href="ensino_fundamental_ii.php" class="level-card level-card-3">
        <div class="card-icon"><i class="fas fa-atom"></i></div>
        <div class="card-title">Ensino Fundamental II</div>
        <div class="card-sub">6º ao 9º Ano. Pensamento crítico, investigação científica e desenvolvimento da autonomia.</div>
        <div class="card-arrow" style="color:#7c3aed;">Conhecer <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i></div>
      </a>
      <a href="ensino_medio.php" class="level-card level-card-4">
        <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="card-title">Ensino Médio</div>
        <div class="card-sub">1º ao 3º Ano. Preparação intensiva para vestibulares de elite e para o sucesso profissional.</div>
        <div class="card-arrow" style="color:#ea580c;">Conhecer <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i></div>
      </a>
    </div>
  </div>
</section>

<!-- ══════════ DIFERENCIAIS ══════════ -->
<section class="features-strip py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <div class="reveal">
        <div class="section-eyebrow" style="color:rgba(148,163,184,0.8);">Por que nos escolher</div>
        <h2 style="font-family:'Outfit',sans-serif; font-size:clamp(1.8rem,3.5vw,2.6rem); font-weight:800; color:white; line-height:1.2; margin-bottom:16px;">
          Padrão de excelência em cada detalhe
        </h2>
        <p style="font-size:0.95rem; color:rgba(255,255,255,0.45); line-height:1.8; max-width:440px; margin-bottom:32px;">
          Somos referência regional em qualidade de ensino. Combinamos metodologias inovadoras com professores altamente qualificados.
        </p>
        <a href="historico.php" style="display:inline-flex; align-items:center; gap:10px; padding:13px 26px; border-radius:12px; background:rgba(255,255,255,0.08); border:1.5px solid rgba(255,255,255,0.15); color:white; font-weight:600; font-size:0.9rem; text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.14)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
          Nossa história <i class="fas fa-arrow-right" style="font-size:0.8rem;"></i>
        </a>
      </div>

      <div class="reveal">
        <div class="feature-item">
          <div class="feature-dot" style="background:rgba(59,130,246,0.15);">
            <i class="fas fa-medal" style="color:#60a5fa;"></i>
          </div>
          <div>
            <h4>Corpo docente de excelência</h4>
            <p>100% dos professores com pós-graduação. Formação contínua e pedagogia de vanguarda.</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-dot" style="background:rgba(139,92,246,0.15);">
            <i class="fas fa-laptop-code" style="color:#a78bfa;"></i>
          </div>
          <div>
            <h4>Tecnologia integrada ao ensino</h4>
            <p>Salas com lousas digitais, laboratórios de robótica e plataformas de ensino personalizadas.</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-dot" style="background:rgba(236,72,153,0.15);">
            <i class="fas fa-heart" style="color:#f472b6;"></i>
          </div>
          <div>
            <h4>Acompanhamento individual</h4>
            <p>Cada aluno é único. Equipe de psicopedagogia e tutoria personalizada para todos os níveis.</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-dot" style="background:rgba(16,185,129,0.15);">
            <i class="fas fa-globe" style="color:#34d399;"></i>
          </div>
          <div>
            <h4>Visão global e cidadania</h4>
            <p>Inglês desde o berçário, intercâmbios e projetos sociais formando cidadãos do mundo.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════ NOTÍCIAS + EVENTOS ══════════ -->
<section class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-5 gap-12">

      <!-- News (col 3) -->
      <div class="lg:col-span-3">
        <div class="flex items-center justify-between mb-10 reveal">
          <div>
            <div class="section-eyebrow">Novidades</div>
            <h2 class="section-title" style="margin-bottom:0;">Notícias & Comunicados</h2>
          </div>
          <a href="noticias.php" style="font-size:0.85rem; font-weight:700; color:#3b82f6; text-decoration:none; display:flex; align-items:center; gap:6px; white-space:nowrap;" class="hidden md:flex">
            Ver todas <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
          </a>
        </div>

        <div class="grid sm:grid-cols-2 gap-6 reveal">
          <a href="noticias.php" class="news-card" style="sm:col-span-2;">
            <div style="height:180px; background:linear-gradient(135deg,#1d4ed8,#4f46e5); display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
              <div style="position:absolute; inset:0; background:url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=700&q=70') center/cover;"></div>
              <div style="position:absolute; inset:0; background:linear-gradient(135deg, rgba(29,78,216,0.8), rgba(79,70,229,0.7));"></div>
              <div style="position:relative; text-align:center; color:white;">
                <div style="font-size:0.7rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; opacity:0.7; margin-bottom:6px;"><i class="fas fa-thumbtack"></i> Destaque</div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.3rem; font-weight:700;">Matrículas 2026 Abertas</div>
              </div>
            </div>
            <div class="news-body">
              <span class="news-tag" style="background:#dbeafe; color:#1d4ed8;">Institucional</span>
              <div class="news-title">Vagas limitadas para o ano letivo 2026 — Inscreva-se agora</div>
              <div class="news-meta"><i class="fas fa-clock" style="margin-right:4px;"></i> Hoje · 2 min de leitura</div>
            </div>
          </a>

          <a href="noticias.php" class="news-card">
            <div class="news-body" style="padding-top:24px;">
              <span class="news-tag" style="background:#f0fdf4; color:#16a34a;">Calendário</span>
              <div class="news-title">Calendário Escolar 2026 disponível para download</div>
              <div class="news-meta"><i class="fas fa-clock" style="margin-right:4px;"></i> 2 dias atrás</div>
            </div>
          </a>

          <a href="noticias.php" class="news-card">
            <div class="news-body" style="padding-top:24px;">
              <span class="news-tag" style="background:#faf5ff; color:#7c3aed;">Premiação</span>
              <div class="news-title">Alunos conquistam 1º lugar nas Olimpíadas de Matemática</div>
              <div class="news-meta"><i class="fas fa-clock" style="margin-right:4px;"></i> 5 dias atrás</div>
            </div>
          </a>
        </div>
      </div>

      <!-- Events (col 2) -->
      <div class="lg:col-span-2">
        <div class="flex items-center justify-between mb-10 reveal">
          <div>
            <div class="section-eyebrow">Agenda</div>
            <h2 class="section-title" style="margin-bottom:0;">Próximos Eventos</h2>
          </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:12px;" class="reveal">
          <a href="inscrever_evento.php" class="event-card">
            <div class="event-date-box"><div class="day">15</div><div class="month">DEZ</div></div>
            <div class="event-info">
              <h4>Formatura Ensino Médio</h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Teatro Municipal</p>
            </div>
            <div class="event-arrow"><i class="fas fa-chevron-right"></i></div>
          </a>
          <a href="inscrever_evento.php" class="event-card">
            <div class="event-date-box" style="background:#f0fdf4;"><div class="day" style="color:#16a34a;">20</div><div class="month">MAR</div></div>
            <div class="event-info">
              <h4>Gincana Escolar 2026</h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Quadra Poliesportiva</p>
            </div>
            <div class="event-arrow"><i class="fas fa-chevron-right"></i></div>
          </a>
          <a href="inscrever_evento.php" class="event-card">
            <div class="event-date-box" style="background:#faf5ff;"><div class="day" style="color:#7c3aed;">10</div><div class="month">ABR</div></div>
            <div class="event-info">
              <h4>Feira de Ciências</h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Ginásio Principal</p>
            </div>
            <div class="event-arrow"><i class="fas fa-chevron-right"></i></div>
          </a>
          <a href="inscrever_evento.php" class="event-card">
            <div class="event-date-box" style="background:#fff7ed;"><div class="day" style="color:#ea580c;">25</div><div class="month">MAI</div></div>
            <div class="event-info">
              <h4>Festival de Música</h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Auditório Central</p>
            </div>
            <div class="event-arrow"><i class="fas fa-chevron-right"></i></div>
          </a>
          <a href="inscrever_evento.php" class="event-card">
            <div class="event-date-box" style="background:#fef2f2;"><div class="day" style="color:#dc2626;">08</div><div class="month">JUN</div></div>
            <div class="event-info">
              <h4>Dia do Meio Ambiente</h4>
              <p><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>Área Verde da Escola</p>
            </div>
            <div class="event-arrow"><i class="fas fa-chevron-right"></i></div>
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ══════════ ACESSO RÁPIDO ══════════ -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14 reveal">
      <div class="section-eyebrow" style="justify-content:center;">Serviços Online</div>
      <h2 class="section-title" style="margin:0 auto 14px;">Tudo o que você precisa, a um clique</h2>
      <p class="section-desc" style="margin:0 auto; text-align:center;">Acesse os serviços mais utilizados da nossa comunidade escolar de forma rápida e prática.</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 reveal">
      <a href="biblioteca.php" class="qa-card">
        <div class="qa-icon" style="background:#dbeafe;"><i class="fas fa-book" style="color:#1d4ed8;"></i></div>
        <div class="qa-label">Biblioteca</div>
        <div class="qa-sub">Acervo digital</div>
      </a>
      <a href="portal_pais.php" class="qa-card">
        <div class="qa-icon" style="background:#dcfce7;"><i class="fas fa-users" style="color:#16a34a;"></i></div>
        <div class="qa-label">Portal dos Pais</div>
        <div class="qa-sub">Área do responsável</div>
      </a>
      <a href="calendario_escolar.php" class="qa-card">
        <div class="qa-icon" style="background:#ede9fe;"><i class="fas fa-calendar" style="color:#7c3aed;"></i></div>
        <div class="qa-label">Calendário</div>
        <div class="qa-sub">Datas importantes</div>
      </a>
      <a href="formularios.php" class="qa-card">
        <div class="qa-icon" style="background:#ffedd5;"><i class="fas fa-file-download" style="color:#ea580c;"></i></div>
        <div class="qa-label">Formulários</div>
        <div class="qa-sub">Downloads</div>
      </a>
    </div>
  </div>
</section>

<!-- ══════════ DEPOIMENTOS ══════════ -->
<section class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <div class="section-eyebrow" style="justify-content:center;">Depoimentos</div>
      <h2 class="section-title" style="margin:0 auto;">O que dizem as famílias</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6 reveal">
      <div class="testimonial-card">
        <p class="testimonial-text">"Meu filho cresceu de uma forma extraordinária aqui. A escola vai muito além das notas — forma o caráter e a visão de mundo."</p>
        <div class="testimonial-author">
          <div class="avatar-circle" style="background:linear-gradient(135deg,#1d4ed8,#7c3aed);">MA</div>
          <div><div class="author-name">Maria Aparecida S.</div><div class="author-role">Mãe de aluno do 9º ano</div></div>
        </div>
      </div>
      <div class="testimonial-card">
        <p class="testimonial-text">"A qualidade dos professores e a estrutura da escola são impressionantes. Minha filha aprovada na federal com 18 anos. Resultado do trabalho desta instituição!"</p>
        <div class="testimonial-author">
          <div class="avatar-circle" style="background:linear-gradient(135deg,#16a34a,#0284c7);">JR</div>
          <div><div class="author-name">João Roberto M.</div><div class="author-role">Pai de ex-aluna</div></div>
        </div>
      </div>
      <div class="testimonial-card">
        <p class="testimonial-text">"Desde a educação infantil percebemos a diferença. Atendimento humanizado, professores dedicados e um ambiente seguro e estimulante."</p>
        <div class="testimonial-author">
          <div class="avatar-circle" style="background:linear-gradient(135deg,#ec4899,#ea580c);">CF</div>
          <div><div class="author-name">Camila Figueiredo</div><div class="author-role">Mãe de aluno do Pré</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════ CTA BANNER ══════════ -->
<section class="py-16 bg-gray-50">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 reveal">
    <div class="cta-banner">
      <div class="section-eyebrow" style="justify-content:center; color:rgba(148,163,184,0.7); margin-bottom:16px;">
        Vagas Limitadas
      </div>
      <div class="cta-title">Garanta a vaga do seu filho hoje</div>
      <p class="cta-desc">
        As matrículas para 2026 estão abertas. Agende uma visita e conheça pessoalmente nossa estrutura de excelência.
      </p>
      <div style="display:flex; flex-wrap:wrap; gap:14px; justify-content:center; position:relative;">
        <a href="pre_matricula.php" class="btn-hero-primary" style="font-size:1rem; padding:16px 36px;">
          <i class="fas fa-user-graduate"></i> Fazer Pré-Matrícula
        </a>
        <a href="agendar_visita.php" class="btn-hero-secondary" style="font-size:1rem; padding:15px 30px;">
          <i class="fas fa-calendar-check"></i> Agendar Visita
        </a>
      </div>
    </div>
  </div>
</section>

<script>
// Scroll reveal
const reveals = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });
reveals.forEach(el => revealObserver.observe(el));
</script>

<?php require_once 'includes/footer.php'; ?>

