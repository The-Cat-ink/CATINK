<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    
    if (empty($ACL['leer'])) {
        header("Location: admin.php");
        exit();
    }
    require_once("./../views/helpers/urlhelper.php");

    // Asegurar que existan las 6 páginas obligatorias en la BD
    $seccionesObligatorias = ['nosotros', 'terminos', 'privacidad', 'cookies', 'contacto', 'suscripcion'];
    foreach ($seccionesObligatorias as $sec) {
        $checkSec = @$con->query("SELECT id_pag FROM paginas WHERE nombre_pag='$sec'");
        if ($checkSec && $checkSec->num_rows === 0) {
            @$con->query("INSERT INTO paginas (nombre_pag, contenido_pag) VALUES ('$sec', '')");
        }
    }

    // Logos de marcas
    $logosRes = @$con->query("SELECT * FROM logos_marcas ORDER BY orden ASC, creado ASC");
    $logos    = ($logosRes && method_exists($logosRes, 'fetch_all')) ? $logosRes->fetch_all(MYSQLI_ASSOC) : [];

    $sql = "SELECT * FROM paginas";
    $result = $con->query($sql);

    $paginas = [];
    while($row = $result->fetch_assoc()){
        $paginas[] = $row;
    }
    $totalPaginas = count($paginas);

    // Configuración estética por sección
    $pageMap = [
        'nosotros'    => ['icon' => 'bi-people-fill',            'color' => '#EF3363', 'bg' => 'rgba(239,51,99,0.12)', 'url' => siteUrl() . '/nosotros',    'label' => 'Sobre Nosotros'],
        'terminos'    => ['icon' => 'bi-file-earmark-text-fill', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)', 'url' => siteUrl() . '/terminos',    'label' => 'Términos y Condiciones'],
        'privacidad'  => ['icon' => 'bi-shield-lock-fill',       'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'url' => siteUrl() . '/privacidad',  'label' => 'Aviso de Privacidad'],
        'cookies'     => ['icon' => 'bi-cookie',                 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)', 'url' => siteUrl() . '/cookies',    'label' => 'Política de Cookies'],
        'contacto'    => ['icon' => 'bi-envelope-heart-fill',    'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)', 'url' => siteUrl() . '/contactanos', 'label' => 'Contáctanos'],
        'suscripcion' => ['icon' => 'bi-bell-fill',              'color' => '#ec4899', 'bg' => 'rgba(236,72,153,0.12)', 'url' => siteUrl() . '/suscripcion', 'label' => 'Suscríbete'],
        'unete'       => ['icon' => 'bi-briefcase-fill',         'color' => '#f43f5e', 'bg' => 'rgba(244,63,94,0.12)', 'url' => siteUrl() . '/unete',       'label' => 'Únete al Equipo']
    ];
?>
<div class="container-fluid px-3 py-2">

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); border-radius:12px; margin-bottom:20px; font-weight:600;">
            <i class="bi bi-check-circle-fill me-2"></i> Página y datos de contenido actualizados correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ── Título Principal y Stats ───────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h2 font-weight-bold m-0" style="font-weight:900; color:var(--text); letter-spacing:-0.02em;">Gestión de Páginas & CMS</h1>
            <p class="text-muted m-0 mt-1" style="font-size:0.88rem;">Administra las secciones secundarias, aviso legal, marcas colaboradoras y correos de la plataforma.</p>
        </div>
        <a href="./preview_email.php" target="_blank" class="btn btn-accent px-3 py-2" style="display:inline-flex; align-items:center; gap:8px; font-size:0.88rem; font-weight:800; border-radius:12px; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
            <i class="bi bi-envelope-paper-heart-fill"></i> Previsualizador de Correos
        </a>
    </div>

    <!-- ── 3 Tarjetas de Resumen Rápido ────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div style="width:48px; height:48px; border-radius:14px; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">
                        <i class="bi bi-file-earmark-code-fill"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $totalPaginas ?></div>
                        <div style="font-size:0.8rem; font-weight:700; color:var(--muted); margin-top:4px;">Secciones Editables CMS</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div style="width:48px; height:48px; border-radius:14px; background:rgba(139,92,246,0.12); color:#8b5cf6; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">
                        <i class="bi bi-building-check"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= count($logos) ?></div>
                        <div style="font-size:0.8rem; font-weight:700; color:var(--muted); margin-top:4px;">Marcas Colaboradoras</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:48px; height:48px; border-radius:14px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                        <div>
                            <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;">6</div>
                            <div style="font-size:0.8rem; font-weight:700; color:var(--muted); margin-top:4px;">Plantillas HTML de Correo</div>
                        </div>
                    </div>
                    <a href="./preview_email.php" target="_blank" class="btn btn-sm btn-outline-secondary" style="border-radius:10px; font-size:0.78rem; font-weight:700; padding:6px 12px; white-space:nowrap;">
                        Ver <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Cuadrícula principal: tabla izq / subir logo der ── -->
    <div class="paginas-layout-grid mb-4">

        <!-- ── Columna izquierda: Páginas legales / Secundarias ── -->
        <div class="d-flex flex-column h-100">
            <div class="card border-0 shadow-sm flex-fill" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                        <i class="bi bi-journal-text me-2 text-accent" style="color:var(--accent);"></i> Páginas y Secciones
                    </h5>
                    <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border-radius:20px; padding:6px 12px; font-weight:800; font-size:0.75rem;">
                        <?= $totalPaginas ?> Registradas
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 paginas-table" style="color:var(--text);">
                            <thead>
                                <tr>
                                    <th>Sección / Página</th>
                                    <th>Estado</th>
                                    <th style="text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paginas as $row): 
                                    $key = strtolower(trim($row['nombre_pag']));
                                    $cfg = $pageMap[$key] ?? [
                                        'icon'  => 'bi-file-text-fill',
                                        'color' => '#64748b',
                                        'bg'    => 'rgba(100,116,139,0.12)',
                                        'url'   => siteUrl() . '/' . $key,
                                        'label' => ucfirst($row['nombre_pag'])
                                    ];
                                ?>
                                    <tr style="border-bottom:1px solid var(--border); transition:background 0.2s ease;">
                                        <td style="padding:14px 18px;">
                                            <div class="d-flex align-items-center gap-3">
                                                <div style="width:40px; height:40px; border-radius:12px; background:<?= $cfg['bg'] ?>; color:<?= $cfg['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0;">
                                                    <i class="bi <?= $cfg['icon'] ?>"></i>
                                                </div>
                                                <div>
                                                    <strong style="font-size:0.95rem; font-weight:800; color:var(--text); display:block; line-height:1.2;"><?= htmlspecialchars($cfg['label']) ?></strong>
                                                    <span style="font-size:0.75rem; color:var(--muted); font-family:monospace;"><?= str_replace(siteUrl(), '', $cfg['url']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding:14px 18px;">
                                            <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); padding:5px 10px; border-radius:20px; font-weight:700; font-size:0.72rem;">
                                                <i class="bi bi-circle-fill" style="font-size:0.45rem; vertical-align:middle; margin-right:4px;"></i> CMS Activo
                                            </span>
                                        </td>
                                        <td style="padding:14px 18px; text-align:right;">
                                            <div class="d-inline-flex gap-2">
                                                <button
                                                    class="btn btn-sm btn-accent btnEditar"
                                                    data-id="<?= $row['id_pag'] ?>"
                                                    data-nombre="<?= htmlspecialchars($row['nombre_pag']) ?>"
                                                    data-contenido="<?= base64_encode($row['contenido_pag'] ?? '') ?>"
                                                    data-meta="<?= htmlspecialchars($row['meta_json'] ?? '', ENT_QUOTES) ?>"
                                                    style="padding:6px 14px; font-size:0.82rem; font-weight:800; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                                                    <i class="bi bi-pencil-square"></i> Editar
                                                </button>
                                                <a href="<?= $cfg['url'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="padding:6px 12px; font-size:0.82rem; font-weight:600; border-radius:10px; display:inline-flex; align-items:center; gap:4px;" title="Ver en la web">
                                                    <i class="bi bi-eye"></i> Ver
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Columna derecha: Subir logo de marca ────────────── -->
        <div class="d-flex flex-column h-100">
            <div class="card border-0 shadow-sm flex-fill" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                        <i class="bi bi-cloud-arrow-up-fill me-2 text-accent" style="color:var(--accent);"></i> Subir Marca Colaboradora
                    </h5>
                    <p class="text-muted m-0 mt-1" style="font-size:0.8rem;">Se muestran en el carrusel de "Sobre Nosotros".</p>
                </div>
                <div class="card-body p-3">
                    <label class="file-drop-zone" id="logoDropZone" style="border-radius:14px; padding:24px; text-align:center;">
                        <input type="file" id="logoFile" accept="image/*" hidden>
                        <div class="file-drop-icon" style="font-size:2.2rem; color:var(--accent);"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="file-drop-text" style="font-size:0.95rem; font-weight:700; margin-top:8px;">Arrastra una imagen o <span style="color:var(--accent); text-decoration:underline;">haz clic aquí</span></div>
                        <div class="file-drop-hint" style="font-size:0.78rem; color:var(--muted); margin-top:4px;">Formatos recomendados: PNG, WebP o SVG (fondo transparente)</div>
                        <img id="logoPreview" class="file-drop-preview" style="display:none; max-height:80px; object-fit:contain; margin:10px auto 0;">
                    </label>

                    <div class="cn-field mt-3">
                        <label for="logoNombre" style="font-size:0.82rem; font-weight:700; color:var(--text);">Nombre de la marca <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
                        <input type="text" id="logoNombre" class="cn-input" placeholder="Ej: Disney+, Sony, Crunchyroll..." style="border-radius:10px;">
                    </div>

                    <div class="cn-field mt-3">
                        <label style="font-size:0.82rem; font-weight:700; color:var(--text); display:block; margin-bottom:6px;">Visibilidad / Expiración <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="logo-exp-fields flex-fill">
                                <div class="cn-date-input" style="border-radius:10px;">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="date" id="logoExpFecha" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                                <div class="cn-date-input" style="border-radius:10px;">
                                    <i class="bi bi-clock"></i>
                                    <input type="time" id="logoExpHora" value="23:59">
                                </div>
                            </div>
                            <button type="button" id="btnSubirLogo" class="btn btn-accent px-3 py-2" style="border-radius:10px; font-weight:800; font-size:0.85rem; flex-shrink:0;">
                                <i class="bi bi-upload me-1"></i> Subir Logo
                            </button>
                        </div>
                    </div>
                    <p id="logoMsg" style="margin-top:10px; font-size:0.85rem; font-weight:700; text-align:right; min-height:1.2em;"></p>
                </div>
            </div>
        </div>

    </div><!-- /.paginas-layout-grid -->

    <!-- ── Galería de logos de marcas: ancho completo ──────────── -->
    <div class="card border-0 shadow-sm" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important;">
        <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                <i class="bi bi-grid-3x3-gap-fill me-2 text-accent" style="color:var(--accent);"></i> Marcas Colaboradoras Registradas
            </h5>
            <span class="badge" style="background:rgba(139,92,246,0.12); color:#8b5cf6; border-radius:20px; padding:6px 12px; font-weight:800; font-size:0.75rem;">
                <?= count($logos) ?> Marcas
            </span>
        </div>
        <div class="card-body p-3">
            <div class="logos-grid" id="logosGrid">
                <?php if (empty($logos)): ?>
                    <div style="text-align:center; padding:48px 24px; grid-column:1/-1;" id="logosEmpty">
                        <div style="width:64px; height:64px; border-radius:50%; background:rgba(239,51,99,0.1); color:var(--accent); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.8rem;">
                            <i class="bi bi-images"></i>
                        </div>
                        <h4 style="margin:0 0 6px; font-weight:800; color:var(--text);">Aún no has agregado marcas colaboradoras</h4>
                        <p style="margin:0; color:var(--muted); font-size:0.88rem; max-width:440px; margin-inline:auto;">Sube los logotipos de empresas y marcas aliadas en el formulario de arriba para mostrarlos en el carrusel de <strong>"Sobre Nosotros"</strong>.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($logos as $i => $logo):
                    $exp       = $logo['fecha_expiracion'] ?? null;
                    $vencido   = $exp && strtotime($exp) < time();
                    $expBadge  = '';
                    if ($exp) {
                        if ($vencido) {
                            $expBadge = '<div class="logo-exp-badge logo-exp-vencido"><i class="bi bi-clock-history"></i> Vencido</div>';
                        } else {
                            $diff     = strtotime($exp) - time();
                            $dias     = (int) floor($diff / 86400);
                            $horas    = (int) floor(($diff % 86400) / 3600);
                            $label    = $dias > 0 ? "{$dias}d {$horas}h restantes" : "{$horas}h restantes";
                            $expBadge = '<div class="logo-exp-badge logo-exp-activo"><i class="bi bi-calendar-check"></i> ' . $label . '</div>';
                        }
                    }
                ?>
                    <div class="logo-card <?= $vencido ? 'logo-card-vencida' : '' ?>" id="logo-<?= $logo['id_logo'] ?>" draggable="true" data-id="<?= $logo['id_logo'] ?>" style="border-radius:14px;">
                        <div class="logo-num"><?= $i + 1 ?></div>
                        <div class="logo-img-wrap" style="padding:16px;">
                            <img src="<?= imageUrl($logo['imagen']) ?>" alt="<?= htmlspecialchars($logo['nombre']) ?>" loading="lazy">
                        </div>
                        <?php if ($logo['nombre']): ?>
                            <div class="logo-nombre" style="font-weight:700;"><?= htmlspecialchars($logo['nombre']) ?></div>
                        <?php endif; ?>
                        <?= $expBadge ?>
                        <div class="logo-actions">
                            <button class="btn-edit-logo"
                                    data-id="<?= $logo['id_logo'] ?>"
                                    data-nombre="<?= htmlspecialchars($logo['nombre'] ?? '') ?>"
                                    data-exp="<?= htmlspecialchars($logo['fecha_expiracion'] ?? '') ?>"
                                    data-imagen="<?= htmlspecialchars($logo['imagen']) ?>"
                                    title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn-delete-logo" data-id="<?= $logo['id_logo'] ?>" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div><!-- /.container-fluid -->

<!-- ══ Modal editar página ════════════════════════════════════════ -->
<div id="modalPagina" class="crop-modal" style="display: none;">
    <div class="crop-modal-content" style="max-width: 850px; width:95%; border-radius:18px;">
        <h3 style="font-weight:800; margin-top:0;"><i class="bi bi-pencil-square text-accent"></i> Editar Configuración de Página</h3>

        <form id="formPagina" action="./../controllers/pagina.php" method="POST">
            <input type="hidden" name="id" id="pagina_id">

            <div style="margin-bottom: 16px;">
                <label for="nombre" style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text);">Sección / Página</label>
                <select name="nombre" id="nombre" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 0.9rem; background: var(--bg); color: var(--text); font-weight:700;">
                    <option value="nosotros">Nosotros</option>
                    <option value="terminos">Términos y condiciones</option>
                    <option value="privacidad">Aviso de privacidad</option>
                    <option value="cookies">Política de cookies</option>
                    <option value="suscripcion">Suscríbete</option>
                    <option value="contacto">Contáctanos</option>
                    <option value="unete">Únete al Equipo</option>
                </select>
            </div>

            <input type="hidden" name="meta_json_raw" id="meta_json_raw">

            <div id="metaFieldsContainer" style="margin-bottom: 20px; border-top: 1px solid var(--border); padding-top: 16px; max-height: 420px; overflow-y: auto;">
                <!-- Poblado dinámicamente según la sección elegida -->
            </div>

            <div style="margin-bottom: 16px;" id="editorGroupContainer">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text);">Contenido Editor (Texto / HTML)</label>
                <div class="document-editor">
                    <div class="document-editor__toolbar" id="toolbarpag"></div>
                    <div class="document-editor__editable-container" style="height: 280px; overflow-y: auto;">
                        <div id="editorpag" class="editor-content"></div>
                    </div>
                </div>
                <input type="hidden" name="contenido" id="contenido">
            </div>

            <div class="crop-actions" style="margin-top:20px;">
                <button type="button" class="btn btn-secondary px-4 py-2" id="modalClosePag" style="border-radius:10px; font-weight:700;">Cancelar</button>
                <button type="submit" class="btn btn-accent px-4 py-2" style="border-radius:10px; font-weight:800;"><i class="bi bi-save me-1"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Modal editar logo ══════════════════════════════════════════ -->
<div id="modalEditLogo" class="crop-modal" style="display:none;">
  <div class="crop-modal-content" style="max-width:440px; width:95%; border-radius:18px;">
    <h3 style="margin-top:0; font-weight:800;"><i class="bi bi-pencil-square text-accent"></i> Editar Logo</h3>

    <div class="cn-field" style="margin-bottom:16px;">
      <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">
        Imagen <span style="color:var(--muted);font-weight:400;">(dejar sin cambios para conservar)</span>
      </label>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <img id="editLogoImgActual" style="max-height:56px;max-width:90px;object-fit:contain;border:1px solid var(--border);border-radius:10px;padding:4px;background:var(--bg);">
        <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-weight:700;color:var(--text);background:var(--bg);">
          <i class="bi bi-image"></i> Cambiar imagen
          <input type="file" id="editLogoFile" accept="image/*" hidden>
        </label>
        <img id="editLogoNewPreview" style="display:none;max-height:56px;max-width:90px;object-fit:contain;border:2px solid var(--accent);border-radius:10px;padding:4px;background:var(--bg);">
      </div>
    </div>

    <div class="cn-field" style="margin-bottom:16px;">
      <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">
        Nombre de la marca <span style="color:var(--muted);font-weight:400;">(opcional)</span>
      </label>
      <input type="text" id="editLogoNombre" class="cn-input" placeholder="Ej: Disney+" style="border-radius:10px;">
    </div>

    <div class="cn-field" style="margin-bottom:24px;">
      <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">
        Visible hasta <span style="color:var(--muted);font-weight:400;">(dejar en blanco = sin vencimiento)</span>
      </label>
      <div class="logo-exp-fields">
        <div class="cn-date-input" style="border-radius:10px;">
          <i class="bi bi-calendar3"></i>
          <input type="date" id="editLogoFecha" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="cn-date-input" style="border-radius:10px;">
          <i class="bi bi-clock"></i>
          <input type="time" id="editLogoHora">
        </div>
      </div>
    </div>

    <div class="crop-actions">
      <button type="button" class="btn btn-secondary px-3" id="closeEditLogo" style="border-radius:10px; font-weight:700;">Cancelar</button>
      <button type="button" class="btn btn-accent px-4" id="btnGuardarEditLogo" style="border-radius:10px; font-weight:800;"><i class="bi bi-save me-1"></i> Guardar</button>
    </div>
    <p id="editLogoMsg" style="margin-top:10px;font-size:0.85rem;font-weight:700;text-align:right;min-height:1.2em;"></p>
  </div>
</div>

<style>
/* ── Estilos de Tabla Adaptables al Tema ──────────────────────── */
.paginas-table thead th {
    background: rgba(0, 0, 0, 0.03) !important;
    color: var(--text) !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 1px solid var(--border) !important;
    padding: 12px 18px !important;
}
[data-bs-theme="dark"] .paginas-table thead th {
    background: rgba(255, 255, 255, 0.04) !important;
}

/* ── Layout de dos columnas ──────────────────────────────────── */
.paginas-layout-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 24px;
    align-items: stretch;
}
@media (max-width: 992px) {
    .paginas-layout-grid { grid-template-columns: 1fr; }
}

/* ── Galería de logos ────────────────────────────────────────── */
.logos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
}
.logo-card {
    position: relative;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: var(--bg);
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.logo-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.12); border-color: var(--accent); }
.logo-img-wrap {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 96px;
}
.logo-img-wrap img {
    max-width: 100%;
    max-height: 75px;
    object-fit: contain;
    display: block;
}
.logo-nombre {
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    padding: 0 10px 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.logo-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: none;
    gap: 6px;
}
.logo-card:hover .logo-actions { display: flex; }
.btn-delete-logo,
.btn-edit-logo {
    border: none;
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    cursor: pointer;
    backdrop-filter: blur(6px);
    transition: background .2s, transform .15s;
}
.btn-delete-logo { background: rgba(239,51,99,.9); }
.btn-delete-logo:hover { background: #d42a55; transform: scale(1.08); }
.btn-edit-logo { background: rgba(99,102,241,.9); }
.btn-edit-logo:hover { background: #4f46e5; transform: scale(1.08); }

/* Inputs fecha + hora de expiración */
.logo-exp-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cn-date-input {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px; border: 1px solid var(--border);
    border-radius: 10px; background: var(--bg); color: var(--text); font-size: 13px;
}
.cn-date-input i { color: var(--muted); font-size: 15px; flex-shrink: 0; }
.cn-date-input input[type="date"],
.cn-date-input input[type="time"] {
    border: none; background: none; color: var(--text);
    font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit; font-weight:600;
}

/* Badge de expiración */
.logo-exp-badge {
    font-size: 10px;
    font-weight: 800;
    text-align: center;
    padding: 4px 0;
    letter-spacing: .02em;
}
.logo-exp-activo {
    color: #10b981;
    background: rgba(16,185,129,0.1);
}
.logo-exp-vencido {
    color: #fff;
    background: rgba(239,51,99,.85);
}
.logo-card-vencida {
    opacity: .55;
    border-color: rgba(239,51,99,.4);
}

/* Badge numérico de orden */
.logo-num {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--accent);
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    pointer-events: none;
    box-shadow: 0 2px 6px rgba(239,51,99,0.4);
}

/* Drag & drop */
.logo-card { cursor: grab; }
.logo-card:active { cursor: grabbing; }
.logo-card.dragging { opacity: .35; box-shadow: none; }
.logo-card.drag-over { border: 2px dashed var(--accent); background: rgba(239,51,99,.08); }
</style>

<script>
const BASE_PATH = '<?= basePath() ?>';
document.addEventListener('DOMContentLoaded', () => {
    // ── Logo upload ──────────────────────────────
    const logoFile     = document.getElementById('logoFile');
    const logoDropZone = document.getElementById('logoDropZone');
    const logoPreview  = document.getElementById('logoPreview');
    const logoNombre   = document.getElementById('logoNombre');
    const logoExpFecha = document.getElementById('logoExpFecha');
    const logoExpHora  = document.getElementById('logoExpHora');
    const btnSubirLogo = document.getElementById('btnSubirLogo');
    const logoMsg      = document.getElementById('logoMsg');
    const logosGrid    = document.getElementById('logosGrid');

    logoFile.addEventListener('change', () => {
        const f = logoFile.files[0];
        if (!f) return;
        logoPreview.src = URL.createObjectURL(f);
        logoPreview.style.display = 'block';
        logoDropZone.querySelector('.file-drop-icon').style.display = 'none';
        logoDropZone.querySelector('.file-drop-text').style.display = 'none';
        logoDropZone.querySelector('.file-drop-hint').style.display = 'none';
    });

    // Drag & drop
    logoDropZone.addEventListener('dragover', e => { e.preventDefault(); logoDropZone.classList.add('dragover'); });
    logoDropZone.addEventListener('dragleave', () => logoDropZone.classList.remove('dragover'));
    logoDropZone.addEventListener('drop', e => {
        e.preventDefault();
        logoDropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            logoFile.files = e.dataTransfer.files;
            logoFile.dispatchEvent(new Event('change'));
        }
    });

    btnSubirLogo.addEventListener('click', async () => {
        const f = logoFile.files[0];
        if (!f) {
            logoMsg.style.color = '#ef4444';
            logoMsg.textContent = 'Selecciona una imagen primero';
            return;
        }

        btnSubirLogo.disabled = true;
        btnSubirLogo.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Subiendo...';
        logoMsg.textContent = '';

        const fd = new FormData();
        fd.append('accion', 'crear');
        fd.append('imagen', f);
        fd.append('nombre', logoNombre.value.trim());

        if (logoExpFecha.value) {
            const hora = logoExpHora.value || '23:59';
            fd.append('fecha_expiracion', `${logoExpFecha.value} ${hora}:00`);
        }

        try {
            const res = await fetch(BASE_PATH + '/controllers/logo_marca.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                location.reload();
            } else {
                logoMsg.style.color = '#ef4444';
                logoMsg.textContent = data.msg || 'Error al subir';
            }
        } catch (e) {
            logoMsg.style.color = '#ef4444';
            logoMsg.textContent = 'Error de red al subir el logo';
        } finally {
            btnSubirLogo.disabled = false;
            btnSubirLogo.innerHTML = '<i class="bi bi-upload me-1"></i> Subir Logo';
        }
    });

    // ── Delete Logo ──────────────────────────────
    document.addEventListener('click', async e => {
        const btnDel = e.target.closest('.btn-delete-logo');
        if (!btnDel) return;
        const confirmed = await cnConfirm({
            title: '¿Eliminar Marca?',
            message: '¿Seguro que deseas eliminar esta marca colaboradora del carrusel?',
            confirmText: 'Eliminar Marca',
            cancelText: 'Cancelar',
            isDanger: true
        });
        if (!confirmed) return;

        const id = btnDel.dataset.id;
        const fd = new FormData();
        fd.append('accion', 'eliminar');
        fd.append('id', id);

        try {
            const res = await fetch(BASE_PATH + '/controllers/logo_marca.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                const card = document.getElementById('logo-' + id);
                if (card) card.remove();
            } else {
                alert(data.msg || 'Error al eliminar');
            }
        } catch(err) {
            alert('Error de conexión al eliminar logo');
        }
    });

    // ── Edit Logo ────────────────────────────────
    const modalEditLogo      = document.getElementById('modalEditLogo');
    const closeEditLogo      = document.getElementById('closeEditLogo');
    const editLogoImgActual  = document.getElementById('editLogoImgActual');
    const editLogoFile       = document.getElementById('editLogoFile');
    const editLogoNewPreview = document.getElementById('editLogoNewPreview');
    const editLogoNombre     = document.getElementById('editLogoNombre');
    const editLogoFecha      = document.getElementById('editLogoFecha');
    const editLogoHora       = document.getElementById('editLogoHora');
    const btnGuardarEditLogo = document.getElementById('btnGuardarEditLogo');
    const editLogoMsg        = document.getElementById('editLogoMsg');
    let editLogoId = null;

    document.addEventListener('click', e => {
        const btnEdit = e.target.closest('.btn-edit-logo');
        if (!btnEdit) return;

        editLogoId = btnEdit.dataset.id;
        editLogoImgActual.src = BASE_PATH + '/' + btnEdit.dataset.imagen;
        editLogoNombre.value  = btnEdit.dataset.nombre || '';
        editLogoFile.value    = '';
        editLogoNewPreview.style.display = 'none';
        editLogoMsg.textContent = '';

        const rawExp = btnEdit.dataset.exp;
        if (rawExp) {
            const parts = rawExp.split(' ');
            editLogoFecha.value = parts[0] || '';
            editLogoHora.value  = parts[1] ? parts[1].substring(0, 5) : '23:59';
        } else {
            editLogoFecha.value = '';
            editLogoHora.value  = '23:59';
        }

        modalEditLogo.style.display = 'flex';
    });

    editLogoFile.addEventListener('change', () => {
        const f = editLogoFile.files[0];
        if (!f) return;
        editLogoNewPreview.src = URL.createObjectURL(f);
        editLogoNewPreview.style.display = 'block';
    });

    closeEditLogo.addEventListener('click', () => modalEditLogo.style.display = 'none');
    window.addEventListener('click', e => { if (e.target === modalEditLogo) modalEditLogo.style.display = 'none'; });

    btnGuardarEditLogo.addEventListener('click', async () => {
        if (!editLogoId) return;

        btnGuardarEditLogo.disabled = true;
        btnGuardarEditLogo.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        editLogoMsg.textContent = '';

        const fd = new FormData();
        fd.append('accion', 'editar');
        fd.append('id', editLogoId);
        fd.append('nombre', editLogoNombre.value.trim());

        if (editLogoFile.files[0]) {
            fd.append('imagen', editLogoFile.files[0]);
        }

        if (editLogoFecha.value) {
            const h = editLogoHora.value || '23:59';
            fd.append('fecha_expiracion', `${editLogoFecha.value} ${h}:00`);
        } else {
            fd.append('fecha_expiracion', '');
        }

        try {
            const res = await fetch(BASE_PATH + '/controllers/logo_marca.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                location.reload();
            } else {
                editLogoMsg.style.color = '#ef4444';
                editLogoMsg.textContent = data.msg || 'Error al guardar';
            }
        } catch (e) {
            editLogoMsg.style.color = '#ef4444';
            editLogoMsg.textContent = 'Error de comunicación con el servidor';
        } finally {
            btnGuardarEditLogo.disabled = false;
            btnGuardarEditLogo.innerHTML = '<i class="bi bi-save me-1"></i> Guardar';
        }
    });

    // ── CMS Edit Page Modal ──────────────────────
    let editorpag;
    if (typeof DecoupledEditor !== 'undefined') {
        DecoupledEditor
            .create(document.querySelector('#editorpag'), {
                placeholder: 'Escribe el contenido de esta sección aquí...',
                language: 'es',
                toolbar: {
                    items: [
                        'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|',
                        'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                        'alignment', '|', 'numberedList', 'bulletedList', '|',
                        'outdent', 'indent', '|', 'link', 'blockQuote', 'insertTable', '|',
                        'undo', 'redo'
                    ]
                }
            })
            .then(ed => {
                editorpag = ed;
                const toolbarContainer = document.querySelector('#toolbarpag');
                if (toolbarContainer) {
                    toolbarContainer.appendChild(ed.ui.view.toolbar.element);
                }
            })
            .catch(error => {
                console.error('Error inicializando DecoupledEditor en paginas.php:', error);
            });
    } else if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(document.querySelector('#editorpag'), {
                toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo' ]
            })
            .then(ed => {
                editorpag = ed;
            })
            .catch(error => {
                console.error(error);
            });
    }

    const modalPagina = document.getElementById("modalPagina");
    const modalClosePag = document.getElementById("modalClosePag");

    function renderMetaFields(pageName, meta = {}) {
        const container = document.getElementById("metaFieldsContainer");
        if (!container) return;

        let html = '';

        if (pageName === 'nosotros') {
            const rawStats = (Array.isArray(meta.estadisticas) && meta.estadisticas.length > 0) ? meta.estadisticas : (
                (meta.stat1_num || meta.stat2_num || meta.stat3_num) ? [
                    { num: meta.stat1_num || '', lbl: meta.stat1_lbl || '' },
                    { num: meta.stat2_num || '', lbl: meta.stat2_lbl || '' },
                    { num: meta.stat3_num || '', lbl: meta.stat3_lbl || '' }
                ].filter(s => s.num || s.lbl) : [
                    { num: '500K+', lbl: 'Lectores Mensuales' },
                    { num: '10K+',  lbl: 'Artículos Publicados' },
                    { num: '100%',  lbl: 'Pasión Geek' }
                ]
            );

            let statsHtml = '';
            rawStats.forEach(st => {
                statsHtml += `
                    <div class="stat-row-item" style="display:grid; grid-template-columns:130px 1fr 38px; gap:8px; align-items:center; background:var(--bg); padding:6px 10px; border-radius:10px; border:1px solid var(--border);">
                        <input type="text" class="cn-input meta-stat-num" value="${st.num || ''}" placeholder="Número (500K+)">
                        <input type="text" class="cn-input meta-stat-lbl" value="${st.lbl || ''}" placeholder="Etiqueta (Lectores Mensuales)">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stat" onclick="this.closest('.stat-row-item').remove()" title="Eliminar estadística" style="border-radius:8px; padding:4px 8px; font-size:0.85rem;">
                            <i class="bi bi-trash" style="pointer-events:none;"></i>
                        </button>
                    </div>
                `;
            });

            html = `
                <h5 style="margin:0 0 12px; font-weight:800; color:var(--accent);"><i class="bi bi-sliders"></i> Campos Exclusivos de "Nosotros"</h5>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Título</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_title" value="${meta.hero_title || 'SOBRE NOSOTROS'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Subtítulo</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_sub" value="${meta.hero_sub || ''}">
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <label style="font-weight:800; font-size:0.85rem; color:var(--text); margin:0;">
                        <i class="bi bi-bar-chart-line text-accent me-1"></i> Estadísticas Principales (Dinámicas)
                    </label>
                    <button type="button" class="btn btn-sm btn-outline-accent" id="btnAddStat" style="font-weight:700; font-size:0.78rem; border-radius:8px; padding:4px 10px;">
                        <i class="bi bi-plus-circle me-1"></i> Agregar Estadística
                    </button>
                </div>

                <div id="statsListContainer" style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                    ${statsHtml}
                </div>

                <div style="font-weight:700; font-size:0.85rem; margin-bottom:6px; color:var(--text);">Misión / Visión / Valores</div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
                    <textarea class="cn-input meta-field" data-key="mision" rows="3" placeholder="Misión">${meta.mision || ''}</textarea>
                    <textarea class="cn-input meta-field" data-key="vision" rows="3" placeholder="Visión">${meta.vision || ''}</textarea>
                    <textarea class="cn-input meta-field" data-key="valores" rows="3" placeholder="Valores">${meta.valores || ''}</textarea>
                </div>
            `;
        } else if (pageName === 'terminos' || pageName === 'privacidad') {
            html = `
                <h5 style="margin:0 0 12px; font-weight:800; color:var(--accent);"><i class="bi bi-sliders"></i> Campos Exclusivos de Encabezado</h5>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Título</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_title" value="${meta.hero_title || ''}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Subtítulo / Versión</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_sub" value="${meta.hero_sub || ''}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Fecha de Actualización</label>
                        <input type="text" class="cn-input meta-field" data-key="updated_date" value="${meta.updated_date || ''}">
                    </div>
                </div>
            `;
        } else if (pageName === 'suscripcion') {
            const ben = meta.beneficios || [
                { icono: 'bi-lightning-charge-fill', titulo: 'Noticias en tiempo real', desc: 'Recibe alertas sobre las novedades más importantes al instante.' },
                { icono: 'bi-star-fill', titulo: 'Contenido exclusivo', desc: 'Resúmenes semanales y análisis que no encontrarás en otro lugar.' },
                { icono: 'bi-gift-fill', titulo: 'Sorteos y beneficios', desc: 'Acceso a dinámicas exclusivas para miembros de nuestra comunidad.' },
                { icono: 'bi-shield-check', titulo: 'Cero spam garantizado', desc: 'Solo contenido relevante. Puedes cancelar tu suscripción en un clic.' }
            ];

            const availableIcons = [
                { code: 'bi-lightning-charge-fill', label: '⚡ Rayo (Tiempo real)' },
                { code: 'bi-star-fill',             label: '⭐ Estrella (Exclusivo)' },
                { code: 'bi-gift-fill',             label: '🎁 Regalo (Sorteos / Beneficios)' },
                { code: 'bi-shield-check',          label: '🛡️ Escudo (Cero Spam)' },
                { code: 'bi-bell-fill',             label: '🔔 Campana (Alertas)' },
                { code: 'bi-envelope-heart-fill',   label: '💌 Correo (Boletín)' },
                { code: 'bi-fire',                  label: '🔥 Fuego (Tendencias)' },
                { code: 'bi-controller',            label: '🎮 Control (Gaming)' },
                { code: 'bi-tv-fill',               label: '📺 Tele (Anime / Cine)' },
                { code: 'bi-book-fill',             label: '📖 Libro (Manga / Cómics)' },
                { code: 'bi-gem',                   label: '💎 Gema (Premium)' },
                { code: 'bi-trophy-fill',           label: '🏆 Trofeo (Premios)' },
                { code: 'bi-chat-left-text-fill',   label: '💬 Chat (Comunidad)' },
                { code: 'bi-heart-fill',            label: '❤️ Corazón (Pasión Geek)' },
                { code: 'bi-award-fill',            label: '🎖️ Medalla (Reconocimientos)' },
                { code: 'bi-clock-fill',            label: '⏰ Reloj (Instantáneo)' },
                { code: 'bi-rocket-takeoff-fill',   label: '🚀 Cohete (Lanzamientos)' },
                { code: 'bi-newspaper',            label: '📰 Periódico (Noticias)' },
                { code: 'bi-tag-fill',              label: '🏷️ Etiqueta (Descuentos)' },
                { code: 'bi-sparkles',              label: '✨ Destellos (Novedades)' }
            ];

            html = `
                <h5 style="margin:0 0 12px; font-weight:800; color:var(--accent);"><i class="bi bi-sliders"></i> Configuración de la Página de Suscripción</h5>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Título</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_title" value="${meta.hero_title || 'Suscríbete a CatInk'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Subtítulo</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_sub" value="${meta.hero_sub || ''}">
                    </div>
                </div>
                <div style="font-weight:700; font-size:0.85rem; margin-bottom:8px; color:var(--text);">4 Beneficios Destacados</div>
            `;

            ben.forEach((b, idx) => {
                const currentIcon = b.icono || 'bi-star-fill';
                let optionsHtml = '';
                availableIcons.forEach(ic => {
                    optionsHtml += `<option value="${ic.code}" ${currentIcon === ic.code ? 'selected' : ''}>${ic.label}</option>`;
                });
                if (!availableIcons.some(ic => ic.code === currentIcon) && currentIcon) {
                    optionsHtml += `<option value="${currentIcon}" selected>✨ Custom (${currentIcon})</option>`;
                }

                html += `
                    <div style="border:1px solid var(--border); padding:12px; border-radius:12px; margin-bottom:10px; background:var(--bg);">
                        <div style="font-size:0.78rem; font-weight:800; color:var(--accent); margin-bottom:6px;">Beneficio ${idx+1}</div>
                        <div style="display:grid; grid-template-columns: 210px 1fr; gap:10px; margin-bottom:8px; align-items:center;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:36px; height:36px; border-radius:10px; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; border:1px solid rgba(239,51,99,0.2);">
                                    <i class="bi ${currentIcon}"></i>
                                </div>
                                <select class="cn-input meta-ben-icon" onchange="this.previousElementSibling.querySelector('i').className = 'bi ' + this.value;" style="font-weight:700; font-size:0.82rem; padding:8px 10px; cursor:pointer;">
                                    ${optionsHtml}
                                </select>
                            </div>
                            <input type="text" class="cn-input meta-ben-title" value="${b.titulo || ''}" placeholder="Título del Beneficio">
                        </div>
                        <input type="text" class="cn-input meta-ben-desc" value="${b.desc || ''}" placeholder="Descripción corta explicativa">
                    </div>
                `;
            });
        } else if (pageName === 'contacto') {
            const hor = meta.horario || [
                { dia: 'Lunes – Viernes', hora: '9:00 – 18:00 hrs' },
                { dia: 'Sábado', hora: '10:00 – 14:00 hrs' },
                { dia: 'Domingo', hora: 'Cerrado' }
            ];
            html = `
                <h5 style="margin:0 0 12px; font-weight:800; color:var(--accent);"><i class="bi bi-sliders"></i> Configuración de la Página de Contacto</h5>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Etiqueta Eyebrow</label>
                        <input type="text" class="cn-input meta-field" data-key="eyebrow" value="${meta.eyebrow || 'HABLEMOS'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Título</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_title" value="${meta.hero_title || 'Contáctanos'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Subtítulo</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_sub" value="${meta.hero_sub || ''}">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Título Columna Info</label>
                        <input type="text" class="cn-input meta-field" data-key="info_title" value="${meta.info_title || 'Estamos para ayudarte'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Subtítulo Columna Info</label>
                        <input type="text" class="cn-input meta-field" data-key="info_sub" value="${meta.info_sub || ''}">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Título Formulario</label>
                        <input type="text" class="cn-input meta-field" data-key="form_title" value="${meta.form_title || 'Envíanos un mensaje'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Subtítulo Formulario</label>
                        <input type="text" class="cn-input meta-field" data-key="form_sub" value="${meta.form_sub || ''}">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Email General</label>
                        <input type="text" class="cn-input meta-field" data-key="email_general" value="${meta.email_general || 'contacto@catink.com.mx'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Email Publicidad</label>
                        <input type="text" class="cn-input meta-field" data-key="email_publicidad" value="${meta.email_publicidad || 'contacto@catink.com.mx'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Ubicación</label>
                        <input type="text" class="cn-input meta-field" data-key="ubicacion" value="${meta.ubicacion || 'Toluca de Lerdo, Estado de México, México'}">
                    </div>
                </div>
                <div style="font-weight:700; font-size:0.85rem; margin-bottom:6px; color:var(--text);">Horario de Atención</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:6px;">
                    <input type="text" class="cn-input meta-field" data-key="horario_lv_dia" value="${hor[0]?.dia || 'Lunes – Viernes'}">
                    <input type="text" class="cn-input meta-field" data-key="horario_lv_hora" value="${hor[0]?.hora || '9:00 – 18:00 hrs'}">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:6px;">
                    <input type="text" class="cn-input meta-field" data-key="horario_sab_dia" value="${hor[1]?.dia || 'Sábado'}">
                    <input type="text" class="cn-input meta-field" data-key="horario_sab_hora" value="${hor[1]?.hora || '10:00 – 14:00 hrs'}">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <input type="text" class="cn-input meta-field" data-key="horario_dom_dia" value="${hor[2]?.dia || 'Domingo'}">
                    <input type="text" class="cn-input meta-field" data-key="horario_dom_hora" value="${hor[2]?.hora || 'Cerrado'}">
                </div>
            `;
        } else if (pageName === 'unete') {
            html = `
                <h5 style="margin:0 0 12px; font-weight:800; color:var(--accent);"><i class="bi bi-sliders"></i> Configuración de "Únete al Equipo"</h5>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Etiqueta Eyebrow</label>
                        <input type="text" class="cn-input meta-field" data-key="eyebrow" value="${meta.eyebrow || 'Únete al Equipo'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Título</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_title" value="${meta.hero_title || 'Vacantes Abiertas'}">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:700;">Hero Subtítulo</label>
                        <input type="text" class="cn-input meta-field" data-key="hero_sub" value="${meta.hero_sub || ''}">
                    </div>
                </div>
            `;
        } else {
            html = `<p style="font-size:0.85rem; color:var(--muted);">No hay opciones adicionales configurables para esta sección.</p>`;
        }

        container.innerHTML = html;
        container.dataset.pageName = pageName;

        const btnAddStat = container.querySelector('#btnAddStat');
        const statsListContainer = container.querySelector('#statsListContainer');
        if (btnAddStat && statsListContainer) {
            btnAddStat.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'stat-row-item';
                row.style.cssText = 'display:grid; grid-template-columns:130px 1fr 38px; gap:8px; align-items:center; background:var(--bg); padding:6px 10px; border-radius:10px; border:1px solid var(--border);';
                row.innerHTML = `
                    <input type="text" class="cn-input meta-stat-num" value="" placeholder="Número (50+)"/>
                    <input type="text" class="cn-input meta-stat-lbl" value="" placeholder="Etiqueta (Premios Ganados)"/>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stat" onclick="this.closest('.stat-row-item').remove()" title="Eliminar estadística" style="border-radius:8px; padding:4px 8px; font-size:0.85rem;">
                        <i class="bi bi-trash" style="pointer-events:none;"></i>
                    </button>
                `;
                statsListContainer.appendChild(row);
            });
        }
    }

    // Delegación de eventos para eliminar estadísticas
    document.addEventListener('click', e => {
        const btnDel = e.target.closest('.btn-remove-stat');
        if (btnDel) {
            const row = btnDel.closest('.stat-row-item');
            if (row) row.remove();
        }
    });

    const pagesWithEditor = ['nosotros', 'terminos', 'privacidad', 'cookies'];

    function updateEditorState(pageName, content = '') {
        const needsEditor = pagesWithEditor.includes(pageName);
        const editorGrp = document.getElementById("editorGroupContainer");
        if (editorGrp) {
            editorGrp.style.display = needsEditor ? 'block' : 'none';
        }
        if (needsEditor) {
            setTimeout(() => {
                if (editorpag) {
                    try { editorpag.setData(content); } catch(e) {}
                } else {
                    const el = document.getElementById('editorpag');
                    if (el) el.innerHTML = content;
                }
                window.dispatchEvent(new Event('resize'));
            }, 60);
        }
    }

    function serializeMetaFields() {
        const container = document.getElementById("metaFieldsContainer");
        const rawInput = document.getElementById("meta_json_raw");
        if (!container || !rawInput) return;

        const pageName = container.dataset.pageName || '';
        const metaObj = {};

        container.querySelectorAll(".meta-field").forEach(inp => {
            const key = inp.dataset.key;
            if (key) {
                metaObj[key] = inp.value;
            }
        });

        if (pageName === 'nosotros') {
            const statNums = container.querySelectorAll('.meta-stat-num');
            const statLbls = container.querySelectorAll('.meta-stat-lbl');
            metaObj.estadisticas = [];
            statNums.forEach((inp, idx) => {
                const numVal = inp.value.trim();
                const lblVal = statLbls[idx]?.value.trim() || '';
                if (numVal || lblVal) {
                    metaObj.estadisticas.push({ num: numVal, lbl: lblVal });
                }
            });
            // Fallback compatibilidad legacy
            metaObj.stat1_num = metaObj.estadisticas[0]?.num || '';
            metaObj.stat1_lbl = metaObj.estadisticas[0]?.lbl || '';
            metaObj.stat2_num = metaObj.estadisticas[1]?.num || '';
            metaObj.stat2_lbl = metaObj.estadisticas[1]?.lbl || '';
            metaObj.stat3_num = metaObj.estadisticas[2]?.num || '';
            metaObj.stat3_lbl = metaObj.estadisticas[2]?.lbl || '';
        } else if (pageName === 'contacto') {
            metaObj.horario = [
                { dia: metaObj.horario_lv_dia || 'Lunes – Viernes', hora: metaObj.horario_lv_hora || '9:00 – 18:00 hrs' },
                { dia: metaObj.horario_sab_dia || 'Sábado', hora: metaObj.horario_sab_hora || '10:00 – 14:00 hrs' },
                { dia: metaObj.horario_dom_dia || 'Domingo', hora: metaObj.horario_dom_hora || 'Cerrado' }
            ];
            delete metaObj.horario_lv_dia; delete metaObj.horario_lv_hora;
            delete metaObj.horario_sab_dia; delete metaObj.horario_sab_hora;
            delete metaObj.horario_dom_dia; delete metaObj.horario_dom_hora;
        } else if (pageName === 'suscripcion') {
            const benIcons = container.querySelectorAll(".meta-ben-icon");
            const benTitles = container.querySelectorAll(".meta-ben-title");
            const benDescs = container.querySelectorAll(".meta-ben-desc");
            metaObj.beneficios = [];
            benIcons.forEach((inp, i) => {
                metaObj.beneficios.push({
                    icono: inp.value,
                    titulo: benTitles[i]?.value || '',
                    desc: benDescs[i]?.value || ''
                });
            });
        }

        rawInput.value = JSON.stringify(metaObj);
    }

    document.querySelectorAll(".btnEditar").forEach(btn => {
        btn.addEventListener("click", function(){
            document.getElementById("pagina_id").value = this.dataset.id;

            const selectNombre = document.getElementById("nombre");
            for(let i=0; i<selectNombre.options.length; i++){
                if(selectNombre.options[i].value === this.dataset.nombre) {
                    selectNombre.selectedIndex = i;
                    break;
                }
            }

            let contenido = '';
            try {
                contenido = decodeURIComponent(escape(atob(this.dataset.contenido)));
            } catch(e) {
                contenido = this.dataset.contenido || '';
            }

            let metaData = {};
            try {
                metaData = JSON.parse(this.dataset.meta || '{}');
            } catch(e) {}

            renderMetaFields(this.dataset.nombre, metaData);
            updateEditorState(this.dataset.nombre, contenido);

            modalPagina.style.display = "flex";
            setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 80);
        });
    });

    document.getElementById("nombre").addEventListener("change", function(){
        renderMetaFields(this.value, {});
        updateEditorState(this.value, '');
    });

    document.getElementById("formPagina").addEventListener("submit", function(){
        const pageName = document.getElementById("nombre").value;
        if (pagesWithEditor.includes(pageName)) {
            if (editorpag) {
                document.getElementById("contenido").value = editorpag.getData();
            } else {
                const el = document.getElementById('editorpag');
                if (el) document.getElementById("contenido").value = el.innerHTML;
            }
        } else {
            document.getElementById("contenido").value = '';
        }
        serializeMetaFields();
    });

    modalClosePag.addEventListener('click', () => {
        modalPagina.style.display = "none";
    });

    window.addEventListener('click', (e) => {
        if(e.target === modalPagina) {
            modalPagina.style.display = "none";
        }
    });
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
