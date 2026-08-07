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

$stmt = $con->prepare("SELECT id, titulo, descripcion, contenido, fecha_publicacion, fecha_programada, crop1, crop2, crop3, crop4, creado_por, editado_por, ultima_edicion, tipo_publicacion, calificacion, pros, contras, es_estreno, seccion_estreno FROM noticias WHERE id = ? AND eliminado_en IS NULL");
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

// Fecha para pre-cargar el programador.
// Un borrador no tiene fecha de publicación (NULL). Si al escribirlo se dejó
// programado, esa fecha quedó en `fecha_programada`: la reusamos para no tener
// que volver a programar la nota. Si no, se precarga con la fecha/hora actual.
$esBorrador = empty($noticia['fecha_publicacion']);
$fechaProgramada = $noticia['fecha_programada'] ?? null;

if ($esBorrador) {
    $tsPublicacion = !empty($fechaProgramada) ? strtotime($fechaProgramada) : time();
} else {
    $tsPublicacion = strtotime($noticia['fecha_publicacion']);
}
$fechaExistente = date('Y-m-d\TH:i', $tsPublicacion);

// Se muestra el programador si la nota ya está programada a futuro, o si es un
// borrador que traía una fecha programada desde "Crear noticia".
$esProgramada = $esBorrador
    ? !empty($fechaProgramada)
    : ($tsPublicacion > time());

// URLs de imágenes usando imageUrl() para servir correctamente
// Nota: imageUrl('') devuelve el placeholder.svg, así que guardamos contra
// vacío para que una zona sin crop quede realmente vacía (no con placeholder).
$crop1Url = !empty($noticia['crop1']) ? imageUrl($noticia['crop1']) : '';
$crop2Url = !empty($noticia['crop2']) ? imageUrl($noticia['crop2']) : '';
$crop3Url = !empty($noticia['crop3']) ? imageUrl($noticia['crop3']) : '';
$crop4Url = !empty($noticia['crop4']) ? imageUrl($noticia['crop4']) : '';
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
.cn-section:not(.collapsed),
.cn-section:not(.collapsed) .cn-section-body {
  overflow: visible;
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

/* ── VISTA PREVIA POR SECCIÓN ── */
.preview-section { margin-top: 14px; }
.preview-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.05em; color: var(--muted); margin-bottom: 10px; }
/* Tabs */
.pv-tabs { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 12px; }
.pv-tab-btn {
  padding: 4px 11px; font-size: 11px; font-weight: 600;
  border-radius: 20px; border: 1.5px solid var(--border);
  background: transparent; color: var(--muted);
  cursor: pointer; transition: all .15s; font-family: inherit;
}
.pv-tab-btn.active { background: var(--accent); color:#fff; border-color: var(--accent); }
.pv-panel { display:none; }
/* Ancho moderado y constante para que todas las previsualizaciones se vean al mismo tamaño */
.pv-panel.active { display:block; max-width:520px; }
/* Reviews y Estrenos muestran lista + barra lateral, necesitan un poco más de ancho */
#pv-reviews.active, #pv-estrenos.active { max-width:660px; }
/* Helpers */
.pv-bg { width:100%; height:100%; background-size:cover; background-position:center; background-color: var(--border); }
.pv-card { position:relative; border-radius:8px; overflow:hidden; background:var(--border); }
.pv-overlay {
  position:absolute; inset:0;
  background:linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,.35) 45%, transparent 75%);
  display:flex; flex-direction:column; justify-content:flex-end; padding:8px; color:#fff;
}
.pv-tag { display:inline-block; background:var(--accent); color:#fff; font-size:7px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:2px 5px; border-radius:3px; margin-bottom:3px; align-self:flex-start; }
.pv-title-txt { font-size:10px; font-weight:700; line-height:1.3; color:#fff; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.pv-desc-txt  { font-size:8px; color:rgba(255,255,255,.8); margin-top:2px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.pv-title-txt.dark { color:var(--text); }
.pv-desc-txt.dark  { color:var(--muted); }
.pv-label { font-size:9px; color:var(--muted); text-align:center; margin-top:6px; }
.pv-meta  { font-size:8px; color:var(--muted); margin-top:auto; padding-top:4px; }

/* Formas de tarjeta que replican el front real */
.pv-wide   { aspect-ratio:32/10; }   /* card-width-2-3 (Top/Estrenos/Debatido grande) */
.pv-square { aspect-ratio:16/10; }   /* card-width-1-3 (laterales/cuadradas) */

/* ─ Principal / Hero ─ */
.pv-hero { aspect-ratio:21/6; border-radius:8px; overflow:hidden; position:relative; }
.pv-hero-overlay {
  position:absolute; inset:0;
  display:flex; flex-direction:column; justify-content:center; padding:14px 18px;
  text-shadow: 0 1px 4px rgba(0,0,0,.85), 0 0 2px rgba(0,0,0,.6);
}
.pv-hero-overlay .pv-title-txt { font-size:14px; max-width:55%; }
.pv-hero-overlay .pv-desc-txt  { font-size:10px; max-width:50%; margin-top:4px; color:rgba(255,255,255,.85); }

/* ─ Top Semanal / Estrenos: fila ancha + cuadrada ─ */
.pv-top-grid { display:grid; grid-template-columns:2fr 1fr; gap:6px; align-items:stretch; }

/* ─ Tarjeta horizontal (Reviews / Recientes) ─ */
.pv-hcard { display:grid; grid-template-columns:210px 1fr; gap:14px; align-items:stretch;
  background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:10px; }
.pv-hcard-thumb { aspect-ratio:16/9; }
.pv-hcard-body  { display:flex; flex-direction:column; gap:3px; padding:2px 4px; }
.pv-hcard-body .pv-title-txt { font-size:12px; }
.pv-hcard-body .pv-desc-txt  { font-size:9px; }
/* Badge de calificación circular (como .review-rating-badge del front) */
.pv-badge { position:absolute; top:6px; right:6px; width:24px; height:24px; padding:0; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  background:var(--accent); color:#fff; font-size:8px; font-weight:800; box-shadow:0 2px 6px rgba(0,0,0,.35); }

/* ─ Layout de dos columnas (Reviews / Estrenos): lista + barra lateral ─ */
.pv-col2 { display:grid; grid-template-columns:1.8fr 1fr; gap:14px; align-items:start; }
.pv-col2-main { display:flex; flex-direction:column; gap:10px; }
.pv-col2 .pv-hcard { grid-template-columns:120px 1fr; gap:10px; }
.pv-est-duo { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

/* ─ Barra lateral tipo ranking (calca de .ranking-item-hero del front) ─ */
.pv-rank { display:flex; flex-direction:column; gap:8px; }
.pv-rank-title { font-size:10px; font-weight:800; color:var(--accent); text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
.pv-rank-hero { aspect-ratio:12/5; border:2px solid var(--accent); border-radius:8px; box-shadow:0 4px 12px rgba(239,51,99,.25); }
.pv-rank-sub  { aspect-ratio:12/5; border-radius:8px; }
.pv-rank-num  { position:absolute; left:8px; top:40%; transform:translateY(-50%);
  font-size:46px; font-weight:900; font-style:italic; font-family:'Arial Black',Impact,sans-serif;
  color:var(--accent); line-height:.8; z-index:2; text-shadow:0 2px 6px rgba(0,0,0,.6); pointer-events:none; }
.pv-rank-ov { position:absolute; inset:0; display:flex; flex-direction:column; justify-content:flex-end;
  padding:6px 32px 6px 8px; background:linear-gradient(to top, rgba(0,0,0,.95) 0%, rgba(0,0,0,.55) 55%, transparent 100%); }
.pv-rank-name { font-size:9px; font-weight:750; color:#fff; line-height:1.25; text-shadow:0 2px 4px rgba(0,0,0,.9);
  overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.pv-rank-score { position:absolute; top:50%; right:8px; transform:translateY(-50%);
  width:26px; height:26px; border-radius:50%; background:var(--accent); color:#fff; font-size:8px; font-weight:800;
  display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,.4); z-index:3; }

/* ─ Debatido (fondo oscuro como en el front) ─ */
.pv-debatido-wrap { background:#0c0c13; border:1px solid rgba(255,255,255,.06); border-radius:10px; padding:10px; }
.pv-debatido-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
.pv-chat { display:inline-flex; align-items:center; gap:3px; align-self:flex-start;
  background:var(--accent); color:#fff; font-size:7px; font-weight:700; padding:2px 5px; border-radius:3px; margin-bottom:3px; }

/* ─ Random: tarjeta vertical ─ */
.pv-random-card  { max-width:230px; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--bg); }
.pv-random-thumb { aspect-ratio:16/9; border-radius:0; }
.pv-random-body  { padding:8px; display:flex; flex-direction:column; gap:3px; }

/* En pantallas angostas, apilar las dos columnas y reducir miniaturas */
@media (max-width: 620px) {
  .pv-col2 { grid-template-columns:1fr; gap:12px; }
  .pv-hcard, .pv-col2 .pv-hcard { grid-template-columns:140px 1fr; gap:10px; }
}

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
      <?php if ($esProgramada): ?>
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
                  <input type="checkbox" id="scheduleToggle" checked>
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
      <?php endif; ?>
      <?php if (!empty($ACL['editar'])): ?>
      <div style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
        <button type="submit" class="cn-publish-btn" id="btnGuardar" name="guardarEdicion">
          <i class="bi bi-floppy"></i> Guardar cambios
        </button>
        <button type="button" id="btnVerHistorial" style="background: rgba(255,255,255,0.06); color: var(--text); border: 1px solid var(--border); padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; font-size: 0.9rem; transition: all 0.2s;">
          <i class="bi bi-clock-history"></i> Historial de Cambios
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
            <div class="upload-zone cn-zone-banner <?= $crop2Url ? 'has-image' : '' ?>" id="zone2" onclick="openCrop(2)">
              <?php if ($crop2Url): ?><img class="preview-img" src="<?= htmlspecialchars($crop2Url) ?>" fetchpriority="high" alt=""><?php endif; ?>
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
            <div class="upload-zone cn-zone-paisaje <?= $crop4Url ? 'has-image' : '' ?>" id="zone4" onclick="openCrop(4)">
              <?php if ($crop4Url): ?><img class="preview-img" src="<?= htmlspecialchars($crop4Url) ?>" fetchpriority="high" alt=""><?php endif; ?>
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
            <div class="upload-zone cn-zone-mini <?= $crop3Url ? 'has-image' : '' ?>" id="zone3" onclick="openCrop(3)">
              <?php if ($crop3Url): ?><img class="preview-img" src="<?= htmlspecialchars($crop3Url) ?>" fetchpriority="high" alt=""><?php endif; ?>
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
              <button type="button" class="pv-tab-btn active" data-tab="principal">Principal</button>
              <button type="button" class="pv-tab-btn" data-tab="top">Top Semanal</button>
              <button type="button" class="pv-tab-btn" data-tab="reviews">Reviews</button>
              <button type="button" class="pv-tab-btn" data-tab="estrenos">Estrenos</button>
              <button type="button" class="pv-tab-btn" data-tab="debatido">Debatido</button>
              <button type="button" class="pv-tab-btn" data-tab="random">Random</button>
              <button type="button" class="pv-tab-btn" data-tab="recientes">Recientes</button>
            </div>

            <!-- Principal (Slider / Hero) -->
            <div class="pv-panel active" id="pv-principal">
              <div class="pv-hero pv-card">
                <div class="pv-bg" id="pvHeroBg"></div>
                <div class="pv-hero-overlay">
                  <span class="pv-tag" id="pvHeroCat">CATEGORÍA</span>
                  <div class="pv-title-txt" id="pvHeroTitle">Título de la noticia</div>
                  <div class="pv-desc-txt"  id="pvHeroDesc">Descripción corta del artículo...</div>
                </div>
              </div>
              <div class="pv-label">Slider principal &middot; banner 21:6</div>
            </div>

            <!-- Top Semanal -->
            <div class="pv-panel" id="pv-top">
              <div class="pv-top-grid">
                <div class="pv-card pv-wide">
                  <div class="pv-bg" id="pvTopMain"></div>
                  <div class="pv-overlay">
                    <span class="pv-tag" id="pvTopCat">CATEGORÍA</span>
                    <div class="pv-title-txt" id="pvTopTitle">Título de la noticia</div>
                  </div>
                </div>
                <div class="pv-card pv-square">
                  <div class="pv-bg" id="pvTopSide"></div>
                  <div class="pv-overlay">
                    <div class="pv-title-txt" style="color:rgba(255,255,255,.55)">Otra noticia</div>
                  </div>
                </div>
              </div>
              <div class="pv-label">Tu nota (ancha) &middot; nota lateral (cuadrada)</div>
            </div>

            <!-- Nuestras Reviews + Lo que más te recomendamos -->
            <div class="pv-panel" id="pv-reviews">
              <div class="pv-col2">
                <!-- Columna izquierda: lista de reviews -->
                <div class="pv-col2-main">
                  <div class="pv-hcard">
                    <div class="pv-hcard-thumb pv-card">
                      <div class="pv-bg" id="pvRevThumb"></div>
                      <span class="pv-badge" id="pvRevBadge">8.5</span>
                    </div>
                    <div class="pv-hcard-body">
                      <span class="pv-tag" id="pvRevCat">CATEGORÍA</span>
                      <div class="pv-title-txt dark" id="pvRevTitle">Título de la noticia</div>
                      <div class="pv-desc-txt dark"  id="pvRevDesc">Descripción corta del artículo...</div>
                      <div class="pv-meta">Publicado ahora &middot; Por ti</div>
                    </div>
                  </div>
                </div>
                <!-- Columna derecha: Lo que más te recomendamos (puesto 1) -->
                <div class="pv-rank">
                  <div class="pv-rank-title">Lo que más te recomendamos</div>
                  <div class="pv-rank-hero pv-card">
                    <div class="pv-bg" id="pvRecomHero"></div>
                    <div class="pv-rank-num">1</div>
                    <div class="pv-rank-ov"><div class="pv-rank-name" id="pvRecomName">Título de la noticia</div></div>
                    <div class="pv-rank-score" id="pvRecomScore">10.0</div>
                  </div>
                </div>
              </div>
              <div class="pv-label">Nuestras Reviews (izq) &middot; Lo que más te recomendamos (der)</div>
            </div>

            <!-- Próximos Estrenos + Lo que más esperamos -->
            <div class="pv-panel" id="pv-estrenos">
              <div class="pv-col2">
                <!-- Columna izquierda: card ancha (Películas y Series) + 2 cuadradas (Videojuegos/Anime) -->
                <div class="pv-col2-main">
                  <div class="pv-card pv-wide">
                    <div class="pv-bg" id="pvEstMain"></div>
                    <div class="pv-overlay">
                      <span class="pv-tag" id="pvEstCat">CATEGORÍA</span>
                      <div class="pv-title-txt" id="pvEstTitle">Título de la noticia</div>
                    </div>
                  </div>
                  <div class="pv-est-duo">
                    <div class="pv-card pv-square">
                      <div class="pv-bg" id="pvEstThumb"></div>
                      <div class="pv-overlay"><div class="pv-title-txt" id="pvEstSmallTitle" style="font-size:8px">Videojuegos</div></div>
                    </div>
                    <div class="pv-card pv-square">
                      <div class="pv-bg" id="pvEstThumb2"></div>
                      <div class="pv-overlay"><div class="pv-title-txt" style="font-size:8px;color:rgba(255,255,255,.6)">Anime</div></div>
                    </div>
                  </div>
                </div>
                <!-- Columna derecha: Lo que más esperamos (puesto 1) -->
                <div class="pv-rank">
                  <div class="pv-rank-title">Lo que más esperamos</div>
                  <div class="pv-rank-hero pv-card">
                    <div class="pv-bg" id="pvEspHero"></div>
                    <div class="pv-rank-ov"><div class="pv-rank-name" id="pvEspName">Título de la noticia</div></div>
                    <div class="pv-rank-score" id="pvEspScore">10.0</div>
                  </div>
                </div>
              </div>
              <div class="pv-label">Estreno principal (ancha) + videojuegos/anime (cuadradas) &middot; Lo que más esperamos</div>
            </div>

            <!-- Lo más debatido -->
            <div class="pv-panel" id="pv-debatido">
              <div class="pv-debatido-wrap">
                <div class="pv-debatido-row">
                  <div class="pv-card pv-square">
                    <div class="pv-bg" id="pvDebSmall"></div>
                    <div class="pv-overlay">
                      <span class="pv-chat"><i class="bi bi-chat-fill"></i> 12</span>
                      <div class="pv-title-txt" id="pvDebSmallTitle">Título de la noticia</div>
                    </div>
                  </div>
                  <div class="pv-card pv-square">
                    <div class="pv-bg" id="pvDebSmall2"></div>
                    <div class="pv-overlay">
                      <span class="pv-chat"><i class="bi bi-chat-fill"></i> 5</span>
                      <div class="pv-title-txt" style="color:rgba(255,255,255,.75)">Otra noticia</div>
                    </div>
                  </div>
                </div>
                <div class="pv-card pv-wide">
                  <div class="pv-bg" id="pvDebLarge"></div>
                  <div class="pv-overlay">
                    <span class="pv-chat"><i class="bi bi-chat-fill"></i> 18 comentarios</span>
                    <div class="pv-title-txt" id="pvDebTitle">Título de la noticia</div>
                  </div>
                </div>
              </div>
              <div class="pv-label">2 cuadradas arriba &middot; 1 ancha abajo</div>
            </div>

            <!-- Lo más Random -->
            <div class="pv-panel" id="pv-random">
              <div class="pv-random-card">
                <div class="pv-random-thumb pv-card"><div class="pv-bg" id="pvRandThumb"></div></div>
                <div class="pv-random-body">
                  <div class="pv-title-txt dark" id="pvRandTitle">Título de la noticia</div>
                  <div class="pv-desc-txt dark"  id="pvRandDesc">Descripción corta del artículo...</div>
                  <div class="pv-meta">ahora &middot; Por ti</div>
                </div>
              </div>
              <div class="pv-label">Lo más Random &middot; tarjeta vertical 16:9</div>
            </div>

            <!-- Noticias recientes -->
            <div class="pv-panel" id="pv-recientes">
              <div class="pv-hcard">
                <div class="pv-hcard-thumb pv-card">
                  <div class="pv-bg" id="pvRecThumb"></div>
                </div>
                <div class="pv-hcard-body">
                  <span class="pv-tag" id="pvRecCat">CATEGORÍA</span>
                  <div class="pv-title-txt dark" id="pvRecTitle">Título de la noticia</div>
                  <div class="pv-desc-txt dark"  id="pvRecDesc">Descripción corta del artículo...</div>
                  <div class="pv-meta">Publicado ahora &middot; Por ti</div>
                </div>
              </div>
              <div class="pv-label">Noticias recientes &middot; miniatura 16:9</div>
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
          <!-- BARRA DE ZOOM Y MODO ENFOQUE EXCLUSIVO PARA EL REDACTOR -->
          <div class="cn-editor-zoom-bar d-flex align-items-center justify-content-between p-2 mb-2" style="background: var(--bg); border: 1px solid var(--border); border-radius: 10px; font-size: 13px;">
            <div class="d-flex align-items-center gap-2">
              <span style="font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 4px;">
                <i class="bi bi-zoom-in" style="color: var(--accent);"></i> Zoom Redactor:
              </span>
              <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="adjustEditorZoom(-10)" title="Disminuir zoom (A-)">
                <i class="bi bi-dash-lg"></i>
              </button>
              <span id="editorZoomBadge" style="font-weight: 800; color: var(--accent); min-width: 45px; text-align: center;">100%</span>
              <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="adjustEditorZoom(10)" title="Aumentar zoom (A+)">
                <i class="bi bi-plus-lg"></i>
              </button>
              <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-1" onclick="resetEditorZoom()" style="font-size: 11px; text-decoration: underline;">
                Restablecer
              </button>
            </div>
            <div>
              <button type="button" id="btnFocusMode" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="toggleFocusMode()" style="font-weight: 700; font-size: 11px; border-radius: 8px;">
                <i class="bi bi-fullscreen me-1"></i> Modo Enfoque
              </button>
            </div>
          </div>

          <div class="document-editor">
            <div class="document-editor__toolbar"></div>
            <div class="document-editor__editable-container">
              <div id="editor" class="editor-content"><?= preg_replace('/<img(?![^>]*\bloading=)\s/i', '<img loading="lazy" ', $noticia['contenido']) ?></div>
            </div>
          </div>
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
  // Refrescar la vista previa cuando cambia el tipo/calificación (badge de Reviews)
  document.getElementById('tipo_publicacion')?.addEventListener('change', updateAllPreviews);
  document.getElementById('calificacion')?.addEventListener('input', updateAllPreviews);


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
    updateAllPreviews();
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

  /* ── Pre-cargar zonas con imágenes existentes ──
     Las <img> de preview ya vienen renderizadas desde el servidor dentro de
     cada zona (así cargan junto con el resto del HTML, sin quedar al final de
     la cola de descargas detrás de las imágenes del contenido). Aquí solo
     reponemos zoneSources —la fuente para re-recortar— y la vista previa. */
  if (EXISTING_CROP2_URL) {
    zoneSources[2] = EXISTING_CROP1_URL || EXISTING_CROP2_URL;
  }
  if (EXISTING_CROP4_URL) {
    zoneSources[4] = EXISTING_CROP1_URL || EXISTING_CROP2_URL || EXISTING_CROP4_URL;
  }
  if (EXISTING_CROP3_URL) {
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

  if (FECHA_EXISTENTE && schedDate && schedTime) {
    schedDate.value = FECHA_EXISTENTE.slice(0, 10);
    schedTime.value = FECHA_EXISTENTE.slice(11, 16);
  }
  // Programar desactivado por defecto si no está activo, campos ocultos/visibles según estado
  if (schedFields && schedToggle) {
    schedFields.style.display = schedToggle.checked ? '' : 'none';
  }

  schedToggle?.addEventListener('change', () => {
    if (schedFields) {
      schedFields.style.display = schedToggle.checked ? '' : 'none';
    }
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
    let editorHtml = '';
    if (window.editor) {
      editorHtml = window.editor.getData().trim();
    } else {
      const editorEl = document.querySelector('#editor .ck-editor__editable') || document.querySelector('#editor');
      editorHtml = editorEl ? editorEl.innerHTML.trim() : '';
    }
    const emptyEditor = !editorHtml || editorHtml === '<p><br></p>' || editorHtml === '<p></p>' || editorHtml === '';
    if (emptyEditor) errors.push('Contenido del artículo');
    // Guardar con imagenes a medio subir las graba sin src y se pierden.
    const pendientes = window.subidasPendientes ? window.subidasPendientes() : 0;
    if (pendientes > 0) {
      errors.push(`Espera: ${pendientes} imagen(es) del contenido siguen subiendo`);
    }
    return errors;
  }
  /* Si el servidor rechazó el guardado porque venían imágenes sin subir, se
     vuelve aquí con el aviso en la URL. Se muestra la versión guardada (la
     buena) para que se puedan reponer las imágenes que faltaron. */
  (function avisoSubidasIncompletas() {
    const params = new URLSearchParams(location.search);
    if (params.get('error') !== 'subidas_incompletas') return;
    const n = parseInt(params.get('pendientes') || '0', 10);
    setTimeout(() => showToast([
      `No se guardó: ${n || 'algunas'} imagen(es) del contenido no terminaron de subir.`,
      'Vuelve a insertarlas y espera a que carguen antes de guardar.'
    ]), 400);
  })();

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
    if (window.editor) {
      let html = window.editor.getData();
      html = html.replace(/<oembed url="([^"]+)"><\/oembed>/gi, 
                          '<div class="social-embed" data-url="$1"></div>');
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

    // Base crop1 de mayor resolución/calidad (2560px, JPEG 0.95): al reemplazar la imagen y
    // volver a recortar, se parte de una base más nítida y se evita la degradación acumulada.
    const origImg = new Image();
    origImg.onload = function() {
      const MAX = 2560;
      let w = origImg.naturalWidth, h = origImg.naturalHeight;
      if (w > MAX || h > MAX) {
        if (w >= h) { h = Math.round(h * MAX / w); w = MAX; }
        else        { w = Math.round(w * MAX / h); h = MAX; }
      }
      const tmpC = document.createElement('canvas');
      tmpC.width = w; tmpC.height = h;
      tmpC.getContext('2d').drawImage(origImg, 0, 0, w, h);
      document.getElementById('crop1').value = tmpC.toDataURL('image/jpeg', 0.95);
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
  // Prioriza la carga de los crops por encima de las (muchas) imágenes del
  // contenido, que de otro modo saturan el pool de conexiones del navegador
  // y dejan estas previews en cola hasta que se recarga la página.
  img.fetchPriority = 'high';
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

  let options = {
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high'
  };

  const cropNum = activeCrop;

  if (cropNum === 2 || cropNum === 4) {
    // Banner principal y secundario (21:6): forzar 2560px de ancho para excelente nitidez en pantallas Retina
    options.width = 2560;
    options.height = 731;
  } else if (cropNum === 3) {
    // Miniatura (16:9): forzar 1920px de ancho
    options.width = 1920;
    options.height = 1080;
  } else {
    options.maxWidth = 2560;
    options.maxHeight = 2560;
  }

  const canvas = cropperInstance.getCroppedCanvas(options);
  const srcForAuto = document.getElementById('cropImg').src;
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
  }, 'image/jpeg', 0.93);
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
  const c4    = document.getElementById('crop4').value;
  const title = document.getElementById('titulo')?.value.trim()       || 'Título de la noticia';
  const desc  = document.getElementById('descripcion')?.value.trim()  || 'Descripción corta del artículo...';
  const catEl = document.querySelector('#catChips .cn-chip-name');
  const cat   = catEl ? catEl.textContent.trim().toUpperCase() : 'CATEGORÍA';

  // Mapeo de recortes tal como los usa el index real:
  const cWide  = c4 || c2 || c3;   // tarjetas anchas   → img([crop4, crop2, crop1])
  const cThumb = c3 || c2;         // miniaturas/cuadr. → img([crop3, crop1])
  const cHero  = c2 || c3;         // slider principal  → img([crop2, crop1])

  const setTxt = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };

  // Calificación (badge / círculos de score): solo si es review con nota
  const esReview = document.getElementById('tipo_publicacion')?.value === 'review';
  const calif    = parseFloat(document.getElementById('calificacion')?.value);
  const showScore = esReview && !isNaN(calif);
  const scoreTxt  = showScore ? calif.toFixed(1) : '';
  const setScore = (id) => {
    const e = document.getElementById(id);
    if (!e) return;
    if (showScore) { e.textContent = scoreTxt; e.style.display = ''; }
    else { e.style.display = 'none'; }
  };

  // Principal (Hero / Slider)
  setPvBg('pvHeroBg', cHero);
  setTxt('pvHeroCat', cat); setTxt('pvHeroTitle', title); setTxt('pvHeroDesc', desc);

  // Top Semanal (ancha + cuadrada)
  setPvBg('pvTopMain', cWide);
  setPvBg('pvTopSide', cThumb);
  setTxt('pvTopCat', cat); setTxt('pvTopTitle', title);

  // Nuestras Reviews (miniatura + badge de calificación)
  setPvBg('pvRevThumb', cThumb);
  setTxt('pvRevCat', cat); setTxt('pvRevTitle', title); setTxt('pvRevDesc', desc);
  setScore('pvRevBadge');
  // Lo que más te recomendamos (puesto 1, usa recorte ancho como en el index)
  setPvBg('pvRecomHero', cWide);
  setTxt('pvRecomName', title);
  setScore('pvRecomScore');

  // Próximos Estrenos (ancha + 2 cuadradas)
  setPvBg('pvEstMain', cWide);
  setPvBg('pvEstThumb', cThumb);
  setPvBg('pvEstThumb2', cThumb);
  setTxt('pvEstCat', cat); setTxt('pvEstTitle', title);
  // Lo que más esperamos (puesto 1, usa recorte ancho como en el index)
  setPvBg('pvEspHero', cWide);
  setTxt('pvEspName', title);
  setScore('pvEspScore');

  // Lo más debatido (2 cuadradas + 1 ancha)
  setPvBg('pvDebSmall', cThumb);
  setPvBg('pvDebSmall2', cThumb);
  setPvBg('pvDebLarge', cWide);
  setTxt('pvDebSmallTitle', title); setTxt('pvDebTitle', title);

  // Lo más Random (vertical)
  setPvBg('pvRandThumb', cThumb);
  setTxt('pvRandTitle', title); setTxt('pvRandDesc', desc);

  // Noticias recientes (miniatura)
  setPvBg('pvRecThumb', cThumb);
  setTxt('pvRecCat', cat); setTxt('pvRecTitle', title); setTxt('pvRecDesc', desc);
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

<style>
/* Estilos de lujo para el Modal de Historial de Cambios */
.historial-modal-container {
  background: var(--card-bg, #ffffff);
  color: var(--text, #1e293b);
  border-radius: 16px;
  border: 1px solid var(--border, #e2e8f0);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  padding: 24px;
  max-width: 800px;
  width: 92%;
}

.historial-modal-card {
  background: var(--bg-card, #f8fafc);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 16px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

[data-bs-theme="dark"] .historial-modal-container {
  background: #181820;
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}

[data-bs-theme="dark"] .historial-modal-card {
  background: #20202a;
  border-color: rgba(255, 255, 255, 0.08);
}

.historial-badge-rev {
  background: linear-gradient(135deg, #EF3363 0%, #d62250 100%);
  color: #ffffff;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 800;
  box-shadow: 0 2px 8px rgba(239, 51, 99, 0.3);
}

.historial-badge-field {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  margin-right: 6px;
  margin-top: 4px;
}

.diff-toggle-btn {
  background: var(--card-bg, #ffffff);
  color: var(--text, #0f172a);
  border: 1px solid var(--border, #cbd5e1);
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  width: 100%;
  text-align: left;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 14px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

[data-bs-theme="dark"] .diff-toggle-btn {
  background: #181820;
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.15);
}

.diff-toggle-btn:hover {
  border-color: #EF3363;
  color: #EF3363;
  transform: translateY(-1px);
}

.diff-box-container {
  display: none;
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.diff-card-saved {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-left: 5px solid #f43f5e;
  padding: 14px 16px;
  border-radius: 10px;
}

[data-bs-theme="dark"] .diff-card-saved {
  background: rgba(244, 63, 94, 0.12);
  border-color: rgba(244, 63, 94, 0.25);
  border-left-color: #f43f5e;
}

.diff-card-live {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-left: 5px solid #22c55e;
  padding: 14px 16px;
  border-radius: 10px;
}

[data-bs-theme="dark"] .diff-card-live {
  background: rgba(34, 197, 94, 0.12);
  border-color: rgba(34, 197, 94, 0.25);
  border-left-color: #22c55e;
}

.diff-card-label-saved {
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #e11d48;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

[data-bs-theme="dark"] .diff-card-label-saved {
  color: #fda4af;
}

.diff-card-label-live {
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #16a34a;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

[data-bs-theme="dark"] .diff-card-label-live {
  color: #86efac;
}

.diff-card-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 4px;
}

[data-bs-theme="dark"] .diff-card-title {
  color: #f8fafc;
}

.diff-card-desc {
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.4;
}

[data-bs-theme="dark"] .diff-card-desc {
  color: #cbd5e1;
}

.diff-code-box {
  background: #0f172a;
  color: #f8fafc;
  border-radius: 10px;
  padding: 14px;
  border: 1px solid #1e293b;
}

.diff-code-title {
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #94a3b8;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.diff-code-pre {
  max-height: 140px;
  overflow-y: auto;
  color: #e2e8f0;
  white-space: pre-wrap;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.82rem;
  line-height: 1.5;
  background: #020617;
  padding: 12px;
  border-radius: 6px;
}
</style>

<!-- MODAL DE HISTORIAL DE CAMBIOS -->
<div id="historialModal" class="crop-modal" style="display: none; align-items: center; justify-content: center; z-index: 10000; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
  <div class="historial-modal-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border, #e2e8f0); padding-bottom: 14px;">
      <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: 800;">
        <i class="bi bi-clock-history" style="color: #EF3363;"></i> Historial de Modificaciones
      </h3>
      <button type="button" id="closeHistorialModal" style="background: none; border: none; color: var(--muted, #64748b); font-size: 1.6rem; cursor: pointer; line-height: 1;">&times;</button>
    </div>
    
    <div id="historialList" style="max-height: 520px; overflow-y: auto; padding-right: 6px;">
      <div style="text-align: center; color: var(--muted, #64748b); padding: 40px 0; font-weight: 600;">
        <i class="bi bi-arrow-repeat spin" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i> Cargando revisiones...
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const btnVerHistorial = document.getElementById('btnVerHistorial');
  const modal = document.getElementById('historialModal');
  const closeModal = document.getElementById('closeHistorialModal');
  const historialList = document.getElementById('historialList');
  const noticiaId = <?= intval($id) ?>;

  if (btnVerHistorial && modal) {
    btnVerHistorial.addEventListener('click', () => {
      modal.style.display = 'flex';
      cargarHistorial();
    });

    closeModal?.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
      if (e.target === modal) modal.style.display = 'none';
    });
  }

  function cargarHistorial() {
    historialList.innerHTML = `
      <div style="text-align: center; color: var(--muted, #64748b); padding: 40px 0; font-weight: 600;">
        <i class="bi bi-arrow-repeat spin" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i> Cargando revisiones...
      </div>`;
    
    fetch(BASE_PATH + `/controllers/obtener_historial_noticia.php?id=${noticiaId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success || !data.historial || data.historial.length === 0) {
          historialList.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: var(--muted, #64748b);">
              <i class="bi bi-info-circle" style="font-size: 2.5rem; color: #EF3363; opacity: 0.6; display: block; margin-bottom: 12px;"></i>
              <p style="margin: 0; font-size: 1.05rem; font-weight: 700;">No se registran revisiones anteriores de esta noticia todavía.</p>
              <span style="font-size: 0.85rem; opacity: 0.8; margin-top: 4px; display: block;">Cada vez que edites y guardes cambios, la versión previa quedará archivada aquí automáticamente.</span>
            </div>`;
          return;
        }

        const actual = data.actual || {};
        let html = '<div style="display: flex; flex-direction: column; gap: 14px;">';

        data.historial.forEach((ver, index) => {
          // Determinar versión posterior
          const newerVer = index > 0 ? data.historial[index - 1] : actual;
          const labelPosterior = index === 0 ? 'Publicación actual' : `Revisión #${data.historial.length - index + 1}`;

          const titleChanged = trim(ver.titulo) !== trim(newerVer.titulo || '');
          const descChanged = trim(ver.descripcion) !== trim(newerVer.descripcion || '');
          const contentChanged = trim(ver.contenido) !== trim(newerVer.contenido || '');

          let diffBadges = '';
          if (titleChanged) diffBadges += '<span class="historial-badge-field" style="color: #16a34a; border: 1px solid #bbf7d0; background: #f0fdf4;"><i class="bi bi-pencil-square"></i> Título</span>';
          if (descChanged) diffBadges += '<span class="historial-badge-field" style="color: #2563eb; border: 1px solid #bfdbfe; background: #eff6ff;"><i class="bi bi-card-text"></i> Descripción</span>';
          if (contentChanged) diffBadges += '<span class="historial-badge-field" style="color: #d97706; border: 1px solid #fde68a; background: #fffbeb;"><i class="bi bi-file-text"></i> Contenido</span>';
          if (ver.motivo_cambio && ver.motivo_cambio.includes('Imágenes')) {
            diffBadges += '<span class="historial-badge-field" style="color: #9333ea; border: 1px solid #e9d5ff; background: #faf5ff;"><i class="bi bi-image"></i> Imágenes</span>';
          }
          if (!diffBadges) diffBadges = '<span class="historial-badge-field" style="color: #64748b; background: #f1f5f9; border: 1px solid #e2e8f0;"><i class="bi bi-check-all"></i> Sin diferencias de texto</span>';

          html += `
            <div class="historial-modal-card">
              <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="historial-badge-rev">Revisión #${data.historial.length - index}</span>
                  <span style="font-size: 0.85rem; color: var(--muted, #64748b); font-weight: 600;"><i class="bi bi-clock"></i> ${ver.fecha_edicion}</span>
                </div>
                <button type="button" class="btn-restaurar-ver" data-version-id="${ver.id}" style="background: #EF3363; color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(239, 51, 99, 0.25); transition: transform 0.2s;">
                  <i class="bi bi-arrow-counterclockwise"></i> Restaurar esta versión
                </button>
              </div>
              
              <div style="font-size: 0.9rem; margin-bottom: 10px; color: var(--text, #1e293b);">
                <strong>Modificado por:</strong> <span style="font-weight: 700;">${escapeHtml(ver.usuario_nombre)}</span> &bull; 
                <span style="color: var(--muted, #64748b); font-style: italic;">${escapeHtml(ver.motivo_cambio)}</span>
              </div>

              <div style="margin-bottom: 12px;">
                <strong style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted, #64748b); letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Campos alterados:</strong>
                <div>${diffBadges}</div>
              </div>

              <!-- Botón Desplegable Diff -->
              <button type="button" class="diff-toggle-btn" onclick="toggleDiffBox(this)">
                <span><i class="bi bi-file-diff-fill" style="color: #EF3363; margin-right: 8px;"></i> Ver comparación detallada (${labelPosterior})</span>
                <i class="bi bi-chevron-down diff-chevron" style="transition: transform 0.3s ease;"></i>
              </button>

              <!-- Caja Desplegable Diff -->
              <div class="diff-box-container" style="display: none;">
                <div class="diff-card-saved">
                  <div class="diff-card-label-saved">
                    <i class="bi bi-dash-circle-fill"></i> Versión Archivada (#${data.historial.length - index})
                  </div>
                  <div class="diff-card-title">${escapeHtml(ver.titulo)}</div>
                  <div class="diff-card-desc">${escapeHtml(ver.descripcion)}</div>
                </div>

                <div class="diff-card-live">
                  <div class="diff-card-label-live">
                    <i class="bi bi-plus-circle-fill"></i> Reemplazado por (${labelPosterior})
                  </div>
                  <div class="diff-card-title">${escapeHtml(newerVer.titulo || ver.titulo)}</div>
                  <div class="diff-card-desc">${escapeHtml(newerVer.descripcion || ver.descripcion)}</div>
                </div>

                <div class="diff-code-box">
                  <div class="diff-code-title"><i class="bi bi-code-slash"></i> Contenido formateado guardado:</div>
                  <div class="diff-code-pre">${escapeHtml(ver.contenido)}</div>
                </div>
              </div>
            </div>
          `;
        });
        html += '</div>';
        historialList.innerHTML = html;

        // Listener para restaurar
        historialList.querySelectorAll('.btn-restaurar-ver').forEach(btn => {
          btn.addEventListener('click', function() {
            const verId = this.dataset.versionId;
            if (!confirm('¿Estás seguro de que deseas restaurar la noticia a esta versión previa? El estado actual será guardado como una revisión en el historial.')) return;

            fetch(BASE_PATH + '/controllers/restaurar_version_noticia.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ version_id: verId })
            })
            .then(r => r.json())
            .then(res => {
              if (res.success) {
                alert('¡Versión restaurada con éxito!');
                window.location.reload();
              } else {
                alert(res.error || 'Error al restaurar versión');
              }
            });
          });
        });
      })
      .catch(err => {
        historialList.innerHTML = '<div style="color: #EF3363; text-align: center; padding: 20px; font-weight: 700;">Error al cargar el historial.</div>';
      });
  }

  function trim(str) { return str ? str.trim() : ''; }

  function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
  }
});

function toggleDiffBox(btn) {
  const container = btn.nextElementSibling;
  const chevron = btn.querySelector('.diff-chevron');
  if (container.style.display === 'flex' || container.style.display === 'block') {
    container.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  } else {
    container.style.display = 'flex';
    if (chevron) chevron.style.transform = 'rotate(180deg)';
  }
}

/* ── ZOOM EXCLUSIVO Y MODO ENFOQUE PARA EL REDACTOR ── */
let currentEditorZoom = parseInt(localStorage.getItem('catink_editor_zoom') || '100');

function applyEditorZoom(zoomLevel) {
  currentEditorZoom = Math.min(Math.max(zoomLevel, 80), 220);
  localStorage.setItem('catink_editor_zoom', currentEditorZoom);
  const badge = document.getElementById('editorZoomBadge');
  if (badge) badge.textContent = currentEditorZoom + '%';
  
  const containerEl = document.querySelector('.document-editor__editable-container') || document.querySelector('#editor');
  if (containerEl) {
    const scale = currentEditorZoom / 100;
    containerEl.style.zoom = scale;
  }
}

function adjustEditorZoom(delta) {
  applyEditorZoom(currentEditorZoom + delta);
}

function resetEditorZoom() {
  applyEditorZoom(100);
}

function toggleFocusMode() {
  const docEditor = document.querySelector('.document-editor');
  const btn = document.getElementById('btnFocusMode');
  if (!docEditor) return;
  
  docEditor.classList.toggle('focus-mode-active');
  const isFocus = docEditor.classList.contains('focus-mode-active');
  if (btn) {
    btn.innerHTML = isFocus ? '<i class="bi bi-fullscreen-exit me-1"></i> Salir Enfoque' : '<i class="bi bi-fullscreen me-1"></i> Modo Enfoque';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => applyEditorZoom(currentEditorZoom), 600);
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
