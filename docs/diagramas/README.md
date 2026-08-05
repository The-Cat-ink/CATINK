# Diagramas UML — CatInk

Documentación de arquitectura generada a partir del código real del repositorio
(esquema de `data/cat_ink.sql` + `migrations/001…014`, ~110 controladores y 17 helpers).

| # | Diagrama | Archivo | Qué modela |
|---|---|---|---|
| 1 | Clases | [01-diagrama-clases.md](01-diagrama-clases.md) | Entidades del dominio, atributos, métodos y relaciones (herencia, asociación, composición) |
| 2 | Componentes | [02-diagrama-componentes.md](02-diagrama-componentes.md) | Módulos, bibliotecas, APIs y sus dependencias |
| 3 | Despliegue | [03-diagrama-despliegue.md](03-diagrama-despliegue.md) | Nodos físicos: Hostinger, MariaDB, SMTP, CDN, XAMPP local |
| 4 | Objetos | [04-diagrama-objetos.md](04-diagrama-objetos.md) | Instantánea con instancias y valores concretos en un momento dado |

Cada `.md` trae el diagrama en **Mermaid** (se renderiza solo en GitHub y VS Code).

## Archivos PlantUML listos para editor.plantuml.com

La carpeta [puml/](puml/) contiene los diagramas en **PlantUML puro**, todos compilados y
verificados con PlantUML 1.2025.4 (los 15 renderizan sin errores):

### Estructura

| Archivo | Diagrama |
|---|---|
| [01-clases-nucleo.puml](puml/01-clases-nucleo.puml) | Clases — núcleo (usuarios, noticias, comentarios, métricas) |
| [02-clases-modulos.puml](puml/02-clases-modulos.puml) | Clases — publicidad, mailing, CMS y vacantes |
| [03-clases-servicios.puml](puml/03-clases-servicios.puml) | Clases — capa de servicios (`views/helpers/`) |
| [04-componentes.puml](puml/04-componentes.puml) | Componentes — 7 capas con interfaces provistas/requeridas |
| [05-despliegue.puml](puml/05-despliegue.puml) | Despliegue — producción en Hostinger |
| [06-despliegue-local.puml](puml/06-despliegue-local.puml) | Despliegue — desarrollo local (XAMPP) |
| [07-objetos.puml](puml/07-objetos.puml) | Objetos — instantánea del 31/07/2026 19:42 |

### Comportamiento

| Archivo | Diagrama |
|---|---|
| [08-casos-uso-general.puml](puml/08-casos-uso-general.puml) | Casos de uso — visión general del alcance (12 casos agregados) |
| [09-casos-uso-publico.puml](puml/09-casos-uso-publico.puml) | Casos de uso — Visitante, Lector y Aspirante |
| [10-casos-uso-admin.puml](puml/10-casos-uso-admin.puml) | Casos de uso — Redactor, Editor y Super Admin |
| [11-actividades-publicar-noticia.puml](puml/11-actividades-publicar-noticia.puml) | Actividades — crear y publicar noticia (5 calles, fork/join) |
| [12-actividades-comentario.puml](puml/12-actividades-comentario.puml) | Actividades — comentar con filtro de moderación |
| [13-secuencia-registro.puml](puml/13-secuencia-registro.puml) | Secuencia — registro + verificación por correo |
| [14-secuencia-postular-vacante.puml](puml/14-secuencia-postular-vacante.puml) | Secuencia — postulación con CV adjunto |
| [15-secuencia-lectura-offline.puml](puml/15-secuencia-lectura-offline.puml) | Secuencia — guardar y leer sin conexión (PWA) |

**Uso:** abrir el `.puml`, copiar todo el contenido (incluidos `@startuml` y `@enduml`) y pegarlo
en <https://editor.plantuml.com/>. También sirven en <https://www.plantuml.com/plantuml/uml/>
o con la extensión *PlantUML* de VS Code (requiere Java + Graphviz).

## Cómo renderizar los Mermaid

- GitHub: se dibuja solo al abrir el `.md`.
- VS Code: extensión *Markdown Preview Mermaid Support*, luego `Ctrl+Shift+V`.
- Web: pegar el bloque en <https://mermaid.live> y exportar PNG/SVG.

**Para editarlos como dibujo:** <https://app.diagrams.net> (draw.io) importa PlantUML
desde *Arrange → Insert → Advanced → PlantUML*.

## Sobre el diagrama de clases

CatInk es **PHP procedural**, no orientado a objetos: no existen clases PHP salvo las de PHPMailer.
El diagrama de clases es por tanto un **modelo de dominio conceptual** — cada tabla persistente se
representa como clase y sus operaciones son los controladores de `controllers/` que la manipulan.
Está documentado así al inicio de ese archivo para que no se lea como ingeniería inversa del código.

## Fuentes de verdad usadas

- `data/cat_ink.sql` — 16 tablas base y sus claves foráneas
- `migrations/001…014` + `migrations.sql` — 20 tablas adicionales (lectores, comentarios, vacantes,
  historial de ediciones, logos, publicidad_posicion…)
- `data/conexion.php` — conexión, zona horaria y auto-migración (`asegurarColumna()`)
- `.htaccess` — enrutamiento amigable
- `sw.js` + `CSS/offline-manager.js` + `manifest.json` — subsistema PWA/offline
- `views/helpers/*.php` — capa de servicios
