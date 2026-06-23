<?php
include("./../layout/headerAdmin.php");
include("./../controllers/aclcontroller.php");
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear"    => false,
    "leer"     => false,
    "editar"   => false,
    "eliminar" => false
];
proteger('noticias','editar');
include("./../data/conexion.php");
if (empty($ACL['editar'])) { header("Location: admin.php"); exit(); }
if (!isset($_GET['id']))    { header("Location: contenidos.php"); exit; }
$id = intval($_GET['id']);

$stmt = $con->prepare("SELECT id, titulo, descripcion, contenido, fecha_publicacion, crop1, crop2, crop3, crop4, creado_por, editado_por, ultima_edicion, tipo_publicacion, calificacion, pros, contras, es_estreno, seccion_estreno FROM noticias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc();
if (!$noticia) { header("Location: contenidos.php"); exit; }

// Obtener información de quién creó y editó
$creadoPor = null;
$editadoPor = null;
if ($noticia['creado_por']) {
    $stmtCreador = $con->prepare("SELECT nombre FROM usuarios WHERE id_u = ?");
    $stmtCreador->bind_param("i", $noticia['creado_por']);
    $stmtCreador->execute();
    $creadoPor = $stmtCreador->get_result()->fetch_assoc();
}
if ($noticia['editado_por']) {
    $stmtEditor = $con->prepare("SELECT nombre FROM usuarios WHERE id_u = ?");
    $stmtEditor->bind_param("i", $noticia['editado_por']);
    $stmtEditor->execute();
    $editadoPor = $stmtEditor->get_result()->fetch_assoc();
}

$categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
$categorias = [];
while ($row = $categoriasResult->fetch_assoc()) $categorias[] = $row;

$stmtCat = $con->prepare("
    SELECT nc.categoria_id AS id, c.nombre
    FROM noticia_categoria nc
    JOIN categorias c ON c.id_c = nc.categoria_id
    WHERE nc.noticia_id = ?
    ORDER BY nc.orden ASC
");
$stmtCat->bind_param("i", $id);
$stmtCat->execute();
$categoriasSeleccionadas = [];
$resCat = $stmtCat->get_result();
while ($row = $resCat->fetch_assoc()) $categoriasSeleccionadas[] = $row;

// Fecha actual de publicación para pre-cargar el programador
$fechaExistente = date('Y-m-d\TH:i', strtotime($noticia['fecha_publicacion']));

// URLs de imágenes usando imageUrl() para servir correctamente
$crop1Url = !empty($noticia['crop1']) ? imageUrl($noticia['crop1']) : '';
$crop2Url = imageUrl($noticia['crop2'] ?? '');
$crop3Url = imageUrl($noticia['crop3'] ?? '');
$crop4Url = imageUrl($noticia['crop4'] ?? '');
?>
<script>const ACL = <?= json_encode($ACL) ?>;</script>

<style>
/* ── LAYOUT ── */
.cn-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--muted); margin-bottom: 16px;
}
.cn-breadcrumb a { color: var(--muted); text-decoration: none; }
.cn-breadcrumb a:hover { color: var(--accent); }
.cn-breadcrumb span { color: var(--accent); }

.cn-page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 20px; color: var(--text); }

/* Eliminar el límite de admin-container en esta vista */
.admin-container { max-width: none !important; padding: 0 !important; }

/* Grid principal: izq = formulario (2fr), der = multimedia (3fr) */
.cn-wrap {
  width: 100%; margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
  gap: 16px; align-items: start;
}
/* Columna izquierda: flex para que las secciones se apilen sin huecos */
.cn-left-col { display: flex; flex-direction: column; gap: 16px; }
/* Full-width: Contenido */
#sec-content { grid-column: 1 / -1; }
@media (max-width: 860px) {
  .cn-wrap { grid-template-columns: 1fr; }
  .cn-left-col, #sec-media, #sec-content { grid-column: 1; }
}

/* ── SECTION CARD ── */
.cn-section {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden; transition: box-shadow .2s;
}
.cn-section-header {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; cursor: pointer; user-select: none;
  border-bottom: 1px solid var(--border); transition: background .15s;
}
.cn-section-header:hover { background: rgba(239,51,99,.04); }
.cn-section-icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: rgba(239,51,99,0.12); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.cn-section-title { font-size: 0.92rem; font-weight: 700; color: var(--text); margin: 0; }
.cn-section-sub   { font-size: 11px; color: var(--muted); margin: 0; }
.cn-section-toggle {
  margin-left: auto; color: var(--muted);
  font-size: 13px; flex-shrink: 0; transition: transform .25s;
}
.cn-section.collapsed .cn-section-toggle { transform: rotate(-90deg); }
.cn-section-body {
  overflow: hidden; max-height: 60000px; opacity: 1;
  transition: max-height .35s ease, opacity .25s ease;
  padding: 18px;
}
.cn-section.collapsed .cn-section-body {
  max-height: 0; opacity: 0; padding-top: 0; padding-bottom: 0;
}

/* ── FORM FIELDS ── */
.cn-field { margin-bottom: 14px; }
.cn-field:last-child { margin-bottom: 0; }
.cn-field label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
.cn-hint { font-size: 11px; color: var(--muted); text-align: right; margin-top: 3px; }
.cn-input {
  width: 100%; padding: 9px 12px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--bg); color: var(--text);
  font-size: 14px; transition: border-color .2s; font-family: inherit;
}
.cn-input:focus { outline: none; border-color: var(--accent); }
textarea.cn-input { resize: vertical; min-height: 80px; }

/* ── CATEGORÍAS ── */
.cn-cat-dropdown { position: relative; }
.cn-cat-trigger {
  width: 100%; padding: 9px 12px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--bg); color: var(--text);
  font-size: 14px; cursor: pointer; display: flex; align-items: center;
  justify-content: space-between; user-select: none; transition: border-color .2s;
}
.cn-cat-trigger:hover { border-color: var(--accent); }
.cn-cat-trigger i { color: var(--muted); transition: transform .2s; }
.cn-cat-trigger.open i { transform: rotate(180deg); }
.cn-cat-menu {
  display: none; background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.15);
  padding: 6px; margin-top: 6px;
}
.cn-cat-menu.open { display: block; }
.cn-cat-option {
  display: flex; align-items: center; gap: 8px; padding: 7px 10px;
  border-radius: 7px; cursor: pointer; font-size: 13px; color: var(--text);
  transition: background .15s;
}
.cn-cat-option:hover { background: rgba(239,51,99,.08); }
.cn-cat-option input[type="checkbox"] { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
.cn-cat-option.selected { font-weight: 600; color: var(--accent); }
.cn-cat-chips { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
.cn-chip {
  display: flex; align-items: center; gap: 8px; padding: 7px 10px;
  background: rgba(239,51,99,.1); border: 1px solid rgba(239,51,99,.25);
  border-radius: 8px; font-size: 13px; font-weight: 600;
  color: var(--text); cursor: grab; user-select: none; transition: box-shadow .15s;
}
.cn-chip:active { cursor: grabbing; box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.cn-chip.drag-over { border: 2px dashed var(--accent); background: rgba(239,51,99,.2); }
.cn-chip-num {
  width: 20px; height: 20px; border-radius: 50%; background: var(--accent);
  color: #fff; font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cn-chip-name { flex: 1; }
.cn-chip-remove { background: none; border: none; color: var(--muted); cursor: pointer; padding: 0; font-size: 14px; line-height: 1; }
.cn-chip-remove:hover { color: var(--accent); }
.cn-chip-drag { color: var(--muted); font-size: 14px; }
.cn-cat-empty { font-size: 12px; color: var(--muted); text-align: center; padding: 6px 0; }

/* ── UPLOAD ZONES ── */
.cn-zone-label {
  font-size: 11px; font-weight: 600; color: var(--muted);
  text-align: center; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em;
}
.upload-zone {
  border: 2px dashed var(--border); border-radius: 10px; background: var(--bg);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 5px; cursor: pointer; position: relative; overflow: hidden;
  color: var(--muted); font-size: 0.82rem; text-align: center;
  padding: 12px; transition: border-color .2s, background .2s;
}
.upload-zone:hover { border-color: var(--accent); background: rgba(239,51,99,.04); }
.upload-zone.has-image { border-style: solid; border-color: var(--accent); }
.upload-zone img.preview-img {
  position: absolute; inset: 0; width: 100%; height: 100%;
  object-fit: cover; border-radius: 8px; pointer-events: none;
}
.upload-zone .zone-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,.5);
  display: flex; align-items: center; justify-content: center;
  color: #fff; opacity: 0; transition: opacity .2s; border-radius: 8px;
  font-size: 0.8rem; font-weight: 600; pointer-events: none;
}
.upload-zone.has-image:hover .zone-overlay { opacity: 1; }
.zone-ratio { font-size: 0.68rem; background: var(--border); border-radius: 4px; padding: 1px 6px; color: var(--muted); }
.cn-zone-icon { font-size: 22px; }
.upload-zone.has-image .cn-zone-icon,
.upload-zone.has-image > :not(.preview-img):not(.zone-overlay):not(.zone-actions) { display: none; }
.cn-zone-banner { aspect-ratio: 21/6; height: auto; min-height: 60px; }
.cn-zone-paisaje { aspect-ratio: 21/6; height: auto; max-width: min(100%, 520px); margin: 0 auto; }
.cn-zone-mini { aspect-ratio: 16/9; height: auto; max-width: min(100%, 520px); margin: 0 auto; }
.zone-actions { position: absolute; bottom: 6px; right: 6px; display: none; gap: 5px; z-index: 3; }
.upload-zone.has-image .zone-actions { display: flex; }
.zone-btn { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 5px; border: none; cursor: pointer; line-height: 1.5; backdrop-filter: blur(4px); }
.zone-btn-adjust { background: rgba(255,255,255,.18); color: #fff; }
.zone-btn-adjust:hover { background: rgba(255,255,255,.3); }
.zone-btn-remove { background: rgba(239,51,99,.75); color: #fff; }
.zone-btn-remove:hover { background: rgba(239,51,99,1); }

/* ── CROP MODAL ── */
.crop-modal-overlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.78);
  z-index: 2000; align-items: center; justify-content: center;
}
.crop-modal-overlay.open { display: flex; }
.crop-modal-box {
  background: var(--card-bg); border-radius: 14px;
  width: min(92vw, 700px); overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.crop-modal-head {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; border-bottom: 1px solid var(--border); font-weight: 700;
}
.crop-modal-head button { margin-left: auto; background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.2rem; }
.crop-modal-head button:hover { color: var(--accent); }
.crop-modal-body { padding: 16px; }
.crop-area { width: 100%; overflow: hidden; border-radius: 8px; background: var(--bg); }
.crop-area img { max-width: 100%; display: block; }
.crop-modal-foot { display: flex; gap: 10px; padding: 14px 18px; border-top: 1px solid var(--border); justify-content: flex-end; }

/* ── VISTA PREVIA ── */
.preview-section { margin-top: 14px; }
.preview-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.05em; color: var(--muted); margin-bottom: 10px; }
.pv-tabs { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 12px; }
.pv-tab-btn {
  padding: 4px 11px; font-size: 11px; font-weight: 600; border-radius: 20px;
  border: 1.5px solid var(--border); background: transparent; color: var(--muted);
  cursor: pointer; transition: all .15s; font-family: inherit;
}
.pv-tab-btn.active { background: var(--accent); color:#fff; border-color: var(--accent); }
.pv-panel { display:none; }
.pv-panel.active { display:block; }
.pv-bg { width:100%; height:100%; background-size:cover; background-position:center; background-color: var(--border); }
.pv-card { position:relative; border-radius:8px; overflow:hidden; background:var(--border); }
.pv-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.88) 0%, rgba(0,0,0,.35) 45%, transparent 75%); display:flex; flex-direction:column; justify-content:flex-end; padding:8px; color:#fff; }
.pv-tag { display:inline-block; background:var(--accent); color:#fff; font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:2px 5px; border-radius:3px; margin-bottom:3px; align-self:flex-start; }
.pv-title-txt { font-size:10px; font-weight:700; line-height:1.3; }
.pv-desc-txt  { font-size:8px; color:rgba(255,255,255,.8); margin-top:2px; }
.pv-label { font-size:9px; color:var(--muted); text-align:center; margin-top:3px; }
.pv-hero { aspect-ratio:21/6; border-radius:8px; overflow:hidden; position:relative; }
.pv-hero-overlay { position:absolute; inset:0; background:linear-gradient(to right, rgba(0,0,0,.72) 0%, rgba(0,0,0,.25) 55%, transparent 100%); display:flex; flex-direction:column; justify-content:center; padding:14px 18px; }
.pv-hero-overlay .pv-title-txt { font-size:14px; max-width:55%; }
.pv-hero-overlay .pv-desc-txt  { font-size:10px; max-width:50%; margin-top:4px; color:rgba(255,255,255,.85); }
.pv-top-grid { display:grid; grid-template-columns:2fr 1fr; gap:6px; }
.pv-top-main { aspect-ratio:21/6; }
.pv-top-side { aspect-ratio:16/9; }
.pv-rec-item { display:grid; grid-template-columns:220px 1fr; gap:16px; align-items:start; }
.pv-rec-thumb { aspect-ratio:16/9; border-radius:8px; overflow:hidden; }
.pv-rec-info { display:flex; flex-direction:column; gap:5px; padding-top:4px; }
.pv-rec-title { font-size:15px; font-weight:700; color:var(--text); line-height:1.3; }
.pv-rec-desc  { font-size:12px; color:var(--muted); }
.pv-rec-meta  { font-size:11px; color:var(--muted); margin-top:2px; }
.pv-side-cols { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.pv-side-head { font-size:11px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
.pv-side-list { display:flex; flex-direction:column; gap:12px; }
.pv-side-item { display:grid; grid-template-columns:72px 1fr; gap:12px; align-items:center; }
.pv-side-circle { width:72px; height:72px; border-radius:50%; overflow:hidden; background:var(--border); }
.pv-side-name { font-size:13px; font-weight:600; color:var(--text); line-height:1.3; }

/* ── EDITOR QUILL ── */
.cn-editor-wrap { border: 1px solid var(--border); border-radius: 10px; overflow: visible; }
.cn-editor-wrap .editor-toolbar { border-radius: 10px 10px 0 0 !important; border-left: none !important; border-right: none !important; border-top: none !important; margin-bottom: 0 !important; }
.cn-editor-wrap .editor-toolbar.tb-pinned { position: fixed !important; top: 0; z-index: 500; border-radius: 0 !important; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
.cn-editor-wrap .editor-content { min-height: 80px; border-top: 1px solid var(--border); }
.cn-editor-wrap .ql-editor { min-height: 60px; }

/* ── PROGRAMAR ── */
.cn-schedule-inner { display: flex; flex-direction: column; gap: 14px; }
.cn-schedule-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.cn-schedule-info h4 { font-size: 14px; font-weight: 700; color: var(--text); margin: 0 0 2px; }
.cn-schedule-info p  { font-size: 12px; color: var(--muted); margin: 0; }
.cn-toggle-wrap { display: flex; align-items: center; }
.cn-toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
.cn-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.cn-toggle-track { position: absolute; inset: 0; border-radius: 24px; background: var(--border); cursor: pointer; transition: background .2s; }
.cn-toggle input:checked + .cn-toggle-track { background: var(--accent); }
.cn-toggle-thumb { position: absolute; width: 18px; height: 18px; background: #fff; border-radius: 50%; top: 3px; left: 3px; transition: left .2s; pointer-events: none; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.cn-toggle input:checked ~ .cn-toggle-thumb { left: 23px; }
.cn-schedule-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cn-date-input { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 13px; }
.cn-date-input i { color: var(--muted); font-size: 15px; }
.cn-date-input input[type="date"],
.cn-date-input input[type="time"] { border: none; background: none; color: var(--text); font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit; }


/* ── BOTÓN GUARDAR ── */
.cn-publish-btn {
  width: 100%; padding: 13px; background: var(--accent); color: #fff;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; transition: background .2s, transform .15s; font-family: inherit;
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }
.cn-publish-btn:active { transform: translateY(0); }

/* ── BOTÓN ELIMINAR ── */
.cn-delete-btn {
  width: 100%; padding: 12px; background: transparent; color: #e53e3e;
  border: 2px solid #e53e3e; border-radius: 10px; font-size: 15px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; transition: background .2s, color .2s, transform .15s; font-family: inherit;
  margin-top: 10px;
}
.cn-delete-btn:hover { background: #e53e3e; color: #fff; transform: translateY(-2px); }
.cn-delete-btn:active { transform: translateY(0); }

/* ── MODAL ELIMINAR ── */
.del-modal-overlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6);
  z-index: 9999; align-items: center; justify-content: center;
}
.del-modal-overlay.open { display: flex; }
.del-modal-box {
  background: var(--card-bg, #1a1a2e); border-radius: 14px; padding: 28px 28px 22px;
  max-width: 400px; width: 90%; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,.4);
}
.del-modal-box h3 { margin: 0 0 10px; color: #e53e3e; font-size: 18px; }
.del-modal-box p  { margin: 0 0 22px; color: var(--text-muted, #aaa); font-size: 14px; line-height: 1.5; }
.del-modal-actions { display: flex; gap: 10px; }
.del-modal-actions button { flex: 1; padding: 11px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; font-family: inherit; }
.del-btn-cancel  { background: var(--card-border, #333); color: var(--text, #eee); }
.del-btn-confirm { background: #e53e3e; color: #fff; }

/* ── TOAST ── */
.cn-toast {
  position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%);
  background: #1a1a2e; color: #fff; border: 1.5px solid var(--accent);
  border-radius: 10px; padding: 13px 20px; z-index: 9999;
  font-size: 13px; min-width: 270px; max-width: 400px;
  box-shadow: 0 8px 28px rgba(0,0,0,.5); display: none;
}
.cn-toast.show { display: block; animation: toastIn .22s ease; }
@keyframes toastIn {
  from { opacity:0; transform: translateX(-50%) translateY(12px); }
  to   { opacity:1; transform: translateX(-50%) translateY(0); }
}
.cn-toast-title { font-weight: 700; color: var(--accent); margin-bottom: 7px; }
.cn-toast ul { margin: 0; padding-left: 16px; }
.cn-toast li { margin: 3px 0; }
</style>

<div class="admin-container">

  <div class="cn-breadcrumb">
    <a href="contenidos.php">Contenido</a>
    <i class="bi bi-chevron-right"></i>
    <span>Editar Noticia</span>
  </div>

  <h1 class="cn-page-title" style="text-align:center;">Editar noticia</h1>

  <!-- Información de auditoría -->
  <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 12px; color: var(--muted);">
    <div><strong>Creado por:</strong> <?= $creadoPor ? htmlspecialchars($creadoPor['nombre']) : 'Sin información' ?></div>
    <?php if ($editadoPor): ?>
      <div><strong>Última edición por:</strong> <?= htmlspecialchars($editadoPor['nombre']) ?> 
        <?php if ($noticia['ultima_edicion']): ?>
          - <?= htmlspecialchars(date('d/m/Y H:i', strtotime($noticia['ultima_edicion']))) ?>
        <?php endif; ?>
      </div>
    <?php elseif ($noticia['editado_por']): ?>
      <div><strong>Última edición por:</strong> Sin información
        <?php if ($noticia['ultima_edicion']): ?>
          - <?= htmlspecialchars(date('d/m/Y H:i', strtotime($noticia['ultima_edicion']))) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <form id="formEdicion" action="./../controllers/actualizar_noticia.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id"    value="<?= $noticia['id'] ?>">
    <input type="hidden" name="crop1" id="crop1" value="<?= htmlspecialchars($noticia['crop1'] ?? '') ?>">
    <input type="hidden" name="crop2" id="crop2" value="<?= htmlspecialchars($noticia['crop2'] ?? '') ?>">
    <input type="hidden" name="crop3" id="crop3" value="<?= htmlspecialchars($noticia['crop3'] ?? '') ?>">
    <input type="hidden" name="crop4" id="crop4" value="<?= htmlspecialchars($noticia['crop4'] ?? '') ?>">
    <input type="hidden" name="contenido" id="contenido">
    <input type="hidden" name="fecha_publicacion" id="fecha_publicacion_hidden">

    <div class="cn-wrap">

      <div class="cn-left-col">
      <!-- INFORMACIÓN BÁSICA -->
      <div class="cn-section" id="sec-info">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-info-circle"></i></div>
          <div><p class="cn-section-title">Información Básica</p></div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-field">
            <label for="titulo">Título de la Noticia</label>
            <input class="cn-input" type="text" id="titulo" name="titulo" maxlength="80"
                   placeholder="Escribe un título impactante..." required
                   value="<?= htmlspecialchars($noticia['titulo']) ?>">
            <div class="cn-hint"><span id="tituloCount"><?= mb_strlen($noticia['titulo']) ?></span>/80</div>
          </div>
          <div class="cn-field">
            <label for="descripcion">Descripción corta</label>
            <textarea class="cn-input" id="descripcion" name="descripcion" maxlength="150" rows="3"
                      placeholder="Resumen breve para redes sociales y buscadores..." required><?= htmlspecialchars($noticia['descripcion']) ?></textarea>
            <div class="cn-hint"><span id="descCount"><?= mb_strlen($noticia['descripcion']) ?></span>/150</div>
          </div>
          <div class="cn-field">
            <label for="tipo_publicacion">Tipo de Publicación</label>
            <select class="cn-input" id="tipo_publicacion" name="tipo_publicacion" required>
              <option value="noticia" <?= ($noticia['tipo_publicacion'] ?? 'noticia') === 'noticia' ? 'selected' : '' ?>>Noticia</option>
              <option value="review" <?= ($noticia['tipo_publicacion'] ?? '') === 'review' ? 'selected' : '' ?>>Reseña / Review</option>
            </select>
          </div>
          <div class="cn-field" style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px; margin-bottom: 10px;">
            <div>
              <label for="es_estreno" style="margin-bottom: 2px;">¿Es Próximo Estreno?</label>
              <span style="font-size: 11px; color: var(--muted);">Marcar para mostrar en la sección de próximos estrenos</span>
            </div>
            <div class="cn-toggle-wrap">
              <label class="cn-toggle">
                <input type="checkbox" id="es_estreno" name="es_estreno" value="1" <?= ($noticia['es_estreno'] ?? 0) == 1 ? 'checked' : '' ?>>
                <div class="cn-toggle-track"></div>
                <div class="cn-toggle-thumb"></div>
              </label>
            </div>
          </div>
          <div class="cn-field" id="seccionEstrenoWrapper" style="display: none; margin-bottom: 15px;">
            <label for="seccion_estreno">Sección de Estreno</label>
            <select class="cn-input" id="seccion_estreno" name="seccion_estreno">
              <option value="">-- Selecciona una sección --</option>
              <option value="peliculas" <?= ($noticia['seccion_estreno'] ?? '') === 'peliculas' ? 'selected' : '' ?>>Películas y Series</option>
              <option value="videojuegos" <?= ($noticia['seccion_estreno'] ?? '') === 'videojuegos' ? 'selected' : '' ?>>Videojuegos</option>
              <option value="anime" <?= ($noticia['seccion_estreno'] ?? '') === 'anime' ? 'selected' : '' ?>>Anime</option>
            </select>
          </div>

          <!-- Campos específicos para Review (ocultos por defecto si no es review) -->
          <?php
          $isReview = ($noticia['tipo_publicacion'] ?? '') === 'review';
          $calificacion = $noticia['calificacion'] ?? '';
          if ($calificacion === '') {
              $calificacionRangeVal = '5.0';
          } else {
              $calificacionRangeVal = number_format((float)$calificacion, 1, '.', '');
          }
          ?>
          <div id="reviewFieldsWrapper" style="display: <?= $isReview ? 'block' : 'none' ?>; border-top: 1px dashed var(--border); padding-top: 15px; margin-top: 15px;">
            <div class="cn-field">
              <label for="calificacion">Calificación (1.0 - 10.0)</label>
              <div style="display: flex; align-items: center; gap: 12px;">
                <input type="range" class="form-range" id="calificacionRange" min="1.0" max="10.0" step="0.1" value="<?= $calificacionRangeVal ?>" style="flex: 1; accent-color: var(--accent);">
                <input type="number" class="cn-input" id="calificacion" name="calificacion" min="1.0" max="10.0" step="0.1" placeholder="5.0" value="<?= htmlspecialchars($calificacion) ?>" style="width: 80px; text-align: center; font-weight: 700;">
              </div>
            </div>
            <div class="cn-field">
              <label for="pros">Pros (Puntos Positivos - Uno por línea)</label>
              <textarea class="cn-input" id="pros" name="pros" rows="4" placeholder="Ejemplo:&#10;Excelente historia&#10;Animación fluida&#10;Banda sonora increíble"><?= htmlspecialchars($noticia['pros'] ?? '') ?></textarea>
            </div>
            <div class="cn-field">
              <label for="contras">Contras (Puntos Negativos - Uno por línea)</label>
              <textarea class="cn-input" id="contras" name="contras" rows="4" placeholder="Ejemplo:&#10;Ritmo lento al inicio&#10;Desarrollo de personajes apresurado"><?= htmlspecialchars($noticia['contras'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- CATEGORÍAS -->
      <div class="cn-section" id="sec-cats">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-tag"></i></div>
          <div>
            <p class="cn-section-title">Categoría</p>
            <p class="cn-section-sub">El orden define la importancia</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-cat-dropdown">
            <div class="cn-cat-trigger" id="catTrigger">
              <span id="catTriggerLabel">Selecciona las categorías</span>
              <i class="bi bi-chevron-down"></i>
            </div>
            <div class="cn-cat-menu" id="catMenu">
              <?php foreach($categorias as $cat): ?>
              <label class="cn-cat-option" data-id="<?= $cat['id_c'] ?>" data-name="<?= htmlspecialchars($cat['nombre']) ?>">
                <input type="checkbox" value="<?= $cat['id_c'] ?>" data-name="<?= htmlspecialchars($cat['nombre']) ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="cn-cat-chips" id="catChips">
            <p class="cn-cat-empty" id="catEmpty">Ninguna categoría seleccionada</p>
          </div>
          <div id="catInputs"></div>
        </div>
      </div><!-- /sec-cats -->


      <!-- PROGRAMAR -->
      <div class="cn-section" id="sec-schedule">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-calendar-event"></i></div>
          <div><p class="cn-section-title">Programar</p></div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-schedule-inner">
            <div class="cn-schedule-row">
              <div class="cn-schedule-info">
                <p>Programa la publicación o desactiva para guardar con la fecha actual.</p>
              </div>
              <div class="cn-toggle-wrap">
                <label class="cn-toggle">
                  <input type="checkbox" id="scheduleToggle">
                  <div class="cn-toggle-track"></div>
                  <div class="cn-toggle-thumb"></div>
                </label>
              </div>
            </div>
            <div id="scheduleFields">
              <div class="cn-schedule-fields">
                <div class="cn-date-input">
                  <i class="bi bi-calendar3"></i>
                  <input type="date" id="schedDate" required>
                </div>
                <div class="cn-date-input">
                  <i class="bi bi-clock"></i>
                  <input type="time" id="schedTime" required>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /sec-schedule -->
      <?php if (!empty($ACL['editar'])): ?>
      <div style="margin-top:12px;">
        <button type="submit" class="cn-publish-btn" id="btnGuardar" name="guardarEdicion">
          <i class="bi bi-floppy"></i> Guardar cambios
        </button>
      </div>
      <?php endif; ?>
      <?php if (!empty($ACL['eliminar'])): ?>
      <button type="button" class="cn-delete-btn" id="btnEliminar">
        <i class="bi bi-trash"></i> Eliminar noticia
      </button>
      <?php endif; ?>

      </div><!-- /cn-left-col -->

      <!-- MULTIMEDIA -->
      <div class="cn-section" id="sec-media">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-images"></i></div>
          <div>
            <p class="cn-section-title">Multimedia</p>
            <p class="cn-section-sub">Haz clic para cambiar — se mantiene si no subes nada nuevo</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">

          <div style="margin-bottom:12px;">
            <p class="cn-zone-label">Imagen Banner</p>
            <div class="upload-zone cn-zone-banner" id="zone2" onclick="openCrop(2)">
              <div class="zone-overlay"><span>Cambiar imagen</span></div>
              <i class="bi bi-aspect-ratio cn-zone-icon"></i>
              <span class="zone-ratio">21 : 6</span>
              <div class="zone-actions">
                <button type="button" class="zone-btn zone-btn-adjust" onclick="event.stopPropagation();adjustCrop(2)"><i class="bi bi-crop"></i> Ajustar</button>
                <button type="button" class="zone-btn zone-btn-remove" onclick="event.stopPropagation();removeCrop(2)"><i class="bi bi-x-lg"></i> Quitar</button>
              </div>
            </div>
          </div>

          <!-- Imagen centrada 21:6 -->
          <div style="margin-bottom:12px;">
            <p class="cn-zone-label">Imagen centrada <span style="text-transform:none;font-size:10px;font-weight:400;color:var(--muted)">(21:6)</span></p>
            <div class="upload-zone cn-zone-paisaje" id="zone4" onclick="openCrop(4)">
              <div class="zone-overlay"><span>Cambiar</span></div>
              <i class="bi bi-layout-text-window cn-zone-icon"></i>
              <span class="zone-ratio">21 : 9</span>
              <div class="zone-actions">
                <button type="button" class="zone-btn zone-btn-adjust" onclick="event.stopPropagation();adjustCrop(4)"><i class="bi bi-crop"></i> Ajustar</button>
                <button type="button" class="zone-btn zone-btn-remove" onclick="event.stopPropagation();removeCrop(4)"><i class="bi bi-x-lg"></i> Quitar</button>
              </div>
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <p class="cn-zone-label">Miniatura <span style="text-transform:none;font-size:10px;font-weight:400;color:var(--muted)">(clic para cambiar)</span></p>
            <div class="upload-zone cn-zone-mini" id="zone3" onclick="openCrop(3)">
              <div class="zone-overlay"><span>Cambiar</span></div>
              <i class="bi bi-image cn-zone-icon"></i>
              <span class="zone-ratio">16 : 9</span>
              <div class="zone-actions">
                <button type="button" class="zone-btn zone-btn-adjust" onclick="event.stopPropagation();adjustCrop(3)"><i class="bi bi-crop"></i> Ajustar</button>
                <button type="button" class="zone-btn zone-btn-remove" onclick="event.stopPropagation();removeCrop(3)"><i class="bi bi-x-lg"></i> Quitar</button>
              </div>
            </div>
          </div>

          <!-- Vista previa por sección -->
          <div class="preview-section" id="previewSection" style="display:none;">
            <div class="preview-section-title">Vista previa por sección</div>
            <div class="pv-tabs" id="pvTabs">
              <button type="button" class="pv-tab-btn active" data-tab="hero">Inicio</button>
              <button type="button" class="pv-tab-btn" data-tab="top">Top Semana</button>
              <button type="button" class="pv-tab-btn" data-tab="rec">Recientes</button>
              <button type="button" class="pv-tab-btn" data-tab="side">Otros</button>
            </div>
            <div class="pv-panel active" id="pv-hero">
              <div class="pv-hero pv-card">
                <div class="pv-bg" id="pvHeroBg"></div>
                <div class="pv-hero-overlay">
                  <span class="pv-tag" id="pvHeroCat">CATEGORÍA</span>
                  <div class="pv-title-txt" id="pvHeroTitle">Título de la noticia</div>
                  <div class="pv-desc-txt"  id="pvHeroDesc">Descripción corta del artículo...</div>
                </div>
              </div>
            </div>
            <div class="pv-panel" id="pv-top">
              <div class="pv-top-grid">
                <div class="pv-card pv-top-main">
                  <div class="pv-bg" id="pvTopMain"></div>
                  <div class="pv-overlay">
                    <span class="pv-tag" id="pvTopCat">CATEGORÍA</span>
                    <div class="pv-title-txt" id="pvTopTitle">Título de la noticia</div>
                  </div>
                </div>
                <div class="pv-card pv-top-side">
                  <div class="pv-bg" id="pvTopSide"></div>
                </div>
              </div>
            </div>
            <div class="pv-panel" id="pv-rec">
              <div class="pv-rec-item">
                <div class="pv-rec-thumb pv-card">
                  <div class="pv-bg" id="pvRecThumb" style="height:100%;min-height:73px"></div>
                </div>
                <div class="pv-rec-info">
                  <span class="pv-tag" id="pvRecCat">CATEGORÍA</span>
                  <div class="pv-rec-title" id="pvRecTitle">Título de la noticia</div>
                  <div class="pv-rec-desc"  id="pvRecDesc">Descripción corta...</div>
                  <div class="pv-rec-meta">Publicado ahora &middot; Por ti</div>
                </div>
              </div>
            </div>
            <div class="pv-panel" id="pv-side">
              <div class="pv-side-cols">
                <div>
                  <div class="pv-side-head">⏰ Lo más nuevo</div>
                  <div class="pv-side-list">
                    <div class="pv-side-item">
                      <div class="pv-side-circle"><div class="pv-bg" id="pvSide1"></div></div>
                      <div class="pv-side-name" id="pvSideTitle1">Título de la noticia</div>
                    </div>
                  </div>
                </div>
                <div>
                  <div class="pv-side-head">🔥 Lo más popular</div>
                  <div class="pv-side-list">
                    <div class="pv-side-item">
                      <div class="pv-side-circle"><div class="pv-bg" id="pvSide2"></div></div>
                      <div class="pv-side-name" id="pvSideTitle2">Título de la noticia</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- CONTENIDO -->
      <div class="cn-section" id="sec-content">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-pencil-square"></i></div>
          <div><p class="cn-section-title">Contenido</p></div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-editor-wrap">
            <div class="editor-toolbar ql-toolbar ql-snow">
              <select class="ql-font" title="Fuente">
                <option value="arial" selected>Arial</option>
                <option value="times">Times New Roman</option>
                <option value="roboto">Roboto</option>
                <option value="courier">Courier</option>
              </select>
              <select class="ql-size" title="Tamaño">
                <option value="small">Pequeño</option>
                <option selected>Normal</option>
                <option value="large">Grande</option>
                <option value="huge">Muy grande</option>
              </select>
              <button class="ql-bold"></button>
              <button class="ql-italic"></button>
              <button class="ql-underline"></button>
              <button class="ql-strike"></button>
              <select class="ql-color"></select>
              <select class="ql-background"></select>
              <select class="ql-align"></select>
              <select class="ql-lineheight" title="Interlineado">
                <option value="0">0</option>
                <option value="0.85">0.85</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
              </select>
              <button class="ql-list" value="ordered"></button>
              <button class="ql-list" value="bullet"></button>
              <button class="ql-indent" value="-1"></button>
              <button class="ql-indent" value="+1"></button>
              <button class="ql-link"></button>
              <button class="ql-image"></button>
              <button class="ql-video"></button>
              <button class="ql-clean"></button>
              <button class="ql-embed"   title="Embebido"><i class="bi bi-boxes"></i></button>
              <button class="ql-callout" title="Barra lateral"><i class="bi bi-layout-text-sidebar-reverse"></i></button>
            </div>
            <div id="editor" class="editor-content"></div>
          </div>
          <!-- Contenido existente que admin.js carga automáticamente en Quill -->
          <div id="editorContent" style="display:none;"><?= $noticia['contenido'] ?></div>
          <input type="file" id="imageInputEditor" accept="image/*" hidden>
        </div>
      </div>

    </div><!-- /cn-wrap -->
  </form>
</div><!-- /admin-container -->

<!-- ── TOAST ── -->
<div class="cn-toast" id="cnToast">
  <div class="cn-toast-title"><i class="bi bi-exclamation-circle"></i> Faltan campos requeridos:</div>
  <ul id="cnToastList"></ul>
</div>

<!-- ── CROP MODAL ── -->
<div class="crop-modal-overlay" id="cropModal">
  <div class="crop-modal-box">
    <div class="crop-modal-head">
      <i class="bi bi-crop"></i>
      <span id="cropModalTitle">Recortar imagen</span>
      <button type="button" onclick="closeCrop()">✕</button>
    </div>
    <div class="crop-modal-body">
      <div class="crop-area"><img id="cropImg" src=""></div>
    </div>
    <div class="crop-modal-foot">
      <button type="button" class="btn btn-outline-secondary" onclick="closeCrop()">Cancelar</button>
      <button type="button" class="btn btn-accent" onclick="confirmCrop()">
        <i class="bi bi-check"></i> Confirmar
      </button>
    </div>
  </div>
</div>
<input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="onFileSelected(event)">

<!-- ── MODAL ELIMINAR NOTICIA ── -->
<div id="delModalOverlay" class="del-modal-overlay">
  <div class="del-modal-box">
    <h3><i class="bi bi-exclamation-triangle"></i> Eliminar noticia</h3>
    <p>¿Estás seguro de que deseas eliminar esta noticia?<br>Esta acción no se puede deshacer.</p>
    <div class="del-modal-actions">
      <button type="button" class="del-btn-cancel" id="delBtnCancel">Cancelar</button>
      <button type="button" class="del-btn-confirm" id="delBtnConfirm"><i class="bi bi-trash"></i> Eliminar</button>
    </div>
  </div>
</div>
<form id="formEliminar" action="../controllers/eliminar_noticia.php" method="POST" style="display:none;">
  <input type="hidden" name="id" value="<?= $id ?>">
</form>

<!-- ── MODAL HORA INVÁLIDA ── -->
<div id="timeModalOverlay" class="crop-modal" style="display:none;">
  <div class="crop-modal-content">
    <h3>Hora no válida</h3>
    <p>La fecha y hora seleccionadas es menor a la actual.<br><br>¿Qué deseas hacer?</p>
    <div class="modal-actions">
      <button class="btn-accent"   id="autoAdjustBtn"   type="button">Ajustar automáticamente y guardar</button>
      <button class="btn-secondary" id="manualAdjustBtn" type="button">Volver a ajustar la hora</button>
    </div>
  </div>
</div>

<script>
// Datos PHP → JS
const BASE_PATH          = '<?= basePath() ?>';
const EXISTING_CROP1_URL = '<?= addslashes($crop1Url) ?>';
const EXISTING_CROP2_URL = '<?= addslashes($crop2Url) ?>';
const EXISTING_CROP3_URL = '<?= addslashes($crop3Url) ?>';
const EXISTING_CROP4_URL = '<?= addslashes($crop4Url) ?>';
const FECHA_EXISTENTE    = '<?= $fechaExistente ?>';
const CATS_INICIALES     = <?= json_encode($categoriasSeleccionadas) ?>;

/* ── Modal eliminar noticia ── */
(function () {
  const overlay   = document.getElementById('delModalOverlay');
  const btnOpen   = document.getElementById('btnEliminar');
  const btnCancel = document.getElementById('delBtnCancel');
  const btnOk     = document.getElementById('delBtnConfirm');
  if (!overlay || !btnOpen) return;
  btnOpen.addEventListener('click', () => overlay.classList.add('open'));
  btnCancel.addEventListener('click', () => overlay.classList.remove('open'));
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
  btnOk.addEventListener('click', () => document.getElementById('formEliminar').submit());
})();

document.addEventListener('DOMContentLoaded', () => {
  /* ── Review fields toggle & sync ── */
  const tipoPublicacion = document.getElementById('tipo_publicacion');
  const reviewFieldsWrapper = document.getElementById('reviewFieldsWrapper');
  const calificacionRange = document.getElementById('calificacionRange');
  const calificacionInput = document.getElementById('calificacion');

  /* ── Estreno fields toggle ── */
  const esEstrenoCheckbox = document.getElementById('es_estreno');
  const seccionEstrenoWrapper = document.getElementById('seccionEstrenoWrapper');
  const seccionEstrenoSelect = document.getElementById('seccion_estreno');

  if (esEstrenoCheckbox && seccionEstrenoWrapper) {
    const toggleEstrenoFields = () => {
      if (esEstrenoCheckbox.checked) {
        seccionEstrenoWrapper.style.display = 'block';
        seccionEstrenoSelect.setAttribute('required', 'required');
      } else {
        seccionEstrenoWrapper.style.display = 'none';
        seccionEstrenoSelect.removeAttribute('required');
        seccionEstrenoSelect.value = '';
      }
    };
    esEstrenoCheckbox.addEventListener('change', toggleEstrenoFields);
    toggleEstrenoFields();
  }

  if (tipoPublicacion && reviewFieldsWrapper) {
    const toggleReviewFields = () => {
      if (tipoPublicacion.value === 'review') {
        reviewFieldsWrapper.style.display = 'block';
        if (!calificacionInput.value) {
          calificacionInput.value = calificacionRange.value;
        }
      } else {
        reviewFieldsWrapper.style.display = 'none';
      }
    };
    
    tipoPublicacion.addEventListener('change', toggleReviewFields);
    // Don't override initial DB visibility if it's already review
    toggleReviewFields();
  }

  if (calificacionRange && calificacionInput) {
    calificacionRange.addEventListener('input', () => {
      calificacionInput.value = calificacionRange.value;
    });
    calificacionInput.addEventListener('input', () => {
      let val = parseFloat(calificacionInput.value);
      if (isNaN(val)) return;
      if (val < 1.0) val = 1.0;
      if (val > 10.0) val = 10.0;
      calificacionRange.value = val;
    });
    calificacionInput.addEventListener('blur', () => {
      let val = parseFloat(calificacionInput.value);
      if (isNaN(val) || val < 1.0 || val > 10.0) {
        calificacionInput.value = parseFloat(calificacionRange.value).toFixed(1);
      } else {
        calificacionInput.value = val.toFixed(1);
      }
    });
  }

  /* ── Collapse de secciones ── */
  document.querySelectorAll('.cn-section-header').forEach(header => {
    header.addEventListener('click', e => {
      if (e.target.closest('input, select, button, a')) return;
      header.closest('.cn-section').classList.toggle('collapsed');
    });
  });

  /* ── Contadores ── */
  const titulo = document.getElementById('titulo');
  const tCount = document.getElementById('tituloCount');
  titulo?.addEventListener('input', () => { tCount.textContent = titulo.value.length; updateAllPreviews(); });

  const desc   = document.getElementById('descripcion');
  const dCount = document.getElementById('descCount');
  desc?.addEventListener('input', () => { dCount.textContent = desc.value.length; updateAllPreviews(); });


  /* ── Categorías ── */
  const catTrigger = document.getElementById('catTrigger');
  const catMenu    = document.getElementById('catMenu');
  const catChips   = document.getElementById('catChips');
  const catEmpty   = document.getElementById('catEmpty');
  const catInputs  = document.getElementById('catInputs');
  const catLabel   = document.getElementById('catTriggerLabel');

  catTrigger?.addEventListener('click', () => {
    catTrigger.classList.toggle('open');
    catMenu.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!catTrigger?.contains(e.target) && !catMenu?.contains(e.target)) {
      catTrigger?.classList.remove('open');
      catMenu?.classList.remove('open');
    }
  });
  catMenu?.querySelectorAll('input[type="checkbox"]').forEach(chk => {
    chk.addEventListener('change', () => {
      chk.closest('.cn-cat-option').classList.toggle('selected', chk.checked);
      if (chk.checked) addChip(chk.value, chk.dataset.name);
      else removeChip(chk.value);
      updateCatLabel();
    });
  });

  function addChip(id, name) {
    if (catChips.querySelector(`[data-id="${id}"]`)) return;
    catEmpty.style.display = 'none';
    const num  = catChips.querySelectorAll('.cn-chip').length + 1;
    const chip = document.createElement('div');
    chip.className = 'cn-chip'; chip.dataset.id = id; chip.draggable = true;
    chip.innerHTML = `
      <i class="bi bi-grip-vertical cn-chip-drag"></i>
      <span class="cn-chip-num">${num}</span>
      <span class="cn-chip-name">${name}</span>
      <button type="button" class="cn-chip-remove" data-id="${id}"><i class="bi bi-x"></i></button>
    `;
    chip.querySelector('.cn-chip-remove').addEventListener('click', () => {
      removeChip(id);
      const chk = catMenu.querySelector(`input[value="${id}"]`);
      if (chk) { chk.checked = false; chk.closest('.cn-cat-option')?.classList.remove('selected'); }
      updateCatLabel();
    });
    setupDrag(chip);
    catChips.appendChild(chip);
    rebuildInputs();
  }
  function removeChip(id) {
    catChips.querySelector(`[data-id="${id}"]`)?.remove();
    renumberChips(); rebuildInputs();
    if (!catChips.querySelector('.cn-chip')) catEmpty.style.display = '';
  }
  function renumberChips() {
    catChips.querySelectorAll('.cn-chip').forEach((c, i) => {
      c.querySelector('.cn-chip-num').textContent = i + 1;
    });
  }
  function rebuildInputs() {
    catInputs.innerHTML = '';
    catChips.querySelectorAll('.cn-chip').forEach(c => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'categoria[]'; inp.value = c.dataset.id;
      catInputs.appendChild(inp);
    });
  }
  function updateCatLabel() {
    const count = catChips.querySelectorAll('.cn-chip').length;
    catLabel.textContent = count === 0
      ? 'Selecciona las categorías'
      : `${count} categoría${count > 1 ? 's' : ''} seleccionada${count > 1 ? 's' : ''}`;
  }

  let dragSrc = null;
  function setupDrag(chip) {
    chip.addEventListener('dragstart', e => {
      dragSrc = chip; e.dataTransfer.effectAllowed = 'move';
      setTimeout(() => chip.style.opacity = '0.4', 0);
    });
    chip.addEventListener('dragend',  () => { chip.style.opacity = '1'; dragSrc = null; });
    chip.addEventListener('dragover', e => { e.preventDefault(); chip.classList.add('drag-over'); });
    chip.addEventListener('dragleave',() => chip.classList.remove('drag-over'));
    chip.addEventListener('drop', e => {
      e.preventDefault(); chip.classList.remove('drag-over');
      if (!dragSrc || dragSrc === chip) return;
      const all = [...catChips.querySelectorAll('.cn-chip')];
      if (all.indexOf(dragSrc) < all.indexOf(chip)) chip.after(dragSrc);
      else chip.before(dragSrc);
      renumberChips(); rebuildInputs();
    });
  }

  /* ── Pre-poblar categorías existentes ── */
  CATS_INICIALES.forEach(cat => {
    addChip(cat.id, cat.nombre);
    const chk = catMenu.querySelector(`input[value="${cat.id}"]`);
    if (chk) { chk.checked = true; chk.closest('.cn-cat-option')?.classList.add('selected'); }
  });
  updateCatLabel();

  /* ── Pre-cargar zonas con imágenes existentes ── */
  if (EXISTING_CROP2_URL) {
    setZonePreviewFromUrl(2, EXISTING_CROP2_URL);
    zoneSources[2] = EXISTING_CROP1_URL || EXISTING_CROP2_URL;
  }
  if (EXISTING_CROP4_URL) {
    setZonePreviewFromUrl(4, EXISTING_CROP4_URL);
    zoneSources[4] = EXISTING_CROP1_URL || EXISTING_CROP2_URL || EXISTING_CROP4_URL;
  }
  if (EXISTING_CROP3_URL) {
    setZonePreviewFromUrl(3, EXISTING_CROP3_URL);
    zoneSources[3] = EXISTING_CROP1_URL || EXISTING_CROP2_URL || EXISTING_CROP3_URL;
  }
  if (EXISTING_CROP2_URL || EXISTING_CROP3_URL || EXISTING_CROP4_URL) {
    document.getElementById('previewSection').style.display = 'block';
    updateAllPreviews();
  }

  /* ── Programar: pre-cargar fecha existente ── */
  const schedToggle = document.getElementById('scheduleToggle');
  const schedFields = document.getElementById('scheduleFields');
  const schedDate   = document.getElementById('schedDate');
  const schedTime   = document.getElementById('schedTime');

  if (FECHA_EXISTENTE) {
    schedDate.value = FECHA_EXISTENTE.slice(0, 10);
    schedTime.value = FECHA_EXISTENTE.slice(11, 16);
  }
  // Programar desactivado por defecto: campos ocultos hasta que se active
  schedFields.style.display = schedToggle.checked ? '' : 'none';

  schedToggle?.addEventListener('change', () => {
    schedFields.style.display = schedToggle.checked ? '' : 'none';
  });

  function nowLocal() {
    const now = new Date();
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
  }

  /* ── Submit ── */
  const form         = document.getElementById('formEdicion');
  const hiddenFecha  = document.getElementById('fecha_publicacion_hidden');
  const contenidoHid = document.getElementById('contenido');
  let _submitting = false;
  function validateForm() {
    const errors = [];
    if (!document.getElementById('titulo').value.trim())
      errors.push('Título de la noticia');
    if (!document.getElementById('descripcion').value.trim())
      errors.push('Descripción corta');
    const editorEl  = document.querySelector('#editor .ql-editor');
    const editorHtml = editorEl ? editorEl.innerHTML.trim() : '';
    if (!editorHtml || editorHtml === '<p><br></p>' || editorHtml === '<p></p>')
      errors.push('Contenido del artículo');
    return errors;
  }
  function showToast(errors) {
    const toast = document.getElementById('cnToast');
    const list  = document.getElementById('cnToastList');
    list.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('show'), 5000);
  }

  form?.addEventListener('submit', e => {
    if (_submitting) { e.preventDefault(); return; }
    _submitting = true;
    const btn = document.getElementById('btnGuardar');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
    if (window.quill) {
      let html = quill.root.innerHTML;
      html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                          '<div class="social-embed" data-url="$1"></div>');
      contenidoHid.value = html;
    }
    // Si Programar está desactivado, conservar la fecha de publicación original
    hiddenFecha.value = schedToggle?.checked
      ? (schedDate.value + 'T' + schedTime.value).replace('T', ' ')
      : FECHA_EXISTENTE.replace('T', ' ');
  });

  /* ── Click en "Guardar cambios" con validación ── */
  document.getElementById('btnGuardar')?.addEventListener('click', e => {
    e.preventDefault();
    if (_submitting) return;
    const errors = validateForm();
    if (errors.length) { showToast(errors); return; }
    if (!schedToggle?.checked) { form.requestSubmit(); return; }
    const selected = schedDate.value + ' ' + schedTime.value;
    if (selected < nowLocal().replace('T', ' ')) {
      document.getElementById('timeModalOverlay').style.display = 'flex';
    } else {
      form.requestSubmit();
    }
  });

  /* ── Modal hora inválida ── */
  document.getElementById('autoAdjustBtn')?.addEventListener('click', () => {
    if (_submitting) return;
    document.getElementById('autoAdjustBtn').disabled = true;
    const l = nowLocal();
    schedDate.value = l.slice(0, 10); schedTime.value = l.slice(11, 16);
    document.getElementById('timeModalOverlay').style.display = 'none';
    form.requestSubmit();
  });
  document.getElementById('manualAdjustBtn')?.addEventListener('click', () => {
    document.getElementById('timeModalOverlay').style.display = 'none';
  });

  /* ── Toolbar fija al hacer scroll ── */
  (function () {
    const wrap    = document.querySelector('.cn-editor-wrap');
    const toolbar = wrap?.querySelector('.editor-toolbar');
    if (!wrap || !toolbar) return;
    let spacer = null;
    function pin() {
      if (spacer) return;
      const tbH  = toolbar.offsetHeight;
      const rect = wrap.getBoundingClientRect();
      spacer = document.createElement('div');
      spacer.style.height = tbH + 'px';
      toolbar.before(spacer);
      toolbar.style.left  = rect.left + 'px';
      toolbar.style.width = wrap.clientWidth + 'px';
      toolbar.classList.add('tb-pinned');
    }
    function unpin() {
      if (!spacer) return;
      spacer.remove(); spacer = null;
      toolbar.style.left = ''; toolbar.style.width = '';
      toolbar.classList.remove('tb-pinned');
    }
    window.addEventListener('scroll', () => {
      const wrapRect = wrap.getBoundingClientRect();
      if (wrapRect.top < 0 && wrapRect.bottom > (spacer ? spacer.offsetHeight : toolbar.offsetHeight)) {
        pin();
        toolbar.style.left  = wrap.getBoundingClientRect().left + 'px';
        toolbar.style.width = wrap.clientWidth + 'px';
      } else { unpin(); }
    }, { passive: true });
    window.addEventListener('resize', unpin, { passive: true });
  })();

});

/* ════════════════════════════════
   CROPPER
════════════════════════════════ */
const CROP_RATIOS = { 1: 1/1, 2: 21/6, 3: 16/9, 4: 21/6 };
const CROP_TITLES = {
  1: 'Recortar — Imagen Original (1:1)',
  2: 'Recortar — Banner (21:6)',
  3: 'Recortar — Miniatura (16:9)',
  4: 'Recortar — Imagen centrada (21:6)'
};
let activeCrop = null, cropperInstance = null;
const zoneSources = {};
const zoneCropData = {};
let _chainNextCrop = false;
let _chainQueue = [];

function openCrop(num) {
  activeCrop = num;
  _chainNextCrop = true;
  const ORDER = [2, 4, 3];
  const idx = ORDER.indexOf(num);
  _chainQueue = idx >= 0
    ? [...ORDER.slice(idx + 1), ...ORDER.slice(0, idx)]
    : [];
  [2, 3, 4].forEach(n => delete zoneCropData[n]);
  document.getElementById('fileInput').click();
}

function initCropper(num) {
  const cropImg  = document.getElementById('cropImg');
  const cropArea = document.querySelector('.crop-area');
  const maxW     = cropArea.clientWidth || 640;
  const imgRatio = cropImg.naturalWidth / cropImg.naturalHeight;

  cropArea.style.height = Math.min(Math.round(maxW / imgRatio), 480) + 'px';

  const cropRatioOverride = { 2: 21 / 6, 3: 16 / 9, 4: 21 / 6 };
  const effectiveRatio = cropRatioOverride[num] ?? CROP_RATIOS[num];

  cropperInstance = new Cropper(cropImg, {
    aspectRatio: effectiveRatio,
    viewMode: 1,
    autoCropArea: 0.98,
    movable: false,
    zoomable: false,
    cropBoxResizable: true,
    dragMode: 'move',
    responsive: true,
    guides: true,
    background: false,
    ready() {
      if (num === 2) {
        if (zoneCropData[2]) {
          cropperInstance.setData(zoneCropData[2]);
        } else {
          const imgData = cropperInstance.getImageData();
          const ratio = 21 / 6;
          let cbW = imgData.width;
          let cbH = cbW / ratio;
          if (cbH > imgData.height) {
            cbH = imgData.height;
            cbW = cbH * ratio;
          }
          cropperInstance.setCropBoxData({
            width:  cbW,
            height: cbH,
            left:   imgData.left,
            top:    imgData.top + (imgData.height - cbH) / 2
          });
        }
      } else if (num === 3 || num === 4) {
        if (zoneCropData[num]) {
          cropperInstance.setData(zoneCropData[num]);
        } else {
          const imgData = cropperInstance.getImageData();
          const ratio = cropRatioOverride[num];
          let cbH = imgData.height;
          let cbW = cbH * ratio;
          if (cbW > imgData.width) {
            cbW = imgData.width;
            cbH = cbW / ratio;
          }
          const availX = imgData.width - cbW;
          cropperInstance.setCropBoxData({
            width:  cbW,
            height: cbH,
            left:   imgData.left + availX / 2,
            top:    imgData.top + (imgData.height - cbH) / 2
          });
        }
      }
    }
  });
}

function adjustCrop(num) {
  if (!zoneSources[num]) return;
  activeCrop = num;
  _chainNextCrop = false;
  _chainQueue = [];
  const cropImg  = document.getElementById('cropImg');
  if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
  const cropArea = document.querySelector('.crop-area');
  if (!cropArea.contains(cropImg)) cropArea.appendChild(cropImg);
  cropImg.src = '';
  document.getElementById('cropModalTitle').textContent = CROP_TITLES[num];
  document.getElementById('cropModal').classList.add('open');
  cropImg.onload = () => initCropper(num);
  cropImg.src = zoneSources[num];
}

function removeCrop(num) {
  const clearZone = n => {
    document.getElementById('crop' + n).value = '';
    delete zoneSources[n];
    delete zoneCropData[n];
    const z = document.getElementById('zone' + n);
    if (z) { z.querySelectorAll('.preview-img').forEach(el => el.remove()); z.classList.remove('has-image'); }
  };
  [2, 3, 4].forEach(clearZone);
  document.getElementById('crop1').value = '';
  document.getElementById('previewSection').style.display = 'none';
}

function onFileSelected(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const fullSrc = ev.target.result;

    const origImg = new Image();
    origImg.onload = function() {
      const MAX = 1920;
      let w = origImg.naturalWidth, h = origImg.naturalHeight;
      if (w > MAX || h > MAX) {
        if (w >= h) { h = Math.round(h * MAX / w); w = MAX; }
        else        { w = Math.round(w * MAX / h); h = MAX; }
      }
      const tmpC = document.createElement('canvas');
      tmpC.width = w; tmpC.height = h;
      tmpC.getContext('2d').drawImage(origImg, 0, 0, w, h);
      document.getElementById('crop1').value = tmpC.toDataURL('image/jpeg', 0.92);
    };
    origImg.src = fullSrc;

    zoneSources[2] = fullSrc;
    zoneSources[3] = fullSrc;
    zoneSources[4] = fullSrc;
    const cropImg  = document.getElementById('cropImg');
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    const cropArea = document.querySelector('.crop-area');
    if (!cropArea.contains(cropImg)) cropArea.appendChild(cropImg);
    cropImg.src = '';
    document.getElementById('cropModalTitle').textContent = CROP_TITLES[activeCrop];
    document.getElementById('cropModal').classList.add('open');
    cropImg.onload = () => initCropper(activeCrop);
    cropImg.src = fullSrc;
  };
  reader.readAsDataURL(file);
  e.target.value = '';
}

function closeCrop() {
  document.getElementById('cropModal').classList.remove('open');
  if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
  const cropImg  = document.getElementById('cropImg');
  const cropArea = document.querySelector('.crop-area');
  if (!cropArea.contains(cropImg)) cropArea.appendChild(cropImg);
  cropImg.src = '';
  cropArea.style.height = '';
}

function setZonePreview(zoneId, data64) {
  const zone = document.getElementById('zone' + zoneId);
  zone.querySelectorAll('.preview-img').forEach(el => el.remove());
  const img = document.createElement('img');
  img.className = 'preview-img';
  zone.appendChild(img);
  img.src = data64;
  zone.classList.add('has-image');
}

function setZonePreviewFromUrl(zoneId, url) {
  const zone = document.getElementById('zone' + zoneId);
  if (!zone || !url) return;
  zone.querySelectorAll('.preview-img').forEach(el => el.remove());
  const img = document.createElement('img');
  img.className = 'preview-img';
  img.onload = function() {
    zone.classList.add('has-image');
  };
  img.onerror = function() {
    console.error('Error cargando imagen:', url);
    zone.classList.remove('has-image');
  };
  img.src = url;
  zone.appendChild(img);
}

function confirmCrop() {
  if (!cropperInstance) return;
  const canvas      = cropperInstance.getCroppedCanvas({ maxWidth: 2560, maxHeight: 2560 });
  const srcForAuto  = document.getElementById('cropImg').src;
  const cropNum     = activeCrop;
  const chain       = _chainNextCrop;
  _chainNextCrop    = false;
  zoneCropData[cropNum] = cropperInstance.getData();
  closeCrop();

  canvas.toBlob(function(blob) {
    const reader = new FileReader();
    reader.onloadend = function() {
      const data64 = reader.result;
      document.getElementById('crop' + cropNum).value = data64;
      setZonePreview(cropNum, data64);

      if (_chainQueue.length > 0) {
        const nextNum = _chainQueue.shift();
        activeCrop = nextNum;
        zoneSources[nextNum] = srcForAuto;
        const cropImg  = document.getElementById('cropImg');
        const cropArea = document.querySelector('.crop-area');
        if (!cropArea.contains(cropImg)) cropArea.appendChild(cropImg);
        cropImg.src = '';
        document.getElementById('cropModalTitle').textContent = CROP_TITLES[nextNum];
        document.getElementById('cropModal').classList.add('open');
        cropImg.onload = () => initCropper(nextNum);
        cropImg.src = srcForAuto;
      } else {
        const c2 = document.getElementById('crop2').value;
        const c3 = document.getElementById('crop3').value;
        const c4 = document.getElementById('crop4').value;
        document.getElementById('previewSection').style.display = (c2 || c3 || c4) ? 'block' : 'none';
        updateAllPreviews();
      }
    };
    reader.readAsDataURL(blob);
  }, 'image/png', 1.0);
}

/* ── VISTA PREVIA ── */
function cropUrl(val) {
  if (!val) return '';
  if (val.startsWith('data:') || val.startsWith('http')) return val;
  return BASE_PATH + '/serve-image.php?file=' + encodeURIComponent(val);
}
function setPvBg(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  const url = cropUrl(val);
  el.style.backgroundImage = url ? `url(${url})` : '';
}
function updateAllPreviews() {
  const c2    = document.getElementById('crop2').value;
  const c3    = document.getElementById('crop3').value;
  const title = document.getElementById('titulo')?.value.trim()       || 'Título de la noticia';
  const desc  = document.getElementById('descripcion')?.value.trim()  || 'Descripción corta del artículo...';
  const catEl = document.querySelector('#catChips .cn-chip-name');
  const cat   = catEl ? catEl.textContent.trim().toUpperCase() : 'CATEGORÍA';

  setPvBg('pvHeroBg',   c2);
  ['pvHeroTitle'].forEach(id => { const e=document.getElementById(id); if(e) e.textContent=title; });
  ['pvHeroDesc'].forEach(id  => { const e=document.getElementById(id); if(e) e.textContent=desc;  });
  ['pvHeroCat'].forEach(id   => { const e=document.getElementById(id); if(e) e.textContent=cat;   });

  setPvBg('pvTopMain', c2);
  setPvBg('pvTopSide', c3);
  const topTitle = document.getElementById('pvTopTitle'); if (topTitle) topTitle.textContent = title;
  const topCat   = document.getElementById('pvTopCat');   if (topCat)   topCat.textContent   = cat;

  setPvBg('pvRecThumb', c3);
  const recTitle = document.getElementById('pvRecTitle'); if (recTitle) recTitle.textContent = title;
  const recDesc  = document.getElementById('pvRecDesc');  if (recDesc)  recDesc.textContent  = desc;
  const recCat   = document.getElementById('pvRecCat');   if (recCat)   recCat.textContent   = cat;

  setPvBg('pvSide1', c3); setPvBg('pvSide2', c3);
  ['pvSideTitle1','pvSideTitle2'].forEach(id => {
    const e = document.getElementById(id); if (e) e.textContent = title;
  });
}

/* ── TABS VISTA PREVIA ── */
document.getElementById('pvTabs')?.addEventListener('click', e => {
  const btn = e.target.closest('.pv-tab-btn');
  if (!btn) return;
  document.querySelectorAll('.pv-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.pv-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  const panel = document.getElementById('pv-' + btn.dataset.tab);
  if (panel) panel.classList.add('active');
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
