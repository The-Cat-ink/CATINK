<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if (isset($_SESSION['usuario'])) {
    if (($_SESSION['tipo'] ?? '') === 'admin') {
        header('Location: ' . basePath() . '/views/admin.php');
    } else {
        header('Location: ' . basePath() . '/');
    }
    exit();
}
$showRegistro = isset($_GET['modo']) && $_GET['modo'] === 'registro';
$tempRegistro = $_SESSION['temp_registro'] ?? null;
if ($tempRegistro) {
    unset($_SESSION['temp_registro']);
}
// Generar token anti doble envío para el formulario de registro
$regFormToken = bin2hex(random_bytes(16));
$_SESSION['reg_form_token'] = $regFormToken;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CatInk</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
    <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Favicon -->
    <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
    <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
    <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; background: var(--bg);">
<div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px;">
    <a href="<?= basePath() ?>/">
      <img src="<?= imageUrl('img/logo.png') ?>" alt="CatInk" style="width:180px; margin-bottom:24px; border-radius:12px;" loading="lazy" decoding="async">
    </a>

    <div class="card" style="width:100%; max-width:400px; border-radius:12px; padding:24px; overflow:hidden; position:relative;">
      <!-- TABS -->
      <div style="display:flex; gap:0; margin-bottom:20px; border-bottom:2px solid var(--border);">
        <button type="button" class="auth-tab active" id="tabLogin" onclick="showPanel('login')">Iniciar Sesión</button>
        <button type="button" class="auth-tab" id="tabRegistro" onclick="showPanel('registro')">Crear Cuenta</button>
      </div>

      <!-- Panel LOGIN -->
      <div class="auth-panel" id="panelLogin" style="<?= $showRegistro ? 'display:none;' : '' ?>">
        <?php if(isset($_GET['registro'])): ?>
          <p style="color:#28a745; text-align:center; margin-bottom:12px;">
            <?php
              if ($_GET['registro'] === 'verificar') {
                  $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : 'tu correo';
                  echo '¡Te enviamos un correo de verificación a <strong>' . $email . '</strong>! Revisa tu bandeja de entrada (o carpeta de spam) y haz clic en el enlace para activar tu cuenta.';
              } elseif ($_GET['registro'] === 'verificado') {
                  echo '¡Tu cuenta ha sido verificada con éxito! Ya puedes iniciar sesión.';
              } else {
                  echo '¡Cuenta creada! Inicia sesión.';
              }
            ?>
          </p>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
          <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
            <?php
              if ($_GET['error'] == '1') {
                  echo 'Completa todos los campos.';
              } elseif ($_GET['error'] == '3') {
                  echo 'Tu cuenta no está verificada. Por favor, revisa tu correo para verificar tu cuenta.';
              } else {
                  echo 'Usuario o contraseña incorrectos.';
              }
            ?>
          </p>
        <?php endif; ?>
        <form action="<?= basePath() ?>/controllers/logincontroller.php" method="POST">
            <div class="form-group">
                <label for="login_usuario">Usuario o correo electrónico</label>
                <input type="text" id="login_usuario" name="usuario" class="input" placeholder="Tu usuario o correo..." required>
            </div>
            <div class="form-group">
                <label for="login_pass">Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="login_pass" name="pass" class="input" placeholder="Tu contraseña..." required style="padding-right: 40px;">
                    <button type="button" class="btn-toggle-eye" onclick="togglePassVisibility('login_pass', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; padding: 4px; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-perfil-save" style="margin-top:12px;">Iniciar Sesión</button>
        </form>
        <p class="text-center" style="margin-top:16px; font-size:0.9rem;">
            ¿No tienes cuenta? <a href="#" onclick="showPanel('registro'); return false;" style="color:var(--accent); text-decoration:none; font-weight:600;">Regístrate</a>
        </p>
        <p class="text-center" style="margin-top:8px; font-size:0.9rem;">
            <a href="<?= basePath() ?>/olvide_contrasena" style="color:var(--accent); text-decoration:none; font-weight:600;">¿Olvidaste tu contraseña?</a>
        </p>
      </div>

      <!-- Panel REGISTRO -->
      <div class="auth-panel" id="panelRegistro" style="<?= $showRegistro ? '' : 'display:none;' ?>">
        <?php if(isset($_GET['reg_error'])): ?>
          <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
            <?php
              $errores = [
                '1' => 'Todos los campos son obligatorios.',
                '2' => 'Las contraseñas no coinciden.',
                '3' => 'El nombre de usuario ya existe.',
                '4' => 'El correo ya está registrado.',
                '5' => 'Error al registrar. Intenta de nuevo.',
                '6' => 'Debes aceptar los términos y condiciones.',
                '7' => 'La contraseña debe tener al menos 8 caracteres, incluyendo una mayúscula, una minúscula y un número.'
              ];
              echo $errores[$_GET['reg_error']] ?? 'Error desconocido.';
            ?>
          </p>
        <?php endif; ?>
        <!-- Progress -->
        <div style="display:flex; justify-content:center; gap:8px; margin-bottom:16px;">
          <span class="step-dot active" id="dot1"></span>
          <span class="step-dot" id="dot2"></span>
          <span class="step-dot" id="dot3"></span>
          <span class="step-dot" id="dot4"></span>
        </div>
        <form action="<?= basePath() ?>/controllers/registrocontroller.php" method="POST" id="regForm">
          <input type="hidden" name="form_token" value="<?= $regFormToken ?>">
          <div class="reg-step" id="step1">
            <h6 class="text-center" style="margin-bottom:12px; font-weight:600;">¿Cómo te llamas?</h6>
            <div class="form-group">
              <label for="nombre">Nombre completo</label>
              <input type="text" id="nombre" name="nombre" class="input" placeholder="Tu nombre..." required>
            </div>
            <div class="form-group">
              <label for="reg_usuario">Nombre de usuario</label>
              <input type="text" id="reg_usuario" name="usuario" class="input" placeholder="Tu usuario..." required>
            </div>
            <button type="button" class="btn-perfil-save" onclick="nextStep(2)">Siguiente</button>
          </div>
          <div class="reg-step" id="step2" style="display:none;">
            <h6 class="text-center" style="margin-bottom:12px; font-weight:600;">Cuéntanos sobre ti</h6>
            <div class="form-group">
              <label for="fecha_nacimiento">Fecha de nacimiento</label>
              <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">
                <select id="dob_dia" class="input" style="flex: 1; margin-bottom: 0;" required>
                  <option value="" disabled selected>Día</option>
                </select>
                <select id="dob_mes" class="input" style="flex: 1.5; margin-bottom: 0;" required>
                  <option value="" disabled selected>Mes</option>
                </select>
                <select id="dob_anio" class="input" style="flex: 1.2; margin-bottom: 0;" required>
                  <option value="" disabled selected>Año</option>
                </select>
              </div>
              <input type="hidden" id="fecha_nacimiento" name="fecha_nacimiento" required>
            </div>
            <div class="form-group">
              <label for="sexo">Sexo</label>
              <select id="sexo" name="sexo" class="input" required>
                <option value="" disabled selected>Selecciona...</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div class="form-group">
              <label for="entidad">Estado (ubicación)</label>
              <select id="entidad" name="entidad" class="input" required>
                <option value="" disabled selected>Selecciona tu estado...</option>
                <option value="AGU">Aguascalientes</option>
                <option value="BCN">Baja California</option>
                <option value="BCS">Baja California Sur</option>
                <option value="CAM">Campeche</option>
                <option value="CHP">Chiapas</option>
                <option value="CHH">Chihuahua</option>
                <option value="COA">Coahuila</option>
                <option value="COL">Colima</option>
                <option value="CMX">Ciudad de México</option>
                <option value="DUR">Durango</option>
                <option value="GUA">Guanajuato</option>
                <option value="GRO">Guerrero</option>
                <option value="HID">Hidalgo</option>
                <option value="JAL">Jalisco</option>
                <option value="MEX">Estado de México</option>
                <option value="MIC">Michoacán</option>
                <option value="MOR">Morelos</option>
                <option value="NAY">Nayarit</option>
                <option value="NLE">Nuevo León</option>
                <option value="OAX">Oaxaca</option>
                <option value="PUE">Puebla</option>
                <option value="QUE">Querétaro</option>
                <option value="ROO">Quintana Roo</option>
                <option value="SLP">San Luis Potosí</option>
                <option value="SIN">Sinaloa</option>
                <option value="SON">Sonora</option>
                <option value="TAB">Tabasco</option>
                <option value="TAM">Tamaulipas</option>
                <option value="TLA">Tlaxcala</option>
                <option value="VER">Veracruz</option>
                <option value="YUC">Yucatán</option>
                <option value="ZAC">Zacatecas</option>
              </select>
            </div>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn-perfil-save" style="background:var(--muted);" onclick="prevStep(1)">Atrás</button>
              <button type="button" class="btn-perfil-save" onclick="nextStep(3)">Siguiente</button>
            </div>
          </div>
          <div class="reg-step" id="step3" style="display:none;">
            <h6 class="text-center" style="margin-bottom:12px; font-weight:600;">Tu correo electrónico</h6>
            <div class="form-group">
              <label for="correo">Correo</label>
              <input type="email" id="correo" name="correo" class="input" placeholder="correo@ejemplo.com" required>
            </div>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn-perfil-save" style="background:var(--muted);" onclick="prevStep(2)">Atrás</button>
              <button type="button" class="btn-perfil-save" onclick="nextStep(4)">Siguiente</button>
            </div>
          </div>
          <div class="reg-step" id="step4" style="display:none;">
            <h6 class="text-center" style="margin-bottom:12px; font-weight:600;">Crea tu contraseña</h6>
            <div class="form-group">
              <label for="reg_pass">Contraseña</label>
              <input type="password" id="reg_pass" name="pass" class="input" placeholder="Mínimo 8 caracteres (A-Z, a-z, 0-9)..." minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" title="Debe contener mínimo 8 caracteres, incluyendo al menos una mayúscula, una minúscula y un número." required>
            </div>
            <div class="form-group">
              <label for="reg_pass2">Confirmar</label>
              <input type="password" id="reg_pass2" name="pass2" class="input" placeholder="Repite tu contraseña..." minlength="8" required>
            </div>
            <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:12px;">
              <input type="checkbox" id="recibir_correos" name="recibir_correos" style="width:16px; height:16px; accent-color:var(--accent); margin-top:2px; cursor:pointer;" checked>
              <label for="recibir_correos" style="font-weight:normal; font-size:0.85rem; color:var(--text); margin-bottom:0; cursor:pointer; user-select:none;">Deseo recibir correos y novedades de CatInk</label>
            </div>
            <div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:20px;">
              <input type="checkbox" id="terminos_condiciones" name="terminos_condiciones" style="width:16px; height:16px; accent-color:var(--accent); margin-top:2px; cursor:pointer;" required>
              <label for="terminos_condiciones" style="font-weight:normal; font-size:0.85rem; color:var(--text); margin-bottom:0; cursor:pointer; user-select:none;">
                Acepto los <a href="<?= basePath() ?>/terminos" target="_blank" style="color:var(--accent); text-decoration:none; font-weight:600;">términos y condiciones</a>
              </label>
            </div>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn-perfil-save" style="background:var(--muted);" onclick="prevStep(3)">Atrás</button>
              <button type="submit" class="btn-perfil-save">Registrarse</button>
            </div>
          </div>
        </form>
        <p class="text-center" style="margin-top:16px; font-size:0.9rem;">
          ¿Ya tienes cuenta? <a href="#" onclick="showPanel('login'); return false;" style="color:var(--accent); text-decoration:none; font-weight:600;">Inicia sesión</a>
        </p>
      </div>
    </div>
</div>



<style>
.auth-tab {
  flex: 1;
  padding: 10px;
  border: none;
  background: transparent;
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--muted);
  cursor: pointer;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
}
.auth-tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
}
.auth-panel {
  animation: fadeSlide 0.3s ease;
}
@keyframes fadeSlide {
  from { opacity: 0; transform: translateX(10px); }
  to { opacity: 1; transform: translateX(0); }
}
.step-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--border); transition: background 0.3s;
  cursor: pointer;
}
.step-dot.active { background: var(--accent); }
</style>

<script>
function showPanel(panel){
  document.getElementById('panelLogin').style.display = panel === 'login' ? 'block' : 'none';
  document.getElementById('panelRegistro').style.display = panel === 'registro' ? 'block' : 'none';
  document.getElementById('tabLogin').classList.toggle('active', panel === 'login');
  document.getElementById('tabRegistro').classList.toggle('active', panel === 'registro');
  // Re-trigger animation
  const el = document.getElementById(panel === 'login' ? 'panelLogin' : 'panelRegistro');
  el.style.animation = 'none';
  el.offsetHeight;
  el.style.animation = '';
}

// Registro steps
let currentStep = 1;
async function goToStep(n){
  if(n > currentStep){
    const cur = document.getElementById('step'+currentStep);
    const inputs = cur.querySelectorAll('input[required], select[required]');
    
    // Primero limpiamos cualquier error de validación personalizado previo
    for(let inp of inputs){
      inp.setCustomValidity("");
    }
    
    for(let inp of inputs){
      if(!inp.reportValidity()) return;
    }

    // VALIDACIÓN ASÍNCRONA PASO 1 (Usuario)
    if (currentStep === 1) {
      const userInp = document.getElementById('reg_usuario');
      if (userInp) {
        userInp.setCustomValidity(""); // Reset
        const val = userInp.value.trim();
        try {
          const res = await fetch(`<?= basePath() ?>/controllers/validar_registro.php?usuario=${encodeURIComponent(val)}`);
          const data = await res.json();
          if (data.exists) {
            userInp.setCustomValidity(data.message);
            userInp.reportValidity();
            return;
          }
        } catch(e) {
          console.error("Error al validar usuario:", e);
        }
      }
    }

    // VALIDACIÓN ASÍNCRONA PASO 3 (Correo)
    if (currentStep === 3) {
      const emailInp = document.getElementById('correo');
      if (emailInp) {
        emailInp.setCustomValidity(""); // Reset
        const val = emailInp.value.trim();
        try {
          const res = await fetch(`<?= basePath() ?>/controllers/validar_registro.php?correo=${encodeURIComponent(val)}`);
          const data = await res.json();
          if (data.exists) {
            emailInp.setCustomValidity(data.message);
            emailInp.reportValidity();
            return;
          }
        } catch(e) {
          console.error("Error al validar correo:", e);
        }
      }
    }
  }
  currentStep = n;
  document.querySelectorAll('.reg-step').forEach(s => s.style.display='none');
  document.getElementById('step'+n).style.display='block';
  document.querySelectorAll('.step-dot').forEach((d,i)=> d.classList.toggle('active', i < n));
  const firstInput = document.querySelector('#step'+n+' input');
  if(firstInput) firstInput.focus();
}
function nextStep(n){ goToStep(n); }
function prevStep(n){ goToStep(n); }

function initLogin() {
  const diaSel = document.getElementById('dob_dia');
  // Si los selects ya tienen opciones pobladas, no reinicializar los dropdowns
  const alreadyPopulated = diaSel && diaSel.options.length > 1;

  const regForm = document.getElementById('regForm');
  if (regForm && !regForm.dataset.eventsInit) {
    regForm.dataset.eventsInit = '1';
    regForm.addEventListener('keydown', function(e){
      if(e.key === 'Enter' && currentStep < 4){
        e.preventDefault();
        goToStep(currentStep + 1);
      }
    });

    let isSubmitting = false;
    regForm.addEventListener('submit', function(e) {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }
      isSubmitting = true;
      const submitBtn = regForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerText = "Registrando...";
      }
    });
  }

  document.querySelectorAll('.step-dot').forEach((dot, i) => {
    if (!dot.dataset.eventsInit) {
      dot.dataset.eventsInit = '1';
      dot.addEventListener('click', () => {
        const targetStep = i + 1;
        if (targetStep <= currentStep) {
          goToStep(targetStep);
        }
      });
    }
  });

  const regUsuarioInput = document.getElementById('reg_usuario');
  if (regUsuarioInput && !regUsuarioInput.dataset.eventsInit) {
    regUsuarioInput.dataset.eventsInit = '1';
    regUsuarioInput.addEventListener('input', () => {
      regUsuarioInput.setCustomValidity("");
    });
  }
  const regCorreoInput = document.getElementById('correo');
  if (regCorreoInput && !regCorreoInput.dataset.eventsInit) {
    regCorreoInput.dataset.eventsInit = '1';
    regCorreoInput.addEventListener('input', () => {
      regCorreoInput.setCustomValidity("");
    });
  }

  // ---- FECHA DE NACIMIENTO DROPDOWNS ----
  const mesSel = document.getElementById('dob_mes');
  const anioSel = document.getElementById('dob_anio');
  const hiddenInput = document.getElementById('fecha_nacimiento');
  if (diaSel && mesSel && anioSel && hiddenInput && !alreadyPopulated) {
    // Clear options to avoid duplicates or empty states on Turbo loads
    diaSel.innerHTML = '<option value="" disabled selected>Día</option>';
    mesSel.innerHTML = '<option value="" disabled selected>Mes</option>';
    anioSel.innerHTML = '<option value="" disabled selected>Año</option>';

    // Populate days (1-31)
    for (let d = 1; d <= 31; d++) {
      const opt = document.createElement('option');
      opt.value = String(d).padStart(2, '0');
      opt.textContent = d;
      diaSel.appendChild(opt);
    }

    // Populate months (1-12)
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    meses.forEach((m, idx) => {
      const opt = document.createElement('option');
      opt.value = String(idx + 1).padStart(2, '0');
      opt.textContent = m;
      mesSel.appendChild(opt);
    });

    // Populate years (current year down to 1900)
    const currentYear = new Date().getFullYear();
    for (let y = currentYear; y >= 1900; y--) {
      const opt = document.createElement('option');
      opt.value = y;
      opt.textContent = y;
      anioSel.appendChild(opt);
    }

    function updateHiddenDate() {
      const dia = diaSel.value;
      const mes = mesSel.value;
      const anio = anioSel.value;
      if (dia && mes && anio) {
        hiddenInput.value = `${anio}-${mes}-${dia}`;
      } else {
        hiddenInput.value = '';
      }
    }

    diaSel.addEventListener('change', updateHiddenDate);
    mesSel.addEventListener('change', updateHiddenDate);
    anioSel.addEventListener('change', updateHiddenDate);

    // Set default/initial value
    const tempRegistro = <?= json_encode($tempRegistro) ?>;
    let initialDate = tempRegistro && tempRegistro.fecha_nacimiento ? tempRegistro.fecha_nacimiento : '2000-01-01';
    if (initialDate) {
      const parts = initialDate.split('-');
      if (parts.length === 3) {
        anioSel.value = parts[0];
        mesSel.value = parts[1];
        diaSel.value = parts[2];
        hiddenInput.value = initialDate;
      }
    }

    // Pre-fill temp registration values
    if (tempRegistro) {
      if (tempRegistro.nombre) document.getElementById('nombre').value = tempRegistro.nombre;
      if (tempRegistro.usuario) document.getElementById('reg_usuario').value = tempRegistro.usuario;
      if (tempRegistro.correo) document.getElementById('correo').value = tempRegistro.correo;
      if (tempRegistro.sexo) document.getElementById('sexo').value = tempRegistro.sexo;
      if (tempRegistro.entidad) document.getElementById('entidad').value = tempRegistro.entidad;
      if (tempRegistro.recibir_correos === 0) {
        const rc = document.getElementById('recibir_correos');
        if (rc) rc.checked = false;
      }
    }
  }

  <?php if(isset($_GET['reg_error'])): ?>
  currentStep = 4;
  goToStep(4);
  <?php endif; ?>

  <?php if($showRegistro): ?>
  showPanel('registro');
  <?php endif; ?>

  // Configurar los botones de ver/ocultar contraseña (ojo)
  setupPasswordToggles();

  // Limpiar parámetros de la URL para que no persistan al recargar la página
  if (window.history.replaceState && window.location.search) {
    if (window.location.search.includes('registro') || window.location.search.includes('error')) {
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  function setupPasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
      if (!input.parentElement.classList.contains('password-wrapper')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        wrapper.style.position = 'relative';
        wrapper.style.width = '100%';
        
        // Transferir márgenes del input al wrapper para evitar que descuadren el botón absoluto
        const computedStyle = window.getComputedStyle(input);
        wrapper.style.marginTop = computedStyle.marginTop;
        wrapper.style.marginBottom = computedStyle.marginBottom;
        input.style.marginTop = '0px';
        input.style.marginBottom = '0px';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        input.style.paddingRight = '40px';
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.style.position = 'absolute';
        btn.style.right = '12px';
        btn.style.top = '0';
        btn.style.height = '100%';
        btn.style.background = 'none';
        btn.style.border = 'none';
        btn.style.color = 'var(--text-muted, #999)';
        btn.style.cursor = 'pointer';
        btn.style.padding = '0';
        btn.style.margin = '0';
        btn.style.display = 'none';
        btn.style.alignItems = 'center';
        btn.style.justifyContent = 'center';
        btn.style.zIndex = '10';
        btn.innerHTML = '<i class="bi bi-eye" style="font-size: 1.15rem; color: #999;"></i>';
        
        wrapper.appendChild(btn);
        
        const updateBtnVisibility = () => {
          if (input.value.length > 0) {
            btn.style.display = 'flex';
          } else {
            btn.style.display = 'none';
          }
        };

        input.addEventListener('input', updateBtnVisibility);
        input.addEventListener('change', updateBtnVisibility);
        
        // Verificaciones periódicas iniciales para capturar autofill del navegador
        updateBtnVisibility();
        setTimeout(updateBtnVisibility, 100);
        setTimeout(updateBtnVisibility, 500);
        
        btn.addEventListener('click', () => {
          const icon = btn.querySelector('i');
          if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
          } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
          }
        });
      }
    });
  }
}

window.togglePassVisibility = function(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
};

// Soportar tanto navegación Turbo como carga directa de la página
document.addEventListener('turbo:load', initLogin);
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLogin);
} else {
  initLogin();
}
</script>
</body>
</html>