<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>{{ $exception ? 'Degraded' : 'Healthy' }} · {{ config('app.name', 'LaraGram') }}</title>
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22156%22%20height%3D%22156%22%20viewBox%3D%220%200%20156%20156%22%3E%3Cpath%20d%3D%22M0%200%20C-0.99%203.3%20-1.98%206.6%20-3%2010%20C-5.44%209.26%20-5.44%209.26%20-7.94%208.5%20C-20.83%205.3%20-34.45%205.58%20-46.19%2012.19%20C-54.16%2017.58%20-58.58%2024.77%20-61.59%2033.75%20C-63.27%2042.89%20-63.02%2053.56%20-59%2062%20C-56.05%2068.61%20-53.73%2071.72%20-50%2075%20C-40.38%2082.3%20-33.22%2083.18%20-24.56%2083.19%20C-19.16%2083.24%20-19.16%2083.24%20-10%2081%20C-9.67%2071.43%20-9.34%2061.86%20-9%2052%20C-15.27%2052%20-21.54%2052%20-28%2052%20C-28%2048.7%20-28%2045.4%20-28%2042%20C-17.77%2042%20-7.54%2042%203%2042%20C3.67%2067.59%203.67%2067.59%20-0.91%2093.84%20C-4.27%2096.66%20-7.68%2099.69%20-11.04%20103.13%20C-14.38%20106.46%20-17.79%20109.85%20-25%20117%20C-31.96%20112.31%20-36.2%20108.02%20-45.98%2098.2%20C-56.7%2087.39%20-65.35%2077.58%20-74%2061%20C-77.37%2043.38%20-76.17%2028.91%20-67.67%2016.2%20C-59.85%205.9%20-48.84%20-0.82%20-36%20-3%20C-22.66%20-5.14%20-10.91%20-3.48%200%200%20Z%22%20fill%3D%22%23FE0102%22%20transform%3D%22translate(108%2C15)%22%2F%3E%3Cpath%20d%3D%22M0%200%20C9.7%208.64%2018.59%2017.38%2032.31%2030.75%20C41.29%2040.42%2050.62%2049.72%2060%2059%20C66.59%2054.62%2071.62%2049.42%2082.5%2038.28%20C86.5%2034.17%2088.35%2032.38%2092%2032%20C93.65%2033.98%2095.3%2035.96%2097%2038%20C88.32%2047.81%2077.28%2058.8%2060%2076%20C50.9%2068.04%2040.91%2058.02%2024.24%2041.31%20C16.98%2034.03%206.23%2023.26%20-8%209%20C-4.05%204.37%20-2.39%202.44%200%200%20Z%22%20fill%3D%22%230288FE%22%20transform%3D%22translate(21%2C72)%22%2F%3E%3C%2Fsvg%3E">
    <style>
        :root {
            --ink: #0a0d13; --surface: #10141d; --surface-2: #141926;
            --line: #1e2635; --line-soft: #1a212e;
            --text: #eef1f7; --muted: #8b93a6; --faint: #5b6474;
            --blue: #2aabee; --red: #f0473e; --green: #35d07f;
            --ok: #35d07f; --ok-glow: rgba(53,208,127,.5);
        }
        .is-down {
            --ok: #f0473e; --ok-glow: rgba(240,71,62,.5);
        }
        @media (prefers-color-scheme: light) {
            :root {
                --ink: #f3f5f9; --surface: #ffffff; --surface-2: #f7f9fc;
                --line: #e4e8f0; --line-soft: #eef1f6;
                --text: #101521; --muted: #5c6675; --faint: #97a0b0;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            background: var(--ink); color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100dvh; display: flex; align-items: center; justify-content: center;
            padding: 24px; position: relative; overflow: hidden;
        }
        body::before {
            content: ""; position: fixed; inset: 0; pointer-events: none;
            background-image: radial-gradient(circle at 1px 1px, color-mix(in srgb, var(--muted) 20%, transparent) 1px, transparent 0);
            background-size: 24px 24px;
            -webkit-mask-image: radial-gradient(80% 60% at 50% 50%, #000 0%, transparent 75%);
            mask-image: radial-gradient(80% 60% at 50% 50%, #000 0%, transparent 75%);
            opacity: .45;
        }
        body::after {
            content: ""; position: fixed; inset: 0; pointer-events: none;
            background: radial-gradient(560px 320px at 50% 42%, color-mix(in srgb, var(--ok) 22%, transparent), transparent 70%);
        }
        .card {
            position: relative; z-index: 1; width: 100%; max-width: 440px;
            background: var(--surface); border: 1px solid var(--line);
            border-radius: 18px; padding: 30px; overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,.2), 0 24px 60px -24px rgba(0,0,0,.55);
        }
        .card::before {
            content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, transparent, var(--ok), transparent);
            opacity: .9;
        }
        /* HUD corners */
        .card::after {
            content: ""; position: absolute; inset: 12px; border-radius: 12px; pointer-events: none;
            background:
                linear-gradient(var(--ok),var(--ok)) top left, linear-gradient(var(--ok),var(--ok)) top left,
                linear-gradient(var(--ok),var(--ok)) top right, linear-gradient(var(--ok),var(--ok)) top right,
                linear-gradient(var(--ok),var(--ok)) bottom left, linear-gradient(var(--ok),var(--ok)) bottom left,
                linear-gradient(var(--ok),var(--ok)) bottom right, linear-gradient(var(--ok),var(--ok)) bottom right;
            background-size: 12px 1.5px, 1.5px 12px; background-repeat: no-repeat;
            background-position:
                top left, top left, top right, top right,
                bottom left, bottom left, bottom right, bottom right;
            opacity: .35;
        }
        .top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 26px; }
        .brand { display: flex; align-items: center; gap: 9px; }
        .brand svg { width: 22px; height: 22px; }
        .brand span { font-size: 13.5px; font-weight: 650; letter-spacing: -.01em; }
        .brand span b { color: var(--blue); }
        .env {
            font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11px; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted);
            border: 1px solid var(--line); border-radius: 7px; padding: 4px 9px;
        }

        .beacon { display: flex; justify-content: center; margin: 6px 0 22px; }
        .pulse { position: relative; width: 64px; height: 64px; display: grid; place-items: center; }
        .pulse .ring { position: absolute; inset: 0; border-radius: 50%; border: 1.5px solid var(--ok); opacity: .0; animation: ping 2.4s cubic-bezier(0,0,.2,1) infinite; }
        .pulse .ring:nth-child(2) { animation-delay: .8s; }
        .pulse .core { width: 20px; height: 20px; border-radius: 50%; background: var(--ok); box-shadow: 0 0 0 6px color-mix(in srgb, var(--ok) 22%, transparent), 0 0 22px var(--ok-glow); }
        @keyframes ping { 0% { transform: scale(.55); opacity: .8; } 100% { transform: scale(1.6); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .pulse .ring { animation: none; } }

        .status-line { text-align: center; }
        .status-line .label {
            font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11.5px; letter-spacing: .18em;
            text-transform: uppercase; color: var(--ok); margin-bottom: 8px;
        }
        .status-line h1 { font-size: 24px; font-weight: 680; letter-spacing: -.02em; }
        .status-line p { margin-top: 8px; font-size: 13.5px; color: var(--muted); }

        .metrics { margin-top: 26px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .metric {
            background: var(--surface-2); border: 1px solid var(--line-soft); border-radius: 11px;
            padding: 13px 14px;
        }
        .metric .k { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .metric .v { margin-top: 5px; font-size: 15px; font-weight: 640; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
        .metric .v small { font-size: 11px; font-weight: 500; color: var(--muted); }

        .foot { margin-top: 22px; text-align: center; font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11px; color: var(--faint); letter-spacing: .03em; }
    </style>
</head>
<body class="{{ $exception ? 'is-down' : '' }}">
    <div class="card">
        <div class="top">
            <div class="brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156 156">
                    <path fill="#FE0102" transform="translate(108,15)" d="M0 0 C-0.99 3.3 -1.98 6.6 -3 10 C-5.44 9.26 -5.44 9.26 -7.94 8.5 C-20.83 5.3 -34.45 5.58 -46.19 12.19 C-54.16 17.58 -58.58 24.77 -61.59 33.75 C-63.27 42.89 -63.02 53.56 -59 62 C-56.05 68.61 -53.73 71.72 -50 75 C-40.38 82.3 -33.22 83.18 -24.56 83.19 C-19.16 83.24 -19.16 83.24 -10 81 C-9.67 71.43 -9.34 61.86 -9 52 C-15.27 52 -21.54 52 -28 52 C-28 48.7 -28 45.4 -28 42 C-17.77 42 -7.54 42 3 42 C3.67 67.59 3.67 67.59 -0.91 93.84 C-4.27 96.66 -7.68 99.69 -11.04 103.13 C-14.38 106.46 -17.79 109.85 -25 117 C-31.96 112.31 -36.2 108.02 -45.98 98.2 C-56.7 87.39 -65.35 77.58 -74 61 C-77.37 43.38 -76.17 28.91 -67.67 16.2 C-59.85 5.9 -48.84 -0.82 -36 -3 C-22.66 -5.14 -10.91 -3.48 0 0 Z"/>
                    <path fill="#0288FE" transform="translate(21,72)" d="M0 0 C9.7 8.64 18.59 17.38 32.31 30.75 C41.29 40.42 50.62 49.72 60 59 C66.59 54.62 71.62 49.42 82.5 38.28 C86.5 34.17 88.35 32.38 92 32 C93.65 33.98 95.3 35.96 97 38 C88.32 47.81 77.28 58.8 60 76 C50.9 68.04 40.91 58.02 24.24 41.31 C16.98 34.03 6.23 23.26 -8 9 C-4.05 4.37 -2.39 2.44 0 0 Z"/>
                </svg>
                <span>Lara<b>Gram</b></span>
            </div>
            <span class="env">{{ strtoupper(config('app.env', 'production')) }}</span>
        </div>

        <div class="beacon">
            <div class="pulse">
                <span class="ring"></span><span class="ring"></span>
                <span class="core"></span>
            </div>
        </div>

        <div class="status-line">
            <div class="label">Health Check</div>
            <h1>{{ $exception ? 'Service Degraded' : 'All Systems Operational' }}</h1>
            <p>
                @if ($exception)
                    The application received the request but reported a problem.
                @else
                    The application is up and responding to requests.
                @endif
            </p>
        </div>

        <div class="metrics">
            <div class="metric">
                <div class="k">Status</div>
                <div class="v">{{ $exception ? 'DOWN' : 'UP' }} <small>· HTTP {{ $exception ? '503' : '200' }}</small></div>
            </div>
            <div class="metric">
                <div class="k">Response</div>
                <div class="v">
                    @if (defined('LARAGRAM_START'))
                        {{ round((microtime(true) - LARAGRAM_START) * 1000) }}<small> ms</small>
                    @else
                        &lt;1<small> ms</small>
                    @endif
                </div>
            </div>
            <div class="metric">
                <div class="k">Runtime</div>
                <div class="v">PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</div>
            </div>
            <div class="metric">
                <div class="k">Framework</div>
                <div class="v">v{{ app()->version() }}</div>
            </div>
        </div>

        <div class="foot">CHECKED {{ strtoupper(date('D M j · H:i:s')) }} {{ strtoupper(date('T')) }}</div>
    </div>
</body>
</html>
