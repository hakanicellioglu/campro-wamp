<?php
// error/500.php
http_response_code(500);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>500 • Sunucu Hatası</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{
    --bg1:#111827; --bg2:#0b1020; --accent:#34d399; --accent2:#10b981; --text:#e5e7eb;
  }
  html,body{height:100%}
  body{
    margin:0; color:var(--text);
    background:
      radial-gradient(900px 900px at 15% 15%, #0d162d, transparent 60%),
      radial-gradient(1000px 1000px at 85% 85%, #0d1428, transparent 60%),
      linear-gradient(135deg, var(--bg1), var(--bg2));
    display:flex; align-items:center; justify-content:center;
    overflow:hidden;
  }
  .orbs{
    position:absolute; inset:-30%;
    background:
      radial-gradient(25% 25% at 20% 30%, rgba(16,185,129,.12), transparent 60%),
      radial-gradient(22% 22% at 80% 70%, rgba(52,211,153,.12), transparent 60%);
    filter: blur(50px);
    animation: floaty 14s ease-in-out infinite alternate;
    pointer-events:none;
  }
  @keyframes floaty{
    0%{ transform: translate3d(0,-2%,0) scale(1) }
    100%{ transform: translate3d(0,2%,0)  scale(1.05) }
  }

  .card-bg{
    backdrop-filter: blur(10px);
    background: rgba(17,24,39,.55);
    border: 1px solid rgba(16,185,129,.25);
    box-shadow: 0 20px 70px rgba(0,0,0,.45);
  }

  .gear { fill: none; stroke: var(--accent); stroke-width:9; stroke-linecap:round; }
  .gear2{ stroke: var(--accent2); }
  .spin-slow{ animation: spin 8s linear infinite; transform-origin: 50% 50%; }
  .spin-fast{ animation: spin 4s linear infinite reverse; transform-origin: 50% 50%; }
  @keyframes spin{ to { transform: rotate(360deg); } }

  @media (prefers-reduced-motion: reduce){
    .orbs, .spin-slow, .spin-fast { animation: none !important; }
  }
</style>
</head>
<body>
  <div class="orbs"></div>

  <main class="container px-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="card card-bg rounded-4 p-4 p-md-5 text-center">
          <div class="mb-4">
            <svg viewBox="0 0 220 120" width="220" height="120" role="img" aria-label="Dönen dişliler">
              <!-- Büyük dişli -->
              <g class="spin-slow" transform="translate(70,60)">
                <circle r="26" class="gear"/>
                <?php for($i=0;$i<12;$i++): $a=$i*30; ?>
                <line class="gear" x1="<?php echo 26*cos(deg2rad($a)); ?>" y1="<?php echo 26*sin(deg2rad($a)); ?>"
                      x2="<?php echo 38*cos(deg2rad($a)); ?>" y2="<?php echo 38*sin(deg2rad($a)); ?>"/>
                <?php endfor; ?>
              </g>
              <!-- Küçük dişli -->
              <g class="spin-fast" transform="translate(140,60)">
                <circle r="18" class="gear gear2"/>
                <?php for($i=0;$i<10;$i++): $a=$i*36; ?>
                <line class="gear gear2" x1="<?php echo 18*cos(deg2rad($a)); ?>" y1="<?php echo 18*sin(deg2rad($a)); ?>"
                      x2="<?php echo 28*cos(deg2rad($a)); ?>" y2="<?php echo 28*sin(deg2rad($a)); ?>"/>
                <?php endfor; ?>
              </g>
            </svg>
          </div>
          <h1 class="display-5 fw-bold mb-2">500</h1>
          <p class="lead mb-4">Sunucuda beklenmeyen bir durum oluştu. Ekibimiz olayı inceliyor.</p>
          <div class="d-grid d-sm-flex gap-2 justify-content-center">
            <a href="/" class="btn btn-success btn-lg">Ana sayfaya dön</a>
            <button class="btn btn-outline-success btn-lg" onclick="location.reload()">Tekrar dene</button>
          </div>
          <p class="mt-3 mb-0 text-secondary">Sorun devam ederse lütfen daha sonra tekrar deneyin.</p>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
