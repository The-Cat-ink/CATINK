<?php
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__."/../controllers/aclcontroller.php");
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false,
    "leer"  => false,
    "editar"=> false,
    "eliminar" => false
];
proteger('noticias','crear');
include(__DIR__ . "/../data/conexion.php");
if (empty($ACL['crear'])) {
    header("Location: admin.php");
    exit();
}
?>
<script>const ACL = <?= json_encode($ACL) ?>;</script>
<?php
$categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
$categorias = [];
while($row = $categoriasResult->fetch_assoc()) $categorias[] = $row;

// ── Continuar borrador ──
// Solo cuando se entra por continuar_borrador.php (que ya validó el id). Entrar
// directo a crear.php siempre abre en blanco.
$borradorId = isset($BORRADOR_ID) ? intval($BORRADOR_ID) : 0;
$borradorData = null;
if ($borradorId > 0) {
    $stmtB = $con->prepare("
        SELECT id, titulo, descripcion, contenido, tipo_publicacion, calificacion, pros, contras,
               es_estreno, seccion_estreno, fecha_programada, crop1, crop2, crop3, crop4
        FROM noticias
        WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL
    ");
    $stmtB->bind_param("i", $borradorId);
    $stmtB->execute();
    $b = $stmtB->get_result()->fetch_assoc();

    if ($b) {
        $catsB = [];
        $rcB = $con->prepare("SELECT categoria_id FROM noticia_categoria WHERE noticia_id = ? ORDER BY orden ASC");
        $rcB->bind_param("i", $borradorId);
        $rcB->execute();
        $resCB = $rcB->get_result();
        while ($rowCB = $resCB->fetch_assoc()) $catsB[] = (int)$rowCB['categoria_id'];

        $cropsB = [];
        foreach (['crop1', 'crop2', 'crop3', 'crop4'] as $ck) {
            $cropsB[$ck] = !empty($b[$ck]) ? imageUrl($b[$ck]) : null;
        }

        $borradorData = [
            'id'               => (int)$b['id'],
            'titulo'           => $b['titulo'],
            'descripcion'      => $b['descripcion'],
            'contenido'        => $b['contenido'],
            'tipo_publicacion' => $b['tipo_publicacion'],
            'calificacion'     => $b['calificacion'],
            'pros'             => $b['pros'],
            'contras'          => $b['contras'],
            'es_estreno'       => (int)$b['es_estreno'],
            'seccion_estreno'  => $b['seccion_estreno'],
            // 'YYYY-MM-DD HH:MM' para repoblar el programador
            'fecha_programada' => !empty($b['fecha_programada']) ? date('Y-m-d H:i', strtotime($b['fecha_programada'])) : null,
            'categorias'       => $catsB,
            'crops'            => $cropsB,
        ];
    } else {
        $borradorId = 0;
    }
}
$esContinuacion = $borradorData !== null;
?>
<script>const BORRADOR = <?= $borradorData ? json_encode($borradorData) : 'null' ?>;</script>

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
.admin-container {
  max-width: none !important;
  padding: 0 !important;
}

/* Grid principal: izq = formulario (2fr), der = multimedia (3fr) */
.cn-wrap {
  width: 100%;
  margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
  gap: 16px;
  align-items: start;
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
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  transition: box-shadow .2s;
}
.cn-section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
.cn-section-header:hover { background: rgba(239,51,99,.04); }
.cn-section-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: rgba(239,51,99,0.12);
  color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.cn-section-title { font-size: 0.92rem; font-weight: 700; color: var(--text); margin: 0; }
.cn-section-sub   { font-size: 11px; color: var(--muted); margin: 0; }
.cn-section-toggle {
  margin-left: auto; color: var(--muted);
  font-size: 13px; flex-shrink: 0;
  transition: transform .25s;
}
.cn-section.collapsed .cn-section-toggle { transform: rotate(-90deg); }

/* Cuerpo colapsable */
.cn-section-body {
  overflow: hidden;
  max-height: 60000px;
  opacity: 1;
  transition: max-height .35s ease, opacity .25s ease;
  padding: 18px;
}
.cn-section.collapsed .cn-section-body {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
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
  width: 100%; padding: 9px 12px;
  border-radius: 8px; border: 1px solid var(--border);
  background: var(--bg); color: var(--text);
  font-size: 14px; transition: border-color .2s;
  font-family: inherit;
}
.cn-input:focus { outline: none; border-color: var(--accent); }
textarea.cn-input { resize: vertical; min-height: 80px; }

/* ── CATEGORÍAS ── */
.cn-cat-dropdown { position: relative; }
.cn-cat-trigger {
  width: 100%; padding: 9px 12px;
  border-radius: 8px; border: 1px solid var(--border);
  background: var(--bg); color: var(--text);
  font-size: 14px; cursor: pointer;
  display: flex; align-items: center; justify-content: space-between;
  user-select: none; transition: border-color .2s;
}
.cn-cat-trigger:hover { border-color: var(--accent); }
.cn-cat-trigger i { color: var(--muted); transition: transform .2s; }
.cn-cat-trigger.open i { transform: rotate(180deg); }
.cn-cat-menu {
  display: none;
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 4px 16px rgba(0,0,0,.15);
  padding: 6px; margin-top: 6px;
}
.cn-cat-menu.open { display: block; }
.cn-cat-option {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 10px; border-radius: 7px; cursor: pointer;
  font-size: 13px; color: var(--text); transition: background .15s;
}
.cn-cat-option:hover { background: rgba(239,51,99,.08); }
.cn-cat-option input[type="checkbox"] { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
.cn-cat-option.selected { font-weight: 600; color: var(--accent); }

.cn-cat-chips { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
.cn-chip {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 10px;
  background: rgba(239,51,99,.1); border: 1px solid rgba(239,51,99,.25);
  border-radius: 8px; font-size: 13px; font-weight: 600;
  color: var(--text); cursor: grab; user-select: none;
  transition: box-shadow .15s;
}
.cn-chip:active { cursor: grabbing; box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.cn-chip.drag-over { border: 2px dashed var(--accent); background: rgba(239,51,99,.2); }
.cn-chip-num {
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--accent); color: #fff;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cn-chip-name { flex: 1; }
.cn-chip-remove {
  background: none; border: none; color: var(--muted);
  cursor: pointer; padding: 0; font-size: 14px; line-height: 1;
}
.cn-chip-remove:hover { color: var(--accent); }
.cn-chip-drag { color: var(--muted); font-size: 14px; }
.cn-cat-empty { font-size: 12px; color: var(--muted); text-align: center; padding: 6px 0; }

/* ── UPLOAD ZONES ── */
.cn-zone-label {
  font-size: 11px; font-weight: 600; color: var(--muted);
  text-align: center; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em;
}
.upload-zone {
  border: 2px dashed var(--border); border-radius: 10px;
  background: var(--bg); display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 5px;
  cursor: pointer; position: relative; overflow: hidden;
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
  position: absolute; inset: 0;
  background: rgba(0,0,0,.5); display: flex;
  align-items: center; justify-content: center;
  color: #fff; opacity: 0; transition: opacity .2s;
  border-radius: 8px; font-size: 0.8rem; font-weight: 600;
  pointer-events: none;
}
.upload-zone.has-image:hover .zone-overlay { opacity: 1; }
.zone-ratio {
  font-size: 0.68rem; background: var(--border);
  border-radius: 4px; padding: 1px 6px; color: var(--muted);
}
.cn-zone-icon { font-size: 22px; }
.upload-zone.has-image .cn-zone-icon,
.upload-zone.has-image > :not(.preview-img):not(.zone-overlay):not(.zone-actions) { display: none; }
.cn-zone-original { height: 150px; }
/* Banner: ratio exacto 21:6 para ver la imagen tal como queda publicada */
.cn-zone-banner { aspect-ratio: 21/6; height: auto; min-height: 60px; }
/* Paisaje: mismo ratio que el banner (21:6) */
.cn-zone-paisaje {
  aspect-ratio: 21/6; height: auto;
  max-width: min(100%, 520px); margin: 0 auto;
}
/* Miniatura: ratio 16:9, acotada a un ancho cómodo centrada */
.cn-zone-mini {
  aspect-ratio: 16/9; height: auto;
  max-width: min(100%, 520px); margin: 0 auto;
}
.zone-actions {
  position: absolute; bottom: 6px; right: 6px;
  display: none; gap: 5px; z-index: 3;
}
.upload-zone.has-image .zone-actions { display: flex; }
.zone-btn {
  font-size: 11px; font-weight: 600; padding: 3px 9px;
  border-radius: 5px; border: none; cursor: pointer; line-height: 1.5;
  backdrop-filter: blur(4px);
}
.zone-btn-adjust { background: rgba(255,255,255,.18); color: #fff; }
.zone-btn-adjust:hover { background: rgba(255,255,255,.3); }
.zone-btn-remove { background: rgba(239,51,99,.75); color: #fff; }
.zone-btn-remove:hover { background: rgba(239,51,99,1); }

.cn-media-row1 { display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px; }
@media (max-width: 600px) { .cn-media-row1 { grid-template-columns: 1fr; } }

/* ── CROP MODAL ── */
.crop-modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.78); z-index: 2000;
  align-items: center; justify-content: center;
}
.crop-modal-overlay.open { display: flex; }
.crop-modal-box {
  background: var(--card-bg); border-radius: 14px;
  width: min(92vw, 700px); overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.crop-modal-head {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; border-bottom: 1px solid var(--border); font-weight: 700;
}
.crop-modal-head button {
  margin-left: auto; background: none; border: none;
  color: var(--muted); cursor: pointer; font-size: 1.2rem;
}
.crop-modal-head button:hover { color: var(--accent); }
.crop-modal-body { padding: 16px; }
.crop-area {
  width: 100%; overflow: hidden;
  border-radius: 8px; background: var(--bg);
}
.crop-area img { max-width: 100%; display: block; }
.crop-modal-foot {
  display: flex; gap: 10px; padding: 14px 18px;
  border-top: 1px solid var(--border); justify-content: flex-end;
}

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
.cn-editor-wrap .editor-toolbar {
  border-radius: 10px 10px 0 0 !important; border-left: none !important;
  border-right: none !important; border-top: none !important; margin-bottom: 0 !important;
}
.cn-editor-wrap .editor-toolbar.tb-pinned {
  position: fixed !important;
  top: 0; z-index: 500;
  border-radius: 0 !important;
  box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.cn-editor-wrap .editor-content { min-height: 80px; border-top: 1px solid var(--border); }
.cn-editor-wrap .ql-editor { min-height: 60px; }

/* ── PROGRAMAR ── */
.cn-schedule-inner {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.cn-schedule-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.cn-schedule-info h4 { font-size: 14px; font-weight: 700; color: var(--text); margin: 0 0 2px; }
.cn-schedule-info p  { font-size: 12px; color: var(--muted); margin: 0; }
.cn-toggle-wrap { display: flex; align-items: center; }
.cn-toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
.cn-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.cn-toggle-track {
  position: absolute; inset: 0; border-radius: 24px;
  background: var(--border); cursor: pointer; transition: background .2s;
}
.cn-toggle input:checked + .cn-toggle-track { background: var(--accent); }
.cn-toggle-thumb {
  position: absolute; width: 18px; height: 18px;
  background: #fff; border-radius: 50%; top: 3px; left: 3px;
  transition: left .2s; pointer-events: none;
  box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.cn-toggle input:checked ~ .cn-toggle-thumb { left: 23px; }
.cn-schedule-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cn-date-input {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; border: 1px solid var(--border);
  border-radius: 8px; background: var(--bg); color: var(--text); font-size: 13px;
}
.cn-date-input i { color: var(--muted); font-size: 15px; }
.cn-date-input input[type="date"],
.cn-date-input input[type="time"] {
  border: none; background: none; color: var(--text);
  font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit;
}


/* ── BOTÓN PUBLICAR ── */
.cn-publish-wrap {
  display: flex;
  justify-content: center;
  margin-top: 4px;
}
.cn-publish-btn {
  width: 100%; max-width: 400px;
  padding: 13px; background: var(--accent); color: #fff;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: background .2s, transform .15s; font-family: inherit;
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }
.cn-publish-btn:active { transform: translateY(0); }

/* ── TOAST VALIDACIÓN ── */
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

/* ── AUTOGUARDADO EN BORRADORES (indicador de estado) ── */
.cn-autosave-status {
  font-size: 11px; color: var(--muted); text-align: center; margin-top: 2px;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.cn-autosave-status::before { content: "✓"; color: #3fb950; font-weight: 700; }
</style>

<div class="admin-container">

  <div class="cn-breadcrumb">
    <?php if ($esContinuacion): ?>
      <a href="borradores.php">Borradores</a>
      <i class="bi bi-chevron-right"></i>
      <span>Continuar Borrador</span>
    <?php else: ?>
      <a href="contenidos.php">Contenido</a>
      <i class="bi bi-chevron-right"></i>
      <span>Crear Noticia</span>
    <?php endif; ?>
  </div>

  <h1 class="cn-page-title" style="text-align: center;">
    <?= $esContinuacion ? 'Continuar borrador' : 'Alta de noticia' ?>
  </h1>

  <form id="formPublicacion" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="autor" value="<?= $_SESSION['id_u'] ?? '' ?>">
    <input type="hidden" name="crop1" id="crop1">
    <input type="hidden" name="crop2" id="crop2">
    <input type="hidden" name="crop3" id="crop3">
    <input type="hidden" name="crop4" id="crop4">
    <input type="hidden" name="contenido" id="contenido">
    <input type="hidden" name="fecha_publicacion" id="fecha_publicacion_hidden">
    <input type="hidden" name="borrador" id="borrador_flag" value="0">
    <input type="hidden" name="draft_id" id="draft_id_hidden" value="0">

    <div class="cn-wrap">

      <div class="cn-left-col">
      <!-- INFORMACIÓN BÁSICA -->
      <div class="cn-section" id="sec-info">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-info-circle"></i></div>
          <div>
            <p class="cn-section-title">Información Básica</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-field">
            <label for="titulo">Título de la Noticia</label>
            <input class="cn-input" type="text" id="titulo" name="titulo" maxlength="80"
                   placeholder="Escribe un título impactante..." required>
            <div class="cn-hint"><span id="tituloCount">0</span>/80</div>
          </div>
          <div class="cn-field">
            <label for="descripcion">Descripción corta</label>
            <textarea class="cn-input" id="descripcion" name="descripcion" maxlength="150" rows="3"
                      placeholder="Resumen breve para redes sociales y buscadores..." required></textarea>
            <div class="cn-hint"><span id="descCount">0</span>/150</div>
          </div>
          <div class="cn-field">
            <label for="tipo_publicacion">Tipo de Publicación</label>
            <select class="cn-input" id="tipo_publicacion" name="tipo_publicacion" required>
              <option value="noticia" selected>Noticia</option>
              <option value="review">Reseña / Review</option>
            </select>
          </div>

          <div class="cn-field" style="display: flex; align-items: center; justify-content: space-between; margin-top: 15px; margin-bottom: 10px;">
            <div>
              <label for="es_estreno" style="margin-bottom: 2px;">¿Es Próximo Estreno?</label>
              <span style="font-size: 11px; color: var(--muted);">Marcar para mostrar en la sección de próximos estrenos</span>
            </div>
            <div class="cn-toggle-wrap">
              <label class="cn-toggle">
                <input type="checkbox" id="es_estreno" name="es_estreno" value="1">
                <div class="cn-toggle-track"></div>
                <div class="cn-toggle-thumb"></div>
              </label>
            </div>
          </div>
          <div class="cn-field" id="seccionEstrenoWrapper" style="display: none; margin-bottom: 15px;">
            <label for="seccion_estreno">Sección de Estreno</label>
            <select class="cn-input" id="seccion_estreno" name="seccion_estreno">
              <option value="">-- Selecciona una sección --</option>
              <option value="peliculas">Películas y Series</option>
              <option value="videojuegos">Videojuegos</option>
              <option value="anime">Anime</option>
            </select>
          </div>

          <div id="reviewFieldsWrapper" style="display: none; border-top: 1px dashed var(--border); padding-top: 15px; margin-top: 15px;">
            <div class="cn-field">
              <label for="calificacion">Calificación (1.0 - 10.0)</label>
              <div style="display: flex; align-items: center; gap: 12px;">
                <input type="range" class="form-range" id="calificacionRange" min="1.0" max="10.0" step="0.1" value="5.0" style="flex: 1; accent-color: var(--accent);">
                <input type="number" class="cn-input" id="calificacion" name="calificacion" min="1.0" max="10.0" step="0.1" placeholder="5.0" style="width: 80px; text-align: center; font-weight: 700;">
              </div>
            </div>
            <div class="cn-field">
              <label for="pros">Pros (Puntos Positivos - Uno por línea)</label>
              <textarea class="cn-input" id="pros" name="pros" rows="4" placeholder="Ejemplo:&#10;Excelente historia&#10;Animación fluida&#10;Banda sonora increíble"></textarea>
            </div>
            <div class="cn-field">
              <label for="contras">Contras (Puntos Negativos - Uno por línea)</label>
              <textarea class="cn-input" id="contras" name="contras" rows="4" placeholder="Ejemplo:&#10;Ritmo lento al inicio&#10;Desarrollo de personajes apresurado"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- CATEGORÍAS (debajo de info básica) -->
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
          <div>
            <p class="cn-section-title">Programar</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <div class="cn-schedule-inner">
            <div class="cn-schedule-row">
              <div class="cn-schedule-info">
                <p>Programa tu publicación o desactiva para publicar de inmediato.</p>
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
      <?php if (!empty($ACL['crear'])): ?>
      <div style="margin-top:12px; display:flex; flex-direction:column; gap:10px;">
        <button type="submit" class="cn-publish-btn" name="guardarNoticia">
          <i class="bi bi-send"></i> Publicar noticia
        </button>
        <div id="autosaveStatus" class="cn-autosave-status" style="display:none;"></div>
      </div>
      <?php endif; ?>

      </div><!-- /cn-left-col -->

      <!-- MULTIMEDIA -->
      <div class="cn-section" id="sec-media">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-images"></i></div>
          <div>
            <p class="cn-section-title">Multimedia</p>
            <p class="cn-section-sub">Haz clic en cada zona para subir y recortar</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">

          <!-- Banner -->
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

          <!-- Miniatura -->
          <div style="margin-bottom:14px;">
            <p class="cn-zone-label">Miniatura <span style="text-transform:none;font-size:10px;font-weight:400;color:var(--muted)">(se auto-genera del banner — clic para cambiar)</span></p>
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
          <div>
            <p class="cn-section-title">Contenido</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <!-- BARRA DE ZOOM ESTILO WORD (DESLIZADOR 50% - 260%) -->
          <div class="cn-word-zoom-bar d-flex align-items-center justify-content-between px-3 py-2 mb-2" style="background: var(--bg); border: 1px solid var(--border); border-radius: 10px; font-size: 13px;">
            <div class="d-flex align-items-center gap-2">
              <span style="font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-file-earmark-richtext" style="color: var(--accent); font-size: 1.1rem;"></i> Zoom Hoja de Redacción:
              </span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="adjustEditorZoom(-10)" title="Disminuir zoom">
                <i class="bi bi-dash-lg"></i>
              </button>
              <input type="range" id="wordZoomRange" min="50" max="260" step="5" value="100" style="width: 140px; cursor: pointer; accent-color: var(--accent);" oninput="applyEditorZoom(this.value)">
              <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="adjustEditorZoom(10)" title="Aumentar zoom">
                <i class="bi bi-plus-lg"></i>
              </button>
              <span id="editorZoomBadge" style="font-weight: 800; color: var(--accent); min-width: 45px; text-align: center; font-size: 13px;">100%</span>
              <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-0 ms-1" onclick="resetEditorZoom()" style="font-size: 11px; border-radius: 6px;">
                100%
              </button>
              <button type="button" id="btnFocusMode" class="btn btn-sm btn-outline-danger px-2 py-1 ms-2" onclick="toggleFocusMode()" style="font-weight: 700; font-size: 11px; border-radius: 8px;">
                <i class="bi bi-fullscreen me-1"></i> Modo Enfoque
              </button>
            </div>
          </div>

          <div class="document-editor">
            <div class="document-editor__toolbar"></div>
            <div class="document-editor__editable-container">
              <div id="editor" class="editor-content"></div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /cn-wrap -->
  </form>
</div><!-- /admin-container -->

<!-- ── TOAST VALIDACIÓN ── -->
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

<!-- ── MODAL HORA INVÁLIDA ── -->
<div id="timeModalOverlay" class="crop-modal" style="display:none;">
  <div class="crop-modal-content">
    <h3>Hora no válida</h3>
    <p>La fecha y hora seleccionadas es menor a la actual.<br><br>¿Qué deseas hacer?</p>
    <div class="modal-actions">
      <button class="btn-accent" id="autoAdjustBtn" type="button">Ajustar automáticamente y guardar</button>
      <button class="btn-secondary" id="manualAdjustBtn" type="button">Volver a ajustar la hora</button>
    </div>
  </div>
</div>

<script>
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
      // Evitar que el click en inputs/selects dentro del header colapse
      if (e.target.closest('input, select, button, a')) return;
      header.closest('.cn-section').classList.toggle('collapsed');
    });
  });

  /* ── Contadores ── */
  const titulo = document.getElementById('titulo');
  const tCount = document.getElementById('tituloCount');
  titulo?.addEventListener('input', () => { tCount.textContent = titulo.value.length; updateAllPreviews(); });
  document.getElementById('descripcion')?.addEventListener('input', updateAllPreviews);
  // Refrescar la vista previa cuando cambia el tipo/calificación (badge de Reviews)
  document.getElementById('tipo_publicacion')?.addEventListener('change', updateAllPreviews);
  document.getElementById('calificacion')?.addEventListener('input', updateAllPreviews);

  const desc   = document.getElementById('descripcion');
  const dCount = document.getElementById('descCount');
  desc?.addEventListener('input', () => { dCount.textContent = desc.value.length; });

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


  /* ── Programar toggle ── */
  const schedToggle = document.getElementById('scheduleToggle');
  const schedFields = document.getElementById('scheduleFields');
  const schedDate   = document.getElementById('schedDate');
  const schedTime   = document.getElementById('schedTime');

  function nowLocal() {
    const now = new Date();
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0,16);
  }
  const ln = nowLocal();
  schedDate.value = ln.slice(0,10);
  schedTime.value = ln.slice(11,16);

  schedFields.style.display = 'none';
  schedToggle?.addEventListener('change', () => {
    schedFields.style.display = schedToggle.checked ? '' : 'none';
  });

  /* ── Submit ── */
  const form         = document.getElementById('formPublicacion');
  const hiddenFecha  = document.getElementById('fecha_publicacion_hidden');
  const contenidoHid = document.getElementById('contenido');

  let _submitting = false;

  function validateForm() {
    const errors = [];
    if (!document.getElementById('titulo').value.trim())
      errors.push('Título de la noticia');
    if (!document.getElementById('descripcion').value.trim())
      errors.push('Descripción corta');
    if (!document.getElementById('crop2').value && !document.getElementById('crop3').value)
      errors.push('Imagen (banner y miniatura)');
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

  function showToast(errors) {
    const toast = document.getElementById('cnToast');
    const list  = document.getElementById('cnToastList');
    list.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('show'), 5000);
  }

  form?.addEventListener('submit', e => {
    e.preventDefault(); 
    // Todo lo maneja el boton guardarNoticia click event
  });

  /* ── Modal hora inválida ── */
  const modalTime       = document.getElementById('timeModalOverlay');
  const autoAdjustBtn   = document.getElementById('autoAdjustBtn');
  const manualAdjustBtn = document.getElementById('manualAdjustBtn');

  const borradorFlag = document.getElementById('borrador_flag');

  document.getElementsByName('guardarNoticia')[0]?.addEventListener('click', e => {
    e.preventDefault();
    if (_submitting) return;
    const errors = validateForm();
    if (errors.length) { showToast(errors); return; }
    borradorFlag.value = '0';

    // Actualizamos valores ocultos
    if (window.editor) {
      let html = window.editor.getData();
      html = html.replace(/<oembed url="([^"]+)"><\/oembed>/gi, 
                          '<div class="social-embed" data-url="$1"></div>');
      html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                          '<div class="social-embed" data-url="$1"></div>');
      contenidoHid.value = html;
    }
    hiddenFecha.value = schedToggle?.checked
      ? (schedDate.value + 'T' + schedTime.value).replace('T',' ')
      : nowLocal().replace('T',' ');

    if (!schedToggle?.checked) { 
        submitAjax(); 
        return; 
    }
    const selected = schedDate.value + ' ' + schedTime.value;
    if (selected < nowLocal().replace('T',' ')) { modalTime.style.display = 'flex'; }
    else { submitAjax(); }
  });

  autoAdjustBtn?.addEventListener('click', () => {
    if (_submitting) return;
    autoAdjustBtn.disabled = true;
    const l = nowLocal();
    schedDate.value = l.slice(0,10); schedTime.value = l.slice(11,16);
    hiddenFecha.value = schedDate.value + ' ' + schedTime.value;
    modalTime.style.display = 'none'; 
    submitAjax();
  });
  manualAdjustBtn?.addEventListener('click', () => { modalTime.style.display = 'none'; });
  document.querySelector('#timeModalOverlay .crop-modal-content')
    ?.addEventListener('click', e => e.stopPropagation());

  // === LA VERDADERA MAGIA AJAX ===
  function submitAjax() {
      if (_submitting) return;
      _submitting = true;

      const publishBtn = document.querySelector('.cn-publish-btn');
      const originalHtml = publishBtn.innerHTML;
      publishBtn.disabled = true;
      publishBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px;"></span> Guardando...';

      const formData = new FormData(form);

      // Limpiamos los arrays raros de categorías para el Ajax (FormData ya los agrupa bien por nombre[] o podemos armarlos manual)
      // Como ya existen inputs hidden con name="categoria[]" se van bien.

      fetch('./../controllers/noticiascontroller.php', {
          method: 'POST',
          body: formData
      })
      .then(async response => {
          const text = await response.text();
          try {
              const data = JSON.parse(text);
              if (data.success) {
                  if (window.__clearBorradorPtr) window.__clearBorradorPtr();
                  window.location.href = './contenidos.php?msg=creado';
              } else {
                  // Falló alguna validación interna
                  showToast(["Error del servidor: " + (data.error || "Datos inválidos")]);
                  publishBtn.disabled = false;
                  publishBtn.innerHTML = originalHtml;
                  _submitting = false;
              }
          } catch(e) {
              // El controlador antiguo (si no lo hemos modificado) hace "header(Location: ...)" 
              // en lugar de enviar JSON. Por lo tanto, si la URL no fue JSON sino un redirect OK:
              if (response.ok) {
                  // Redirección silenciosa o 200 sin JSON: redirigimos por seguridad
                  if (window.__clearBorradorPtr) window.__clearBorradorPtr();
                  window.location.href = './contenidos.php?msg=creado';
              } else {
                  console.error("Respuesta fallida del servidor:", text);
                  showToast(["Error fatal de conexión. Revisa los permisos de carpeta."]);
                  publishBtn.disabled = false;
                  publishBtn.innerHTML = originalHtml;
                  _submitting = false;
              }
          }
      })
      .catch(err => {
          console.error("Error Fetch:", err);
          showToast(["Se perdió la conexión. Revisa tu internet."]);
          publishBtn.disabled = false;
          publishBtn.innerHTML = originalHtml;
          _submitting = false;
      });
  }

  /* ── Toolbar fija al hacer scroll ── */
  (function () {
    const wrap    = document.querySelector('.cn-editor-wrap');
    const toolbar = wrap?.querySelector('.editor-toolbar');
    if (!wrap || !toolbar) return;
    let spacer = null;

    function pin() {
      if (spacer) return;
      const tbH = toolbar.offsetHeight;
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
      } else {
        unpin();
      }
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
  const cropImg = document.getElementById('cropImg');
  const cropArea = document.querySelector('.crop-area');
  const maxW = cropArea.clientWidth || 640;
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
          const availX = imgData.width - cbW;
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
          const leftOffset = imgData.left + availX / 2;
          cropperInstance.setCropBoxData({
            width:  cbW,
            height: cbH,
            left:   leftOffset,
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
  const cropImg = document.getElementById('cropImg');
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
  if (window.__autosaveTrigger) window.__autosaveTrigger();  // reflejar que se quitaron las imágenes
}
function onFileSelected(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const fullSrc = ev.target.result;

    // Guardar original escalado en crop1 para que editar.php pueda re-recortar desde el original.
    // Base de mayor resolución/calidad (2560px, JPEG 0.95): al editar, los recortes se regeneran
    // desde crop1, así que una base más nítida evita la degradación acumulada en cada edición.
    // Sigue siendo JPEG (mismo formato de siempre) y es una sola imagen, no impacta el peso del POST.
    const origImg = new Image();
    origImg.onload = function() {
      const MAX = 2560;
      let w = origImg.naturalWidth, h = origImg.naturalHeight;
      if (w > MAX || h > MAX) {
        if (w >= h) { h = Math.round(h * MAX / w); w = MAX; }
        else { w = Math.round(w * MAX / h); h = MAX; }
      }
      const tmpC = document.createElement('canvas');
      tmpC.width = w; tmpC.height = h;
      tmpC.getContext('2d').drawImage(origImg, 0, 0, w, h);
      document.getElementById('crop1').value = tmpC.toDataURL('image/jpeg', 0.95);
    };
    origImg.src = fullSrc;

    zoneSources[activeCrop] = fullSrc;
    const cropImg = document.getElementById('cropImg');
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
  const cropImg = document.getElementById('cropImg');
  const cropArea = document.querySelector('.crop-area');
  if (!cropArea.contains(cropImg)) cropArea.appendChild(cropImg);
  cropImg.src = '';
  cropArea.style.height = '';  // resetear para la próxima imagen
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
  const chain     = _chainNextCrop;
  _chainNextCrop  = false;
  zoneCropData[cropNum] = cropperInstance.getData();
  closeCrop();

  canvas.toBlob(function(blob) {
    const reader = new FileReader();
    reader.onloadend = function() {
      const data64 = reader.result;
      document.getElementById('crop' + cropNum).value = data64;
      setZonePreview(cropNum, data64);
      if (window.__autosaveTrigger) window.__autosaveTrigger();  // guardar la imagen recién recortada

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

function autoFillMiniature(srcDataUrl) {
  zoneSources[3] = srcDataUrl;
  const tmpImg = new Image();
  tmpImg.onload = function () {
    const ratio = 16 / 9;
    let sw = tmpImg.width, sh = tmpImg.height;
    if (sw / sh > ratio) { sw = sh * ratio; } else { sh = sw / ratio; }
    const sx = (tmpImg.width  - sw) / 2;
    const sy = (tmpImg.height - sh) / 2;
    const canvas = document.createElement('canvas');
    // 2560px: la miniatura 16:9 también alimenta el hero del artículo, que en pantallas
    // HiDPI se muestra a ~2x; con más resolución deja de verse suave. Mismo formato (PNG 16:9).
    canvas.width  = Math.min(Math.round(sw), 2560);
    canvas.height = Math.round(canvas.width / ratio);
    const ctx3 = canvas.getContext('2d');
    ctx3.imageSmoothingEnabled = true;
    ctx3.imageSmoothingQuality = 'high';
    ctx3.drawImage(tmpImg, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    const data64 = canvas.toDataURL('image/png');
    document.getElementById('crop3').value = data64;
    setZonePreview(3, data64);
    document.getElementById('previewSection').style.display = 'block';
    updateAllPreviews();
  };
  tmpImg.src = srcDataUrl;
}
function autoFillBanner(srcDataUrl) {
  zoneSources[2] = srcDataUrl;
  const tmpImg = new Image();
  tmpImg.onload = function () {
    const ratio = 21 / 6;
    let sw = tmpImg.width, sh = tmpImg.height;
    if (sw / sh > ratio) { sw = sh * ratio; } else { sh = sw / ratio; }
    const sx = (tmpImg.width  - sw) / 2;
    const sy = (tmpImg.height - sh) / 2;
    const canvas = document.createElement('canvas');
    canvas.width  = Math.min(Math.round(sw), 2560);
    canvas.height = Math.round(canvas.width / ratio);
    const ctx2 = canvas.getContext('2d');
    ctx2.imageSmoothingEnabled = true;
    ctx2.imageSmoothingQuality = 'high';
    ctx2.drawImage(tmpImg, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    const data64 = canvas.toDataURL('image/png');
    document.getElementById('crop2').value = data64;
    setZonePreview(2, data64);
    document.getElementById('previewSection').style.display = 'block';
    updateAllPreviews();
  };
  tmpImg.src = srcDataUrl;
}

/* ── FUNCIONES DE VISTA PREVIA ── */
function setPvBg(id, data64) {
  const el = document.getElementById(id);
  if (!el) return;
  el.style.backgroundImage = data64 ? `url(${data64})` : '';
}
function updateAllPreviews() {
  const c2 = document.getElementById('crop2').value;
  const c3 = document.getElementById('crop3').value;
  const c4 = document.getElementById('crop4').value;
  const title = document.getElementById('titulo')?.value.trim() || 'Título de la noticia';
  const desc  = document.getElementById('descripcion')?.value.trim() || 'Descripción corta del artículo...';
  const catEl = document.querySelector('#catChips .cn-chip-name');
  const cat   = catEl ? catEl.textContent.trim().toUpperCase() : 'CATEGORÍA';

  // Mapeo de recortes tal como los usa el index real:
  const cWide  = c4 || c2 || c3;   // tarjetas anchas   → img([crop4, crop2, crop1])
  const cThumb = c3 || c2;         // miniaturas/cuadr. → img([crop3, crop1])
  const cHero  = c2 || c3;         // slider principal  → img([crop2, crop1])

  const setTxt = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };

  // Principal (Hero / Slider)
  setPvBg('pvHeroBg', cHero);
  setTxt('pvHeroCat', cat); setTxt('pvHeroTitle', title); setTxt('pvHeroDesc', desc);

  // Top Semanal (ancha + cuadrada)
  setPvBg('pvTopMain', cWide);
  setPvBg('pvTopSide', cThumb);
  setTxt('pvTopCat', cat); setTxt('pvTopTitle', title);

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

/* ── TABS DE VISTA PREVIA ── */
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

<!-- ════════════════════════════════
     AUTOGUARDADO EN BORRADORES (servidor)
     Mientras escribes se crea/actualiza un borrador real (borrador = 1) que
     aparece en "Borradores", con texto E imágenes. Si la sesión se interrumpe
     (recarga, cierre de pestaña, corte de luz o de internet), "Crear noticia"
     vuelve a abrir EN BLANCO: el trabajo no se reanuda aquí, se continúa desde
     el apartado "Borradores". Al publicar se corta el autoguardado.
════════════════════════════════ -->
<script>
(function () {
  const form = document.getElementById('formPublicacion');
  if (!form) return;
  const $ = id => document.getElementById(id);

  const draftInput = $('draft_id_hidden');
  let draftId   = 0;
  let dirty     = false;
  let saving    = false;
  let suppress  = false;                        // no guardar mientras limpiamos
  let stopped   = false;                         // cortar autoguardado tras publicar
  let lastSig   = '';
  let timer     = null;
  const lastCrops = { crop1: null, crop2: null, crop3: null, crop4: null };

  function editorHtml() {
    if (window.editor && typeof window.editor.getData === 'function') return window.editor.getData();
    return ($('contenido') && $('contenido').value) || '';
  }
  function cats() {
    return Array.from(document.querySelectorAll('#catInputs input[name="categoria[]"]')).map(i => i.value);
  }
  function hasAnyImage() {
    return ['crop1', 'crop2', 'crop3', 'crop4'].some(ck => ($(ck)?.value || '').indexOf('data:image/') === 0);
  }
  function meaningful() {
    const t = ($('titulo')?.value || '').trim();
    const c = editorHtml().replace(/<[^>]*>/g, '').trim();
    const d = ($('descripcion')?.value || '').trim();
    return !!(t || c || d || hasAnyImage());
  }

  // Devuelve los crops que cambiaron desde el último guardado (para no re-subir).
  function changedCrops() {
    const out = {};
    ['crop1', 'crop2', 'crop3', 'crop4'].forEach(ck => {
      const v = $(ck)?.value || '';
      if (v && v.indexOf('data:image/') === 0 && v !== lastCrops[ck]) out[ck] = v;
    });
    return out;
  }

  // Fecha del programador, si está activado. Se guarda en el borrador para no
  // tener que volver a programar la nota al continuarla.
  function fechaProgramada() {
    if (!$('scheduleToggle')?.checked) return '';
    const d = $('schedDate')?.value || '';
    const t = $('schedTime')?.value || '';
    return (d && t) ? (d + ' ' + t) : '';
  }

  function payload(cropsToSend) {
    const p = new URLSearchParams();
    p.set('draft_id',         draftId);
    p.set('fecha_programada', fechaProgramada());
    p.set('titulo',           $('titulo')?.value || '');
    p.set('descripcion',      $('descripcion')?.value || '');
    p.set('contenido',        editorHtml());
    p.set('tipo_publicacion', $('tipo_publicacion')?.value || 'noticia');
    p.set('calificacion',     $('calificacion')?.value || '');
    p.set('pros',             $('pros')?.value || '');
    p.set('contras',          $('contras')?.value || '');
    p.set('es_estreno',       $('es_estreno')?.checked ? '1' : '0');
    p.set('seccion_estreno',  $('seccion_estreno')?.value || '');
    cats().forEach(c => p.append('categoria[]', c));
    Object.keys(cropsToSend).forEach(ck => p.set(ck, cropsToSend[ck]));
    return p;
  }

  function setStatus(txt) { const el = $('autosaveStatus'); if (el) { el.textContent = txt; el.style.display = ''; } }
  function hora() { return new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }); }

  async function save() {
    if (saving || suppress || stopped || !meaningful()) return;
    const crops = changedCrops();
    // Firma solo del texto (sin base64); los crops se envían aparte si cambiaron.
    const sig = payload({}).toString();
    if (sig === lastSig && Object.keys(crops).length === 0) { dirty = false; return; }
    saving = true;
    setStatus('Guardando…');
    try {
      const res  = await fetch('../controllers/autoguardar_borrador.php', { method: 'POST', body: payload(crops) });
      const data = await res.json();
      if (data && data.ok) {
        if (data.id) {
          draftId = data.id;
          if (draftInput) draftInput.value = draftId;
        }
        Object.keys(crops).forEach(ck => { lastCrops[ck] = crops[ck]; });
        lastSig = sig;
        dirty = false;
        setStatus('Guardado en Borradores · ' + hora());
      } else {
        setStatus('No se pudo autoguardar · reintentando…');
      }
    } catch (e) {
      setStatus('Sin conexión · se reintentará');
    } finally {
      saving = false;
    }
  }

  function schedule() { if (suppress || stopped) return; dirty = true; clearTimeout(timer); timer = setTimeout(save, 1500); }

  // ── Arrancar siempre en blanco ──
  // Al recargar, el navegador repuebla por su cuenta los campos que el usuario
  // había escrito. Como el trabajo ya quedó a salvo en "Borradores", aquí lo
  // borramos para que "Crear noticia" siempre empiece de cero.
  function limpiarFormulario() {
    suppress = true;

    ['titulo', 'descripcion', 'calificacion', 'pros', 'contras'].forEach(id => {
      const el = $(id);
      if (el && el.value !== '') { el.value = ''; el.dispatchEvent(new Event('input', { bubbles: true })); }
    });

    const tipo = $('tipo_publicacion');
    if (tipo && tipo.value !== 'noticia') { tipo.value = 'noticia'; tipo.dispatchEvent(new Event('change', { bubbles: true })); }

    const estreno = $('es_estreno');
    if (estreno && estreno.checked) { estreno.checked = false; estreno.dispatchEvent(new Event('change', { bubbles: true })); }

    const prog = $('scheduleToggle');
    if (prog && prog.checked) { prog.checked = false; prog.dispatchEvent(new Event('change', { bubbles: true })); }

    // Categorías marcadas (dispara el 'change' para que se quiten los chips)
    document.querySelectorAll('#catMenu input[type="checkbox"]:checked').forEach(chk => {
      chk.checked = false;
      chk.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Imágenes recortadas y editor
    ['crop1', 'crop2', 'crop3', 'crop4'].forEach(ck => { if ($(ck)) $(ck).value = ''; });
    if ($('contenido')) $('contenido').value = '';
    (function vaciarEditor() {
      if (window.editor && typeof window.editor.setData === 'function') window.editor.setData('');
      else setTimeout(vaciarEditor, 200);
    })();

    lastSig = '';
    dirty = false;
    suppress = false;
  }

  // ── Continuar un borrador (se entró por continuar_borrador.php) ──
  function urlToBase64(url) {
    return fetch(url)
      .then(r => r.blob())
      .then(blob => new Promise(res => { const fr = new FileReader(); fr.onloadend = () => res(fr.result); fr.readAsDataURL(blob); }));
  }

  async function precargar(d) {
    suppress = true;                       // no reguardar mientras repoblamos
    draftId = d.id;                        // el autoguardado sigue sobre ESTE borrador
    if (draftInput) draftInput.value = draftId;

    const setEl = (id, val, ev) => { const el = $(id); if (!el) return; el.value = val ?? ''; if (ev) el.dispatchEvent(new Event(ev, { bubbles: true })); };
    setEl('titulo', d.titulo, 'input');
    setEl('descripcion', d.descripcion, 'input');
    setEl('tipo_publicacion', d.tipo_publicacion || 'noticia', 'change');
    if (d.calificacion) setEl('calificacion', d.calificacion, 'input');
    setEl('pros', d.pros);
    setEl('contras', d.contras);
    if ($('es_estreno')) { $('es_estreno').checked = !!d.es_estreno; $('es_estreno').dispatchEvent(new Event('change', { bubbles: true })); }
    if (d.seccion_estreno) setEl('seccion_estreno', d.seccion_estreno, 'change');

    // Programación: si el borrador ya venía programado, no hay que volver a programarlo
    if (d.fecha_programada && $('scheduleToggle')) {
      $('scheduleToggle').checked = true;
      $('scheduleToggle').dispatchEvent(new Event('change', { bubbles: true }));
      setEl('schedDate', d.fecha_programada.slice(0, 10));
      setEl('schedTime', d.fecha_programada.slice(11, 16));
    }

    // Contenido, en cuanto el editor esté listo
    (function aplicar() {
      if (window.editor && typeof window.editor.setData === 'function') window.editor.setData(d.contenido || '');
      else setTimeout(aplicar, 200);
    })();

    // Categorías (el 'change' reconstruye los chips)
    (d.categorias || []).forEach(id => {
      const chk = document.querySelector('#catMenu input[type="checkbox"][value="' + id + '"]');
      if (chk && !chk.checked) { chk.checked = true; chk.dispatchEvent(new Event('change', { bubbles: true })); }
    });

    // Imágenes: los crops guardados vuelven a base64 para las zonas y el preview
    let algunaImg = false;
    for (const ck of ['crop1', 'crop2', 'crop3', 'crop4']) {
      const url = d.crops ? d.crops[ck] : null;
      if (!url) continue;
      try {
        const b64 = await urlToBase64(url);
        if ($(ck)) $(ck).value = b64;
        lastCrops[ck] = b64;                        // ya está guardado: no re-subirlo
        const zona = ck.replace('crop', '');
        if (typeof setZonePreview === 'function' && zona !== '1') setZonePreview(zona, b64);
        algunaImg = true;
      } catch (e) { /* imagen no disponible: continuar */ }
    }
    if (algunaImg) { const pv = $('previewSection'); if (pv) pv.style.display = 'block'; }
    if (typeof updateAllPreviews === 'function') updateAllPreviews();

    lastSig = payload({}).toString();
    dirty = false;
    suppress = false;
    setStatus('Continuando borrador · guardado ' + hora());
  }

  // Enganches de guardado
  form.addEventListener('input', schedule);
  form.addEventListener('change', schedule);
  (function hookEditor() {
    if (window.editor && window.editor.model) window.editor.model.document.on('change:data', schedule);
    else setTimeout(hookEditor, 300);
  })();
  setInterval(() => { if (dirty) save(); }, 20000);

  // Disparador para acciones discretas que NO emiten 'input' (recortar/quitar
  // imágenes fijan el valor por JS). Guarda pronto para no perder la imagen.
  window.__autosaveTrigger = function () { if (suppress || stopped) return; dirty = true; clearTimeout(timer); timer = setTimeout(save, 600); };

  // Red de seguridad ante recargas rápidas: volcar el texto pendiente al ocultar
  // la página (sendBeacon no espera respuesta). Las imágenes ya se guardan al recortar.
  function beaconFlush() {
    if (stopped || !dirty || !meaningful()) return;
    try {
      const fd = new FormData();
      for (const [k, v] of payload({}).entries()) fd.append(k, v);
      navigator.sendBeacon('../controllers/autoguardar_borrador.php', fd);
    } catch (e) {}
  }
  window.addEventListener('pagehide', beaconFlush);
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') beaconFlush(); });

  // Al publicar con éxito, cortar el autoguardado (evita que el beacon de
  // pagehide recree el borrador que el controlador acaba de borrar).
  window.__clearBorradorPtr = function () { stopped = true; clearTimeout(timer); dirty = false; };

  // Puntero de la versión anterior (reanudación): ya no se usa.
  try { localStorage.removeItem('catink_borrador_id'); } catch (e) {}

  // Al cargar: si venimos de "Continuar borrador", repoblar ese borrador; si no,
  // arrancar en blanco (lo escrito antes ya vive en "Borradores").
  //
  // Debe correr DESPUÉS del init de la vista (que engancha los chips de
  // categoría y los previews en DOMContentLoaded, y precarga el programador con
  // la fecha de hoy). Si lo hiciéramos ahora, en pleno parseo, los eventos que
  // dispara la precarga no tendrían quien los escuche y el init pisaría la
  // fecha programada del borrador.
  function iniciar() {
    if (typeof BORRADOR !== 'undefined' && BORRADOR) precargar(BORRADOR);
    else limpiarFormulario();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar, { once: true });
  else iniciar();
})();

/* ── ZOOM EXCLUSIVO Y MODO ENFOQUE PARA EL REDACTOR ── */
let currentEditorZoom = parseInt(localStorage.getItem('catink_editor_zoom') || '100');

function applyEditorZoom(zoomLevel) {
  currentEditorZoom = Math.min(Math.max(parseInt(zoomLevel), 50), 260);
  localStorage.setItem('catink_editor_zoom', currentEditorZoom);
  
  const badge = document.getElementById('editorZoomBadge');
  if (badge) badge.textContent = currentEditorZoom + '%';
  
  const range = document.getElementById('wordZoomRange');
  if (range) range.value = currentEditorZoom;
  
  const scale = currentEditorZoom / 100;
  
  const docEditor = document.querySelector('.document-editor');
  if (docEditor) docEditor.style.setProperty('--editor-zoom', scale);
  
  const container = document.querySelector('.document-editor__editable-container');
  if (container) container.style.setProperty('--editor-zoom', scale);
  
  const paperSheet = document.querySelector('.document-editor__editable-container .ck-editor__editable') || document.querySelector('#editor');
  if (paperSheet) {
    paperSheet.style.setProperty('--editor-zoom', scale);
    paperSheet.style.setProperty('zoom', scale, 'important');
    paperSheet.style.transformOrigin = 'top center';
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
  setTimeout(() => {
    applyEditorZoom(currentEditorZoom);
    
    const editableContainer = document.querySelector('.document-editor__editable-container');
    if (editableContainer) {
      editableContainer.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault();
          const delta = e.deltaY < 0 ? 5 : -5;
          applyEditorZoom(currentEditorZoom + delta);
        }
      }, { passive: false });
    }
  }, 600);
});
</script>

<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>
