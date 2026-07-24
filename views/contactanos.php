<?php
include("./../layout/header.php");
include("./../data/conexion.php");

$row = $con->query("SELECT contenido_pag, meta_json FROM paginas WHERE nombre_pag='contacto'")->fetch_assoc();
$meta = json_decode($row['meta_json'] ?? '', true) ?: [];

$horario = $meta['horario'] ?? [
    ['dia' => 'Lunes – Viernes', 'hora' => '9:00 – 18:00 hrs'],
    ['dia' => 'Sábado', 'hora' => '10:00 – 14:00 hrs'],
    ['dia' => 'Domingo', 'hora' => 'Cerrado']
];
?>

<style>
.cnt-page { --cw: 1080px; }

/* ─── Hero ─────────────────────────────────────────────────────── */
.cnt-hero {
    padding: 72px 32px 56px;
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.cnt-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 55% 70% at 85% 30%, rgba(239,51,99,0.1), transparent 65%);
    pointer-events: none;
}
.cnt-hero-inner { max-width: var(--cw); margin: 0 auto; position: relative; z-index: 1; }
.cnt-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(239,51,99,0.1); color: var(--accent);
    font-size: 0.7rem; font-weight: 800; letter-spacing: .18em;
    text-transform: uppercase; padding: 6px 14px; border-radius: 30px;
    border: 1px solid rgba(239,51,99,0.2); margin-bottom: 20px;
}
.cnt-hero-title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900; color: var(--text);
    margin: 0 0 14px; line-height: 1.1;
}
.cnt-hero-sub { font-size: 1.05rem; color: var(--muted); max-width: 520px; line-height: 1.65; margin: 0; }

/* ─── Main Split ─────────────────────────────────────────────────── */
.cnt-main { padding: 64px 32px 96px; background: var(--bg); }
.cnt-inner {
    max-width: var(--cw);
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 480px;
    gap: 64px;
    align-items: start;
}

/* ─── Columna info ──────────────────────────────────────────────── */
.cnt-info-title { font-size: 1.4rem; font-weight: 900; color: var(--text); margin: 0 0 8px; }
.cnt-info-sub { font-size: 0.95rem; color: var(--muted); line-height: 1.65; margin: 0 0 36px; }
.cnt-contact-list { list-style: none; padding: 0; margin: 0 0 40px; display: flex; flex-direction: column; gap: 20px; }
.cnt-contact-item { display: flex; align-items: flex-start; gap: 16px; }
.cnt-contact-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: var(--card-bg); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: var(--accent); flex-shrink: 0;
}
.cnt-contact-text h4 { margin: 0 0 3px; font-size: 0.88rem; font-weight: 800; color: var(--text); text-transform: uppercase; letter-spacing: .06em; }
.cnt-contact-text a, .cnt-contact-text span { font-size: 0.9rem; color: var(--muted); text-decoration: none; }
.cnt-contact-text a:hover { color: var(--accent); }
.cnt-divider { height: 1px; background: var(--border); margin: 32px 0; }
.cnt-social-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .18em; color: var(--muted); margin-bottom: 14px; }
.cnt-social-links { display: flex; gap: 10px; flex-wrap: wrap; }
.cnt-social-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 16px; border-radius: 10px; border: 1px solid var(--border);
    font-size: 0.82rem; font-weight: 700; text-decoration: none;
    color: var(--text); background: var(--card-bg);
    transition: all 0.2s ease;
}
.cnt-social-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.cnt-social-btn.fb:hover { border-color:#1877F2; color:#1877F2; }
.cnt-social-btn.ig:hover { border-color:#E1306C; color:#E1306C; }
.cnt-social-btn.tt:hover { border-color:#00f2ea; color:#00f2ea; }
.cnt-social-btn.yt:hover { border-color:#FF0000; color:#FF0000; }
.cnt-social-btn.x:hover  { border-color:var(--text); }
.cnt-hours {
    margin-top: 32px;
    padding: 20px 22px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
}
.cnt-hours-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .15em; color: var(--muted); margin-bottom: 10px; }
.cnt-hours-item { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text); padding: 5px 0; border-bottom: 1px solid var(--border); }
.cnt-hours-item:last-child { border-bottom: none; }
.cnt-hours-item span { color: var(--muted); }

/* ─── Formulario ─────────────────────────────────────────────────── */
.cnt-form-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 40px 36px;
    position: sticky;
    top: 90px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}
.cnt-form-title { font-size: 1.2rem; font-weight: 900; color: var(--text); margin: 0 0 6px; }
.cnt-form-sub { font-size: 0.85rem; color: var(--muted); margin: 0 0 28px; }
.cnt-form-label {
    display: block; font-size: 0.75rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .12em;
    color: var(--muted); margin-bottom: 6px;
}
.cnt-form-input, .cnt-form-select, .cnt-form-textarea {
    width: 100%; padding: 12px 14px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 10px; color: var(--text);
    font-size: 0.95rem; outline: none;
    transition: border-color 0.2s ease;
    box-sizing: border-box; font-family: inherit;
}
.cnt-form-textarea { resize: vertical; min-height: 130px; }
.cnt-form-input:focus, .cnt-form-select:focus, .cnt-form-textarea:focus { border-color: var(--accent); }
.cnt-form-group { margin-bottom: 18px; }
.cnt-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cnt-form-btn {
    width: 100%; padding: 14px;
    background: var(--accent); color: #fff;
    border: none; border-radius: 12px;
    font-size: 0.95rem; font-weight: 800; letter-spacing: .04em; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 8px; transition: all 0.25s ease;
}
.cnt-form-btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(239,51,99,0.3); }
.cnt-form-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* ─── Toast ─────────────────────────────────────────────────────── */
.cnt-toast-msg {
    display: none; padding: 12px 16px; border-radius: 10px;
    margin-bottom: 20px; font-size: 0.88rem; font-weight: 600;
}

/* ─── Responsive ─────────────────────────────────────────────── */
@media (max-width: 900px) { .cnt-inner { grid-template-columns: 1fr; gap: 40px; } .cnt-form-card { position: static; } }
@media (max-width: 600px) { .cnt-hero { padding: 48px 20px 36px; } .cnt-main { padding: 40px 20px 64px; } .cnt-form-card { padding: 28px 20px; } .cnt-form-row { grid-template-columns: 1fr; } }
</style>

<div class="cnt-page">

<!-- ═══ HERO ════════════════════════════════════════════════════════ -->
<section class="cnt-hero">
    <div class="cnt-hero-inner">
        <div class="cnt-hero-eyebrow"><i class="bi bi-chat-heart-fill"></i> Hablemos</div>
        <h1 class="cnt-hero-title"><?= htmlspecialchars($meta['hero_title'] ?? 'Contáctanos') ?></h1>
        <p class="cnt-hero-sub"><?= htmlspecialchars($meta['hero_sub'] ?? '¿Quieres colaborar, tienes una propuesta, necesitas información o simplemente quieres saludar? Escríbenos, estamos aquí.') ?></p>
    </div>
</section>

<!-- ═══ MAIN ══════════════════════════════════════════════════════════ -->
<section class="cnt-main">
    <div class="cnt-inner">

        <!-- Info -->
        <div>
            <h2 class="cnt-info-title">Estamos para ayudarte</h2>
            <p class="cnt-info-sub">Ya sea para colaboraciones, publicidad, press kits o preguntas generales, nuestro equipo responde en menos de 24 horas.</p>

            <ul class="cnt-contact-list">
                <li class="cnt-contact-item">
                    <div class="cnt-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div class="cnt-contact-text">
                        <h4>Correo general</h4>
                        <a href="mailto:<?= htmlspecialchars($meta['email_general'] ?? 'contacto@catink.com.mx') ?>"><?= htmlspecialchars($meta['email_general'] ?? 'contacto@catink.com.mx') ?></a>
                    </div>
                </li>
                <li class="cnt-contact-item">
                    <div class="cnt-contact-icon"><i class="bi bi-briefcase-fill"></i></div>
                    <div class="cnt-contact-text">
                        <h4>Publicidad y marcas</h4>
                        <a href="mailto:<?= htmlspecialchars($meta['email_publicidad'] ?? 'contacto@catink.com.mx') ?>"><?= htmlspecialchars($meta['email_publicidad'] ?? 'contacto@catink.com.mx') ?></a>
                    </div>
                </li>
                <li class="cnt-contact-item">
                    <div class="cnt-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="cnt-contact-text">
                        <h4>Ubicación</h4>
                        <span><?= htmlspecialchars($meta['ubicacion'] ?? 'Toluca de Lerdo, Estado de México, México') ?></span>
                    </div>
                </li>
            </ul>

            <div class="cnt-hours">
                <p class="cnt-hours-label"><i class="bi bi-clock-fill"></i> Horario de atención</p>
                <?php foreach($horario as $h): ?>
                <div class="cnt-hours-item"><?= htmlspecialchars($h['dia'] ?? '') ?> <span><?= htmlspecialchars($h['hora'] ?? '') ?></span></div>
                <?php endforeach; ?>
            </div>

            <div class="cnt-divider"></div>
            <p class="cnt-social-label">Síguenos en redes</p>
            <div class="cnt-social-links">
                <a href="https://www.facebook.com/catink.mx" target="_blank" rel="noopener" class="cnt-social-btn fb"><i class="bi bi-facebook"></i> Facebook</a>
                <a href="https://www.instagram.com/catink.mx" target="_blank" rel="noopener" class="cnt-social-btn ig"><i class="bi bi-instagram"></i> Instagram</a>
                <a href="https://www.tiktok.com/@catink.mx" target="_blank" rel="noopener" class="cnt-social-btn tt"><i class="bi bi-tiktok"></i> TikTok</a>
                <a href="https://www.youtube.com/@catink" target="_blank" rel="noopener" class="cnt-social-btn yt"><i class="bi bi-youtube"></i> YouTube</a>
            </div>
        </div>

        <!-- Formulario -->
        <div>
            <div class="cnt-form-card">
                <h3 class="cnt-form-title">Envíanos un mensaje</h3>
                <p class="cnt-form-sub">Responderemos a tu correo en menos de 24 horas hábiles.</p>

                <div class="cnt-toast-msg" id="cntToast"></div>

                <form id="formContacto" novalidate>
                    <div class="cnt-form-row">
                        <div class="cnt-form-group">
                            <label class="cnt-form-label" for="cnt-nombre">Nombre *</label>
                            <input type="text" id="cnt-nombre" name="nombre" class="cnt-form-input" placeholder="Tu nombre" required>
                        </div>
                        <div class="cnt-form-group">
                            <label class="cnt-form-label" for="cnt-email">Email *</label>
                            <input type="email" id="cnt-email" name="email" class="cnt-form-input" placeholder="tu@correo.com" required>
                        </div>
                    </div>
                    <div class="cnt-form-group">
                        <label class="cnt-form-label" for="cnt-asunto">Asunto</label>
                        <select id="cnt-asunto" name="asunto" class="cnt-form-select">
                            <option value="Colaboración">Colaboración o alianza</option>
                            <option value="Publicidad">Publicidad y marcas</option>
                            <option value="Prensa">Prensa / Press kit</option>
                            <option value="Empleo">Empleo o prácticas</option>
                            <option value="Consulta general">Consulta general</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="cnt-form-group">
                        <label class="cnt-form-label" for="cnt-mensaje">Mensaje *</label>
                        <textarea id="cnt-mensaje" name="mensaje" class="cnt-form-textarea" placeholder="Cuéntanos en qué podemos ayudarte..." required></textarea>
                    </div>
                    <button type="submit" class="cnt-form-btn" id="btnEnviarContacto">
                        <i class="bi bi-send-fill"></i> Enviar mensaje
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>

</div>

<script>
(function() {
    const form = document.getElementById('formContacto');
    const toast = document.getElementById('cntToast');
    const btn = document.getElementById('btnEnviarContacto');

    function showToastMsg(msg, type) {
        toast.textContent = msg;
        toast.style.display = 'block';
        if (type === 'success') {
            toast.style.background = 'rgba(16,185,129,0.12)';
            toast.style.color = '#10b981';
            toast.style.border = '1px solid rgba(16,185,129,0.25)';
        } else {
            toast.style.background = 'rgba(239,51,99,0.1)';
            toast.style.color = '#EF3363';
            toast.style.border = '1px solid rgba(239,51,99,0.2)';
        }
        toast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const nombre  = document.getElementById('cnt-nombre').value.trim();
            const email   = document.getElementById('cnt-email').value.trim();
            const mensaje = document.getElementById('cnt-mensaje').value.trim();

            if (!nombre) { showToastMsg('Por favor ingresa tu nombre.', 'error'); return; }
            if (!email)  { showToastMsg('Por favor ingresa tu correo electrónico.', 'error'); return; }
            if (!mensaje){ showToastMsg('Por favor escribe tu mensaje.', 'error'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';

            const fd = new FormData(form);
            try {
                const res = await fetch(BASE_PATH + '/controllers/contacto_enviar.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToastMsg('✓ ' + data.message, 'success');
                    form.reset();
                } else {
                    showToastMsg('✕ ' + (data.error || 'Error al enviar. Intenta de nuevo.'), 'error');
                }
            } catch (err) {
                showToastMsg('✕ Error de conexión. Intenta de nuevo.', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Enviar mensaje';
        });
    }
})();
</script>

<?php include("./../layout/footer.php"); ?>