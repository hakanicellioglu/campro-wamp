<?php http_response_code(500); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Sunucu Hatası</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: linear-gradient(160deg, #f9fafb 0%, #e8ecf3 100%);
            --card-bg: rgba(255, 255, 255, 0.85);
            --text: #1f2933;
            --muted: #52606d;
            --accent: #10b981;
            --accent-hover: #059669;
            --shadow: 0 24px 48px rgba(15, 23, 42, 0.12);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: radial-gradient(circle at top, #111827, #0b1120 60%);
                --card-bg: rgba(17, 25, 40, 0.85);
                --text: #f8fafc;
                --muted: #cbd5f5;
                --shadow: 0 24px 48px rgba(2, 6, 23, 0.66);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            color: var(--text);
        }

        main {
            max-width: 520px;
            width: 100%;
            background: var(--card-bg);
            backdrop-filter: blur(18px);
            border-radius: 28px;
            padding: 3rem 3.25rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 96px;
            height: 96px;
            border-radius: 32px;
            background: rgba(16, 185, 129, 0.12);
            color: var(--accent);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.75rem;
        }

        h1 {
            margin: 0 0 1rem;
            font-size: clamp(1.9rem, 5vw, 2.5rem);
            line-height: 1.1;
        }

        p {
            margin: 0 auto 2.25rem;
            max-width: 420px;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        a.button,
        button.button {
            appearance: none;
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        a.primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.25);
        }

        a.primary:hover,
        a.primary:focus {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        a.secondary {
            background: rgba(16, 185, 129, 0.08);
            color: var(--accent);
        }

        a.secondary:hover,
        a.secondary:focus {
            background: rgba(16, 185, 129, 0.14);
            transform: translateY(-1px);
        }

        footer {
            margin-top: 2.5rem;
            font-size: 0.85rem;
            color: var(--muted);
        }

        @media (max-width: 480px) {
            main {
                padding: 2.5rem 1.75rem;
            }

            .badge {
                width: 84px;
                height: 84px;
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
<main>
    <span class="badge">500</span>
    <h1>Beklenmedik bir sorun oluştu</h1>
    <p>Sunucuda bir aksaklık yaşandı. Sorunu hızla gidermek için çalışıyoruz. Lütfen bir süre sonra tekrar deneyin.</p>
    <div class="actions">
        <a class="button primary" href="/">Ana sayfaya dön</a>
        <a class="button secondary" href="mailto:support@example.com">Destek ile iletişime geç</a>
    </div>
    <footer>Bu hatayı destek ekibimize ilettik. Anlayışınız için teşekkür ederiz.</footer>
</main>
</body>
</html>
