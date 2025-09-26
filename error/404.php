<?php
// error/404.php
http_response_code(404);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>404 • Sayfa Bulunamadı</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{
    --bg1:#0a0f1c; --bg2:#0b1220; --accent:#a78bfa; --accent2:#c084fc; --text:#e5e7eb;
  }
  html,body{height:100%}
  body{
    margin:0; color:var(--text);
    background: radial-gradient(1100px 1100px at 85% 10%, #1a1030, transparent 60%),
                radial-gradient(900px 900px at 10% 80%, #101635, transparent 60%),
                linear-gradient(135deg, var(--bg1), var(--bg2));
    display:flex; align-items:center; justify-content:center;
    overflow:hidden;
  }
  .grid{
    position:absolute; inset:0; opacity:.12; background:
      repeating-linear-gradient(transparent 0 28px, #fff 29px 30px),
      repeating-linear-gradient(90deg, transparent 0 28px, #fff 29px 30px);
    transform: perspective(800px) rotateX(60deg) translateY(10%);
    animation: slide 18s linear infinite;
    pointer-events:none;
  }
  @keyframes slide{
    0%{background-position: 0 0, 0 0}
    100%{background-position: 0 600px, 600px 0}
  }

  .card-bg{
    backdrop-filter: blur(10px);
    background: rgba(17,24,39,.55);
    border: 1px solid rgba(167,139,250,.25);
    box-shadow: 0 20px 70px rgba(0,0,0,.45);
  }

  /* Sonsuz radar tarama animasyonu */
  .radar { width:180px; height:180px; }
  .ring { stroke: var(--accent); fill:none; opacity:.4 }
  .sweep{
    fill: url(#grad); transform-origin: 50% 50%;
    animation: spin 4s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  @media (prefers-reduced-motion: reduce){
    .grid, .sweep { animation: none !important; }
  }
</style>
</head>
<body>
  <div class="grid"></div>

  <main class="container px-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="card card-bg rounded-4 p-4 p-md-5 text-center">
          <div class="mb-4">
            <svg class="radar" viewBox="0 0 200 200" role="img" aria-label="Radar">
              <defs>
                <linearGradient id="grad" x1="0" y1="0" x2="1" y2="0">
                  <stop offset="0%" stop-color="rgba(192,132,252,0.0)"/>
                  <stop offset="60%" stop-color="rgba(192,132,252,0.6)"/>
                  <stop offset="100%" stop-color="rgba(192,132,252,0)"/>
                </linearGradient>
              </defs>
              <circle class="ring" cx="100" cy="100" r="80"/>
              <circle class="ring" cx="100" cy="100" r="55"/>
              <circle class="ring" cx="100" cy="100" r="30"/>
              <g class="sweep">
                <path d="M100,100 L180,100 A80,80 0 0 1 100,180 Z"/>
              </g>
              <circle cx="100" cy="100" r="4" fill="var(--accent2)"/>
            </svg>
          </div>
          <h1 class="display-5 fw-bold mb-2">404</h1>
          <p class="lead mb-4">Aradığınız sayfa bulunamadı veya taşınmış olabilir.</p>
          <div class="d-grid d-sm-flex gap-2 justify-content-center">
            <a href="/" class="btn btn-primary btn-lg">Ana sayfaya dön</a>
            <a href="/search" class="btn btn-outline-light btn-lg">Site içinde ara</a>
          </div>
          <p class="mt-3 mb-0 text-secondary">URL’yi kontrol ederek tekrar deneyin.</p>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
