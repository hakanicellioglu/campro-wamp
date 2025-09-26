 
<?php
// error/403.php
http_response_code(403);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>403 • Erişim Engellendi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{
    --bg1:#0f172a; --bg2:#111827; --accent:#22d3ee; --accent2:#38bdf8; --text:#e5e7eb;
  }
  html,body{height:100%}
  body{
    margin:0; color:var(--text);
    background: radial-gradient(1200px 1200px at 10% 10%, #0b1220, transparent 60%),
                radial-gradient(1000px 1000px at 90% 20%, #0c1a2b, transparent 60%),
                linear-gradient(135deg, var(--bg1), var(--bg2));
    display:flex; align-items:center; justify-content:center;
    overflow:hidden;
  }
  /* Sonsuz arka plan parıltısı */
  .glow{
    position:absolute; inset:-40%;
    background: radial-gradient(40% 40% at 30% 30%, rgba(34,211,238,.18), transparent 60%),
                radial-gradient(35% 35% at 70% 70%, rgba(56,189,248,.15), transparent 60%);
    filter: blur(40px);
    animation: drift 18s linear infinite alternate;
    pointer-events:none;
  }
  @keyframes drift{
    0%   {transform: translate3d(-2%, -2%, 0) rotate(0deg) scale(1);}
    100% {transform: translate3d(2%, 2%, 0) rotate(2deg)  scale(1.05);}
  }

  /* Kilit ikon animasyonu */
  .lock { width:160px; height:160px; position:relative; }
  .shackle, .body { fill:none; stroke-width:10; }
  .shackle { stroke: var(--accent2); stroke-linecap:round; }
  .body    { stroke: var(--accent);  }
  .keyhole { fill: var(--text); opacity:.8 }

  /* Sonsuz nefes (breathing) + hafif sallanma */
  .breath { animation: breath 3.2s ease-in-out infinite; transform-origin: 50% 60%; }
  @keyframes breath {
    0%,100% { transform: scale(1); }
    50%     { transform: scale(1.04); }
  }
  .wobble { animation: wobble 6s ease-in-out infinite; transform-origin: 50% 20%; }
  @keyframes wobble {
    0%,100% { transform: rotate(0deg); }
    25%     { transform: rotate(2deg); }
    75%     { transform: rotate(-2deg); }
  }

  .card-bg{
    backdrop-filter: blur(10px);
    background: rgba(17,24,39,.55);
    border: 1px solid rgba(56,189,248,.25);
    box-shadow: 0 20px 70px rgba(0,0,0,.45);
  }

  /* Hareket azaltma tercihi */
  @media (prefers-reduced-motion: reduce){
    .glow, .breath, .wobble { animation: none !important; }
  }
</style>
</head>
<body>
  <div class="glow"></div>

  <main class="container px-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="card card-bg rounded-4 p-4 p-md-5 text-center">
          <div class="mb-4">
            <svg class="lock breath wobble" viewBox="0 0 200 200" role="img" aria-label="Kilit">
              <path class="shackle" d="M60 90 V70 a40 40 0 0 1 80 0 v20" />
              <rect class="body" x="50" y="90" width="100" height="90" rx="14" />
              <circle class="keyhole" cx="100" cy="135" r="6"/>
              <rect class="keyhole" x="96" y="142" width="8" height="18" rx="3"/>
            </svg>
          </div>
          <h1 class="display-5 fw-bold mb-2">403</h1>
          <p class="lead mb-4">Bu kaynağa erişim yetkiniz bulunmuyor.</p>
          <div class="d-grid d-sm-flex gap-2 justify-content-center">
            <a href="/" class="btn btn-info btn-lg">Ana sayfaya dön</a>
            <button class="btn btn-outline-info btn-lg" onclick="history.back()">Önceki sayfa</button>
          </div>
          <p class="mt-3 mb-0 text-secondary">Erişim gerektiğini düşünüyorsanız sistem yöneticinizle iletişime geçin.</p>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
