<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
// Registro ahora vive dentro de login.php
header('Location: ' . basePath() . '/login?modo=registro');
exit();
?>
<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; padding:20px;">
  <a href="<?= basePath() ?>/">
    <img src="<?= basePath() ?>/img/logo.png" alt="CatInk" style="max-width:150px; margin-bottom:20px; border-radius:12px;">
  </a>
  <div class="card" style="width:100%; max-width:380px; border-radius:12px; padding:24px;">
    <?php if(isset($_GET['error'])): ?>
      <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
        <?php
          $errores = [
            '1' => 'Todos los campos son obligatorios.',
            '2' => 'Las contraseñas no coinciden.',
            '3' => 'El nombre de usuario ya existe.',
            '4' => 'El correo ya está registrado.',
            '5' => 'Error al registrar. Intenta de nuevo.'
          ];
          echo $errores[$_GET['error']] ?? 'Error desconocido.';
        ?>
      </p>
    <?php endif; ?>
    <!-- Progress -->
    <div style="display:flex; justify-content:center; gap:8px; margin-bottom:20px;">
      <span class="step-dot active" id="dot1"></span>
      <span class="step-dot" id="dot2"></span>
      <span class="step-dot" id="dot3"></span>
    </div>
    <form action="<?= basePath() ?>/controllers/registrocontroller.php" method="POST" id="regForm">
      <!-- Paso 1: Nombre y usuario -->
      <div class="reg-step" id="step1">
        <h5 class="text-center" style="margin-bottom:16px; font-weight:700;">¿Cómo te llamas?</h5>
        <div class="form-group">
          <label for="nombre">Nombre completo</label>
          <input type="text" id="nombre" name="nombre" class="input" placeholder="Tu nombre..." required>
        </div>
        <div class="form-group">
          <label for="usuario">Nombre de usuario</label>
          <input type="text" id="usuario" name="usuario" class="input" placeholder="Tu usuario..." required>
        </div>
        <button type="button" class="btn-perfil-save" onclick="nextStep(2)">Siguiente</button>
      </div>
      <!-- Paso 2: Correo -->
      <div class="reg-step" id="step2" style="display:none;">
        <h5 class="text-center" style="margin-bottom:16px; font-weight:700;">Tu correo electrónico</h5>
        <div class="form-group">
          <label for="correo">Correo</label>
          <input type="email" id="correo" name="correo" class="input" placeholder="correo@ejemplo.com" required>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn-perfil-save" style="background:var(--muted);" onclick="prevStep(1)">Atrás</button>
          <button type="button" class="btn-perfil-save" onclick="nextStep(3)">Siguiente</button>
        </div>
      </div>
      <!-- Paso 3: Contraseña -->
      <div class="reg-step" id="step3" style="display:none;">
        <h5 class="text-center" style="margin-bottom:16px; font-weight:700;">Crea tu contraseña</h5>
        <div class="form-group">
          <label for="pass">Contraseña</label>
          <input type="password" id="pass" name="pass" class="input" placeholder="Mínimo 6 caracteres..." minlength="6" required>
        </div>
        <div class="form-group">
          <label for="pass2">Confirmar</label>
          <input type="password" id="pass2" name="pass2" class="input" placeholder="Repite tu contraseña..." minlength="6" required>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn-perfil-save" style="background:var(--muted);" onclick="prevStep(2)">Atrás</button>
          <button type="submit" class="btn-perfil-save">Registrarse</button>
        </div>
      </div>
    </form>
    <p class="text-center" style="margin-top:16px; font-size:0.9rem;">
      ¿Ya tienes cuenta? <a href="<?= basePath() ?>/login" style="color:var(--accent); text-decoration:none; font-weight:600;">Inicia sesión</a>
    </p>
  </div>
</div>
<style>
.step-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--border); transition: background 0.3s;
}
.step-dot.active { background: var(--accent); }
</style>
<script>
let currentStep = 1;
function goToStep(n){
  // Si avanza, validar paso actual
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
  // Focus primer input del paso
  const firstInput = document.querySelector('#step'+n+' input');
  if(firstInput) firstInput.focus();
}
function nextStep(n){ goToStep(n); }
function prevStep(n){ goToStep(n); }

// Enter avanza al siguiente paso (excepto en el último que envía)
document.getElementById('regForm').addEventListener('keydown', function(e){
  if(e.key === 'Enter'){
    if(currentStep < 3){
      e.preventDefault();
      goToStep(currentStep + 1);
    }
  }
});

// Click en dots para navegar
document.querySelectorAll('.step-dot').forEach((dot, i) => {
  dot.style.cursor = 'pointer';
  dot.addEventListener('click', ()=> goToStep(i+1));
});
</script>
<?php include("./../layout/footer.php"); ?>
