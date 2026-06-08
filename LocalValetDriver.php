<?php

class LocalValetDriver extends \Valet\Drivers\ValetDriver
{
    /**
     * Determina si este driver debe servir la petición.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return true;
    }

    /**
     * Determina si la petición es para un archivo estático.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        if (file_exists($sitePath . $uri) && !is_dir($sitePath . $uri) && pathinfo($sitePath . $uri, PATHINFO_EXTENSION) !== 'php') {
            return $sitePath . $uri;
        }
        return false;
    }

    /**
     * Devuelve la ruta absoluta al controlador principal / archivo PHP a ejecutar.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
    {
        // 1. Emular reglas estáticas de .htaccess
        $routes = [
            '#^/login/?$#' => '/views/login.php',
            '#^/acceso/?$#' => '/views/login.php',
            '#^/registro/?$#' => '/views/registro.php',
            '#^/perfil/?$#' => '/views/perfil.php',
            '#^/olvide_contrasena/?$#' => '/views/olvide_contrasena.php',
            '#^/reset_contrasena/?$#' => '/views/reset_contrasena.php',
            '#^/sitemap\.xml$#' => '/views/sitemap.php',
            '#^/sobre-nosotros/?$#' => '/views/nosotros.php',
            '#^/acerca-de/?$#' => '/views/nosotros.php',
            '#^/terminos-condiciones/?$#' => '/views/terminos.php',
            '#^/terminos/?$#' => '/views/terminos.php',
            '#^/aviso-privacidad/?$#' => '/views/privacidad.php',
            '#^/privacidad/?$#' => '/views/privacidad.php',
            '#^/aviso-cookies/?$#' => '/views/politica-cookies.php',
            '#^/cookies/?$#' => '/views/politica-cookies.php',
            '#^/solicitud/?$#' => '/views/unete.php',
            '#^/únete/?$#' => '/views/unete.php',
            '#^/unete/?$#' => '/views/unete.php',
            '#^/suscripcion/?$#' => '/views/suscripcion.php',
            '#^/suscribirse/?$#' => '/views/suscripcion.php',
            '#^/contactanos/?$#' => '/views/contactanos.php',
            '#^/contacto/?$#' => '/views/contactanos.php',
        ];

        foreach ($routes as $regex => $file) {
            if (preg_match($regex, $uri)) {
                return $sitePath . $file;
            }
        }

        // 2. Emular reglas dinámicas (con parámetros GET) de .htaccess
        if (preg_match('#^/n/([a-zA-Z0-9_-]+)/?$#', $uri, $matches) || 
            preg_match('#^/noticia/([a-zA-Z0-9_-]+)/?$#', $uri, $matches) ||
            preg_match('#^/news/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
            $_GET['hash'] = $matches[1];
            return $sitePath . '/views/news.php';
        }

        if (preg_match('#^/categoria/(.+)/?$#', $uri, $matches) ||
            preg_match('#^/noticias/categoria/(.+)/?$#', $uri, $matches) ||
            preg_match('#^/categorias/(.+)/?$#', $uri, $matches)) {
            $_GET['cat'] = $matches[1];
            return $sitePath . '/views/categoria.php';
        }

        if (preg_match('#^/buscar/(.+)/?$#', $uri, $matches) ||
            preg_match('#^/noticias/buscar/(.+)/?$#', $uri, $matches) ||
            preg_match('#^/search/(.+)/?$#', $uri, $matches)) {
            $_GET['q'] = $matches[1];
            return $sitePath . '/views/categoria.php';
        }

        if (preg_match('#^/autor/([0-9]+)/?$#', $uri, $matches)) {
            $_GET['id'] = $matches[1];
            return $sitePath . '/views/autor.php';
        }

        // 3. Regla del Home
        if ($uri === '/' || $uri === '' || $uri === '/home') {
            return $sitePath . '/index.php';
        }

        // 4. Si el archivo físico existe, devolverlo
        if (file_exists($sitePath . $uri) && is_file($sitePath . $uri)) {
            return $sitePath . $uri;
        }

        // 5. Fallback por defecto
        return $sitePath . '/index.php';
    }
}
