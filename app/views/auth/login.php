<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    $routes = [
        'admin'     => '/bimbingan-skripsi/app/Views/admin/dashboard.php',
        'dosen'     => '/bimbingan-skripsi/app/Views/dosen/dashboard.php',
        'mahasiswa' => '/bimbingan-skripsi/app/Views/mahasiswa/dashboard.php',
    ];
    header('Location: ' . ($routes[$role] ?? '/bimbingan-skripsi/public/index.php'));
    exit;
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$remembered_username = $_COOKIE['remember_username'] ?? '';
$remembered_checked  = isset($_COOKIE['remember_checked']) ? 'checked' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Bimbingan Skripsi Universitas Lampung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --unila-blue:    #1A4FA0;
            --unila-blue-dk: #0F3273;
            --unila-blue-lt: #2B69C7;
            --accent:        #E8F0FB;
            --text-dark:     #1C2B4A;
            --text-mid:      #4A5568;
            --text-light:    #8A9BB5;
            --border:        #D6E0F0;
            --white:         #FFFFFF;
            --error:         #D93025;
            --error-bg:      #FEF0EF;
        }

        html, body {
            height: 100%;
            font-family: 'Source Sans 3', sans-serif;
            background: var(--white);
            color: var(--text-dark);
        }

        /* ── LAYOUT ── */
        .page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ── LEFT PANEL ── */
        .panel-left {
            position: relative;
            overflow: hidden;
        }

        .panel-left img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            filter: brightness(0.88) saturate(1.1);
            transition: transform 8s ease;
        }

        .panel-left:hover img {
            transform: scale(1.04);
        }

        /* gradient overlay bawah */
        .panel-left::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                transparent 50%,
                rgba(10, 28, 60, 0.55) 100%
            );
            pointer-events: none;
        }

        /* ── RIGHT PANEL ── */
        .panel-right {
            display: flex;
            flex-direction: column;
            padding: 40px 64px;
            background: var(--white);
            overflow-y: auto;
        }

        /* Logo bar */
        .logo-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: auto;
            padding-bottom: 24px;
        }

        .logo-bar img {
            height: 48px;
            width: auto;
        }

        .logo-divider {
            width: 1px;
            height: 40px;
            background: var(--border);
            margin: 0 4px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text .tagline {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--unila-blue);
        }

        .logo-text .univ-name {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.2;
        }

        /* Form wrapper */
        .form-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 0;
        }

        /* Heading */
        .form-heading {
            margin-bottom: 40px;
        }

        .form-heading .subtitle {
            font-size: 17px;
            font-weight: 300;
            color: var(--text-mid);
            letter-spacing: 0.02em;
            margin-bottom: 4px;
        }

        .form-heading h1 {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--unila-blue);
            letter-spacing: 0.03em;
            line-height: 1.25;
        }

        /* Error alert */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--error-bg);
            border: 1px solid #F5C6C2;
            border-left: 4px solid var(--error);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--error);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error svg {
            flex-shrink: 0;
        }

        /* Form fields */
        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--unila-blue);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'Source Sans 3', sans-serif;
            font-size: 15px;
            color: var(--text-dark);
            background: var(--white);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-wrap input::placeholder {
            color: var(--text-light);
            font-weight: 300;
        }

        .input-wrap input:focus {
            border-color: var(--unila-blue-lt);
            box-shadow: 0 0 0 3px rgba(43, 105, 199, 0.12);
        }

        /* Password toggle */
        .input-wrap input.has-toggle {
            padding-right: 48px;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            display: flex;
            align-items: center;
            padding: 4px;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: var(--unila-blue); }

        /* Remember + forgot */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-mid);
            user-select: none;
        }

        .remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--unila-blue);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            color: var(--unila-blue-lt);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: var(--unila-blue-dk); }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--unila-blue);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-family: 'Source Sans 3', sans-serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.06em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .btn-login:hover {
            background: var(--unila-blue-lt);
            box-shadow: 0 6px 20px rgba(26, 79, 160, 0.35);
        }

        .btn-login:active {
            transform: scale(0.985);
            box-shadow: none;
        }

        /* Loading state */
        .btn-login .btn-text  { transition: opacity 0.2s; }
        .btn-login .btn-spinner { display: none; }

        .btn-login.loading .btn-text    { opacity: 0; }
        .btn-login.loading .btn-spinner { display: flex; align-items: center; justify-content: center; position: absolute; inset: 0; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner-ring {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        /* Footer */
        .form-footer {
            margin-top: auto;
            padding-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .page { grid-template-columns: 1fr; }
            .panel-left { display: none; }
            .panel-right { padding: 32px 28px; }
        }
    </style>
</head>
<body>

<div class="page">

    <!-- LEFT: Foto Gedung Rektorat -->
    <div class="panel-left">
        <img
            src="/bimbingan-skripsi/public/img/rektorat.jpg"
            alt="Gedung Rektorat Universitas Lampung"
        >
    </div>

    <!-- RIGHT: Form Login -->
    <div class="panel-right">

        <!-- Logo Bar -->
        <div class="logo-bar">
            <img src="/bimbingan-skripsi/public/img/be-strong-2023.png" alt="Logo Unila">
            <div class="logo-divider"></div>
        </div>

        <!-- Form -->
        <div class="form-wrapper">

            <div class="form-heading">
                <p class="subtitle">Sistem Bimbingan Skripsi</p>
                <h1>UNIVERSITAS LAMPUNG</h1>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/bimbingan-skripsi/app/controllers/AuthController.php" id="loginForm">

                <!-- Username -->
                <div class="field">
                    <label for="email">Username</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="email"
                            name="email"
                            placeholder="Masukkan Akun Pengguna"
                            value="<?= htmlspecialchars($_POST['email'] ?? $remembered_username) ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan Kata Sandi"
                            class="has-toggle"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-pw" id="togglePw" aria-label="Tampilkan password">
                            <!-- Eye icon -->
                            <svg id="iconEye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye-off icon (hidden) -->
                            <svg id="iconEyeOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember + Forgot -->
                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1" <?= $remembered_checked ?>>
                        Ingat Saya
                    </label>
                    <a href="#" class="forgot-link">Lupa Password?</a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Masuk</span>
                    <span class="btn-spinner"><span class="spinner-ring"></span></span>
                </button>

            </form>

        </div>

        <div class="form-footer">
            &copy; <?= date('Y') ?> Universitas Lampung. Hak cipta dilindungi.
        </div>

    </div>
</div>

<script>
    // Toggle show/hide password
    const togglePw  = document.getElementById('togglePw');
    const pwInput   = document.getElementById('password');
    const iconEye   = document.getElementById('iconEye');
    const iconEyeOff= document.getElementById('iconEyeOff');

    togglePw.addEventListener('click', () => {
        const isHidden = pwInput.type === 'password';
        pwInput.type        = isHidden ? 'text' : 'password';
        iconEye.style.display    = isHidden ? 'none'  : '';
        iconEyeOff.style.display = isHidden ? ''      : 'none';
    });

    // Loading state saat submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnLogin');
        btn.classList.add('loading');
        btn.disabled = true;
    });
</script>

</body>
</html>
