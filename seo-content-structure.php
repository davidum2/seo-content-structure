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
define('SCS_VERSION', '1.0.0');
define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SCS_PLUGIN_FILE', __FILE__);

/**
 * Función para autocargar las clases del plugin
 */
spl_autoload_register(function ($class) {
    // Prefijo del namespace del plugin
    $prefix = 'SEOContentStructure\\';

    // Verificar si la clase utiliza el prefijo del namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Reemplazar el namespace por la ruta de directorios y \ por /
    $file = SCS_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});

// Cargar el archivo de funciones helper
require_once SCS_PLUGIN_DIR . 'includes/utilities/functions.php';

/**
 * Iniciar el plugin cuando todos los plugins estén cargados
 */
add_action('plugins-loaded', 'scs_init_plugin', 20);

/**
 * Inicializa el plugin principal
 */
function scs_init_plugin()
{
    // Carga de traducciones
    load_plugin_textdomain('seo-content-structure', false, dirname(SCS_PLUGIN_BASENAME) . '/languages');

    // Inicializar el plugin principal
    $plugin = new SEOContentStructure\Core\Plugin();
    $plugin->init();
}

/**
 * Código para ejecutar durante la activación del plugin
 */
register_activation_hook(__FILE__, 'scs_activate_plugin');
function scs_activate_plugin()
{
    // Crear tablas personalizadas si son necesarias
    require_once SCS_PLUGIN_DIR . 'includes/core/class-activator.php';
    SEOContentStructure\Core\Activator::activate();

    // Limpiar caché de permalinks
    flush_rewrite_rules();
}

/**
 * Código para ejecutar durante la desactivación del plugin
 */
register_deactivation_hook(__FILE__, 'scs_deactivate_plugin');
function scs_deactivate_plugin()
{
    // Limpiar caché y realizar otras tareas de limpieza
    require_once SCS_PLUGIN_DIR . 'includes/core/class-deactivator.php';
    SEOContentStructure\Core\Deactivator::deactivate();

    // Limpiar caché de permalinks
    flush_rewrite_rules();
}
