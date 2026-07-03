# Análisis de Commits y Estado de Pendientes (To-Do List)

Este documento contiene un análisis detallado del historial de commits del repositorio **CATINK** desde su creación hasta la fecha actual, así como una evaluación minuciosa del estado de cumplimiento de la lista de tareas pendientes (To-Do List) proporcionada.

---

## 📊 Resumen Ejecutivo del Repositorio

*   **Total de Commits:** 517
*   **Primer Commit (Creación):** `d030eaa` (2026-05-25) - "commit inicial"
*   **Último Commit:** `30534f5` (2026-06-29) - "fix: transicion suave en todos los elementos..."
*   **Principales Colaboradores:** Juan Pablo, Samuel Gutiérrez, 4strob0y, y otros.

---

## 📋 Estado de Cumplimiento de la To-Do List

A continuación, se detalla el nivel de cumplimiento de cada uno de los 12 puntos de la lista escrita a mano, indicando qué se ha implementado en los commits y qué falta por hacer.

| # | Tarea de la Lista | Estado | Detalle y Commits Clave | ¿Qué falta por cumplir? |
|---|-------------------|--------|--------------------------|-------------------------|
| 1 | **Sistema de comentarios** | **Completado** | Implementado en `0807902` (sistema completo con crear, editar, eliminar, likes y reportes). Mejorado con protección anti-spam, detección de duplicados, límites de tasa y validación robusta de URLs en `9e2c331` y `f0947d2`. | Nada, el sistema de comentarios base está completamente funcional. |
| 2 | **Comentarios anidados** | ❌ **Pendiente** | No existe código ni estructura para comentarios anidados en la base de datos ni en las vistas. | **Falta implementar por completo:** Modificar la tabla `comentarios` para agregar una columna `parent_id` (o `comentario_padre_id`), adaptar la lógica de inserción en `controllers/comentarios.php` y rediseñar la vista recursiva en `views/news.php` junto con los estilos correspondientes para mostrar las respuestas indentadas. |
| 3 | **Filtro de gestión de usuarios** | ⚠️ **Parcialmente Completado** | Se implementó un buscador de texto simple en la barra superior de `views/usuarios.php` (`nombre LIKE ? OR usuario LIKE ? OR correo LIKE ?`) en el commit `45cdbdc`. | **Falta agregar filtros avanzados:** No es posible filtrar a los usuarios por su rol (Superadmin, Editor, Admin), permisos específicos, ni por rango de fecha de registro. Se requeriría agregar selectores/dropdowns en la interfaz de gestión de usuarios. |
| 4 | **Baneo de usuarios que dicen malas palabras** | ❌ **Pendiente** | Actualmente existe un filtro de palabras prohibidas (`views/helpers/filtrohelper.php` y la tabla `filtro_diccionario`) que reemplaza las groserías por asteriscos (`***`) al comentar (commit `0807902` y mejoras en `9b2a931`/`cc21769`). Sin embargo, no hay lógica de baneo de usuarios. | **Falta implementar por completo:** Agregar una columna `estado` (ej. `enum('activo','baneado')`) o `baneado` (booleano) en la tabla `lectores`. Desarrollar la lógica para que si un usuario acumula cierto número de reportes o comentarios bloqueados, o si el administrador lo decide, se marque como "baneado" y no pueda volver a iniciar sesión o comentar. |
| 5 | **Eliminación de cuentas** | **Completado** | Implementado en `11c8a0d` a través de `controllers/eliminar_cuenta.php`. Permite que los lectores eliminen su propia cuenta confirmando su contraseña, lo cual limpia sus likes, reportes, comentarios y suscripciones. Para el staff (`usuarios`), no se permite la auto-eliminación desde el perfil para evitar noticias huérfanas, pero otro admin con permisos sí puede eliminarlos en `usuarios.php`. | Nada, se cumple correctamente con la funcionalidad tanto para lectores como para administradores (con control de integridad). |
| 6 | **Cambiar nombre de cuenta** | **Completado** | Implementado en el commit `11c8a0d` en `controllers/perfilcontroller.php`. Permite cambiar el nombre de usuario (`usuario`) para administradores y lectores, validando que no esté duplicado en ninguna de las dos tablas (`usuarios` y `lectores`). | El nombre de usuario es modificable. (Nota: El nombre completo del perfil `nombre` no se edita desde allí, pero la cuenta/usuario sí). |
| 7 | **Hacer administrador a un usuario** | ⚠️ **Parcialmente Completado / Solo Staff** | Implementado en `views/editaru.php` y `controllers/editarusuario.php`, donde un administrador con permisos puede marcar los checkboxes para otorgar todos los permisos del sistema a otro miembro del staff (`usuarios`). | **Falta la promoción de lectores:** Actualmente las tablas `lectores` (públicos) y `usuarios` (staff/admins) están separadas. No existe una función en el panel administrativo para promover a un `lector` registrado a la categoría de `usuario` (administrador o editor) de forma automática. Si se desea hacer administrador a un lector, se debe crear manualmente un nuevo registro en la tabla `usuarios`. |
| 8 | **Ajuste de solo 5 cards en "lo que más te recomienda"** | ⚠️ **Parcialmente Completado** | En el commit `cc34ffa` ("Fix: cards recomendados muestran 5 completas con scroll para el resto"), Juan Pablo ajustó los estilos CSS en `.sidebar-ranking-list` a `max-height: 715px` para que quepan visualmente 5 cards en la pantalla y el resto requiera scroll. | **Falta limitar la consulta a la base de datos:** El query SQL en `index.php` (línea 118) sigue teniendo `LIMIT 10;`, lo que significa que el servidor carga e inserta en el DOM hasta 10 cards. Si la regla de negocio es mostrar **únicamente** 5 cards (sin scroll para más elementos), se debe cambiar la consulta SQL a `LIMIT 5;`. |
| 9 | **Responsive móvil** | **Completado** | Se ha realizado un extenso trabajo de responsividad en móviles mediante múltiples commits, incluyendo optimizaciones en el carrusel de la página principal (`75c4fea`, `9526831`, `c4f9989`), ajustes en el navbar y menú móvil (`00bd2e8`), márgenes y grids adaptados (`92a5a90`, `ba006cc`). | Nada, la visualización móvil es completamente funcional y adaptada. |
| 10 | **Calidad de imágenes** | **Completado** | Ha sido uno de los temas más optimizados en el desarrollo del proyecto. Se cambió el procesamiento para usar formato WebP directo con calidades altas (85, 92, 95) en lugar de JPEG de baja resolución, y se implementó guardado PNG sin pérdida (canvas toBlob) para previsualizaciones fieles y cropping nativo (`83de1cb`, `20dbdad`, `a789b99`, `330267b`, `47ece11`, `b71316d`, `fb8148b`). | Nada, la calidad está optimizada al máximo permitido por los componentes actuales. |
| 11 | **Gestión de lectores** | ❌ **Pendiente** | A pesar de que los lectores se separaron exitosamente de la tabla `usuarios` en el commit `2599fdd` (creando la tabla `lectores`), no se desarrolló ninguna interfaz en el panel de administración para ver, buscar, editar o eliminar lectores. | **Falta implementar por completo:** Desarrollar una página `views/lectores.php` y sus controladores asociados (`eliminar_lector.php`, etc.) en el panel de administración para que los administradores tengan control sobre la lista de lectores registrados. |
| 12 | **Mejora de SEO/Arañas (crawlers)** | ⚠️ **Parcialmente Completado** | Se crearon URLs amigables (slugs) en `f8fc122`, sitemap dinámico en `views/sitemap.php` que lista todas las noticias/categorías y se configuró redirección Rewrite en `.htaccess` (commit `d030eaa` / `.htaccess` línea 20). | **Falta corregir el orden de carga en las páginas:** En `views/news.php` (y otras vistas), el layout `layout/header.php` se incluye en la línea 2, **antes** de que se realice la consulta SQL para obtener los datos de la noticia actual (línea 31). Como resultado, las variables `$pageTitle`, `$pageDescription` y `$ogImage` no pueden ser seteadas con los datos dinámicos de la noticia, por lo que **todas las noticias individuales comparten el mismo título y descripción predeterminados**. Esto perjudica gravemente la indexación de las "arañas" (crawlers) de Google. Se debe mover el bloque de consulta SQL antes del `include`. |

---

## 📈 Historial de Commits Completo (Cronológico)

A continuación se detalla cada uno de los **517** commits realizados en el repositorio desde su creación:

| # | Hash | Fecha | Autor | Mensaje de Commit y Detalle de Cambios |
|---|------|-------|-------|-----------------------------------------|
| 1 | `d030eaa` | 2026-05-25 | unknown | commit inicial |
| 2 | `1933ba8` | 2026-05-26 | unknown | Refactor: btn-success → btn-accent, env variables, error handling, 404, avatares UI |
| 3 | `2599fdd` | 2026-05-26 | unknown | refactor: separar lectores de usuarios - crear tabla lectores y refactorizar controllers |
| 4 | `24abc4d` | 2026-05-26 | The-Cat-ink | Merge pull request #1 from The-Cat-ink/sam |
| 5 | `192ba07` | 2026-05-26 | unknown | fix: cache-bust CSS/JS con v=2.1 |
| 6 | `d294c87` | 2026-05-26 | The-Cat-ink | Merge pull request #2 from The-Cat-ink/sam |
| 7 | `89ae399` | 2026-05-26 | unknown | fix: cookie modal roto por JS null check + avatar_eliminar safe para lectores |
| 8 | `e007925` | 2026-05-26 | The-Cat-ink | Merge pull request #3 from The-Cat-ink/sam |
| 9 | `ee799fd` | 2026-05-26 | unknown | fix: logo roto en produccion (rutas relativas) + avatar fallback sesion legacy + cache v2.2 |
| 10 | `91bdc84` | 2026-05-26 | unknown | fix: buscar .env fuera de public_html para sobrevivir git deploy |
| 11 | `045db1b` | 2026-05-26 | The-Cat-ink | Merge pull request #4 from The-Cat-ink/sam |
| 12 | `63e09f2` | 2026-05-26 | unknown | fix: JS crash video carousel + imagenes rotas + doble slash URLs + null check anuncios + cache v2.2 |
| 13 | `81aea0e` | 2026-05-26 | unknown | fix: favicon PNG + apple-touch-icon para Google |
| 14 | `627e1ff` | 2026-05-26 | The-Cat-ink | Merge pull request #5 from The-Cat-ink/sam |
| 15 | `0c727bd` | 2026-05-26 | unknown | fix: migrations.sql columna id_avatar (no id) para avatares_perfil |
| 16 | `e0d095e` | 2026-05-26 | unknown | fix: cache-busting admin CSS + span global -> scoped + logo oval |
| 17 | `0c725df` | 2026-05-26 | The-Cat-ink | Merge pull request #6 from The-Cat-ink/sam |
| 18 | `0603e75` | 2026-05-26 | unknown | fix: usar img() helper para manejar campos vacios (no solo null) en imagenes |
| 19 | `591394b` | 2026-05-26 | unknown | fix: corregir orden columnas en migrations.sql y comentar ALTER condicional |
| 20 | `0c0227a` | 2026-05-26 | The-Cat-ink | Merge pull request #7 from The-Cat-ink/sam |
| 21 | `74069c9` | 2026-05-26 | unknown | Fix boton de editar paginas informativas |
| 22 | `83de1cb` | 2026-05-27 | unknown | fix: mejorar calidad de imagenes - JPEG 0.95, WebP 92 para noticias y 90 para publicidad |
| 23 | `beb83e5` | 2026-05-27 | unknown | feat: perfiles publicos de editores - autor.php, foto_personal WebP, bio, redes sociales |
| 24 | `e4cce89` | 2026-05-27 | unknown | feat: editores pueden editar su perfil publico desde /perfil (foto, bio, redes) |
| 25 | `574a623` | 2026-05-27 | unknown | feat: estilo drop-zone para foto personal en perfil (drag & drop + preview) |
| 26 | `3ac982d` | 2026-05-27 | unknown | refactor: quitar perfil publico de gestion de usuarios (solo desde /perfil) |
| 27 | `e4a21ed` | 2026-05-27 | unknown | fix: mostrar foto_personal en sidebar del perfil (prioridad sobre avatar) |
| 28 | `78ef8c7` | 2026-05-27 | The-Cat-ink | Merge pull request #8 from The-Cat-ink/sam |
| 29 | `45cdbdc` | 2026-05-27 | unknown | feat: motor de busqueda en todas las secciones del admin |
| 30 | `90e3291` | 2026-05-27 | The-Cat-ink | Merge pull request #9 from The-Cat-ink/sam |
| 31 | `e59705f` | 2026-05-27 | Juan Pablo | Resolviendo conflicto: manteniendo mis cambios locales |
| 32 | `ec427cb` | 2026-05-27 | unknown | fix: header centrado + buscador desplegable funcionando correctamente |
| 33 | `ccfbb10` | 2026-05-27 | unknown | fix: header completamente centrado en desktop (logo \| nav \| actions) + mobile ajustado |
| 34 | `add3109` | 2026-05-27 | unknown | fix: grid 3 columnas iguales para centrado visual perfecto del header |
| 35 | `598e03f` | 2026-05-27 | unknown | fix: centrar nav con position absolute para centrado perfecto independiente del contenido |
| 36 | `0a81e80` | 2026-05-27 | unknown | fix: centrado visual perfecto del header con grid minmax(0,1fr) auto minmax(0,1fr) |
| 37 | `b929ada` | 2026-05-27 | unknown | fix: centrado absoluto del nav con position absolute + top/left 50% translate -50% |
| 38 | `d5a90eb` | 2026-05-27 | unknown | fix: centrar todo el header con max-width 1200px y margin auto |
| 39 | `787c94b` | 2026-05-27 | unknown | fix: centrar todo el header como grupo con justify-content center |
| 40 | `87d3e4a` | 2026-05-27 | unknown | fix: anular flex-grow del navbar-collapse para centrar todo el header junto |
| 41 | `39c1282` | 2026-05-27 | unknown | fix: cache auto con filemtime, calidad imagenes con minWidth en crops, picture CSS separado para mobile |
| 42 | `0807902` | 2026-05-28 | unknown | feat: sistema de comentarios completo (crear, editar, eliminar, likes, reportes, filtro palabras) |
| 43 | `a15afe6` | 2026-05-28 | unknown | style: rediseño comentarios similar a referencia (barra roja, avatar grande, btn outlined PUBLICAR) |
| 44 | `838668a` | 2026-05-28 | unknown | fix: eliminar flash de tema oscuro al navegar + quitar alert de comentario publicado |
| 45 | `e61d0df` | 2026-05-28 | unknown | fix: reemplazar alert() con toast animado en sistema de comentarios |
| 46 | `a1e84e8` | 2026-05-28 | unknown | feat: admins pueden comentar como editores con badge Editor |
| 47 | `25ff347` | 2026-05-28 | unknown | fix: corregir ruta action en crear.php (./../../ -> ./../) |
| 48 | `92f777a` | 2026-05-28 | The-Cat-ink | Merge pull request #10 from The-Cat-ink/sam |
| 49 | `b071900` | 2026-05-28 | Juan Pablo | feat: sidebar plegable y mejoras en crear.php |
| 50 | `6af5bf7` | 2026-05-28 | Juan Pablo | merge: integrar cambios de main (filemtime cache busting) manteniendo rediseño de juan |
| 51 | `182f8b9` | 2026-05-28 | The-Cat-ink | Merge pull request #11 from The-Cat-ink/juan |
| 52 | `846f2d8` | 2026-05-28 | unknown | fix: mejorar calidad banner (1920px, JPEG 0.98, WebP 95) |
| 53 | `20dbdad` | 2026-05-28 | unknown | fix: mejorar calidad en TODAS las imágenes (noticias crear/editar + publicidad) |
| 54 | `0de191e` | 2026-05-28 | Juan Pablo | fix: prevenir envío múltiple de noticias al hacer clic repetido |
| 55 | `8bf42c2` | 2026-05-28 | unknown | merge: resolver conflicto con main, usar guardado directo sin GD para mejor calidad |
| 56 | `2d0342d` | 2026-05-28 | Juan Pablo | feat: sidebar hover auto-expand + iconos más grandes + fix avatares.php |
| 57 | `c80368b` | 2026-05-28 | Juan Pablo | fix: corregir desbordamiento de items en sidebar colapsado |
| 58 | `cc8feb8` | 2026-05-28 | Juan Pablo | fix: sidebar se expande solo al apuntar la flecha, colapsa al salir del sidebar |
| 59 | `0cd0c9e` | 2026-05-28 | unknown | feat: header sticky auto-hide, iconos modernos, cards expandibles, dark mode transitions, search bar rediseñado |
| 60 | `28e0f6d` | 2026-05-28 | The-Cat-ink | Merge pull request #12 from The-Cat-ink/sam |
| 61 | `e1f532c` | 2026-05-28 | The-Cat-ink | Merge pull request #13 from The-Cat-ink/juan |
| 62 | `d2de3e4` | 2026-05-28 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK into juan |
| 63 | `c8341ad` | 2026-05-28 | unknown | perf: lazy loading en imagenes, eliminar selectores wildcard de transiciones |
| 64 | `4ce4bab` | 2026-05-28 | The-Cat-ink | Merge pull request #14 from The-Cat-ink/sam |
| 65 | `d245686` | 2026-05-28 | unknown | fix: usar basePath() en todas las rutas de imagenes para produccion |
| 66 | `e5594df` | 2026-05-28 | The-Cat-ink | Merge pull request #15 from The-Cat-ink/sam |
| 67 | `b370e9a` | 2026-05-28 | unknown | fix: incluir urlhelper.php en headerAdmin para que basePath() funcione en admin |
| 68 | `5485fe1` | 2026-05-28 | The-Cat-ink | Merge pull request #16 from The-Cat-ink/sam |
| 69 | `535f507` | 2026-05-28 | unknown | fix: basePath() en publicidad, decoding=async y lazy en sidebar imgs |
| 70 | `6e9dcff` | 2026-05-28 | unknown | feat: mejorar gestion de contenidos - stats, filtros, vista tabla, estado visual, dia resaltado |
| 71 | `eb1231b` | 2026-05-28 | unknown | feat: navegacion mejorada - boton Hoy, date picker, atajos teclado (flechas + T) |
| 72 | `7e7b186` | 2026-05-28 | unknown | fix: calculo de semanas ISO confiable + date picker con lunes correcto |
| 73 | `101c84c` | 2026-05-28 | unknown | feat: columnas fit-screen, drag&drop para reprogramar, vista mes |
| 74 | `c8e223f` | 2026-05-28 | unknown | fix: usar aclcontroller.php en reprogramar_noticia para permisos correctos |
| 75 | `f4c9a6a` | 2026-05-28 | The-Cat-ink | Merge pull request #17 from The-Cat-ink/sam |
| 76 | `e810f18` | 2026-05-28 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK into juan |
| 77 | `368921a` | 2026-05-28 | The-Cat-ink | Merge pull request #18 from The-Cat-ink/juan |
| 78 | `b3c5558` | 2026-05-29 | Juan Pablo | fix: sidebar hover en todo el panel + iconos uniformes + transición moderada |
| 79 | `8e5e2a2` | 2026-05-29 | Juan Pablo | fix: sidebar - transición más lenta, espacio footer, icono categorías |
| 80 | `a3bf2bf` | 2026-05-29 | Juan Pablo | fix: botón < fuera del flujo flex en sidebar expandido |
| 81 | `65a7408` | 2026-05-29 | Juan Pablo | fix: mover botón colapso al footer, footer siempre en columna |
| 82 | `7ce81ec` | 2026-05-29 | Juan Pablo | fix: flecha toggle bajo username jp, footer alineado como el menú |
| 83 | `16b7760` | 2026-05-29 | Juan Pablo | feat: eliminar imagen original, auto-generar miniatura del banner |
| 84 | `fc57447` | 2026-05-29 | Juan Pablo | fix: usar crop2 como imagen titular en vistas de detalle |
| 85 | `dc1f0ab` | 2026-05-29 | Juan Pablo | feat: botones Ajustar y Quitar en zonas de imagen (crear.php) |
| 86 | `f0302f4` | 2026-05-29 | Juan Pablo | fix: acumulación imágenes, sync miniatura↔banner, default publicar inmediato |
| 87 | `d1111d8` | 2026-05-29 | Juan Pablo | fix: imagen antigua en modal, Quitar limpia ambas zonas |
| 88 | `2eb88dd` | 2026-05-29 | Juan Pablo | fix: Ajustar no publica, validaciones antes de publicar |
| 89 | `9bb8d00` | 2026-05-29 | Juan Pablo | fix: validación de contenido lee el DOM de Quill directamente |
| 90 | `f15b674` | 2026-05-29 | Juan Pablo | fix: vista detalle usa crop3 (miniatura) en lugar de crop2 (banner) |
| 91 | `3ed876b` | 2026-05-29 | Juan Pablo | feat: sidebar delay sutil, toolbar Quill adaptada al tema, botón barra lateral |
| 92 | `5d5cdd9` | 2026-05-29 | Juan Pablo | feat: animación sb-label sidebar + correcciones modo oscuro admin |
| 93 | `1241d9d` | 2026-05-29 | Juan Pablo | fix: tarjetas del home y preview de crear usan aspect-ratio del crop |
| 94 | `510f938` | 2026-05-29 | Juan Pablo | fix: modal de recorte sin área negra, imagen fija, crop box llena la imagen |
| 95 | `5f90be9` | 2026-05-29 | Juan Pablo | fix: zonas multimedia con aspect-ratio real, solo ratio en vacío, no sobreescribir crops |
| 96 | `a8c1d8c` | 2026-05-29 | Juan Pablo | feat: vista previa por sección (Hero, Top Semana, Recientes, Sidebar) |
| 97 | `9bc6d2f` | 2026-05-29 | Juan Pablo | fix: preview - renombrar tabs, tamaños consistentes, quitar 'Otra noticia' |
| 98 | `b8df450` | 2026-05-29 | Juan Pablo | feat: layout dos columnas en crear.php al estilo Meta Business |
| 99 | `304d842` | 2026-05-29 | Juan Pablo | feat: rediseño editar.php al estilo crear.php (dos columnas, secciones colapsables) |
| 100 | `efd7faa` | 2026-05-29 | Juan Pablo | fix: usar cn-left-col flex para evitar huecos entre secciones izquierdas |
| 101 | `9ce2463` | 2026-05-29 | unknown | feat: nav arriba en calendario + bloquear drag a fechas pasadas |
| 102 | `8e56b4d` | 2026-05-29 | unknown | fix: tarjetas responsive para calendario 7 columnas - badges compactos, layout ajustado |
| 103 | `b1b94d1` | 2026-05-29 | unknown | feat: modal fecha/hora al arrastrar noticia - permite elegir fecha y hora |
| 104 | `07b6dc2` | 2026-05-29 | unknown | fix: mensaje de error mas claro al intentar drop en zona no permitida |
| 105 | `2ced598` | 2026-05-29 | The-Cat-ink | Merge pull request #19 from The-Cat-ink/sam |
| 106 | `f6d17ec` | 2026-05-29 | Juan Pablo | fix: editor Quill — selectores y placeholder visibles en dark mode |
| 107 | `070b583` | 2026-05-29 | Juan Pablo | fix: avatares.php - cambiar footer.php por footerAdmin.php |
| 108 | `0e3b5ba` | 2026-05-29 | unknown | Ajustes de tema oscuro, drag & drop y validaciones de horario |
| 109 | `d0373be` | 2026-05-29 | unknown | Merge branch 'sam' |
| 110 | `d6ee987` | 2026-05-29 | Juan Pablo | merge: integrar origin/main en juan |
| 111 | `a48f0d5` | 2026-05-29 | unknown | Mejoras en calidad de imágenes y placeholder |
| 112 | `dbb9749` | 2026-05-29 | Juan Pablo | merge: integrar rama juan en main |
| 113 | `27e5d89` | 2026-06-01 | Juan Pablo | fix: imágenes rotas — fallback crop3→crop2→crop1 en index y news |
| 114 | `fe244b7` | 2026-06-01 | Juan Pablo | fix: círculos del slider usan crop3→crop2→crop1 como fallback |
| 115 | `7bc1246` | 2026-06-01 | unknown | Ignora carpeta de noticias en git |
| 116 | `7d4d064` | 2026-06-01 | unknown | Refine admin sidebar interactions |
| 117 | `d8de42a` | 2026-06-01 | unknown | Stabilize calendar nav layout |
| 118 | `cb93b5a` | 2026-06-01 | unknown | Hide sidebar scrollbar |
| 119 | `10666ca` | 2026-06-01 | unknown | Tighten calendar nav spacing |
| 120 | `ed64316` | 2026-06-01 | unknown | Smooth admin sidebar hover animation |
| 121 | `d46defe` | 2026-06-01 | unknown | Refine sidebar collapse width animation |
| 122 | `3cdd77c` | 2026-06-01 | unknown | Restore sidebar slide animation |
| 123 | `b5c2613` | 2026-06-01 | unknown | fix: Refactorizar sidebar para eliminar hover flicker y mejorar rendimiento |
| 124 | `22419f5` | 2026-06-01 | unknown | perf: Optimizar transición del sidebar a 0.3s para animación más rápida |
| 125 | `4eb7955` | 2026-06-01 | unknown | fix: Evitar movimiento del botón 'Hoy' en navegación de semanas |
| 126 | `c0c01f7` | 2026-06-01 | unknown | fix: Restaurar animación del sidebar al recargar la página |
| 127 | `83cddc3` | 2026-06-01 | unknown | feat: Sidebar cerrado por defecto en desktop |
| 128 | `41f1cf2` | 2026-06-01 | unknown | fix: Agregar cache buster a imágenes para evitar problemas con Ctrl+Shift+R |
| 129 | `fcb6c2e` | 2026-06-01 | unknown | fix: Usar placeholder si las imágenes no existen en producción |
| 130 | `85e5288` | 2026-06-01 | unknown | style: Quite animacion del logo |
| 131 | `eb8ef45` | 2026-06-01 | unknown | fix: Sincronizar transiciones del sidebar en ambas direcciones |
| 132 | `23f5c50` | 2026-06-01 | unknown | Refinar UI del sidebar: alineación de iconos, interruptor de tema con iconos sol/luna, y transiciones suaves |
| 133 | `41b8223` | 2026-06-02 | unknown | Remover cache busting de filemtime para evitar corrupción de imágenes |
| 134 | `92a5a90` | 2026-06-01 | Juan Pablo | fix: mejoras de responsive móvil en slider y cards de noticias |
| 135 | `b71316d` | 2026-06-02 | Juan Pablo | fix: calidad de imagen sin pérdida — PNG lossless en canvas + GD server-side |
| 136 | `9e2c331` | 2026-06-02 | unknown | Implementar protecciones críticas contra spam: rate limiting, detección de duplicados, validación de URLs en comentarios y perfil de editor |
| 137 | `1295953` | 2026-06-02 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 138 | `f0947d2` | 2026-06-02 | unknown | Mejorar validación de URLs en comentarios: validar longitud, formato y caracteres peligrosos |
| 139 | `e47897c` | 2026-06-02 | unknown | Implementar protecciones críticas Semana 1: lista negra de dominios adultos, validación MIME type, tamaño, dimensiones y protección contra path traversal |
| 140 | `e27fa55` | 2026-06-02 | unknown | Corregir corrupción de imágenes: estandarizar rutas, mejorar guardado de archivos con validaciones robustas y logging |
| 141 | `1ccce9a` | 2026-06-02 | unknown | Agregar script de diagnóstico para problemas de imágenes en producción |
| 142 | `3931b47` | 2026-06-02 | unknown | Corregir extensión de imágenes: cambiar .jpg a .webp para que coincida con el formato real |
| 143 | `a789b99` | 2026-06-02 | unknown | Mejorar calidad de imágenes: usar WebP directamente con calidad 85 en lugar de JPEG intermedio |
| 144 | `330267b` | 2026-06-02 | unknown | Guardar imágenes en formato original sin compresión para mantener calidad máxima |
| 145 | `45f9316` | 2026-06-02 | unknown | Agregar script de análisis de tamaño de BD |
| 146 | `fb8148b` | 2026-06-02 | Juan Pablo | fix: imágenes sin pérdida de calidad y vista previa de Inicio fiel |
| 147 | `edd975a` | 2026-06-02 | unknown | Solucionar 3 problemas críticos: 1) Compresión de imágenes (PNG sin compresión), 2) Botón editar en listado, 3) Carpetas de imágenes persistentes con .gitkeep |
| 148 | `fd626e6` | 2026-06-02 | unknown | Solucionar pérdida de imágenes en despliegues: guardar en /uploads/ (fuera de git) en lugar de /img/ |
| 149 | `47ece11` | 2026-06-02 | unknown | Eliminar compresión de imágenes: usar canvas.toBlob con calidad 1.0 en lugar de toDataURL |
| 150 | `0285d95` | 2026-06-02 | unknown | Guardar imágenes FUERA de public_html: /home/usuario/uploads/ en lugar de /public_html/uploads/ |
| 151 | `015eacb` | 2026-06-02 | unknown | Corregir visualización de imágenes: usar función imageUrl() en todas las vistas |
| 152 | `479a57b` | 2026-06-02 | unknown | Ruta arreglada de las imagenes |
| 153 | `28161ef` | 2026-06-02 | unknown | Fix: Corregir rutas de imágenes para producción usando imageUrl() |
| 154 | `3e142f9` | 2026-06-02 | Juan Pablo | fix: ajuste de miniatura en editar usa banner como fuente y toggle de programar desactivado por defecto |
| 155 | `d2084c8` | 2026-06-02 | Juan Pablo | Merge branch 'juan' into main |
| 156 | `fcc8785` | 2026-06-02 | unknown | Fix: Ajustar ruta de uploads para Hostinger (fuera de public_html) |
| 157 | `dedeaaa` | 2026-06-02 | Juan Pablo | chore: resolver conflictos de merge con main (remote) |
| 158 | `d8a10a2` | 2026-06-02 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 159 | `cfa9b19` | 2026-06-02 | unknown | Fix: Mejorar detección de ruta de uploads y agregar logging para debugging |
| 160 | `a15d2a5` | 2026-06-02 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 161 | `4777728` | 2026-06-02 | unknown | Add: Script de diagnóstico para verificar rutas de imágenes |
| 162 | `11348f8` | 2026-06-02 | unknown | Fix: Corregir rutas de uploads basado en estructura real de Hostinger |
| 163 | `dac708a` | 2026-06-02 | unknown | Fix: Usar ruta correcta de uploads en Hostinger (un nivel arriba de public_html) |
| 164 | `ff5cbe5` | 2026-06-02 | unknown | Clean: Remover scripts de diagnóstico |
| 165 | `76570c0` | 2026-06-02 | unknown | Add: Script de test para verificar carga de imágenes |
| 166 | `0824fdf` | 2026-06-02 | unknown | Fix: Corregir duplicación de ruta 'uploads/' en serve-image.php |
| 167 | `dccd095` | 2026-06-02 | unknown | Clean: Remover script de test |
| 168 | `7b83308` | 2026-06-02 | unknown | Fix: Actualizar rutas de imágenes a uploads/ y usar imageUrl() |
| 169 | `a8931c4` | 2026-06-02 | unknown | Fix: Corregir rutas de publicidad en index.php usando imageUrl() |
| 170 | `43845f3` | 2026-06-02 | unknown | Fix: Cambiar rutas de avatares de img/ a uploads/ |
| 171 | `9a41509` | 2026-06-02 | unknown | Revert: Mantener avatares en img/avatares/ |
| 172 | `714b123` | 2026-06-02 | unknown | Fix: Corregir rutas para que uploads/ apunte fuera de public_html |
| 173 | `1d709d9` | 2026-06-02 | unknown | Fix: Cambiar avatares a uploads/avatares/ y actualizar imageUrl() |
| 174 | `17e0b9f` | 2026-06-02 | unknown | Fix: Corregir rutas de uploads en controllers de avatares |
| 175 | `ea00926` | 2026-06-02 | unknown | Fix: Convertir rutas antiguas de avatares/editores a uploads/ |
| 176 | `5b96a45` | 2026-06-02 | unknown | Add: Script de test para publicidades |
| 177 | `afca978` | 2026-06-02 | unknown | Fix: Convertir rutas de publicidad de img/ a uploads/ |
| 178 | `dacce43` | 2026-06-02 | unknown | Clean: Remover script de test |
| 179 | `9b2a931` | 2026-06-02 | unknown | Improve: Filtro de palabras detecta variaciones con números y caracteres especiales |
| 180 | `cc21769` | 2026-06-02 | unknown | Fix: Corregir lógica del filtro de palabras prohibidas |
| 181 | `5ff5c7b` | 2026-06-03 | unknown | Add: Botón para editar perfil en vista pública y editar noticia desde la publicación |
| 182 | `db5228b` | 2026-06-03 | unknown | Quite el boton de inicio |
| 183 | `cb0ddb0` | 2026-06-03 | unknown | Remove: Quitar 'Inicio' del header |
| 184 | `ce4dcf8` | 2026-06-03 | unknown | Fix: Cambiar rutas de publicidad para apuntar a uploads/ fuera de public_html |
| 185 | `bba0750` | 2026-06-03 | unknown | Fix: Mejorar manejo de errores en guardado de imágenes de publicidad |
| 186 | `399d672` | 2026-06-03 | unknown | Add: Agregar JavaScript para crop de imágenes en publicidad |
| 187 | `e29262d` | 2026-06-03 | unknown | Add: Script de diagnóstico para rutas de publicidad |
| 188 | `817204f` | 2026-06-03 | unknown | Fix: Corregir rutas en diagnóstico de publicidad |
| 189 | `071e1f6` | 2026-06-03 | unknown | Fix: Usar misma estructura de rutas para publicidad que para noticias |
| 190 | `f436937` | 2026-06-03 | unknown | Fix: Guardar publicidad con file_put_contents en lugar de imagewebp |
| 191 | `098b3d7` | 2026-06-03 | unknown | Fix: Cambiar formato de imagen a PNG en JavaScript de publicidad |
| 192 | `47a5d9b` | 2026-06-03 | unknown | Fix: Mejorar JavaScript para crop de publicidad con DOMContentLoaded |
| 193 | `6d6a244` | 2026-06-03 | unknown | Debug: Agregar logs para verificar si imagenCrop llega al servidor |
| 194 | `9836cf1` | 2026-06-03 | unknown | Fix: Remover declaración duplicada de ACL en publicidad.php |
| 195 | `99ed28c` | 2026-06-03 | unknown | Fix: Cambiar const ACL a window.ACL en headerAdmin.php |
| 196 | `eaa17f7` | 2026-06-03 | unknown | Debug: Mejorar logs en JavaScript para crop de publicidad |
| 197 | `620ebf9` | 2026-06-03 | unknown | Fix: Cambiar a envío de blob en lugar de base64 para publicidad |
| 198 | `6672eaa` | 2026-06-03 | unknown | Fix: Usar misma lógica de notas para guardar publicidad |
| 199 | `3c28be2` | 2026-06-03 | unknown | Fix: Usar upload directo de archivo en lugar de base64 para publicidad |
| 200 | `b10e384` | 2026-06-03 | unknown | Add: Agregar Cropper.js para recorte de imágenes en publicidad |
| 201 | `09e3666` | 2026-06-03 | unknown | Add: Aspect ratio dinámico 16:9 para banner y 1:1 para cuadro |
| 202 | `a37d1c7` | 2026-06-03 | unknown | Feature: Agregar auditoría a noticias (creado_por, editado_por, ultima_edicion) |
| 203 | `c966f46` | 2026-06-03 | unknown | UI: Mostrar información de auditoría en editar noticia |
| 204 | `1e0a37a` | 2026-06-03 | unknown | Fix: Mostrar auditoría incluso si no hay información |
| 205 | `b6bfab1` | 2026-06-03 | unknown | Fix: Llenar creado_por en notas antiguas al editar |
| 206 | `55dc5cf` | 2026-06-03 | Juan Pablo | feat: botón eliminar noticia en editar con modal de confirmación |
| 207 | `abb3095` | 2026-06-03 | unknown | Fix: Redirigir a editar.php para mostrar auditoría actualizada |
| 208 | `eea5712` | 2026-06-03 | unknown | Fix: Usar imageUrl() para servir imágenes correctamente en editar.php |
| 209 | `3dba64d` | 2026-06-03 | unknown | Feature: Eliminar imágenes antiguas al editar notas |
| 210 | `c69ace7` | 2026-06-03 | unknown | Debug: Agregar logs para auditoría de noticias |
| 211 | `0546806` | 2026-06-03 | unknown | Fix: Asegurar que creado_por y editado_por se guardan como integers |
| 212 | `06ada52` | 2026-06-03 | Juan Pablo | feat: mover botón publicar fuera del cuadro de programar en crear.php |
| 213 | `b765edf` | 2026-06-03 | unknown | Fix: Guardar id_u en sesión para auditoría |
| 214 | `4a4bbdb` | 2026-06-03 | Juan Pablo | feat: mover botones guardar y eliminar fuera del cuadro de programar en editar.php |
| 215 | `5bf7bd1` | 2026-06-03 | Juan Pablo | chore: guardar cambios pendientes en styles.css, header y avatares |
| 216 | `a82f6b4` | 2026-06-03 | Juan Pablo | Merge remote-tracking branch 'origin/main' into juan |
| 217 | `5d6f31f` | 2026-06-03 | unknown | Fix: Corregir ruta de require_once en aclcontroller.php |
| 218 | `ab5639f` | 2026-06-03 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 219 | `e20b448` | 2026-06-03 | unknown | Fix: Restaurar animaciones del slider |
| 220 | `23de143` | 2026-06-03 | Juan Pablo | feat: rediseño de crearp.php con el mismo sistema visual de crear/editar |
| 221 | `27c9c7a` | 2026-06-03 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 222 | `e21bd25` | 2026-06-03 | unknown | Fix: Deshabilitar auto-hide del navbar para mantenerlo siempre visible |
| 223 | `cc7b4c8` | 2026-06-03 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 224 | `5ba9291` | 2026-06-03 | unknown | Revert: Restaurar auto-hide del navbar |
| 225 | `df24a6f` | 2026-06-03 | unknown | Fix: Restaurar transiciones suaves del carrusel |
| 226 | `243ef3f` | 2026-06-03 | unknown | Fix: Remover reset global de transiciones que bloqueaba el carrusel |
| 227 | `e7962fd` | 2026-06-03 | unknown | Fix: Agregar !important a transiciones del carrusel |
| 228 | `1e5fe4e` | 2026-06-03 | unknown | Debug: Agregar logs al carrusel |
| 229 | `837b3e6` | 2026-06-03 | unknown | Fix: Remover .site-main del reset de transiciones para que carrusel funcione |
| 230 | `b5610b2` | 2026-06-03 | unknown | Fix: Agregar regla específica para transiciones del carrusel después del reset global |
| 231 | `598e480` | 2026-06-03 | Juan Pablo | fix: rutas de uploads con detección de entorno local/producción |
| 232 | `e613cc2` | 2026-06-04 | unknown | Feat: Agregar botón de eliminar suscriptores |
| 233 | `1f987ce` | 2026-06-04 | unknown | Fix: Mejorar manejo de errores en eliminarSuscriptor.php |
| 234 | `0c0585d` | 2026-06-04 | unknown | Fix: Usar id_sub en lugar de id_suscripcion |
| 235 | `2fd80d0` | 2026-06-04 | unknown | Fix: Mostrar botón eliminar siempre y validar permisos en controlador |
| 236 | `58943dc` | 2026-06-04 | Juan Pablo | Merge branch 'juan' |
| 237 | `257f82e` | 2026-06-04 | unknown | Feat: Agregar botón para enviar correos a suscriptores específicos |
| 238 | `cf69b31` | 2026-06-04 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 239 | `afb9d87` | 2026-06-04 | unknown | Fix: Mejorar logs y permitir envío sin noticias |
| 240 | `1117667` | 2026-06-05 | unknown | Security: Mover todas las credenciales SMTP a .env |
| 241 | `e5855be` | 2026-06-05 | unknown | Feat: Agregar notificaciones Toast para correos y programación |
| 242 | `a5a64ee` | 2026-06-05 | unknown | Fix: Incluir env.php en todos los archivos de correo |
| 243 | `b55882e` | 2026-06-05 | unknown | Feat: Agregar selección múltiple y envío de correos a varios suscriptores |
| 244 | `8b1566f` | 2026-06-05 | Juan Pablo | Fix: Alinear navbar y hero con secciones; eliminar crop1 de crear |
| 245 | `5cf6daf` | 2026-06-05 | unknown | Fix: Mejorar adjunción de imágenes en correos |
| 246 | `2b6d770` | 2026-06-05 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 247 | `6f57bc9` | 2026-06-05 | unknown | Fix: Descargar WebP antes de convertir a PNG |
| 248 | `08a1a5a` | 2026-06-05 | unknown | Fix: Mejorar logs para debug de imágenes en correos |
| 249 | `4540281` | 2026-06-05 | unknown | Fix: Agregar archivo de log para debug de correos |
| 250 | `4e3d8d9` | 2026-06-05 | unknown | Fix: Mejorar creación de archivo de log |
| 251 | `0cdaf4d` | 2026-06-05 | unknown | Fix: Usar serve-image.php para obtener imágenes correctamente |
| 252 | `ffde54c` | 2026-06-05 | unknown | Fix: Usar URLs remotas para imágenes en correos en lugar de adjuntarlas |
| 253 | `6e91c33` | 2026-06-05 | unknown | Feat: Sistema completo de recuperación de contraseña para usuarios y administradores |
| 254 | `a2f0053` | 2026-06-05 | unknown | Docs: Agregar tabla password_reset_tokens a migrations.sql |
| 255 | `46434ba` | 2026-06-05 | unknown | Fix: Renombrar archivos sin caracteres especiales (ñ) |
| 256 | `51b6182` | 2026-06-05 | unknown | Fix: Agregar rutas en .htaccess para recuperación de contraseña |
| 257 | `8b4a763` | 2026-06-05 | unknown | Style: Aplicar formato de login/registro a páginas de recuperación de contraseña |
| 258 | `d2ef68f` | 2026-06-05 | unknown | Fix: Permitir acceso a recuperación de contraseña con y sin .php |
| 259 | `426eca5` | 2026-06-05 | unknown | Fix: Crear archivos proxy en raíz para recuperación de contraseña |
| 260 | `8b07467` | 2026-06-05 | unknown | Remove: Eliminar archivos proxy, usar solo .htaccess |
| 261 | `73a85ad` | 2026-06-05 | unknown | Fix: Actualizar .htaccess para solo aceptar URLs sin .php |
| 262 | `e86c901` | 2026-06-05 | unknown | Fix: Actualizar enlace de olvide contraseña en login sin .php |
| 263 | `684d6d9` | 2026-06-05 | unknown | Fix: Corregir URL del enlace de reset en correo |
| 264 | `eeb0937` | 2026-06-05 | unknown | Feat: Aumentar duración del token de reset a 24 horas |
| 265 | `b4df413` | 2026-06-05 | Juan Pablo | Layout: limitar ancho a 1400px y alinear navbar, carousel y contenido principal |
| 266 | `085277c` | 2026-06-05 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 267 | `cb089d0` | 2026-06-05 | unknown | Fix: Agregar versión al favicon para forzar recarga |
| 268 | `8cc74dd` | 2026-06-05 | unknown | Fix: Usar URL absoluta para favicon en producción |
| 269 | `1f8639e` | 2026-06-05 | Juan Pablo | Fix: eliminar containers anidados y alinear carousel caption con navbar |
| 270 | `9d20ee9` | 2026-06-05 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 271 | `3e8e960` | 2026-06-05 | Juan Pablo | Feat: aumentar límite de título de noticia de 50 a 80 caracteres |
| 272 | `f426dc8` | 2026-06-05 | unknown | Fix: Cambiar rutas relativas a absolutas para compatibilidad con Linux |
| 273 | `a858197` | 2026-06-05 | unknown | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 274 | `fdddda8` | 2026-06-05 | unknown | Feat: Agregar drag-and-drop para reordenar categorías |
| 275 | `a006af9` | 2026-06-05 | Juan Pablo | Fix: indicador de carousel siempre blanco sin importar el modo de color |
| 276 | `48a2231` | 2026-06-05 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 277 | `f1995da` | 2026-06-05 | Juan Pablo | Fix: usar basePath() en layouts y mover indicadores carousel a la izquierda |
| 278 | `8dca959` | 2026-06-08 | Juan Pablo | WIP: layout top publicaciones y fix grid col-md-4/col-md-8 |
| 279 | `6f71bdb` | 2026-06-08 | Juan Pablo | WIP: agregar fila 4 (thumb+banner) en top publicaciones de la semana |
| 280 | `dbc0e6a` | 2026-06-08 | Juan Pablo | Feat: layout top publicaciones semana y ajuste visual de tarjetas |
| 281 | `0657de4` | 2026-06-08 | Juan Pablo | Fix: quitar Fila 4 de Top Publicaciones y reducir slice a 7 |
| 282 | `60d34dc` | 2026-06-08 | Juan Pablo | Fix: reducir tamaño de cards top publicaciones a aspect-ratio 16/9 |
| 283 | `04c21c9` | 2026-06-08 | Juan Pablo | Feat: mover buscador del header al menú móvil |
| 284 | `1cb1cea` | 2026-06-08 | Samuel Gutiérrez | Mejoras UI/UX: Estandarización panel admin, SortableJS, Modales modernos y Guardado AJAX en noticias |
| 285 | `94b0e98` | 2026-06-08 | Samuel Gutiérrez | Agregue la ultima modificacion del sql al archivo migrations |
| 286 | `e9ddc80` | 2026-06-08 | Juan Pablo | Fix: ajustes visuales navbar móvil — layout sin espacios muertos |
| 287 | `f5ca385` | 2026-06-08 | Juan Pablo | Fix: aumentar tamaño iconos navbar móvil y corregir switch de tema |
| 288 | `bf7f4c1` | 2026-06-08 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 289 | `5050a7d` | 2026-06-08 | Samuel Gutiérrez | Nuevos iconos |
| 290 | `1aaceb9` | 2026-06-08 | Juan Pablo | Fix: quitar iconos de títulos de secciones |
| 291 | `4c60cfd` | 2026-06-08 | Samuel Gutiérrez | Logo ajustado |
| 292 | `8792b57` | 2026-06-08 | Juan Pablo | Feat: mover indicadores del carrusel a columna vertical izquierda |
| 293 | `c4edfca` | 2026-06-08 | Juan Pablo | Fix: exponer instancia de Quill como window.quill para que crear.php pueda leer el contenido del editor al publicar |
| 294 | `9018519` | 2026-06-08 | Juan Pablo | Merge: resolver conflicto en index.php conservando headings sin iconos y guardando código PHP del remote |
| 295 | `7a4f8e8` | 2026-06-09 | Juan Pablo | Feat: unificar formato de recorte de miniatura a 16:9 en crear y editar |
| 296 | `bd6e900` | 2026-06-09 | Samuel Gutiérrez | Mejora: reducir tamaño del logo, agregar 'Crear Noticia' al menú y actualizar Top Semanal por vistas |
| 297 | `9da76a5` | 2026-06-09 | Samuel Gutiérrez | Quite el royo de el top semanal/mensual |
| 298 | `a195dd4` | 2026-06-09 | Samuel Gutiérrez | Fijar título del Top a 'Top Semanal' |
| 299 | `eb24297` | 2026-06-09 | Juan Pablo | Fix: img-titular ocupa ancho completo de columna; unificar formato cropper editar.php |
| 300 | `8b3f5d4` | 2026-06-09 | Samuel Gutiérrez | Restaurar iconos perdidos de Recomendados y Noticias recientes |
| 301 | `9f351ec` | 2026-06-09 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 302 | `d78677b` | 2026-06-09 | Samuel Gutiérrez | Restaurar icono en 'Lo más recientes' |
| 303 | `5176057` | 2026-06-09 | Samuel Gutiérrez | Solucionar caché de CSS añadiendo versión dinámica por timestamp |
| 304 | `0a48007` | 2026-06-09 | Juan Pablo | Fix: aumentar max-height del acordeón en crear y editar para permitir artículos largos en Quill |
| 305 | `ac4da07` | 2026-06-09 | Samuel Gutiérrez | Solucionar enlaces de categorias: aumentar z-index para que sean clickeables en vez de la tarjeta |
| 306 | `9eb775e` | 2026-06-09 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 307 | `42f9ad5` | 2026-06-09 | Samuel Gutiérrez | Convertir spans de categorias a etiquetas 'a' (hipervinculos) |
| 308 | `6461f91` | 2026-06-09 | Samuel Gutiérrez | Crear páginas de Top Semanal y Lo más recientes e integrar hipervínculos en el home |
| 309 | `326e60c` | 2026-06-09 | Samuel Gutiérrez | Añadir rutas de top y recientes al htaccess |
| 310 | `351e9cd` | 2026-06-09 | Samuel Gutiérrez | Solucionar imagen ovalada en sidebar e hipervincular los títulos Lo más nuevo y Lo más popular en todas las vistas |
| 311 | `42b2128` | 2026-06-09 | Samuel Gutiérrez | Crear vista popular.php e hipervincularla en el sidebar para Lo más popular de todos los tiempos |
| 312 | `a283470` | 2026-06-09 | Juan Pablo | Feat: responsividad en monitores grandes usando variable --cw escalable por breakpoint |
| 313 | `1d37e91` | 2026-06-09 | Samuel Gutiérrez | Agruegue al htacces la nueva ruta para crear una noticia desde el panel de administracion |
| 314 | `8fbc64f` | 2026-06-09 | Samuel Gutiérrez | Corregir enlaces del dashboard de admin para que apunten a la ruta limpia de creacion de noticias |
| 315 | `fffdd36` | 2026-06-09 | Samuel Gutiérrez | Corregi htacces |
| 316 | `2a53f8d` | 2026-06-09 | Samuel Gutiérrez | Restaurar las rutas relativas originales para el boton crear noticia |
| 317 | `9720af0` | 2026-06-09 | Samuel Gutiérrez | Mover la sección de Ultimas Noticias al principio del panel Admin, arreglar enlaces y agregar botón de editar noticia. |
| 318 | `940916f` | 2026-06-09 | Samuel Gutiérrez | Cambiar boton de cerrar sesion a un dropdown, e incluir opcion rapida para crear noticias desde el header |
| 319 | `3c49cff` | 2026-06-09 | Samuel Gutiérrez | Solucionar dropdown de usuario roto en header debido a estilos de bootstrap en la vista admin. Modificar enlace de crear noticia en navbar para ir a views/crear.php en lugar de la url amigable vieja. |
| 320 | `23b7ec2` | 2026-06-09 | Samuel Gutiérrez | Solucionar imagenes rotas en admin local (Laravel Herd catink.test). El serve-image detectaba produccion |
| 321 | `83a60ee` | 2026-06-09 | Juan Pablo | Feat: mover indicadores del carousel a fila horizontal debajo del texto |
| 322 | `e2c0854` | 2026-06-09 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 323 | `1f9aae6` | 2026-06-09 | Samuel Gutiérrez | Corregir ciertos botones y corregir algunas otras partes(perfil y agregar boton de escribir en el header) |
| 324 | `23e94c0` | 2026-06-09 | Juan Pablo | Fix: agregar margen horizontal en vista de noticia en movil |
| 325 | `ad997b4` | 2026-06-09 | Juan Pablo | Fix: normalizar iconos del navbar en movil y ocultar nombre de usuario |
| 326 | `75c4fea` | 2026-06-09 | Juan Pablo | Fix: usar aspect-ratio 16/9 en carousel movil para evitar upscale de imagenes |
| 327 | `d44c662` | 2026-06-10 | Juan Pablo | Fix: toggle dropdown de usuario al hacer click en movil |
| 328 | `98fbc8d` | 2026-06-10 | Juan Pablo | Fix: deshabilitar hover en movil para dropdown de usuario, solo toggle por JS |
| 329 | `f8fc122` | 2026-06-10 | Samuel Gutiérrez | Feat: url amigable |
| 330 | `83d71f0` | 2026-06-10 | Samuel Gutiérrez | Fix: agregar fallback en newsUrl para noticias sin slug |
| 331 | `29a001f` | 2026-06-10 | Samuel Gutiérrez | Fix: usar COALESCE(slug, id) para compatibilidad con noticias existentes |
| 332 | `f1186c7` | 2026-06-10 | Samuel Gutiérrez | Fix: actualizar .htaccess para aceptar cualquier carácter en URLs de noticias |
| 333 | `4e311c9` | 2026-06-10 | Samuel Gutiérrez | Fix: agregar lógica para decodificar IDs codificados en news.php |
| 334 | `d850ba1` | 2026-06-10 | Juan Pablo | Corregir ciertos botones y corregir algunas otras partes(perfil y agregar boton de escribir en el header) |
| 335 | `43513fd` | 2026-06-10 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 336 | `c060530` | 2026-06-10 | Samuel Gutiérrez | Fix: agregar fallback para decodificar ID si no se encuentra por slug |
| 337 | `73e793e` | 2026-06-10 | Samuel Gutiérrez | Fix: corregir bind_param type cuando se decodifica ID |
| 338 | `26a30bf` | 2026-06-10 | Juan Pablo | Fix: asignar $id desde $noticia para evitar variable indefinida con slug |
| 339 | `fd4603d` | 2026-06-10 | Samuel Gutiérrez | Refactor: usar newsUrlFromRow para manejar slug NULL con fallback a ID codificado |
| 340 | `3f4e2b1` | 2026-06-10 | Juan Pablo | Fix: agregar COALESCE(n.slug, n.id) en query principal de index.php |
| 341 | `e5cc1e1` | 2026-06-10 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 342 | `787cf76` | 2026-06-10 | Juan Pablo | Fix: corregir tipos en bind_param de noticiascontroller y quitar COALESCE innecesario en index |
| 343 | `f891595` | 2026-06-10 | Juan Pablo | Fix: corregir tipo de bind_param en actualizar_noticia (ssssssiii -> sssssiii) |
| 344 | `c8ec341` | 2026-06-10 | Juan Pablo | Feat: agregar botón de cerrar sesión en página de perfil |
| 345 | `00bd2e8` | 2026-06-10 | Juan Pablo | Fix: corregir layout móvil del navbar — hamburguesa arriba, menú sin opciones duplicadas abajo |
| 346 | `f19899c` | 2026-06-10 | Juan Pablo | Fix: corregir sidebar admin en móvil — mostrar ancho y etiquetas al abrir menú |
| 347 | `6d98ee4` | 2026-06-10 | Samuel Gutiérrez | Feat menu desplegable en click de perfil y ciertas correciones de estilos y un par de detallitos mas |
| 348 | `7307c2c` | 2026-06-10 | Samuel Gutiérrez | Nuevos estilos |
| 349 | `9526831` | 2026-06-10 | Juan Pablo | Fix: mejorar carousel móvil — indicadores fuera de imagen, texto al fondo, descripción completa visible |
| 350 | `c4f9989` | 2026-06-10 | Juan Pablo | Fix: animación de paneo completo (izq→der→izq) en 10s por slide en móvil |
| 351 | `ed37888` | 2026-06-10 | Juan Pablo | Fix: reducir espacios entre carousel, indicadores y Top Semanal en móvil |
| 352 | `ae7494f` | 2026-06-10 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 353 | `0437dc3` | 2026-06-10 | Juan Pablo | Fix: centrar section-separator en móvil con líneas a ambos lados |
| 354 | `59ebcc6` | 2026-06-11 | Juan Pablo | Fix: reducir tamaño del label de sección en móvil |
| 355 | `3b481bf` | 2026-06-11 | Juan Pablo | Fix: corregir fondo del login usando --bg en lugar de --bg-body indefinida |
| 356 | `df518a3` | 2026-06-11 | Samuel Gutiérrez | Perf: Optimizacion de la pagina principal, reduccion de tiempos de carga con consultas preparadas y reduccion de carga de noticias |
| 357 | `13f6c96` | 2026-06-11 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 358 | `2cdc9d5` | 2026-06-11 | Samuel Gutiérrez | Solucion de errores |
| 359 | `f13b9ae` | 2026-06-11 | Juan Pablo | Fix: corregir race condition en confirmCrop — previsualizaciones aparecen al instante |
| 360 | `eafeb5f` | 2026-06-11 | Samuel Gutiérrez | Cambiar rutas para solucion de errores |
| 361 | `f512479` | 2026-06-11 | Samuel Gutiérrez | Solucion error foto de perfil personalizada |
| 362 | `a3dd53b` | 2026-06-11 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 363 | `f76a2c1` | 2026-06-11 | Samuel Gutiérrez | Fix: eliminar prefijo duplicado img/avatares/ en rutas de avatares |
| 364 | `1e8baeb` | 2026-06-11 | Juan Pablo | Fix: sincronizar banner y miniatura al subir imagen por cualquier zona |
| 365 | `0fd3909` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir subida de foto personal de editores |
| 366 | `0df50ed` | 2026-06-11 | Samuel Gutiérrez | Debug: agregar logs para investigar subida de foto personal |
| 367 | `ca38549` | 2026-06-11 | Juan Pablo | Feat: flujo de recorte en 2 pasos — confirmar uno abre el otro formato automáticamente |
| 368 | `14ee9ac` | 2026-06-11 | Samuel Gutiérrez | Feature: mostrar ruta de imagen enviada en mensaje de exito |
| 369 | `6c7c5b0` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir ruta de fotos de editores para estar afuera de public_html |
| 370 | `f6b6a77` | 2026-06-11 | Samuel Gutiérrez | Fix: actualizar serve-image.php para servir fotos de editores desde /home/u780114275/uploads/ |
| 371 | `c2a055b` | 2026-06-11 | Juan Pablo | Fix: ajustar posición inicial del recorte de miniatura a 0.85 (más a la derecha) |
| 372 | `7e71533` | 2026-06-11 | Samuel Gutiérrez | Feature: agregar opcion de foto personal en modal de avatares |
| 373 | `b517163` | 2026-06-11 | Samuel Gutiérrez | Fix: redimensionar imagenes automaticamente en lugar de rechazarlas |
| 374 | `0918672` | 2026-06-11 | Juan Pablo | Fix: restaurar posición del recorte al ajustar — recuerda donde lo dejaste |
| 375 | `96fd756` | 2026-06-11 | Samuel Gutiérrez | Feature: agregar cropper para recortar fotos de perfil |
| 376 | `d6ca4cf` | 2026-06-11 | Samuel Gutiérrez | Fix: permitir que cualquier admin edite notas desde la vista de la nota |
| 377 | `752911a` | 2026-06-11 | Samuel Gutiérrez | Feature: subir foto personal automaticamente al confirmar recorte |
| 378 | `e5450a9` | 2026-06-11 | Juan Pablo | Fix: portar mejoras del cropper de crear.php a editar.php |
| 379 | `1f0711d` | 2026-06-11 | Samuel Gutiérrez | Feature: mostrar foto personal o avatar en icono de perfil del header |
| 380 | `cca8784` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir error imageUrl is not defined en JavaScript |
| 381 | `cf10ab0` | 2026-06-11 | Samuel Gutiérrez | Fix: deshabilitar movimiento de imagen en cropper |
| 382 | `fabacea` | 2026-06-11 | Samuel Gutiérrez | Fix: relajar validaciones de regex para links sociales |
| 383 | `e26fb3e` | 2026-06-11 | Samuel Gutiérrez | Debug: agregar logs en usuarios.php para identificar error silencioso |
| 384 | `4220760` | 2026-06-11 | Samuel Gutiérrez | Debug: habilitar display_errors para ver error en pantalla |
| 385 | `c9ab530` | 2026-06-11 | Samuel Gutiérrez | Revert: temporalmente revertir cambio de imageUrl en usuarios.php |
| 386 | `8237be9` | 2026-06-11 | Samuel Gutiérrez | Debug: cambiar logs a comentarios HTML para ver en código fuente |
| 387 | `0e5f4c3` | 2026-06-11 | Samuel Gutiérrez | Debug: agregar archivo test.php para verificar PHP |
| 388 | `52fbb43` | 2026-06-11 | Samuel Gutiérrez | Debug: simplificar usuarios.php al minimo absoluto |
| 389 | `f140121` | 2026-06-11 | Samuel Gutiérrez | Feature: tooltips interactivos en gestión de contenidos |
| 390 | `b671318` | 2026-06-11 | Juan Pablo | Feat: guardar original escalado en crop1 para que Ajustar en editar.php muestre la imagen completa |
| 391 | `0ddf23c` | 2026-06-11 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 392 | `f33a2fb` | 2026-06-11 | Samuel Gutiérrez | Fix: cambiar posición del tooltip a la derecha del día |
| 393 | `ca7d1ae` | 2026-06-11 | Samuel Gutiérrez | Fix: cambiar posición del tooltip a arriba de la columna |
| 394 | `43595b7` | 2026-06-11 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 395 | `a58f7cd` | 2026-06-11 | Samuel Gutiérrez | Fix: agregar delay de 300ms antes de ocultar tooltip |
| 396 | `f5ff259` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir comportamiento de tooltips |
| 397 | `2e11fac` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir tooltip de noticias que no desaparece |
| 398 | `8928cee` | 2026-06-11 | Samuel Gutiérrez | Fix: simplificar lógica del tooltip de noticias |
| 399 | `dbf1e31` | 2026-06-11 | Samuel Gutiérrez | Fix: eliminar animaciones CSS de tooltips para simplificar lógica |
| 400 | `c5cca2a` | 2026-06-11 | Samuel Gutiérrez | Feature: cerrar tooltips al hacer click fuera |
| 401 | `91cfcc8` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir tooltips para mostrar solo noticias del día y evitar duplicados |
| 402 | `5ee6e80` | 2026-06-11 | Samuel Gutiérrez | Revert: restaurar usuarios.php a su estado original |
| 403 | `d2c8e16` | 2026-06-11 | Samuel Gutiérrez | Feature: agregar animaciones suaves a tooltips y mejorar consistencia |
| 404 | `34cecd2` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir data-date en tooltip de autor para usar fecha correcta |
| 405 | `4706416` | 2026-06-11 | Samuel Gutiérrez | Debug: agregar console.logs para investigar por qué no aparecen noticias en tooltip de autor |
| 406 | `1c209db` | 2026-06-11 | Juan Pablo | Fix: usuarios.php en blanco por re-include de urlhelper.php + merge remoto |
| 407 | `47538d1` | 2026-06-11 | Juan Pablo | Fix: mostrar foto_personal en lugar del avatar preset en usuarios.php |
| 408 | `cdb5e40` | 2026-06-11 | Samuel Gutiérrez | Fix: aumentar delay de tooltips de 200ms a 500ms y eliminar debug logs |
| 409 | `988f1fa` | 2026-06-11 | Samuel Gutiérrez | Fix: aumentar delay de tooltips a 1000ms (1 segundo) |
| 410 | `ef26b52` | 2026-06-11 | Samuel Gutiérrez | Fix: corregir tooltip de autores que desaparece instantáneamente |
| 411 | `2b0b004` | 2026-06-11 | Juan Pablo | Fix: tooltips de contenidos — posición incorrecta y desaparición prematura |
| 412 | `eaaf2b7` | 2026-06-11 | Juan Pablo | Fix: tooltip de autores solo se activa sobre el badge .day-count |
| 413 | `408d394` | 2026-06-11 | Juan Pablo | Feat: gestión de foto de perfil desde usuarios.php + reducir section-separator-label |
| 414 | `0a93042` | 2026-06-11 | Juan Pablo | Fix: tooltip de autores no disparaba — trigger en .day-header en vez de .day-count |
| 415 | `d0c566a` | 2026-06-11 | Juan Pablo | Fix: tooltip de noticias se activaba múltiples veces por bubbling |
| 416 | `90936fc` | 2026-06-11 | Juan Pablo | Fix: tooltips no desaparecían al volver el mouse rápido |
| 417 | `b108e93` | 2026-06-11 | Juan Pablo | Feat: separadores de sección — links en móvil y alineación/espaciado en desktop |
| 418 | `8be193a` | 2026-06-11 | Juan Pablo | Feat: validaciones en crearp.php + fix cropper miniatura en noticias antiguas |
| 419 | `1b2e92c` | 2026-06-11 | Juan Pablo | Fix: previsualizaciones en editar.php no cargaban en producción |
| 420 | `6988853` | 2026-06-11 | Juan Pablo | Fix: paneo del carousel móvil limitado al centro y rango ajustado |
| 421 | `e4092c0` | 2026-06-11 | Juan Pablo | Feat: paneo del carousel móvil arranca desde el centro |
| 422 | `6394c46` | 2026-06-12 | Juan Pablo | Feat: carousel móvil — 7s, paneo solo derecha→izquierda |
| 423 | `aa6a419` | 2026-06-12 | Juan Pablo | Fix: paneo del carousel móvil cambiado a izquierda → derecha |
| 424 | `5fc63a2` | 2026-06-12 | Juan Pablo | Fix: animación de progreso de círculos sincronizada con el intervalo del carousel |
| 425 | `015032d` | 2026-06-12 | Juan Pablo | Fix: paneo del carousel móvil — rango reducido a 35%→65% |
| 426 | `319c974` | 2026-06-12 | Juan Pablo | Fix: paneo del carousel móvil — rango ajustado a 42%→58% (solo zona central) |
| 427 | `3c6075e` | 2026-06-12 | Juan Pablo | Fix: ruta /nosotros faltante en .htaccess causaba 404 |
| 428 | `87a899d` | 2026-06-12 | Juan Pablo | Fix: modal de páginas legales scrolleable cuando el contenido es largo |
| 429 | `35ccd34` | 2026-06-12 | Samuel Gutiérrez | Mejorar diseño y distribucion de la pagina |
| 430 | `59f5af8` | 2026-06-12 | Juan Pablo | Feat: gestión de logos de marcas + fix encoding acentos en paginas.php |
| 431 | `b5c0680` | 2026-06-12 | Juan Pablo | Feat: rediseño página nosotros — hero, layout y animación horizontal de logos |
| 432 | `fafb20f` | 2026-06-12 | Juan Pablo | Merge: integrar cambios remotos en index.php |
| 433 | `55109cd` | 2026-06-12 | Juan Pablo | Fix: revertir navbar-hero — el navbar no debe superponerse al carousel |
| 434 | `603e8fe` | 2026-06-15 | Juan Pablo | Feat: migración 002 — CREATE TABLE logos_marcas |
| 435 | `9e9dc73` | 2026-06-15 | Juan Pablo | Feat: fecha de expiración automática para logos de marcas |
| 436 | `e1e8db9` | 2026-06-15 | Juan Pablo | Feat: picker de fecha+hora para expiración de logos — diseño de crear.php |
| 437 | `638189a` | 2026-06-15 | Juan Pablo | Feat: botón editar en tarjetas de logos — nombre y fecha de vencimiento |
| 438 | `950bf9b` | 2026-06-15 | Juan Pablo | Fix: alineación de columnas y botón en paginas.php |
| 439 | `a9e8368` | 2026-06-15 | Juan Pablo | Feat: migraciones logos_marcas — tabla, fecha_expiracion y orden |
| 440 | `f12ced3` | 2026-06-15 | Juan Pablo | Feat: orden de logos con drag & drop y filas por importancia en nosotros |
| 441 | `ef7ba75` | 2026-06-15 | Juan Pablo | Fix: eliminar hueco en carrusel de logos con filas incompletas |
| 442 | `c8f87e3` | 2026-06-15 | Juan Pablo | Feat: filas de logos dinámicas y mensaje cuando no hay logos en nosotros.php |
| 443 | `e40ef8b` | 2026-06-16 | Juan Pablo | Fix: ajuste de espaciado en nosotros.php y texto de hint en paginas.php |
| 444 | `b0c1cf4` | 2026-06-16 | Juan Pablo | Feat: cambiar imagen de logo desde el modal de edición en paginas.php |
| 445 | `d8c6aed` | 2026-06-16 | Juan Pablo | Feat: mostrar días y horas restantes en badge de expiración de logos |
| 446 | `c1de86f` | 2026-06-16 | Juan Pablo | Fix: quitar clase logo-card-vencida al actualizar fecha de expiración de logo |
| 447 | `812ca28` | 2026-06-16 | Juan Pablo | Fix: margen horizontal en carrusel de logos y alineación del texto en nosotros.php |
| 448 | `4eec818` | 2026-06-16 | Juan Pablo | Fix: eliminar desvanecido del carrusel — corte limpio en el margen del texto |
| 449 | `7a152a4` | 2026-06-16 | Juan Pablo | Fix: alinear carrusel de logos con el margen del texto usando nos-brands-outer |
| 450 | `35308d4` | 2026-06-16 | Juan Pablo | Feat: agregar calificación editorial (patitas 1-5) a noticias |
| 451 | `9fdde30` | 2026-06-16 | Samuel Gutiérrez | Respaldar cambios actuales antes de comenzar rediseño y sistema de reseñas |
| 452 | `ba006cc` | 2026-06-16 | Samuel Gutiérrez | style: align sidebars, center pagination, and reduce vertical spacing of video sections |
| 453 | `7254bd6` | 2026-06-16 | Juan Pablo | Feat: quitar tercera fila Top Semanal y separador de sección en marcas |
| 454 | `5fabb6d` | 2026-06-17 | Juan Pablo | Merge: integrar cambios remotos (estilos sidebar, paginacion, proxy YouTube) con cambios locales (calificacion editorial, top semanal simplificado) |
| 455 | `7234db0` | 2026-06-17 | Juan Pablo | Fix: usar diseno de produccion en index.php (version remota con sidebar de ranking, paginacion y soporte de calificacion) |
| 456 | `68aef40` | 2026-06-17 | Juan Pablo | Feat: respetar orden de importancia de categorias al publicar noticias |
| 457 | `0d59136` | 2026-06-17 | Juan Pablo | Fix: eliminar llave de cierre duplicada en styles.css |
| 458 | `36a242e` | 2026-06-17 | Juan Pablo | Feat: agregar formato Paisaje (21:9) a multimedia en crear/editar noticias |
| 459 | `67ff1e7` | 2026-06-17 | Samuel Gutiérrez | feat: mejoras en calificaciones, proximos estrenos y notificaciones |
| 460 | `cc9ff30` | 2026-06-18 | Juan Pablo | Refactor: reemplazar cadena fija de crop por cola dinamica (_chainQueue) |
| 461 | `e283480` | 2026-06-18 | Juan Pablo | Merge: integrar sistema de reviews/estrenos del remoto con crop4 y orden de categorias local |
| 462 | `3222afc` | 2026-06-18 | Juan Pablo | Feat: rediseñar modal de banner para llenar toda el area (21:6, viewMode 3, arranque desde tope) |
| 463 | `3428656` | 2026-06-18 | Juan Pablo | Feat: posicion inicial del recorte Paisaje (21:9) pegada a la derecha |
| 464 | `08bf1c7` | 2026-06-18 | Juan Pablo | Fix: eliminar seccion de calificacion editorial (5 patitas) de crear y editar |
| 465 | `656b1c5` | 2026-06-18 | Juan Pablo | Feat: usar imagen centrada (21:9) en cards anchas y rediseñar card de review |
| 466 | `bb3252b` | 2026-06-18 | Samuel Gutiérrez | feat: sistema curado recomendados/esperados + fix reviews homepage + reseña arriba del contenido + sidebar compacto |
| 467 | `3608caf` | 2026-06-22 | Juan Pablo | Fix: revertir card ancha TOP SEMANAL a crop2 y aspect-ratio original |
| 468 | `314f0c7` | 2026-06-22 | Juan Pablo | Merge: integrar cambios remotos con crop4, ranking y review card local |
| 469 | `27a431c` | 2026-06-22 | Samuel Gutiérrez | style: rediseño de widgets recomendados y esperados + cropper 8:3 |
| 470 | `bcbafe4` | 2026-06-22 | Juan Pablo | Feat: top semanal usa crop4 en card ancha con grid 21fr/16fr para mostrar imágenes completas |
| 471 | `7a678ad` | 2026-06-22 | Samuel Gutiérrez | merge: resolver conflicto de mezcla en index.php |
| 472 | `83214c1` | 2026-06-22 | Juan Pablo | Movimiento sutil de imagenes principales en movil |
| 473 | `d7f64b6` | 2026-06-22 | Samuel Gutiérrez | fix: calificaciones reales en widgets recomendados |
| 474 | `6e6a8d8` | 2026-06-22 | Juan Pablo | Fix: crop banner muestra imagen completa + top semanal filtra por semana |
| 475 | `bbd2558` | 2026-06-22 | Juan Pablo | Revert: restaurar lógica original de Top Semanal por vistas totales |
| 476 | `a0516dc` | 2026-06-22 | Juan Pablo | Fix: imagen centrada usa ratio 21:6 igual que el banner |
| 477 | `548bde8` | 2026-06-23 | Juan Pablo | Fix: aplicar cambios de cropper de crear.php a editar.php |
| 478 | `97c9403` | 2026-06-23 | Juan Pablo | Fix: posiciones iniciales de recorte en crear.php |
| 479 | `bf1f421` | 2026-06-23 | Juan Pablo | Fix: cards recomendados usan crop4 + altura 100px en sub-cards |
| 480 | `fde5646` | 2026-06-23 | Juan Pablo | Merge remote-tracking branch 'origin/main' |
| 481 | `765a0b3` | 2026-06-23 | Juan Pablo | Fix: cards recomendados uniformes (100px) con crop4 y calificaciones reales |
| 482 | `fb166bf` | 2026-06-23 | 4strob0y | fix: programacion de correos automatica y visualizacion de imagenes rotas en envios |
| 483 | `30ed2be` | 2026-06-23 | 4strob0y | fix: verificar existencia real de subida en directorio publico para evitar imagenes rotas en produccion |
| 484 | `cc34ffa` | 2026-06-24 | Juan Pablo | Fix: cards recomendados muestran 5 completas con scroll para el resto |
| 485 | `4c58683` | 2026-06-24 | Juan Pablo | Fix: banner del carrusel respeta proporcion 21:6 en desktop |
| 486 | `1e4e4f4` | 2026-06-24 | Juan Pablo | Feat: cards de Lo que mas esperamos usan el mismo formato que recomendados |
| 487 | `bd101df` | 2026-06-24 | 4strob0y | Feat: agregar casillas de terminos y correos, auto-login y retencion de datos en registro |
| 488 | `0b3c050` | 2026-06-24 | 4strob0y | Fix |
| 489 | `ab558a9` | 2026-06-24 | 4strob0y | Fix: corregir ruta de urlhelper en index.php |
| 490 | `e72f1e8` | 2026-06-25 | Juan Pablo | Fix: restaurar colores originales de Sam en la calificacion de reviews |
| 491 | `e6a0367` | 2026-06-25 | 4strob0y | Migración de Quill a CKEditor 5 estilo Google Docs |
| 492 | `06e9d87` | 2026-06-26 | Juan Pablo | Merge remote-tracking branch 'origin/main' |
| 493 | `f5af36a` | 2026-06-26 | Juan Pablo | Fix: corregir orden de tipos en bind_param de actualizar_noticia |
| 494 | `70f45e8` | 2026-06-26 | Juan Pablo | Feat: estrenos de Peliculas y Series usan el formato top-card |
| 495 | `b3eaf12` | 2026-06-26 | 4strob0y | Refactor: align admin sidebar icons, revert public slider height, and add Turbo Drive transitions with top fuchsia progress bar |
| 496 | `21c8632` | 2026-06-26 | 4strob0y | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 497 | `5c32521` | 2026-06-26 | Juan Pablo | Fix: subir números del ranking para no tapar el texto del título |
| 498 | `ffb364a` | 2026-06-26 | Juan Pablo | Fix: imágenes del contenido como archivos en vez de base64 (evita freeze al editar) |
| 499 | `e88b243` | 2026-06-26 | Juan Pablo | Merge remote-tracking branch 'origin/main' |
| 500 | `d1b3907` | 2026-06-29 | Juan Pablo | Fix: renderizar previews de multimedia desde el servidor para que carguen a la primera |
| 501 | `a832bb4` | 2026-06-29 | 4strob0y | corregir barra lateral atascada, error al cambiar de categoria y completar integracion de turbo drive |
| 502 | `79598b3` | 2026-06-29 | 4strob0y | Merge de los cambios de producción y local (reconciliar subida de imágenes y Turbo) |
| 503 | `4d937f9` | 2026-06-29 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 504 | `8ab487b` | 2026-06-29 | 4strob0y | opt: agregar prefetcher de enlaces en hover para acelerar navegacion |
| 505 | `689a7a0` | 2026-06-29 | 4strob0y | perf: remover quill y cropper del header publico y agregar utilitario de symlink |
| 506 | `3d9f97a` | 2026-06-29 | 4strob0y | perf: renombrar directorio conflictivo uploads en create_symlink.php |
| 507 | `3b9bf45` | 2026-06-29 | 4strob0y | cleanup: eliminar script temporal de symlink |
| 508 | `7f85232` | 2026-06-29 | Juan Pablo | Fix: centrar iconos del sidebar colapsado (padding simetrico descentraba) |
| 509 | `45ce034` | 2026-06-29 | Juan Pablo | Fix: forzar recarga completa en crear/editar bajo Turbo Drive |
| 510 | `3911222` | 2026-06-29 | Juan Pablo | Merge branch 'main' of https://github.com/The-Cat-ink/CATINK |
| 511 | `11c8a0d` | 2026-06-29 | 4strob0y | feat: cambiar nombre de usuario y eliminar cuenta desde el perfil |
| 512 | `493031b` | 2026-06-29 | 4strob0y | fix: agregar <?php al inicio de perfil.php para que Valet lo procese correctamente |
| 513 | `57aa975` | 2026-06-29 | 4strob0y | feat: eliminar seccion Perfil Publico del perfil de usuario |
| 514 | `6b25d57` | 2026-06-29 | 4strob0y | fix: restaurar Perfil Publico sin foto personal |
| 515 | `f74df81` | 2026-06-29 | 4strob0y | fix: avatar del header llena el circulo correctamente + rediseno perfil |
| 516 | `b3d2b29` | 2026-06-29 | 4strob0y | feat: mostrar foto del editor junto al nombre del autor en noticias |
| 517 | `30534f5` | 2026-06-29 | 4strob0y | fix: transicion suave en todos los elementos al cambiar modo oscuro/claro |
