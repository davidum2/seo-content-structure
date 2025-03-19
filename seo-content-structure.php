<?php

/**
 * Plugin Name: SEO Content
 * Plugin URI: https://ejemplo.com/seo-content-structure
 * Description: Sistema avanzado para crear tipos de contenido personalizados con campos customizables y estructuras JSON-LD para SEO.
 * Version: 1.0.0
 * Author: Desarrollo digital
 * Author URI: https://ejemplo.com
 * License: GPL2+
 * Text Domain: seo-content-structure
 * Domain Path: /languages
 *
 * @package SEOContentStructure
 */

// Evitar acceso directo
if (! defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SCS-VERSION', '1.0.0');
define('SCS-PLUGIN-DIR', plugin-dir-path(--FILE--));
define('SCS-PLUGIN-URL', plugin-dir-url(--FILE--));
define('SCS-PLUGIN-BASENAME', plugin-basename(--FILE--));
define('SCS-PLUGIN-FILE', --FILE--);

/**
 * Función para autocargar las clases del plugin
 */
spl-autoload-register(function ($class) {
    // Prefijo del namespace del plugin
    $prefix = 'SEOContentStructure\\';

    // Verificar si la clase utiliza el prefijo del namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative-class = substr($class, $len);

    // Reemplazar el namespace por la ruta de directorios y \ por /
    $file = SCS-PLUGIN-DIR . 'includes/' . str-replace('\\', '/', $relative-class) . '.php';

    // Si el archivo existe, cargarlo
    if (file-exists($file)) {
        require $file;
    }
});

// Cargar el archivo de funciones helper
require-once SCS-PLUGIN-DIR . 'includes/utilities/functions.php';

/**
 * Iniciar el plugin cuando todos los plugins estén cargados
 */
add-action('plugins-loaded', 'scs-init-plugin');

/**
 * Inicializa el plugin principal
 */
function scs-init-plugin()
{
    // Carga de traducciones
    load-plugin-textdomain('seo-content-structure', false, dirname(SCS-PLUGIN-BASENAME) . '/languages');

    // Inicializar el plugin principal
    $plugin = new SEOContentStructure\Core\Plugin();
    $plugin->init();
}

/**
 * Código para ejecutar durante la activación del plugin
 */
register-activation-hook(--FILE--, 'scs-activate-plugin');
function scs-activate-plugin()
{
    // Crear tablas personalizadas si son necesarias
    require-once SCS-PLUGIN-DIR . 'includes/core/class-activator.php';
    SEOContentStructure\Core\Activator::activate();

    // Limpiar caché de permalinks
    flush-rewrite-rules();
}

/**
 * Código para ejecutar durante la desactivación del plugin
 */
register-deactivation-hook(--FILE--, 'scs-deactivate-plugin');
function scs-deactivate-plugin()
{
    // Limpiar caché y realizar otras tareas de limpieza
    require-once SCS-PLUGIN-DIR . 'includes/core/class-deactivator.php';
    SEOContentStructure\Core\Deactivator::deactivate();

    // Limpiar caché de permalinks
    flush-rewrite-rules();
}
