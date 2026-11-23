<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sesi Berakhir &middot; Sistem Absensi Sekolah</title>
<style>
    :root {
        --ink: #0f172a;
        --body: #334155;
        --muted: #64748b;
        --line: #dbe4f0;
        --blue: #1680ff;
        --blue-dark: #0758dc;
        --bg: #eef4fb;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        min-height: 100%;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
        background:
            radial-gradient(circle at 12% 10%, rgba(22, 128, 255, .10), transparent 34%),
            radial-gradient(circle at 88% 90%, rgba(13, 148, 136, .09), transparent 36%),
            var(--bg);
        color: var(--ink);
        font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .wrap {
        width: 100%;
        max-width: 560px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--body);
    }

    .mark {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--blue), var(--blue-dark));
        color: #ffffff;
        font-family: Consolas, monospace;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        box-shadow: 0 6px 14px rgba(22, 128, 255, .28);
    }

    .card {
        padding: 34px 34px 30px;
        background: #ffffff;
        border: 1px solid var(--line);
        border-radius: 22px;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .10);
    }

    .code {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #fff7ed;
        color: #b45309;
        font-family: Consolas, monospace;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
    }

    .code::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #f59e0b;
    }

    h1 {
        margin: 16px 0 0;
        font-size: 30px;
        line-height: 1.12;
        letter-spacing: -.03em;
        font-weight: 800;
    }

    p {
        margin: 12px 0 0;
        font-size: 14.5px;
        line-height: 1.65;
        color: var(--body);
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 20px;
        border-radius: 11px;
        border: 1px solid transparent;
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1.3;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .btn:hover { transform: translateY(-1px); }

    .btn-primary {
        background: var(--blue);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(22, 128, 255, .26);
    }

    .btn-primary:hover { background: var(--blue-dark); }

    .btn-secondary {
        background: #ffffff;
        color: var(--blue-dark);
        border-color: var(--line);
    }

    .btn-secondary:hover { background: #f8fafc; }

    .tip {
        margin-top: 24px;
        padding: 12px 14px;
        border-radius: 11px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        font-size: 12.5px;
        line-height: 1.55;
        color: var(--muted);
    }

    .tip strong {
        color: var(--body);
        font-weight: 700;
    }

    .foot {
        margin: 18px 2px 0;
        font-family: Consolas, monospace;
        font-size: 10px;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #94a3b8;
        text-align: center;
    }

    @media (max-width: 480px) {
        .card { padding: 26px 22px 24px; }
        h1 { font-size: 25px; }
        .btn { flex: 1; }
    }
</style>
</head>
<body>
<main class="wrap">
    <div class="brand">
        <span class="mark" aria-hidden="true">SA</span>
        <span>Sistem Absensi Sekolah</span>
    </div>

    <section class="card">
        <span class="code">419 &middot; SESI BERAKHIR</span>
        <h1>Sesi Anda sudah berakhir</h1>
        <p>
            Halaman ini terlalu lama dibiarkan terbuka, atau sesi Anda sudah diperbarui
            di tab lain. Data yang belum sempat dikirim tidak tersimpan.
        </p>

        <div class="actions">
            <a href="/login" class="btn btn-primary">Masuk kembali</a>
            <a href="/dashboard" class="btn btn-secondary">Ke dashboard</a>
        </div>

        <div class="tip">
            <strong>Tips:</strong> gunakan satu tab saat mengisi formulir, dan hindari
            membiarkan halaman terbuka lebih dari 8 jam. Setelah masuk kembali, ulangi
            pengisian dari awal agar data tersimpan.
        </div>
    </section>

    <p class="foot">Kode 419 &middot; Token keamanan (CSRF) tidak cocok</p>
</main>
</body>
</html>
