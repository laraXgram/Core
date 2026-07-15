<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="<?= $this->charset; ?>" />
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="color-scheme" content="dark light" />
    <title>Error <?= $statusCode; ?> — <?= $statusText; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20128%20128%22%3E%3Ctext%20y%3D%22.9em%22%20font-size%3D%2290%22%3E%E2%9A%A0%EF%B8%8F%3C%2Ftext%3E%3C%2Fsvg%3E" />
    <style><?= $this->include('assets/css/error.css'); ?></style>
</head>
<body>
<div class="card">
    <div class="top">
        <div class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156 156">
                <path fill="#FE0102" transform="translate(108,15)" d="M0 0 C-0.99 3.3 -1.98 6.6 -3 10 C-5.44 9.26 -5.44 9.26 -7.94 8.5 C-20.83 5.3 -34.45 5.58 -46.19 12.19 C-54.16 17.58 -58.58 24.77 -61.59 33.75 C-63.27 42.89 -63.02 53.56 -59 62 C-56.05 68.61 -53.73 71.72 -50 75 C-40.38 82.3 -33.22 83.18 -24.56 83.19 C-19.16 83.24 -19.16 83.24 -10 81 C-9.67 71.43 -9.34 61.86 -9 52 C-15.27 52 -21.54 52 -28 52 C-28 48.7 -28 45.4 -28 42 C-17.77 42 -7.54 42 3 42 C3.67 67.59 3.67 67.59 -0.91 93.84 C-4.27 96.66 -7.68 99.69 -11.04 103.13 C-14.38 106.46 -17.79 109.85 -25 117 C-31.96 112.31 -36.2 108.02 -45.98 98.2 C-56.7 87.39 -65.35 77.58 -74 61 C-77.37 43.38 -76.17 28.91 -67.67 16.2 C-59.85 5.9 -48.84 -0.82 -36 -3 C-22.66 -5.14 -10.91 -3.48 0 0 Z"/>
                <path fill="#0288FE" transform="translate(21,72)" d="M0 0 C9.7 8.64 18.59 17.38 32.31 30.75 C41.29 40.42 50.62 49.72 60 59 C66.59 54.62 71.62 49.42 82.5 38.28 C86.5 34.17 88.35 32.38 92 32 C93.65 33.98 95.3 35.96 97 38 C88.32 47.81 77.28 58.8 60 76 C50.9 68.04 40.91 58.02 24.24 41.31 C16.98 34.03 6.23 23.26 -8 9 C-4.05 4.37 -2.39 2.44 0 0 Z"/>
            </svg>
            <span>Lara<b>Gram</b></span>
        </div>
        <span class="tag"><span class="dot"></span> Error</span>
    </div>

    <div class="code"><?= $statusCode; ?></div>
    <h1><?= $statusText; ?></h1>
    <p>Something went wrong while handling your request. Please try again in a moment — if the problem persists, let us know what you were doing.</p>

    <div class="footer">
        Powered by <b>LaraGram</b> <span class="sep">·</span> HTTP <?= $statusCode; ?>
    </div>
</div>
</body>
</html>
