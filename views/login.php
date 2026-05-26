<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if (isset($_SESSION['usuario'])) {
    header('Location: ' . basePath() . '/views/admin.php');
    exit();
}
include("./../layout/header.php");
$showRegistro = isset($_GET['modo']) && $_GET['modo'] === 'registro';
?>
<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:80vh; padding:20px;">
    <a href="<?= basePath() ?>/">
      <img src="<?= basePath() ?>/img/logo.png" alt="CatInk" style="width:180px; margin-bottom:24px; border-radius:12px;">
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
          <p style="color:#28a745; text-align:center; margin-bottom:12px;">¡Cuenta creada! Inicia sesión.</p>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
          <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
            <?= ($_GET['error'] == '1') ? 'Completa todos los campos.' : 'Usuario o contraseña incorrectos.' ?>
          </p>
        <?php endif; ?>
        <form action="<?= basePath() ?>/controllers/logincontroller.php" method="POST">
            <div class="form-group">
                <label for="login_usuario">Usuario</label>
                <input type="text" id="login_usuario" name="usuario" class="input" placeholder="Tu usuario..." required>
            </div>
            <div class="form-group">
                <label for="login_pass">Contraseña</label>
                <input type="password" id="login_pass" name="pass" class="input" placeholder="Tu contraseña..." required>
            </div>
            <button type="submit" class="btn-perfil-save" style="margin-top:12px;">Iniciar Sesión</button>
        </form>
        <p class="text-center" style="margin-top:16px; font-size:0.9rem;">
            ¿No tienes cuenta? <a href="#" onclick="showPanel('registro'); return false;" style="color:var(--accent); text-decoration:none; font-weight:600;">Regístrate</a>
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
                '5' => 'Error al registrar. Intenta de nuevo.'
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
              <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="input" required>
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
              <input type="password" id="reg_pass" name="pass" class="input" placeholder="Mínimo 6 caracteres..." minlength="6" required>
            </div>
            <div class="form-group">
              <label for="reg_pass2">Confirmar</label>
              <input type="password" id="reg_pass2" name="pass2" class="input" placeholder="Repite tu contraseña..." minlength="6" required>
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

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
function goToStep(n){
  if(n > currentStep){
    const cur = document.getElementById('step'+currentStep);
    const inputs = cur.querySelectorAll('input[required]');
    for(let inp of inputs){
      if(!inp.reportValidity()) return;
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

document.getElementById('regForm').addEventListener('keydown', function(e){
  if(e.key === 'Enter' && currentStep < 4){
    e.preventDefault();
    goToStep(currentStep + 1);
  }
});

document.querySelectorAll('.step-dot').forEach((dot, i) => {
  dot.addEventListener('click', ()=> goToStep(i+1));
});

<?php if($showRegistro): ?>
showPanel('registro');
<?php endif; ?>

flatpickr('#fecha_nacimiento', {
  locale: 'es',
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'j F Y',
  maxDate: 'today',
  defaultDate: '2000-01-01',
  disableMobile: false
});
</script>
<?php
include("./../layout/footer.php");
?>