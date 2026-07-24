<?php
// Catalogo de iconos disponibles para las categorias.
//
// Fuente unica de verdad: la usa el selector de views/cats.php para pintar la
// cuadricula y la usan crearc.php / editarc.php para validar lo que llega por
// POST. Asi el panel nunca puede guardar una clase CSS arbitraria en la BD,
// que despues se imprimiria tal cual dentro de un <i class="bi ...">.
//
// Todos los nombres son de Bootstrap Icons 1.11.3, la version que ya cargan
// layout/header.php y layout/headerAdmin.php.

const ICONO_CATEGORIA_DEFAULT = 'bi-tag-fill';

/**
 * Iconos agrupados por tema, para que el selector se pueda leer de un vistazo.
 *
 * @return array<string, string[]>
 */
function iconosCategoriaPorGrupo(): array {
    return [
        'Cine y series' => [
            'bi-film', 'bi-camera-reels-fill', 'bi-tv-fill', 'bi-camera-video-fill',
            'bi-projector-fill', 'bi-ticket-perforated-fill', 'bi-play-btn-fill', 'bi-collection-play-fill',
        ],
        'Juegos y anime' => [
            'bi-controller', 'bi-joystick', 'bi-dpad-fill', 'bi-nintendo-switch',
            'bi-stars', 'bi-magic', 'bi-emoji-heart-eyes-fill', 'bi-dice-5-fill',
        ],
        'Tecnologia' => [
            'bi-cpu-fill', 'bi-motherboard-fill', 'bi-pc-display', 'bi-laptop-fill',
            'bi-phone-fill', 'bi-robot', 'bi-gpu-card', 'bi-rocket-takeoff-fill',
        ],
        'Musica y cultura' => [
            'bi-music-note-beamed', 'bi-vinyl-fill', 'bi-mic-fill', 'bi-headphones',
            'bi-book-fill', 'bi-palette-fill', 'bi-brush-fill', 'bi-easel-fill',
        ],
        'Editorial' => [
            'bi-star-fill', 'bi-trophy-fill', 'bi-fire', 'bi-lightning-charge-fill',
            'bi-megaphone-fill', 'bi-newspaper', 'bi-chat-quote-fill', 'bi-patch-check-fill',
        ],
        'General' => [
            'bi-tag-fill', 'bi-bookmark-star-fill', 'bi-grid-fill', 'bi-shuffle',
            'bi-globe-americas', 'bi-people-fill', 'bi-calendar-event-fill', 'bi-heart-fill',
        ],
    ];
}

/**
 * Lista plana de todos los iconos permitidos.
 *
 * @return string[]
 */
function iconosCategoriaPermitidos(): array {
    return array_merge(...array_values(iconosCategoriaPorGrupo()));
}

/**
 * Normaliza el icono que llega por POST o desde la BD.
 * Cualquier valor fuera del catalogo cae al icono por defecto.
 */
function sanearIconoCategoria(?string $icono): string {
    $icono = trim((string)$icono);
    return in_array($icono, iconosCategoriaPermitidos(), true)
        ? $icono
        : ICONO_CATEGORIA_DEFAULT;
}
