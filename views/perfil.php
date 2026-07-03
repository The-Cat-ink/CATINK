<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(!isset($_SESSION['usuario'])){
    header('Location: ' . basePath() . '/login');
    exit;
}
$noTurboCache = true; // El perfil es un formulario: nunca servir snapshot cacheado de Turbo
include("./../layout/header.php");
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<?php
require_once("./../data/conexion.php");
require_once(__DIR__ . "/helpers/moderacion.php");
$tipoUsuario = $_SESSION['tipo'] ?? 'lector';
if($tipoUsuario === 'admin'){
    $stmtUser = $con->prepare("SELECT *, registro AS creado FROM usuarios WHERE usuario = ?");
} else {
    $stmtUser = $con->prepare("SELECT * FROM lectores WHERE usuario = ?");
}
$stmtUser->bind_param("s", $_SESSION['usuario']);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$palabras  = explode(' ', $user['nombre']);
$iniciales = strtoupper(substr($palabras[0],0,1) . (isset($palabras[1]) ? substr($palabras[1],0,1) : ''));
$fechaRegistro = ($user['creado'] ?? $user['registro'] ?? null) ? date('M Y', strtotime($user['creado'] ?? $user['registro'])) : 'N/A';
$avatarActual = null;
if($user['avatar_id'] ?? null){
    $stmtAv = $con->prepare("SELECT imagen FROM avatares_perfil WHERE id_avatar = ?");
    $stmtAv->bind_param("i", $user['avatar_id']);
    $stmtAv->execute();
    $avRow = $stmtAv->get_result()->fetch_assoc();
    if($avRow) $avatarActual = $avRow['imagen'];
}
$avatares = $con->query("SELECT * FROM avatares_perfil WHERE activo = 1 ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);
$ent = $user['entidad'] ?? '';
$estadosMX = ['AGU'=>'Aguascalientes','BCN'=>'Baja California','BCS'=>'Baja California Sur','CAM'=>'Campeche','CHP'=>'Chiapas','CHH'=>'Chihuahua','CMX'=>'Ciudad de México','COA'=>'Coahuila','COL'=>'Colima','DUR'=>'Durango','GUA'=>'Guanajuato','GRO'=>'Guerrero','HID'=>'Hidalgo','JAL'=>'Jalisco','MEX'=>'Estado de México','MIC'=>'Michoacán','MOR'=>'Morelos','NAY'=>'Nayarit','NLE'=>'Nuevo León','OAX'=>'Oaxaca','PUE'=>'Puebla','QUE'=>'Querétaro','ROO'=>'Quintana Roo','SLP'=>'San Luis Potosí','SIN'=>'Sinaloa','SON'=>'Sonora','TAB'=>'Tabasco','TAM'=>'Tamaulipas','TLA'=>'Tlaxcala','VER'=>'Veracruz','YUC'=>'Yucatán','ZAC'=>'Zacatecas'];

// Notificaciones del usuario (avisos de moderación, etc.)
$notifTipo   = $tipoUsuario === 'admin' ? 'admin' : 'lector';
$notifUserId = (int)($tipoUsuario === 'admin' ? ($user['id_u'] ?? 0) : ($user['id'] ?? 0));
$notificaciones = [];
$notifNoLeidas  = 0;
if ($notifUserId > 0) {
    $stmtNotif = @$con->prepare("SELECT * FROM notificaciones WHERE tipo_usuario = ? AND user_id = ? ORDER BY creada DESC LIMIT 20");
    if ($stmtNotif) {
        $stmtNotif->bind_param("si", $notifTipo, $notifUserId);
        $stmtNotif->execute();
        $notificaciones = $stmtNotif->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($notificaciones as $n) { if (!$n['leida']) $notifNoLeidas++; }
    }
}

// Comentarios publicados por el usuario (para mostrarlos en su propio perfil)
$misComentarios = [];
if ($notifUserId > 0) {
    $colUsuario = $tipoUsuario === 'admin' ? 'usuario_id' : 'lector_id';
    $sqlMisCom = "SELECT c.id_comentario, c.contenido, c.fecha_publicacion,
                         n.id AS noticia_id, n.slug AS noticia_slug, n.titulo AS noticia_titulo,
                         (SELECT COUNT(*) FROM likes_comentarios lc WHERE lc.comentario_id = c.id_comentario) AS total_likes
                  FROM comentarios c
                  INNER JOIN noticias n ON c.noticia_id = n.id
                  WHERE c.$colUsuario = ? AND c.estado = 'activo'
                  ORDER BY c.fecha_publicacion DESC LIMIT 30";
    $stmtMisCom = @$con->prepare($sqlMisCom);
    if ($stmtMisCom) {
        $stmtMisCom->bind_param("i", $notifUserId);
        $stmtMisCom->execute();
        $misComentarios = $stmtMisCom->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ============================================================
// MODERACIÓN (solo superadmin): lista de usuarios suspendidos
// Reúne lectores (comentaristas) y usuarios (admins/editores) que
// están baneados ahora mismo (permanente o con baneado_hasta futuro).
// Fail-soft: si la migración de baneo aún no se aplicó, prepare/query
// falla y la lista queda vacía sin romper la página.
// ============================================================
$esSuper   = !empty($_SESSION['superadmin']);
$baneados  = [];
if ($esSuper) {
    $qLec = @$con->query(
        "SELECT id, nombre, usuario, correo, baneado_hasta, baneado_permanente, baneado_motivo
         FROM lectores
         WHERE baneado_permanente = 1 OR (baneado_hasta IS NOT NULL AND baneado_hasta > NOW())
         ORDER BY baneado_permanente DESC, baneado_hasta DESC"
    );
    if ($qLec) { while ($r = $qLec->fetch_assoc()) { $r['tipo'] = 'lector'; $baneados[] = $r; } }

    $qAdm = @$con->query(
        "SELECT id_u AS id, nombre, usuario, correo, baneado_hasta, baneado_permanente, baneado_motivo
         FROM usuarios
         WHERE baneado_permanente = 1 OR (baneado_hasta IS NOT NULL AND baneado_hasta > NOW())
         ORDER BY baneado_permanente DESC, baneado_hasta DESC"
    );
    if ($qAdm) { while ($r = $qAdm->fetch_assoc()) { $r['tipo'] = 'admin'; $baneados[] = $r; } }
}
?>

<!-- Notificaciones -->
<?php if(isset($_GET['ok'])): ?>
<div class="perfil-toast perfil-toast--ok"><i class="bi bi-check-circle-fill"></i> Perfil actualizado correctamente.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<?php $errores = ['1'=>'La contraseña actual es incorrecta.','2'=>'Error al actualizar.','3'=>'Ese nombre de usuario ya está en uso.','4'=>'El nombre de usuario contiene caracteres inválidos.','5'=>'Contraseña incorrecta. No se eliminó la cuenta.','6'=>'Error al eliminar la cuenta.']; ?>
<div class="perfil-toast perfil-toast--err"><i class="bi bi-exclamation-circle-fill"></i> <?= $errores[$_GET['error']] ?? 'Error desconocido.' ?></div>
<?php endif; ?>

<!-- HERO BANNER -->
<div class="perfil-hero">
  <div class="perfil-hero-bg"></div>
  <div class="perfil-hero-content">
    <!-- Avatar -->
    <div class="perfil-avatar-wrap">
      <div class="perfil-avatar" id="perfilAvatar">
        <?php if($tipoUsuario === 'admin' && !empty($user['foto_personal'])): ?>
          <img src="<?= imageUrl($user['foto_personal']) ?>" alt="Foto personal">
        <?php elseif($avatarActual): ?>
          <img src="<?= imageUrl($avatarActual) ?>" alt="Avatar">
        <?php else: ?>
          <span><?= htmlspecialchars($iniciales) ?></span>
        <?php endif; ?>
      </div>
      <button type="button" class="perfil-camera" id="openAvatarPicker" title="Cambiar avatar">
        <i class="bi bi-camera-fill"></i>
      </button>
    </div>
    <!-- Info -->
    <div class="perfil-hero-info">
      <h1 class="perfil-hero-nombre"><?= htmlspecialchars($user['nombre']) ?></h1>
      <p class="perfil-hero-username">@<?= htmlspecialchars($user['usuario']) ?></p>
      <div class="perfil-hero-badges">
        <span class="perfil-badge"><i class="bi bi-calendar3"></i> Miembro desde <?= $fechaRegistro ?></span>
        <?php if($tipoUsuario === 'admin'): ?>
          <span class="perfil-badge perfil-badge--admin"><i class="bi bi-shield-check-fill"></i> Editor</span>
        <?php endif; ?>
      </div>
    </div>
    <!-- Cerrar sesión -->
    <a href="<?= basePath() ?>/controllers/logoutcontroller.php" class="perfil-hero-logout" data-turbo="false">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>
  </div>
</div>

<!-- TABS -->
<div class="perfil-page">
  <div class="perfil-tabs" role="tablist">
    <button class="perfil-tab active" data-tab="cuenta" role="tab"><i class="bi bi-person-fill"></i> Cuenta</button>
    <button class="perfil-tab" data-tab="personal" role="tab"><i class="bi bi-info-circle-fill"></i> Información</button>
    <button class="perfil-tab" data-tab="notificaciones" role="tab"><i class="bi bi-bell-fill"></i> Notificaciones
      <?php if($notifNoLeidas > 0): ?><span class="perfil-notif-badge"><?= $notifNoLeidas ?></span><?php endif; ?>
    </button>
    <button class="perfil-tab" data-tab="comentarios" role="tab"><i class="bi bi-chat-left-text-fill"></i> Mis comentarios</button>
    <?php if($tipoUsuario === 'admin'): ?>
    <button class="perfil-tab" data-tab="publico" role="tab"><i class="bi bi-globe2"></i> Perfil Público</button>
    <?php endif; ?>
    <?php if($esSuper): ?>
    <button class="perfil-tab" data-tab="moderacion" role="tab"><i class="bi bi-shield-lock-fill"></i> Baneos
      <?php if(count($baneados) > 0): ?><span class="perfil-mod-count"><?= count($baneados) ?></span><?php endif; ?>
    </button>
    <?php endif; ?>
    <button class="perfil-tab perfil-tab--danger" data-tab="peligro" role="tab"><i class="bi bi-shield-exclamation"></i> Seguridad</button>
  </div>

  <form id="perfilForm" action="<?= basePath() ?>/controllers/perfilcontroller.php" method="POST" enctype="multipart/form-data">

    <!-- TAB: CUENTA -->
    <div class="perfil-tab-panel active" id="tab-cuenta">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-person-fill"></i>
          <div>
            <h2>Datos de Cuenta</h2>
            <p>Tu nombre de usuario y correo electrónico.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-field-group">
            <label for="nombre_usuario">Nombre de Usuario</label>
            <div class="perfil-input-prefix">
              <span class="prefix">@</span>
              <input type="text" id="nombre_usuario" name="nombre_usuario" class="input" value="<?= htmlspecialchars($user['usuario']) ?>"
                required pattern="[a-zA-Z0-9_.]{3,30}" title="3–30 caracteres: letras, números, puntos y guiones bajos.">
            </div>
            <small>3–30 caracteres. Solo letras, números, puntos y guiones bajos.</small>
          </div>
          <div class="perfil-field-group">
            <label for="correo">Correo Electrónico</label>
            <input type="email" id="correo" name="correo" class="input" value="<?= htmlspecialchars($user['correo']) ?>" required>
          </div>
          <div class="perfil-field-group">
            <label>Contraseña</label>
            <button type="button" id="togglePass" class="perfil-toggle-pass">
              <i class="bi bi-key-fill"></i> Cambiar contraseña
              <i class="bi bi-chevron-down" id="togglePassIcon" style="margin-left:auto;transition:transform .2s;"></i>
            </button>
            <div id="passFields" style="display:none;">
              <div class="perfil-fields" style="margin-top:12px;">
                <div class="perfil-field-group">
                  <label for="pass_actual">Contraseña Actual</label>
                  <input type="password" id="pass_actual" name="pass_actual" class="input" placeholder="••••••••">
                </div>
                <div class="perfil-field-group">
                  <label for="pass_nueva">Nueva Contraseña</label>
                  <input type="password" id="pass_nueva" name="pass_nueva" class="input" placeholder="••••••••">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>

    <!-- TAB: INFORMACIÓN PERSONAL -->
    <div class="perfil-tab-panel" id="tab-personal">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-info-circle-fill"></i>
          <div>
            <h2>Información Personal</h2>
            <p>Datos opcionales sobre ti.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-fields-row">
            <div class="perfil-field-group">
              <label for="sexo">Sexo</label>
              <select id="sexo" name="sexo" class="input">
                <option value="">Seleccionar...</option>
                <option value="masculino" <?= ($user['sexo'] ?? '') == 'masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="femenino" <?= ($user['sexo'] ?? '') == 'femenino' ? 'selected' : '' ?>>Femenino</option>
                <option value="otro" <?= ($user['sexo'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
              </select>
            </div>
            <div class="perfil-field-group">
              <label for="nacimiento">Fecha de Nacimiento</label>
              <div style="display: flex; gap: 8px; align-items: center;">
                <select id="dob_dia" class="input" style="flex: 1; margin-bottom: 0;">
                  <option value="" disabled selected>Día</option>
                </select>
                <select id="dob_mes" class="input" style="flex: 1.5; margin-bottom: 0;">
                  <option value="" disabled selected>Mes</option>
                </select>
                <select id="dob_anio" class="input" style="flex: 1.2; margin-bottom: 0;">
                  <option value="" disabled selected>Año</option>
                </select>
              </div>
              <input type="hidden" id="nacimiento" name="nacimiento" value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '') ?>">
            </div>
          </div>
          <div class="perfil-field-group">
            <label for="entidad">Entidad Federativa</label>
            <select id="entidad" name="entidad" class="input">
              <option value="">Seleccionar...</option>
              <?php foreach($estadosMX as $clave => $nombre): ?>
              <option value="<?= $clave ?>" <?= $ent == $clave ? 'selected' : '' ?>><?= $nombre ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>

    <!-- TAB: PERFIL PÚBLICO (solo admins) -->
    <?php if($tipoUsuario === 'admin'): ?>
    <div class="perfil-tab-panel" id="tab-publico">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-globe2"></i>
          <div>
            <h2>Perfil Público</h2>
            <p>Lo que verán los lectores en tu página de autor.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-field-group">
            <label for="biografia">Biografía</label>
            <textarea id="biografia" name="biografia" class="input" rows="4" style="resize:vertical;" placeholder="Cuéntale al mundo sobre ti..."><?= htmlspecialchars($user['biografia'] ?? '') ?></textarea>
          </div>
          <div class="perfil-fields-row">
            <div class="perfil-field-group">
              <label for="link_twitter"><i class="bi bi-twitter-x"></i> Twitter / X</label>
              <input type="url" id="link_twitter" name="link_twitter" class="input" value="<?= htmlspecialchars($user['link_twitter'] ?? '') ?>" placeholder="https://x.com/tu_usuario">
            </div>
            <div class="perfil-field-group">
              <label for="link_instagram"><i class="bi bi-instagram"></i> Instagram</label>
              <input type="url" id="link_instagram" name="link_instagram" class="input" value="<?= htmlspecialchars($user['link_instagram'] ?? '') ?>" placeholder="https://instagram.com/tu_usuario">
            </div>
          </div>
          <a href="<?= basePath() ?>/autor/<?= $user['id_u'] ?>" target="_blank" class="perfil-link-publico">
            <i class="bi bi-box-arrow-up-right"></i> Ver mi perfil público
          </a>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </form><!-- /form -->

  <!-- TAB: NOTIFICACIONES -->
  <div class="perfil-tab-panel" id="tab-notificaciones">
    <section class="perfil-notif" id="perfilNotif" data-base="<?= basePath() ?>">
      <div class="perfil-notif-head">
        <h2><i class="bi bi-bell-fill"></i> Notificaciones
          <?php if ($notifNoLeidas > 0): ?><span class="perfil-notif-badge"><?= $notifNoLeidas ?></span><?php endif; ?>
        </h2>
        <?php if ($notifNoLeidas > 0): ?>
          <button type="button" class="perfil-notif-marcar" id="btnMarcarNotif"><i class="bi bi-check2-all"></i> Marcar como leídas</button>
        <?php endif; ?>
      </div>
      <?php if (empty($notificaciones)): ?>
        <div class="perfil-notif-empty">
          <i class="bi bi-bell-slash"></i>
          <p>No tienes notificaciones por ahora.</p>
        </div>
      <?php else: ?>
        <ul class="perfil-notif-lista">
          <?php foreach ($notificaciones as $n):
            $esSuspension = $n['tipo'] === 'suspension';
            $icono = $esSuspension ? 'bi-slash-circle-fill' : ($n['tipo'] === 'reactivacion' ? 'bi-check-circle-fill' : 'bi-info-circle-fill');
          ?>
            <li class="perfil-notif-item <?= $n['leida'] ? '' : 'is-unread' ?> notif-<?= htmlspecialchars($n['tipo']) ?>">
              <span class="perfil-notif-icono"><i class="bi <?= $icono ?>"></i></span>
              <div class="perfil-notif-cuerpo">
                <div class="perfil-notif-titulo">
                  <?= htmlspecialchars($n['titulo']) ?>
                  <?php if (!$n['leida']): ?><span class="perfil-notif-dot" title="No leída"></span><?php endif; ?>
                </div>
                <p class="perfil-notif-msg"><?= nl2br(htmlspecialchars($n['mensaje'])) ?></p>
                <span class="perfil-notif-fecha"><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($n['creada'])) ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <!-- TAB: MIS COMENTARIOS -->
  <div class="perfil-tab-panel" id="tab-comentarios">
    <div class="perfil-card">
      <div class="perfil-card-header">
        <i class="bi bi-chat-left-text-fill"></i>
        <div>
          <h2>Mis comentarios</h2>
          <p>Los comentarios que has publicado en las notas.</p>
        </div>
      </div>
      <div class="perfil-comentarios">
        <?php if (empty($misComentarios)): ?>
          <div class="perfil-notif-empty">
            <i class="bi bi-chat-left-dots"></i>
            <p>Aún no has publicado comentarios.</p>
          </div>
        <?php else: ?>
          <?php foreach ($misComentarios as $com):
            $url = newsUrl($com['noticia_slug'] ?: $com['noticia_id']);
          ?>
            <div class="perfil-comentario-card">
              <p class="perfil-comentario-texto"><?= nl2br(htmlspecialchars($com['contenido'])) ?></p>
              <div class="perfil-comentario-meta">
                <span><i class="bi bi-heart-fill"></i> <?= (int)$com['total_likes'] ?></span>
                <span><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($com['fecha_publicacion'])) ?></span>
              </div>
              <a href="<?= $url ?>" class="perfil-comentario-noticia">
                <i class="bi bi-link-45deg"></i> En: <?= htmlspecialchars($com['noticia_titulo']) ?>
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- TAB: SEGURIDAD / ZONA DE PELIGRO (fuera del form para evitar submit accidental) -->
  <div class="perfil-tab-panel" id="tab-peligro">
    <div class="perfil-card perfil-card--danger">
      <div class="perfil-card-header">
        <i class="bi bi-shield-exclamation" style="color:#ef3333;"></i>
        <div>
          <h2 style="color:#ef3333;">Zona de Peligro</h2>
          <p>Acciones irreversibles en tu cuenta.</p>
        </div>
      </div>
      <div class="perfil-danger-row">
        <div>
          <strong>Eliminar mi cuenta</strong>
          <p>Se borrarán permanentemente tus datos, comentarios y likes. Esta acción <strong>no se puede deshacer</strong>.</p>
        </div>
        <button type="button" class="perfil-btn-eliminar" id="btnEliminarCuenta">
          <i class="bi bi-trash3-fill"></i> Eliminar cuenta
        </button>
      </div>
    </div>
  </div>

  <!-- TAB: BANEOS / MODERACIÓN (solo superadmin) -->
  <?php if($esSuper): ?>
  <div class="perfil-tab-panel" id="tab-moderacion">
    <div class="perfil-card" data-base="<?= basePath() ?>" id="modCard">
      <div class="perfil-card-header">
        <i class="bi bi-shield-lock-fill"></i>
        <div>
          <h2>Gestión de usuarios</h2>
          <p>Consulta usuarios suspendidos y activos, y modera sus cuentas.</p>
        </div>
      </div>

      <!-- Toggle Suspendidos / Activos -->
      <div class="perfil-mod-toggle">
        <button type="button" class="perfil-mod-tgl active" data-view="suspendidos">
          <i class="bi bi-slash-circle"></i> Suspendidos
          <span class="perfil-mod-count" id="susCount" <?= count($baneados) ? '' : 'style="display:none;"' ?>><?= count($baneados) ?></span>
        </button>
        <button type="button" class="perfil-mod-tgl" data-view="activos">
          <i class="bi bi-person-check"></i> Activos
        </button>
      </div>

      <!-- Vista: Suspendidos -->
      <div class="perfil-mod-view" id="viewSuspendidos">
        <div class="perfil-mod-list" id="modList">
          <?php if(empty($baneados)): ?>
            <div class="perfil-mod-empty" id="modEmpty">
              <i class="bi bi-check-circle"></i>
              <p>No hay usuarios suspendidos en este momento.</p>
            </div>
          <?php else: foreach($baneados as $b):
            $bpal = explode(' ', $b['nombre']);
            $bini = strtoupper(substr($bpal[0],0,1) . (isset($bpal[1]) ? substr($bpal[1],0,1) : ''));
            $estadoTxt = textoBaneo($b);
            $perfilUrl = $b['tipo'] === 'admin'
                ? basePath() . '/autor/' . (int)$b['id']
                : basePath() . '/usuario/' . (int)$b['id'];
          ?>
          <div class="perfil-mod-row" data-tipo="<?= $b['tipo'] ?>" data-userid="<?= (int)$b['id'] ?>">
            <div class="perfil-mod-avatar"><?= htmlspecialchars($bini) ?></div>
            <div class="perfil-mod-info">
              <div class="perfil-mod-nombre">
                <?= htmlspecialchars($b['nombre']) ?>
                <span class="perfil-mod-badge perfil-mod-badge--<?= $b['tipo'] ?>">
                  <?= $b['tipo'] === 'admin' ? 'Editor' : 'Lector' ?>
                </span>
              </div>
              <div class="perfil-mod-user">@<?= htmlspecialchars($b['usuario']) ?></div>
              <div class="perfil-mod-estado"><i class="bi bi-slash-circle"></i> <?= htmlspecialchars($estadoTxt) ?></div>
              <?php if(!empty($b['baneado_motivo'])): ?>
                <div class="perfil-mod-motivo"><i class="bi bi-chat-quote"></i> <?= htmlspecialchars($b['baneado_motivo']) ?></div>
              <?php endif; ?>
            </div>
            <div class="perfil-mod-acciones">
              <a href="<?= $perfilUrl ?>" class="perfil-mod-ver" title="Ver perfil"><i class="bi bi-box-arrow-up-right"></i> Ver</a>
              <button type="button" class="perfil-mod-unban"><i class="bi bi-check-circle"></i> Quitar suspensión</button>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Vista: Activos -->
      <div class="perfil-mod-view" id="viewActivos" style="display:none;">
        <div class="perfil-mod-search">
          <i class="bi bi-search"></i>
          <input type="text" id="modBuscarActivos" class="input" placeholder="Buscar por nombre o @usuario..." autocomplete="off">
        </div>
        <div class="perfil-mod-list" id="modListActivos">
          <div class="perfil-mod-empty" id="modActivosEmpty">
            <i class="bi bi-people"></i>
            <p>Cargando usuarios activos...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: Suspender usuario (desde la lista de activos) -->
  <div id="modSuspenderModal" class="perfil-modal-overlay" style="display:none;">
    <div class="perfil-modal perfil-modal--danger">
      <div class="perfil-modal-icon"><i class="bi bi-slash-circle"></i></div>
      <h3>Suspender a <span id="modSuspNombre"></span></h3>
      <p>El usuario no podrá comentar, dar me gusta ni reportar mientras dure la suspensión.</p>
      <label class="perfil-field-group" style="text-align:left; margin-bottom:12px;">
        <span style="font-size:.85rem; font-weight:600;">Duración</span>
        <select id="modSuspDuracion" class="input">
          <?php foreach(duracionesBaneo() as $key => $d): ?>
            <option value="<?= $key ?>"><?= htmlspecialchars($d['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <input type="text" id="modSuspMotivo" class="input" maxlength="255" placeholder="Motivo (opcional)" style="width:100%; margin-bottom:16px;">
      <div style="display:flex; gap:10px;">
        <button type="button" id="modSuspCancel" class="perfil-modal-btn-cancel">Cancelar</button>
        <button type="button" id="modSuspConfirm" class="perfil-modal-btn-confirm"><i class="bi bi-slash-circle"></i> Suspender</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /perfil-page -->

<!-- MODAL: Confirmar eliminación -->
<div id="modalEliminar" class="perfil-modal-overlay" style="display:none;">
  <div class="perfil-modal perfil-modal--danger">
    <div class="perfil-modal-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
    <h3>¿Eliminar tu cuenta?</h3>
    <p>Esta acción es permanente e irreversible. Escribe tu contraseña para confirmar.</p>
    <form id="formEliminar" action="<?= basePath() ?>/controllers/eliminar_cuenta.php" method="POST">
      <input type="password" name="pass_confirmar" id="passConfirmar" class="input" placeholder="Tu contraseña..." required
        style="width:100%; margin-bottom:16px;">
      <div style="display:flex; gap:10px;">
        <button type="button" id="cancelarEliminar" class="perfil-modal-btn-cancel">Cancelar</button>
        <button type="submit" class="perfil-modal-btn-confirm"><i class="bi bi-trash3-fill"></i> Eliminar cuenta</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Selector de avatar -->
<div id="avatarModal" class="perfil-modal-overlay" style="display:none;">
  <div class="perfil-modal">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <h3 style="margin:0; font-size:1.1rem;">Elige tu avatar</h3>
      <button id="closeAvatarModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">&times;</button>
    </div>
    <?php if($tipoUsuario === 'admin'): ?>
    <div style="margin-bottom:16px; padding:12px; background:rgba(239,51,99,0.08); border-radius:10px; border:1px solid var(--accent);">
      <label class="avatar-upload-btn" style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--accent); font-weight:600;">
        <i class="bi bi-cloud-arrow-up"></i> Subir foto personal
        <input type="file" id="modalFotoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
      </label>
      <div id="modalFotoPreview" style="display:none; margin-top:12px;">
        <div style="max-height:280px; overflow:hidden; border-radius:8px;">
          <img id="modalFotoImg" style="max-width:100%; display:block;">
        </div>
        <div style="margin-top:10px; display:flex; gap:8px;">
          <button type="button" id="confirmCropBtn" class="perfil-btn-save" style="flex:1; font-size:0.88rem; padding:9px;">Confirmar recorte</button>
          <button type="button" id="cancelCropBtn" style="flex:1; padding:9px 16px; border:1px solid var(--border); background:var(--bg); color:var(--text); border-radius:8px; cursor:pointer;">Cancelar</button>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
      <?php if(empty($avatares)): ?>
        <p style="grid-column:1/-1; color:var(--muted); text-align:center;">No hay avatares disponibles aún.</p>
      <?php endif; ?>
      <?php foreach($avatares as $av): ?>
        <div class="avatar-option <?= ($user['avatar_id'] ?? 0) == $av['id_avatar'] ? 'avatar-selected' : '' ?>" data-id="<?= $av['id_avatar'] ?>"
          style="cursor:pointer; border-radius:10px; overflow:hidden; border:3px solid transparent; transition:border-color 0.2s;">
          <img src="<?= imageUrl($av['imagen']) ?>" style="width:100%; height:90px; object-fit:cover; display:block;">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
/* ============================================================
   PERFIL — Diseño 2.0
   ============================================================ */

/* TOAST */
.perfil-toast {
  position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
  padding: 12px 24px; border-radius: 50px; font-size: .9rem; font-weight: 600;
  display: flex; align-items: center; gap: 8px;
  z-index: 10000; box-shadow: 0 4px 24px rgba(0,0,0,.18);
  animation: toastIn .3s ease;
}
@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(-10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.perfil-toast--ok  { background:#1a7f37; color:#fff; }
.perfil-toast--err { background:#cf222e; color:#fff; }

/* NOTIFICACIONES */
.perfil-notif {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 16px; padding: 20px 22px; margin-bottom: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,.06);
}
.perfil-notif-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
.perfil-notif-head h2 { font-size:1.15rem; margin:0; display:flex; align-items:center; gap:8px; color:var(--text); }
.perfil-notif-head h2 i { color: var(--accent); }
.perfil-notif-badge {
  background:var(--accent); color:#fff; font-size:.72rem; font-weight:700;
  min-width:20px; height:20px; padding:0 6px; border-radius:10px;
  display:inline-flex; align-items:center; justify-content:center;
}
.perfil-notif-marcar {
  background:transparent; border:1px solid var(--border); color:var(--muted);
  border-radius:8px; padding:6px 12px; font-size:.82rem; font-weight:600; cursor:pointer;
  display:inline-flex; align-items:center; gap:6px;
}
.perfil-notif-marcar:hover { border-color:var(--accent); color:var(--accent); }
.perfil-notif-lista { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
.perfil-notif-item {
  display:flex; gap:14px; padding:14px 16px; border-radius:12px;
  background:var(--bg); border:1px solid var(--border);
}
.perfil-notif-item.is-unread { border-color:var(--accent); background:rgba(239,51,99,0.05); }
.perfil-notif-item.notif-suspension .perfil-notif-icono { color:#ef3333; }
.perfil-notif-item.notif-reactivacion .perfil-notif-icono { color:#1a7f37; }
.perfil-notif-icono { font-size:1.4rem; color:var(--accent); flex-shrink:0; line-height:1.2; }
.perfil-notif-cuerpo { flex:1; min-width:0; }
.perfil-notif-titulo { font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.perfil-notif-dot { width:8px; height:8px; border-radius:50%; background:var(--accent); display:inline-block; }
.perfil-notif-msg { margin:4px 0 6px; color:var(--muted); font-size:.9rem; line-height:1.45; }
.perfil-notif-fecha { font-size:.78rem; color:var(--muted); display:inline-flex; align-items:center; gap:5px; }
.perfil-notif-empty { text-align:center; color:var(--muted); padding:36px 0; }
.perfil-notif-empty i { font-size:2rem; display:block; margin-bottom:8px; color:var(--muted); }
.perfil-notif-empty p { margin:0; font-size:.92rem; }

/* MIS COMENTARIOS */
.perfil-comentarios { padding:16px 20px 20px; display:flex; flex-direction:column; gap:12px; }
.perfil-comentario-card { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:14px 16px; }
.perfil-comentario-texto { margin:0 0 10px; color:var(--text); line-height:1.5; }
.perfil-comentario-meta { display:flex; gap:16px; flex-wrap:wrap; color:var(--muted); font-size:.82rem; margin-bottom:8px; }
.perfil-comentario-noticia { display:inline-flex; align-items:center; gap:4px; color:var(--accent); text-decoration:none; font-size:.88rem; font-weight:600; }
.perfil-comentario-noticia:hover { text-decoration:underline; }

/* HERO */
.perfil-hero {
  position: relative; overflow: hidden;
  padding: 60px 24px 80px;
  margin-bottom: -48px;
}
.perfil-hero-bg {
  position: absolute; inset: 0;
  background: var(--accent); /* fucsia sólido de la marca, sin degradado */
  opacity: 1;
}
.perfil-hero::after {
  content:''; position:absolute; bottom:0; left:0; right:0; height:60px;
  background: var(--bg);
  clip-path: ellipse(55% 100% at 50% 100%);
}
.perfil-hero-content {
  position: relative; z-index: 1;
  display: flex; align-items: center; gap: 28px;
  max-width: 800px; margin: 0 auto;
  flex-wrap: wrap;
}
.perfil-hero-info { flex: 1; min-width: 200px; }
.perfil-hero-nombre { font-size: 1.8rem; font-weight: 800; margin: 0 0 4px; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,.18); }
.perfil-hero-username { color: rgba(255,255,255,.85); font-size: .95rem; margin: 0 0 12px; }
.perfil-hero-badges { display: flex; flex-wrap: wrap; gap: 8px; }
.perfil-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 12px; border-radius: 50px; font-size: .78rem; font-weight: 600;
  background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35); color: #fff;
  -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
}
.perfil-badge--admin { background: #fff; border-color: #fff; color: var(--accent); }
.perfil-hero-logout {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 50px; font-size: .88rem; font-weight: 600;
  border: 1.5px solid rgba(255,255,255,.75); color: #fff; text-decoration: none;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.perfil-hero-logout:hover { background: #fff; color: var(--accent); }

/* AVATAR */
.perfil-avatar-wrap { position: relative; flex-shrink: 0; }
.perfil-avatar {
  width: 96px; height: 96px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), #7c1d5e);
  display: flex; align-items: center; justify-content: center;
  font-size: 2.4rem; font-weight: 800; color: #fff;
  overflow: hidden;
  border: 4px solid #fff;
  box-shadow: 0 6px 22px rgba(0,0,0,.22);
}
.perfil-avatar img { width: 100%; height: 100%; object-fit: cover; }
.perfil-camera {
  position: absolute; bottom: 2px; right: 2px;
  width: 30px; height: 30px; border-radius: 50%;
  background: var(--accent); color: #fff; border: 2px solid #fff;
  font-size: .85rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: transform .15s;
}
.perfil-camera:hover { transform: scale(1.12); }

/* PAGE & TABS */
.perfil-page {
  position: relative; z-index: 2; /* el hero tiene margin-bottom negativo + position:relative; sin esto tapa las pestañas */
  max-width: 760px; margin: 0 auto 60px;
  padding: 0 16px;
}
.perfil-tabs {
  display: flex; gap: 4px; flex-wrap: wrap;
  border-bottom: 2px solid var(--border);
  margin-bottom: 28px;
}
.perfil-tab {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; border-radius: 8px 8px 0 0;
  border: none; background: transparent;
  font-size: .88rem; font-weight: 600; cursor: pointer;
  color: var(--muted); transition: color .15s, background .15s;
  border-bottom: 2px solid transparent; margin-bottom: -2px;
}
.perfil-tab:hover { color: var(--text); }
.perfil-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
.perfil-tab--danger:hover { color: #ef3333; }
.perfil-tab--danger.active { color: #ef3333; border-bottom-color: #ef3333; }

/* PANELS */
.perfil-tab-panel { display: none; }
.perfil-tab-panel.active { display: block; }

/* CARD */
.perfil-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
}
.perfil-card--danger { border-color: rgba(239,51,51,.3); }
.perfil-card-header {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 22px 24px;
  border-bottom: 1px solid var(--border);
  font-size: 1.6rem; color: var(--accent);
}
.perfil-card--danger .perfil-card-header { border-color: rgba(239,51,51,.2); }
.perfil-card-header h2 { margin: 0 0 2px; font-size: 1.05rem; font-weight: 700; }
.perfil-card-header p  { margin: 0; font-size: .83rem; color: var(--muted); }
.perfil-card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end;
}

/* FIELDS */
.perfil-fields { padding: 20px 24px; display: flex; flex-direction: column; gap: 18px; }
.perfil-fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.perfil-field-group { display: flex; flex-direction: column; gap: 6px; }
.perfil-field-group label { font-size: .85rem; font-weight: 600; color: var(--text); }
.perfil-field-group small { font-size: .76rem; color: var(--muted); }
.perfil-input-prefix { position: relative; display: flex; align-items: center; }
.perfil-input-prefix .prefix {
  position: absolute; left: 12px; font-weight: 700; color: var(--muted); pointer-events: none;
}
.perfil-input-prefix .input { padding-left: 28px; }

/* Toggle pass */
.perfil-toggle-pass {
  display: flex; align-items: center; gap: 8px;
  padding: 11px 14px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--card-bg);
  color: var(--accent); font-weight: 600; font-size: .9rem; cursor: pointer;
  transition: background .15s;
}
.perfil-toggle-pass:hover { background: rgba(239,51,99,.07); }

/* Danger zone row */
.perfil-danger-row {
  display: flex; align-items: center; justify-content: space-between; gap: 24px;
  padding: 20px 24px; flex-wrap: wrap;
}
.perfil-danger-row > div > strong { display: block; margin-bottom: 4px; }
.perfil-danger-row > div > p { margin: 0; font-size: .85rem; color: var(--muted); }

/* Buttons */
.perfil-btn-save {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 22px; border-radius: 8px;
  background: var(--accent); color: #fff; border: none;
  font-size: .9rem; font-weight: 700; cursor: pointer;
  transition: opacity .15s, transform .12s;
}
.perfil-btn-save:hover { opacity: .9; transform: translateY(-1px); }
.perfil-btn-eliminar {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; border-radius: 8px;
  border: 1.5px solid #ef3333; background: transparent;
  color: #ef3333; font-weight: 700; font-size: .88rem; cursor: pointer;
  white-space: nowrap; transition: background .15s, color .15s;
}
.perfil-btn-eliminar:hover { background: #ef3333; color: #fff; }

/* Link público */
.perfil-link-publico {
  display: inline-flex; align-items: center; gap: 6px;
  color: var(--accent); font-size: .88rem; font-weight: 600; text-decoration: none;
}
.perfil-link-publico:hover { text-decoration: underline; }

/* MODAL */
.perfil-modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.65);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
}
.perfil-modal {
  background: var(--card-bg); border-radius: 20px;
  padding: 32px; max-width: 440px; width: 92%;
  box-shadow: 0 20px 60px rgba(0,0,0,.3);
  animation: modalIn .25s ease;
}
@keyframes modalIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }
.perfil-modal--danger { border: 1.5px solid rgba(239,51,51,.4); }
.perfil-modal-icon { font-size: 2.5rem; color: #ef3333; text-align: center; margin-bottom: 12px; }
.perfil-modal h3 { margin: 0 0 8px; font-size: 1.1rem; text-align: center; }
.perfil-modal p { color: var(--muted); font-size: .9rem; text-align: center; margin-bottom: 20px; }
.perfil-modal-btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  border: 1px solid var(--border); background: transparent; color: var(--text); cursor: pointer; font-size: .9rem;
}
.perfil-modal-btn-confirm {
  flex: 1; padding: 10px; border-radius: 8px;
  border: none; background: #ef3333; color: #fff; font-weight: 700; cursor: pointer; font-size: .9rem;
  display: flex; align-items: center; justify-content: center; gap: 7px;
}

/* AVATAR PICKER */
.avatar-option:hover { border-color: var(--accent) !important; }
.avatar-option.avatar-selected { border-color: var(--accent) !important; }

/* MODERACIÓN */
.perfil-mod-count {
  background:#ef3333; color:#fff; font-size:.7rem; font-weight:700;
  min-width:18px; height:18px; padding:0 5px; border-radius:9px;
  display:inline-flex; align-items:center; justify-content:center; margin-left:2px;
}
.perfil-mod-toggle {
  display:flex; gap:6px; padding:14px 16px 0; flex-wrap:wrap;
}
.perfil-mod-tgl {
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 16px; border-radius:50px; cursor:pointer;
  background:transparent; border:1px solid var(--border); color:var(--muted);
  font-size:.85rem; font-weight:600; transition:background .15s, color .15s, border-color .15s;
}
.perfil-mod-tgl:hover { border-color:var(--accent); color:var(--accent); }
.perfil-mod-tgl.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.perfil-mod-tgl.active .perfil-mod-count { background:#fff; color:var(--accent); }
.perfil-mod-search {
  display:flex; align-items:center; gap:8px; padding:14px 16px 4px;
  position:relative;
}
.perfil-mod-search i { position:absolute; left:28px; color:var(--muted); pointer-events:none; }
.perfil-mod-search .input { padding-left:34px; }
.perfil-mod-list { padding: 12px 16px; display:flex; flex-direction:column; gap:10px; }
.perfil-mod-empty { text-align:center; color:var(--muted); padding:32px 0; }
.perfil-mod-empty i { font-size:2rem; color:#1a7f37; display:block; margin-bottom:8px; }
.perfil-mod-empty p { margin:0; font-size:.92rem; }
.perfil-mod-row {
  display:flex; align-items:center; gap:14px;
  padding:14px 16px; border-radius:12px;
  background:var(--bg); border:1px solid var(--border);
}
.perfil-mod-avatar {
  width:44px; height:44px; border-radius:50%; flex-shrink:0;
  background:linear-gradient(135deg, var(--accent), #7c1d5e);
  color:#fff; font-weight:700; font-size:.95rem;
  display:flex; align-items:center; justify-content:center;
}
.perfil-mod-info { flex:1; min-width:0; }
.perfil-mod-nombre { font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.perfil-mod-badge { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:50px; text-transform:uppercase; letter-spacing:.3px; }
.perfil-mod-badge--admin  { background:rgba(239,51,99,.12); color:var(--accent); }
.perfil-mod-badge--lector { background:rgba(120,120,120,.15); color:var(--muted); }
.perfil-mod-user { font-size:.82rem; color:var(--muted); margin-top:1px; }
.perfil-mod-estado { font-size:.82rem; color:#ef3333; font-weight:600; margin-top:5px; display:flex; align-items:center; gap:5px; }
.perfil-mod-motivo { font-size:.8rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:5px; }
.perfil-mod-acciones { display:flex; flex-direction:column; gap:7px; flex-shrink:0; align-items:stretch; }
.perfil-mod-ver {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; color:var(--muted); text-decoration:none;
  border:1px solid var(--border); border-radius:8px; padding:6px 12px;
}
.perfil-mod-ver:hover { border-color:var(--accent); color:var(--accent); }
.perfil-mod-unban {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; cursor:pointer;
  background:transparent; color:#1a7f37; border:1px solid #1a7f37;
  border-radius:8px; padding:6px 12px; white-space:nowrap;
}
.perfil-mod-unban:hover { background:#1a7f37; color:#fff; }
.perfil-mod-unban:disabled { opacity:.6; cursor:not-allowed; }
.perfil-mod-suspend {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; cursor:pointer;
  background:transparent; color:#ef3333; border:1px solid #ef3333;
  border-radius:8px; padding:6px 12px; white-space:nowrap;
}
.perfil-mod-suspend:hover { background:#ef3333; color:#fff; }
.perfil-mod-suspend:disabled { opacity:.6; cursor:not-allowed; }
@media (max-width: 600px) {
  .perfil-mod-row { flex-wrap:wrap; }
  .perfil-mod-acciones { flex-direction:row; width:100%; }
  .perfil-mod-acciones > * { flex:1; }
}

/* Responsive */
@media (max-width: 600px) {
  .perfil-hero-content { flex-direction: column; text-align: center; }
  .perfil-hero-badges { justify-content: center; }
  .perfil-fields-row { grid-template-columns: 1fr; }
  .perfil-danger-row { flex-direction: column; align-items: flex-start; }
  .perfil-tabs { gap: 2px; }
  .perfil-tab { padding: 8px 12px; font-size: .8rem; }
}
</style>

<script>
// Inicializa la página de perfil. Se ejecuta en carga directa y en cada
// navegación Turbo (turbo:load). La guarda por body evita doble enlace.
function initPerfil(){
  if (!document.body || document.body.dataset.perfilInit === '1') return;
  document.body.dataset.perfilInit = '1';

  // ---- FECHA DE NACIMIENTO DROPDOWNS ----
  (() => {
    const diaSel = document.getElementById('dob_dia');
    const mesSel = document.getElementById('dob_mes');
    const anioSel = document.getElementById('dob_anio');
    const hiddenInput = document.getElementById('nacimiento');
    if (!diaSel || !mesSel || !anioSel || !hiddenInput) return;

    // Clear options to avoid duplicates on Turbo loads
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

    // Set default value if exists
    const initialDate = hiddenInput.value;
    if (initialDate) {
      const parts = initialDate.split('-');
      if (parts.length === 3) {
        anioSel.value = parts[0];
        mesSel.value = parts[1];
        diaSel.value = parts[2];
      }
    }
  })();

// ---- TABS ----
document.querySelectorAll('.perfil-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.perfil-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.perfil-tab-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    const panel = document.getElementById('tab-' + tab.dataset.tab);
    if (panel) panel.classList.add('active');
  });
});

// ---- TOGGLE CONTRASEÑA ----
document.getElementById('togglePass').addEventListener('click', function(){
  const fields = document.getElementById('passFields');
  const icon   = document.getElementById('togglePassIcon');
  const open   = fields.style.display === 'none';
  fields.style.display = open ? 'block' : 'none';
  icon.style.transform  = open ? 'rotate(180deg)' : 'rotate(0deg)';
  if (!open) {
    document.getElementById('pass_actual').value = '';
    document.getElementById('pass_nueva').value  = '';
  }
});

// ---- MODAL ELIMINAR ----
const btnEliminar    = document.getElementById('btnEliminarCuenta');
const modalEliminar  = document.getElementById('modalEliminar');
const cancelarEliminar = document.getElementById('cancelarEliminar');
if (btnEliminar && modalEliminar) {
  btnEliminar.addEventListener('click', () => { modalEliminar.style.display = 'flex'; });
  cancelarEliminar.addEventListener('click', () => { modalEliminar.style.display = 'none'; });
  modalEliminar.addEventListener('click', e => { if (e.target === modalEliminar) modalEliminar.style.display = 'none'; });
}

// ---- MODAL AVATAR ----
const modalAv = document.getElementById('avatarModal');
document.getElementById('openAvatarPicker').addEventListener('click', () => { modalAv.style.display = 'flex'; });
document.getElementById('closeAvatarModal').addEventListener('click', () => { modalAv.style.display = 'none'; });
modalAv.addEventListener('click', e => { if (e.target === modalAv) modalAv.style.display = 'none'; });

// ---- SELECCIONAR AVATAR ----
document.querySelectorAll('.avatar-option').forEach(el => {
  el.addEventListener('click', async function(){
    const res  = await fetch('<?= basePath() ?>/controllers/avatar_seleccionar.php', {
      method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `avatar_id=${this.dataset.id}`
    });
    const data = await res.json();
    if (data.ok) location.reload();
  });
});

// ---- TOAST AUTO-HIDE ----
const toast = document.querySelector('.perfil-toast');
if (toast) setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .5s'; setTimeout(() => toast.remove(), 500); }, 4000);

// ---- UPLOAD FOTO PERSONAL (admin) ----
(() => {
  const modalInput  = document.getElementById('modalFotoInput');
  const modalPreview = document.getElementById('modalFotoPreview');
  const modalImg    = document.getElementById('modalFotoImg');
  const confirmBtn  = document.getElementById('confirmCropBtn');
  const cancelBtn   = document.getElementById('cancelCropBtn');
  if (!modalInput) return;
  let cropper = null;

  modalInput.addEventListener('change', () => {
    if (!modalInput.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      modalImg.src = e.target.result;
      modalPreview.style.display = 'block';
      if (cropper) cropper.destroy();
      cropper = new Cropper(modalImg, { aspectRatio:1, viewMode:1, dragMode:'none', movable:false, zoomable:false, autoCropArea:0.8 });
    };
    reader.readAsDataURL(modalInput.files[0]);
  });

  confirmBtn && confirmBtn.addEventListener('click', async () => {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width:500, height:500 });
    canvas.toBlob(async blob => {
      const fd = new FormData();
      fd.append('foto_personal', new File([blob], 'photo.webp', {type:'image/webp'}));
      const res  = await fetch('<?= basePath() ?>/controllers/subir_foto_personal.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.ok) {
        const pa = document.getElementById('perfilAvatar');
        if (pa) pa.innerHTML = `<img src="<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(data.imagen)}" alt="">`;
        modalAv.style.display = 'none';
        cropper.destroy(); cropper = null;
        modalPreview.style.display = 'none';
      } else { alert('Error: ' + (data.error || 'desconocido')); }
    }, 'image/webp', 0.92);
  });

  cancelBtn && cancelBtn.addEventListener('click', () => {
    if (cropper) { cropper.destroy(); cropper = null; }
    modalPreview.style.display = 'none';
    modalInput.value = '';
  });
})();

} // fin initPerfil

// Disparar en navegación Turbo y en carga directa (sin depender del parseo).
document.addEventListener('turbo:load', initPerfil);
if (document.readyState !== 'loading') initPerfil();
else document.addEventListener('DOMContentLoaded', initPerfil);
</script>
<script>
// Marcar notificaciones como leídas
(() => {
  const btn = document.getElementById('btnMarcarNotif');
  if (!btn) return;
  const base = document.getElementById('perfilNotif').dataset.base;
  btn.addEventListener('click', async () => {
    btn.disabled = true;
    try {
      const res = await fetch(base + '/controllers/notificaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=marcar_leidas'
      });
      const data = await res.json();
      if (data.ok) {
        document.querySelectorAll('.perfil-notif-item.is-unread').forEach(el => el.classList.remove('is-unread'));
        document.querySelectorAll('.perfil-notif-dot').forEach(el => el.remove());
        const badge = document.querySelector('.perfil-notif-badge');
        if (badge) badge.remove();
        btn.remove();
      }
    } catch (e) { btn.disabled = false; }
  });
})();
</script>
<script>
// ============================================================
// Panel de Baneos (solo superadmin): suspendidos + activos
// ============================================================
(() => {
  const card = document.getElementById('modCard');
  if (!card) return;
  const base = card.dataset.base;
  const post = (body) => fetch(base + '/controllers/moderar_usuario.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body
  }).then(r => r.json());

  const initials = (nombre) => {
    const p = (nombre || '').trim().split(/\s+/);
    return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?';
  };
  const esc = (s) => (s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  // ---- Contador de suspendidos (pestaña + toggle) ----
  const susList = document.getElementById('modList');
  function refreshSusCount() {
    const n = susList.querySelectorAll('.perfil-mod-row').length;
    document.querySelectorAll('.perfil-mod-count').forEach(el => {
      el.textContent = n;
      el.style.display = n > 0 ? '' : 'none';
    });
    if (n === 0 && !document.getElementById('modEmpty')) {
      susList.innerHTML = '<div class="perfil-mod-empty" id="modEmpty"><i class="bi bi-check-circle"></i><p>No hay usuarios suspendidos en este momento.</p></div>';
    }
  }

  // ---- Toggle de vistas ----
  const viewSus = document.getElementById('viewSuspendidos');
  const viewAct = document.getElementById('viewActivos');
  let activosCargados = false;
  document.querySelectorAll('.perfil-mod-tgl').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.perfil-mod-tgl').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const activos = btn.dataset.view === 'activos';
      viewSus.style.display = activos ? 'none' : '';
      viewAct.style.display = activos ? '' : 'none';
      if (activos && !activosCargados) { activosCargados = true; cargarActivos(''); }
    });
  });

  // ---- Quitar suspensión (vista suspendidos) ----
  susList.addEventListener('click', async (e) => {
    const btn = e.target.closest('.perfil-mod-unban');
    if (!btn) return;
    const row = btn.closest('.perfil-mod-row');
    if (!confirm('¿Quitar la suspensión a este usuario?')) return;
    btn.disabled = true;
    try {
      const data = await post(`action=unban&tipo=${row.dataset.tipo}&user_id=${row.dataset.userid}`);
      if (data.ok) {
        row.style.transition = 'opacity .25s';
        row.style.opacity = '0';
        setTimeout(() => { row.remove(); refreshSusCount(); }, 250);
      } else { alert(data.msg || 'No se pudo quitar la suspensión.'); btn.disabled = false; }
    } catch (err) { alert('Error de red.'); btn.disabled = false; }
  });

  // ---- Lista de usuarios activos (búsqueda server-side) ----
  const actList = document.getElementById('modListActivos');
  const inputBuscar = document.getElementById('modBuscarActivos');
  let buscarTimer = null, buscarToken = 0;

  async function cargarActivos(q) {
    const token = ++buscarToken;
    actList.innerHTML = '<div class="perfil-mod-empty"><i class="bi bi-hourglass-split"></i><p>Buscando...</p></div>';
    try {
      const data = await post(`action=listar_activos&q=${encodeURIComponent(q)}`);
      if (token !== buscarToken) return; // llegó una búsqueda más nueva
      if (!data.ok || !Array.isArray(data.usuarios) || data.usuarios.length === 0) {
        actList.innerHTML = '<div class="perfil-mod-empty"><i class="bi bi-people"></i><p>' +
          (q ? 'Sin resultados para tu búsqueda.' : 'No hay usuarios activos.') + '</p></div>';
        return;
      }
      actList.innerHTML = data.usuarios.map(u => {
        const perfilUrl = base + (u.tipo === 'admin' ? '/autor/' : '/usuario/') + u.id;
        const badge = u.tipo === 'admin' ? 'Editor' : 'Lector';
        return `<div class="perfil-mod-row" data-tipo="${u.tipo}" data-userid="${u.id}" data-nombre="${esc(u.nombre)}">
          <div class="perfil-mod-avatar">${esc(initials(u.nombre))}</div>
          <div class="perfil-mod-info">
            <div class="perfil-mod-nombre">${esc(u.nombre)}
              <span class="perfil-mod-badge perfil-mod-badge--${u.tipo}">${badge}</span>
            </div>
            <div class="perfil-mod-user">@${esc(u.usuario)}</div>
          </div>
          <div class="perfil-mod-acciones">
            <a href="${perfilUrl}" class="perfil-mod-ver" title="Ver perfil"><i class="bi bi-box-arrow-up-right"></i> Ver</a>
            <button type="button" class="perfil-mod-suspend"><i class="bi bi-slash-circle"></i> Suspender</button>
          </div>
        </div>`;
      }).join('');
    } catch (err) {
      if (token !== buscarToken) return;
      actList.innerHTML = '<div class="perfil-mod-empty"><i class="bi bi-wifi-off"></i><p>Error de red.</p></div>';
    }
  }

  inputBuscar && inputBuscar.addEventListener('input', () => {
    clearTimeout(buscarTimer);
    buscarTimer = setTimeout(() => cargarActivos(inputBuscar.value.trim()), 300);
  });

  // ---- Modal Suspender ----
  const modal = document.getElementById('modSuspenderModal');
  const modalNombre = document.getElementById('modSuspNombre');
  const selDur = document.getElementById('modSuspDuracion');
  const inMotivo = document.getElementById('modSuspMotivo');
  const btnConfirm = document.getElementById('modSuspConfirm');
  const btnCancel = document.getElementById('modSuspCancel');
  let objetivo = null; // {tipo, userId, row}

  function abrirModal(tipo, userId, nombre, row) {
    objetivo = { tipo, userId, row };
    modalNombre.textContent = nombre;
    selDur.selectedIndex = 0;
    inMotivo.value = '';
    modal.style.display = 'flex';
  }
  function cerrarModal() { modal.style.display = 'none'; objetivo = null; }

  actList.addEventListener('click', (e) => {
    const btn = e.target.closest('.perfil-mod-suspend');
    if (!btn) return;
    const row = btn.closest('.perfil-mod-row');
    abrirModal(row.dataset.tipo, row.dataset.userid, row.dataset.nombre || 'este usuario', row);
  });

  btnCancel.addEventListener('click', cerrarModal);
  modal.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });

  btnConfirm.addEventListener('click', async () => {
    if (!objetivo) return;
    btnConfirm.disabled = true;
    try {
      const body = `action=ban&tipo=${objetivo.tipo}&user_id=${objetivo.userId}` +
        `&duracion=${encodeURIComponent(selDur.value)}&motivo=${encodeURIComponent(inMotivo.value)}`;
      const data = await post(body);
      if (data.ok) {
        const { tipo, userId, row } = objetivo;
        const nombre  = (row && row.dataset.nombre) || modalNombre.textContent;
        const usuario = (row && row.querySelector('.perfil-mod-user')?.textContent.replace(/^@/, '')) || '';
        const durTxt  = selDur.options[selDur.selectedIndex].text;
        const estadoTxt = data.estado || (selDur.value === 'perm'
          ? 'Suspendido permanentemente' : 'Suspendido (' + durTxt + ')');
        const motivo  = inMotivo.value.trim();

        // Quitar de la lista de activos
        if (row) {
          row.style.transition = 'opacity .25s';
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 250);
        }
        // Inyectar la fila en la lista de suspendidos (ambas vistas quedan consistentes)
        const empty = document.getElementById('modEmpty');
        if (empty) empty.remove();
        const perfilUrl = base + (tipo === 'admin' ? '/autor/' : '/usuario/') + userId;
        const badge = tipo === 'admin' ? 'Editor' : 'Lector';
        susList.insertAdjacentHTML('afterbegin',
          `<div class="perfil-mod-row" data-tipo="${tipo}" data-userid="${userId}">
            <div class="perfil-mod-avatar">${esc(initials(nombre))}</div>
            <div class="perfil-mod-info">
              <div class="perfil-mod-nombre">${esc(nombre)} <span class="perfil-mod-badge perfil-mod-badge--${tipo}">${badge}</span></div>
              <div class="perfil-mod-user">@${esc(usuario)}</div>
              <div class="perfil-mod-estado"><i class="bi bi-slash-circle"></i> ${esc(estadoTxt)}</div>
              ${motivo ? `<div class="perfil-mod-motivo"><i class="bi bi-chat-quote"></i> ${esc(motivo)}</div>` : ''}
            </div>
            <div class="perfil-mod-acciones">
              <a href="${perfilUrl}" class="perfil-mod-ver" title="Ver perfil"><i class="bi bi-box-arrow-up-right"></i> Ver</a>
              <button type="button" class="perfil-mod-unban"><i class="bi bi-check-circle"></i> Quitar suspensión</button>
            </div>
          </div>`);
        refreshSusCount();
        cerrarModal();
        alert(data.msg || 'Usuario suspendido.');
      } else {
        alert(data.msg || 'No se pudo suspender al usuario.');
      }
    } catch (err) { alert('Error de red.'); }
    btnConfirm.disabled = false;
  });
})();
</script>
<?php include("./../layout/footer.php"); ?>
