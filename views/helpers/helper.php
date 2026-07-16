<?php
function tienePermiso($modulo, $accion){
    return $_SESSION['ACL'][$modulo][$accion] ?? false;
}
function esSuperAdmin($u){
    foreach(getModulosACL($u) as $m){
        if($m['permiso'] != 15){
            return false;
        }
    }
    return true;
}
function primerModuloLectura($acl){
    $vistas = [
        'categorias'     => '../views/cats.php',
        'noticias'       => '../views/contenidos.php',
        'publicidad'     => '../views/publicidad.php',
        'suscripciones'  => '../views/suscripciones.php',
        'usuarios'       => '../views/usuarios.php',
        'correos'        => '../views/correos.php',
        'videos'         => '../views/videos.php',
        'lectores'       => '../views/lectores.php',
        'recomendados'   => '../views/recomendados.php',
        'esperamos'      => '../views/esperamos.php',
        'paginas'        => '../views/paginas.php',
        'actividad'      => '../views/actividad.php',
        'papelera'       => '../views/papelera.php',
        'avatares'       => '../views/avatares.php'
    ];
    foreach($acl as $modulo => $permisos){
        if($permisos['leer']){
            return $vistas[$modulo];
        }
    }
    return null;
}
function mapearPermisos($user){
    return [
        'usuarios' => [
            'crear'    => ($user['perm_usuarios'] & 1) === 1,
            'leer'     => ($user['perm_usuarios'] & 2) === 2,
            'editar'   => ($user['perm_usuarios'] & 4) === 4,
            'eliminar' => ($user['perm_usuarios'] & 8) === 8
        ],
        'categorias' => [
            'crear'    => ($user['perm_categorias'] & 1) === 1,
            'leer'     => ($user['perm_categorias'] & 2) === 2,
            'editar'   => ($user['perm_categorias'] & 4) === 4,
            'eliminar' => ($user['perm_categorias'] & 8) === 8
        ],
        'noticias' => [
            'crear'    => ($user['perm_noticias'] & 1) === 1,
            'leer'     => ($user['perm_noticias'] & 2) === 2,
            'editar'   => ($user['perm_noticias'] & 4) === 4,
            'eliminar' => ($user['perm_noticias'] & 8) === 8
        ],
        'publicidad' => [
            'crear'    => ($user['perm_publicidad'] & 1) === 1,
            'leer'     => ($user['perm_publicidad'] & 2) === 2,
            'editar'   => ($user['perm_publicidad'] & 4) === 4,
            'eliminar' => ($user['perm_publicidad'] & 8) === 8
        ],
        'suscripciones' => [
            'crear'    => ($user['perm_suscripciones'] & 1) === 1,
            'leer'     => ($user['perm_suscripciones'] & 2) === 2,
            'editar'   => ($user['perm_suscripciones'] & 4) === 4,
            'eliminar' => ($user['perm_suscripciones'] & 8) === 8
        ],
        'correos' => [
            'crear'    => ($user['perm_correos'] & 1) === 1,
            'leer'     => ($user['perm_correos'] & 2) === 2,
            'editar'   => ($user['perm_correos'] & 4) === 4,
            'eliminar' => ($user['perm_correos'] & 8) === 8
        ],
        'videos' => [
            'crear'    => ($user['perm_videos'] & 1) === 1,
            'leer'     => ($user['perm_videos'] & 2) === 2,
            'editar'   => ($user['perm_videos'] & 4) === 4,
            'eliminar' => ($user['perm_videos'] & 8) === 8
        ],
        'lectores' => [
            'crear'    => ($user['perm_lectores'] & 1) === 1,
            'leer'     => ($user['perm_lectores'] & 2) === 2,
            'editar'   => ($user['perm_lectores'] & 4) === 4,
            'eliminar' => ($user['perm_lectores'] & 8) === 8
        ],
        'recomendados' => [
            'crear'    => ($user['perm_recomendados'] & 1) === 1,
            'leer'     => ($user['perm_recomendados'] & 2) === 2,
            'editar'   => ($user['perm_recomendados'] & 4) === 4,
            'eliminar' => ($user['perm_recomendados'] & 8) === 8
        ],
        'esperamos' => [
            'crear'    => ($user['perm_esperamos'] & 1) === 1,
            'leer'     => ($user['perm_esperamos'] & 2) === 2,
            'editar'   => ($user['perm_esperamos'] & 4) === 4,
            'eliminar' => ($user['perm_esperamos'] & 8) === 8
        ],
        'paginas' => [
            'crear'    => ($user['perm_paginas'] & 1) === 1,
            'leer'     => ($user['perm_paginas'] & 2) === 2,
            'editar'   => ($user['perm_paginas'] & 4) === 4,
            'eliminar' => ($user['perm_paginas'] & 8) === 8
        ],
        'actividad' => [
            'crear'    => ($user['perm_actividad'] & 1) === 1,
            'leer'     => ($user['perm_actividad'] & 2) === 2,
            'editar'   => ($user['perm_actividad'] & 4) === 4,
            'eliminar' => ($user['perm_actividad'] & 8) === 8
        ],
        'papelera' => [
            'crear'    => ($user['perm_papelera'] & 1) === 1,
            'leer'     => ($user['perm_papelera'] & 2) === 2,
            'editar'   => ($user['perm_papelera'] & 4) === 4,
            'eliminar' => ($user['perm_papelera'] & 8) === 8
        ],
        'avatares' => [
            'crear'    => ($user['perm_avatares'] & 1) === 1,
            'leer'     => ($user['perm_avatares'] & 2) === 2,
            'editar'   => ($user['perm_avatares'] & 4) === 4,
            'eliminar' => ($user['perm_avatares'] & 8) === 8
        ]
    ];
}
function getModulosACL($u){
    return [
        'categorias' => [
            'permiso' => $u['perm_categorias'],
            'vista'   => '../views/cats.php'
        ],
        'noticias' => [
            'permiso' => $u['perm_noticias'],
            'vista'   => '../views/contenidos.php'
        ],
        'publicidad' => [
            'permiso' => $u['perm_publicidad'],
            'vista'   => '../views/publicidad.php'
        ],
        'correos' => [
            'permiso' => $u['perm_correos'],
            'vista'   => '../views/correo_pub.php'
        ],
        'suscripciones' => [
            'permiso' => $u['perm_suscripciones'],
            'vista'   => '../views/suscripciones.php'
        ],
        'usuarios' => [
            'permiso' => $u['perm_usuarios'],
            'vista'   => '../views/usuarios.php'
        ],
        'videos' => [
            'permiso' => $u['perm_videos'],
            'vista'   => '../views/videos.php'
        ],
        'lectores' => [
            'permiso' => $u['perm_lectores'],
            'vista'   => '../views/lectores.php'
        ],
        'recomendados' => [
            'permiso' => $u['perm_recomendados'],
            'vista'   => '../views/recomendados.php'
        ],
        'esperamos' => [
            'permiso' => $u['perm_esperamos'],
            'vista'   => '../views/esperamos.php'
        ],
        'paginas' => [
            'permiso' => $u['perm_paginas'],
            'vista'   => '../views/paginas.php'
        ],
        'actividad' => [
            'permiso' => $u['perm_actividad'],
            'vista'   => '../views/actividad.php'
        ],
        'papelera' => [
            'permiso' => $u['perm_papelera'],
            'vista'   => '../views/papelera.php'
        ],
        'avatares' => [
            'permiso' => $u['perm_avatares'],
            'vista'   => '../views/avatares.php'
        ]
    ];
}