<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

$email = trim($_GET['email'] ?? '');
if (empty($email)) {
    header("Location: " . basePath() . "/login");
    exit();
}

// Si ya está verificado y logueado, llevarlo al perfil
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'lector') {
    header("Location: " . basePath() . "/views/perfil.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esperando Verificación - CatInk</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
    <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
    <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
    <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">

    <style>
        .wait-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            padding: 20px;
            font-family: inherit;
        }

        .wait-card {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .mail-icon-pulse {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 51, 99, 0.1);
            color: var(--accent);
            border-radius: 50%;
            font-size: 2.5rem;
        }

        .mail-icon-pulse::after {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            opacity: 0.4;
            animation: pulseRing 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        @keyframes pulseRing {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.15); opacity: 0.2; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        .wait-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
        }

        .wait-desc {
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .email-badge {
            display: inline-block;
            background: rgba(239, 51, 99, 0.08);
            color: var(--accent);
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.9rem;
            word-break: break-all;
            margin-bottom: 24px;
            border: 1px solid rgba(239, 51, 99, 0.2);
        }

        .polling-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 15px;
        }

        .spinner-dots {
            display: inline-flex;
            gap: 4px;
        }

        .spinner-dot {
            width: 6px;
            height: 6px;
            background: var(--accent);
            border-radius: 50%;
            animation: dotBounce 1.4s infinite ease-in-out both;
        }

        .spinner-dot:nth-child(1) { animation-delay: -0.32s; }
        .spinner-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes dotBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        .success-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border-radius: 50%;
            font-size: 3rem;
            animation: scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .btn-resend {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            margin-top: 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            border: 1px solid var(--accent);
            background: transparent;
            color: var(--accent);
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-resend:disabled {
            opacity: 0.55;
            border-color: var(--border);
            color: var(--muted);
            cursor: not-allowed;
        }

        .btn-resend:not(:disabled):hover {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 6px 16px rgba(239, 51, 99, 0.25);
        }

        @keyframes spinIcon {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: spinIcon 1s linear infinite;
        }
    </style>
</head>
<body>
    <div class="wait-container">
        
        <div style="margin-bottom: 24px; text-align: center;">
            <a href="<?= basePath() ?>/" style="text-decoration:none;">
                <span style="font-family:'Outfit', sans-serif; font-size:2.8rem; font-weight:900; color:var(--text); letter-spacing:-1.5px;">
                    Cat<span style="color:#EF3363;">Ink</span>
                </span>
            </a>
        </div>

        <div class="wait-card" id="waitCard">
            <div class="mail-icon-pulse">
                <i class="bi bi-envelope-open-heart"></i>
            </div>

            <h1 class="wait-title">Esperando verificación...</h1>
            
            <p class="wait-desc">
                Hemos enviado un correo con un botón de confirmación a tu dirección de correo electrónico:
            </p>

            <div class="email-badge">
                <i class="bi bi-envelope-at-fill" style="margin-right:4px;"></i> <?= htmlspecialchars($email) ?>
            </div>

            <p style="font-size:0.85rem; color:var(--muted); line-height:1.5;">
                En cuanto presiones <strong>"Verificar mi cuenta"</strong> desde tu correo (en tu celular o cualquier pestaña), esta pantalla te detectará automáticamente, iniciará sesión y te llevará a tu perfil.
            </p>

            <button type="button" id="btnReenviar" class="btn-resend" disabled onclick="reenviarCorreo()">
                <i class="bi bi-send-fill" id="btnIcon"></i>
                <span>Volver a enviar correo</span>
                <strong id="countdownTimer" style="margin-left: 4px;">(60s)</strong>
            </button>

            <div class="polling-status">
                <span>Comprobando estado en tiempo real</span>
                <div class="spinner-dots">
                    <div class="spinner-dot"></div>
                    <div class="spinner-dot"></div>
                    <div class="spinner-dot"></div>
                </div>
            </div>

            <div style="margin-top: 25px; border-top: 1px solid var(--border); padding-top: 20px; font-size: 0.85rem; color: var(--muted);">
                ¿No recibiste el correo? Revisa tu carpeta de <strong>Spam</strong> o 
                <a href="<?= basePath() ?>/login?modo=registro" style="color: var(--accent); font-weight: 600; text-decoration: none;">intenta con otro correo</a>.
            </div>
        </div>

    </div>

    <script>
    const userEmail = <?= json_encode($email) ?>;
    const checkUrl = '<?= basePath() ?>/controllers/check_verificacion.php?email=' + encodeURIComponent(userEmail);
    let isRedirecting = false;

    // ── TEMPORIZADOR DE CONTEO REGRESIVO Y REENVÍO ──
    let countdown = 60;
    let countdownInterval = null;
    const btnReenviar = document.getElementById('btnReenviar');
    const timerLabel = document.getElementById('countdownTimer');
    const btnIcon = document.getElementById('btnIcon');

    function startCountdown(seconds = 60) {
        countdown = seconds;
        btnReenviar.disabled = true;
        timerLabel.style.display = 'inline';
        timerLabel.textContent = `(${countdown}s)`;
        
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                btnReenviar.disabled = false;
                timerLabel.style.display = 'none';
            } else {
                timerLabel.textContent = `(${countdown}s)`;
            }
        }, 1000);
    }

    startCountdown(60);

    async function reenviarCorreo() {
        if (btnReenviar.disabled) return;
        btnReenviar.disabled = true;
        btnIcon.className = 'bi bi-arrow-repeat spin';
        
        try {
            const formData = new FormData();
            formData.append('email', userEmail);
            
            const res = await fetch('<?= basePath() ?>/controllers/reenviar_verificacion.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message || 'Correo reenviado con éxito. Revisa tu bandeja.', 'success');
                if (data.ya_verificado) {
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                    return;
                }
                startCountdown(60);
            } else {
                showToast(data.error || 'Error al reenviar correo.', 'error');
                btnReenviar.disabled = false;
            }
        } catch (err) {
            showToast('Error de conexión al reenviar correo.', 'error');
            btnReenviar.disabled = false;
        } finally {
            btnIcon.className = 'bi bi-send-fill';
        }
    }

    // ── NOTIFICACIONES TOAST ──
    function showToast(msg, type = '') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast-msg' + (type ? ' toast-' + type : '');
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ── POLLING EN TIEMPO REAL ──
    async function checkVerificationStatus() {
        if (isRedirecting) return;
        try {
            const res = await fetch(checkUrl, { cache: 'no-store' });
            const data = await res.json();

            if (data && data.verificado) {
                isRedirecting = true;
                const card = document.getElementById('waitCard');
                card.innerHTML = `
                    <div class="success-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h1 class="wait-title" style="color:#10b981;">¡Cuenta Verificada!</h1>
                    <p class="wait-desc">Identidad confirmada con éxito. Iniciando tu sesión y redirigiendo a tu perfil...</p>
                    <div class="polling-status">
                        <span>Cargando perfil</span>
                        <div class="spinner-dots">
                            <div class="spinner-dot" style="background:#10b981;"></div>
                            <div class="spinner-dot" style="background:#10b981;"></div>
                            <div class="spinner-dot" style="background:#10b981;"></div>
                        </div>
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = data.redirect || '<?= basePath() ?>/views/perfil.php?registro=verificado';
                }, 1200);
            }
        } catch (e) {
            console.log('Esperando verificación...');
        }
    }

    setInterval(checkVerificationStatus, 2500);
    setTimeout(checkVerificationStatus, 500);
    </script>
</body>
</html>
