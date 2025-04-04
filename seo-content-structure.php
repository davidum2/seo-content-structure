<?php

/**
 * Plugin Name: SEO Content Structure
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

// Configuración de depuración para detectar el problema
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Función de utilidad para logs
function scs_log($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Solo registrar mensajes de error
        if (strpos(strtolower($message), 'error') !== false) {
            error_log('SCS Debug: ' . $message);
        }
    }
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
    // <<<--- LOG INICIO AUTOLOAD --- >>>
    error_log("SCS_AUTOLOAD: Requesting class: " . $class);

    // Prefijo del namespace del plugin
    $prefix = 'SEOContentStructure\\';

    // Verificar si la clase utiliza el prefijo del namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Convertir namespace a ruta de directorio
    $path_parts = explode('\\', $relative_class);
    $class_name = array_pop($path_parts); // Obtener el último elemento (nombre de la clase)
    $namespace = implode('\\', $path_parts);

    // Convertir CamelCase a kebab-case para el nombre del archivo
    $file_name = 'class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name));

    // Construir la ruta base
    $file = SCS_PLUGIN_DIR . 'includes/';

    // Añadir partes del directorio (si existen)
    if (!empty($path_parts)) {
        // Corrección: Reemplazar 'PostTypes' con 'post-types' sin importar mayúsculas
        foreach ($path_parts as &$part) {
            if (strtolower($part) === 'posttypes') {
                $part = 'post-types';
            }
        }

        // Convertir a minúsculas e implode
        $path = strtolower(implode('/', $path_parts));

        // Caso especial para clases en el namespace PostTypes
        if ($path === 'post-types') {
            // Si es PostType (clase abstracta), cambiar el nombre del archivo
            if ($class_name === 'PostType') {
                $path .= '/post-types';
                // Sobreescribir el nombre del archivo para usar prefijo abstracto
                $file_name = 'abstract-class-post-type';
            }
            // Para ServicePostType y otros tipos de contenido específicos
            else if ($class_name === 'ServicePostType' || $class_name === 'GenericPostType') {
                $path .= '/post-types';
            }
        }


        // Caso especial para la clase Field en namespace Fields
        if ($path === 'fields' && $class_name === 'Field') {
            // Sobreescribir el nombre del archivo para usar prefijo abstracto
            $file_name = 'abstract-class-field';
        }
        $file .= $path . '/';
    }

    // Añadir el nombre del archivo
    $file .= $file_name . '.php';

    // <<<--- LOG RUTA CALCULADA --- >>>
    error_log("SCS_AUTOLOAD: Calculated path for $class: " . $file);

    // Cargar el archivo si existe
    if (file_exists($file)) {
        error_log("SCS_AUTOLOAD: File FOUND. Requiring: " . $file);
        require_once $file;
        return;
    }

    // Para interfaces, intentar con prefijo interface-
    if (strpos($class_name, 'Interface') !== false || strpos($namespace, 'Interfaces') !== false) {
        $interface_name = str_replace('Interface', '', $class_name);
        $file_name = 'interface-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $interface_name));

        $file = SCS_PLUGIN_DIR . 'includes/';
        if (!empty($path_parts)) {
            $file .= strtolower(implode('/', $path_parts)) . '/';
        }
        $file .= $file_name . '.php';

        // Registro de la ruta construida
        error_log("SCS_AUTOLOAD: Trying interface path: " . $file);

        if (file_exists($file)) {
            error_log("SCS_AUTOLOAD: Interface file FOUND. Requiring: " . $file);
            require_once $file;
            return;
        }
    }

    // Para clases abstractas, intentar con prefijo abstract-class-
    if (strpos($class_name, 'Abstract') !== false) {
        $abstract_name = str_replace('Abstract', '', $class_name);
        $file_name = 'abstract-class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $abstract_name));

        $file = SCS_PLUGIN_DIR . 'includes/';
        if (!empty($path_parts)) {
            $file .= strtolower(implode('/', $path_parts)) . '/';
        }
        $file .= $file_name . '.php';

        // Registro de la ruta construida
        error_log("SCS_AUTOLOAD: Trying abstract class path: " . $file);

        if (file_exists($file)) {
            error_log("SCS_AUTOLOAD: Abstract file FOUND. Requiring: " . $file);
            require_once $file;
            return;
        }
    }

    // <<<--- LOG ERROR AUTOLOAD --- >>>
    error_log("SCS_AUTOLOAD_ERROR: File NOT FOUND for class: " . $class . " (Final path checked: " . $file . ")");
});
// Cargar el archivo de funciones helper
require_once SCS_PLUGIN_DIR . 'includes/utilities/functions.php';
scs_log('Funciones helper cargadas');

/**
 * Iniciar el plugin cuando todos los plugins estén cargados
 */
add_action('plugins_loaded', 'scs_init_plugin', 20);

/**
 * Inicializa el plugin principal
 */
function scs_init_plugin()
{
    // <<<--- LOG INICIO PLUGIN --- >>>
    error_log('SCS_TRACE: ========= scs_init_plugin() START =========');

    // Carga de traducciones
    load_plugin_textdomain('seo-content-structure', false, dirname(SCS_PLUGIN_BASENAME) . '/languages');

    // Inicializar el plugin principal
    try {
        // <<<--- LOG ANTES DE INSTANCIAS --- >>>
        error_log('SCS_TRACE: Intentando crear instancia de Plugin y llamar a init()...');

        // Verificar que las clases clave existen
        if (!class_exists('\SEOContentStructure\Core\Plugin')) {
            error_log('SCS_FATAL: Clase Plugin NO ENCONTRADA en scs_init_plugin');
            return;
        }
        if (!class_exists('\SEOContentStructure\Admin\AdminController')) {
            error_log('SCS_FATAL: Clase AdminController NO ENCONTRADA en scs_init_plugin');
            return;
        }
        if (!class_exists('\SEOContentStructure\Core\Loader')) {
            error_log('SCS_FATAL: Clase Loader NO ENCONTRADA en scs_init_plugin');
            return;
        }
        if (!class_exists('\SEOContentStructure\PostTypes\PostTypeFactory')) {
            error_log('SCS_FATAL: Clase PostTypeFactory NO ENCONTRADA en scs_init_plugin');
            return;
        }

        // Inicializar el gestor de post types
        $post_type_manager = new \SEOContentStructure\Admin\PostTypeManager();
        error_log('SCS_TRACE: Instancia de PostTypeManager creada.');

        // Crear instancia del plugin principal
        $plugin = new \SEOContentStructure\Core\Plugin();
        error_log('SCS_TRACE: Instancia de Plugin creada correctamente.');

        error_log('SCS_TRACE: Llamando al método init() de Plugin...');
        $plugin->init();
        error_log('SCS_TRACE: Llamada a Plugin->init() completada.');
    } catch (\Throwable $e) {
        // Registrar el error
        scs_log('ERROR: ' . $e->getMessage());
        scs_log('Traza: ' . $e->getTraceAsString());

        // <<<--- LOG ERROR INIT --- >>>
        error_log("SCS_FATAL: Excepción en scs_init_plugin(): " . $e->getMessage());
        error_log("SCS_FATAL: Traza de la excepción: " . $e->getTraceAsString());

        // Show admin notice
        add_action('admin_notices', function () use ($e) {
?>
            <div class="notice notice-error">
                <p><?php echo esc_html__('Error al inicializar SEO Content Structure:', 'seo-content-structure'); ?> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
<?php
        });
    }
}

// Registrar el menú de administración directamente como fallback
function scs_register_admin_menu_fallback()
{
    scs_log('>>> INICIO scs_register_admin_menu_fallback()');

    // Verificar si el menú ya está registrado por el plugin
    global $submenu;
    if (isset($submenu['seo-content-structure'])) {
        scs_log('El menú ya está registrado - saltando registro fallback');
        return;
    }

    add_menu_page(
        __('SEO Content Structure', 'seo-content-structure'),
        __('SEO Content', 'seo-content-structure'),
        'manage_options',
        'seo-content-structure',
        'scs_render_main_page_fallback',
        'dashicons-screenoptions',
        30
    );

    add_submenu_page(
        'seo-content-structure',
        __('Dashboard', 'seo-content-structure'),
        __('Dashboard', 'seo-content-structure'),
        'manage_options',
        'seo-content-structure',
        'scs_render_main_page_fallback'
    );

    scs_log('Menú de administración registrado por fallback');
    scs_log('<<< FIN scs_register_admin_menu_fallback()');
}

function scs_render_main_page_fallback()
{
    scs_log('Renderizando página principal fallback');
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('SEO Content Structure', 'seo-content-structure') . '</h1>';
    echo '<p>' . esc_html__('Esta es una página de respaldo. El plugin no se ha inicializado correctamente.', 'seo-content-structure') . '</p>';

    echo '<h2>' . esc_html__('Información de depuración', 'seo-content-structure') . '</h2>';
    echo '<p>' . esc_html__('Revisa el archivo de registro de errores para más información.', 'seo-content-structure') . '</p>';

    // Mostrar rutas relevantes
    echo '<h3>' . esc_html__('Rutas', 'seo-content-structure') . '</h3>';
    echo '<ul>';
    echo '<li><strong>SCS_PLUGIN_DIR:</strong> ' . esc_html(SCS_PLUGIN_DIR) . '</li>';
    echo '<li><strong>Plugin Class Path:</strong> ' . esc_html(SCS_PLUGIN_DIR . 'includes/core/class-plugin.php') . '</li>';
    echo '<li><strong>Admin Controller Path:</strong> ' . esc_html(SCS_PLUGIN_DIR . 'includes/admin/class-admin-controller.php') . '</li>';
    echo '</ul>';

    // Verificar archivos críticos
    echo '<h3>' . esc_html__('Verificación de archivos', 'seo-content-structure') . '</h3>';
    echo '<ul>';
    $files_to_check = [
        'includes/core/class-plugin.php',
        'includes/core/class-loader.php',
        'includes/admin/class-admin-controller.php',
        'includes/core/interfaces/interface-registrable.php'
    ];

    foreach ($files_to_check as $file) {
        $full_path = SCS_PLUGIN_DIR . $file;
        $exists = file_exists($full_path);
        echo '<li>';
        echo '<strong>' . esc_html($file) . ':</strong> ';
        if ($exists) {
            echo '<span style="color:green;">✓ Existe</span>';
        } else {
            echo '<span style="color:red;">✗ No existe</span>';
        }
        echo '</li>';
    }
    echo '</ul>';

    echo '</div>';
}

// Registrar el menú fallback como último recurso
add_action('admin_menu', 'scs_register_admin_menu_fallback', 999);

/**
 * Código para ejecutar durante la activación del plugin
 */
register_activation_hook(__FILE__, 'scs_activate_plugin');
function scs_activate_plugin()
{
    scs_log('>>> INICIO scs_activate_plugin()');

    // Crear tablas personalizadas si son necesarias
    require_once SCS_PLUGIN_DIR . 'includes/core/class-activator.php';
    SEOContentStructure\Core\Activator::activate();
    scs_log('Activación completada');

    // Limpiar caché de permalinks
    flush_rewrite_rules();

    scs_log('<<< FIN scs_activate_plugin()');
}

/**
 * Código para ejecutar durante la desactivación del plugin
 */
register_deactivation_hook(__FILE__, 'scs_deactivate_plugin');
function scs_deactivate_plugin()
{
    scs_log('>>> INICIO scs_deactivate_plugin()');

    // Limpiar caché y realizar otras tareas de limpieza
    require_once SCS_PLUGIN_DIR . 'includes/core/class-deactivator.php';
    SEOContentStructure\Core\Deactivator::deactivate();
    scs_log('Desactivación completada');

    // Limpiar caché de permalinks
    flush_rewrite_rules();

    scs_log('<<< FIN scs_deactivate_plugin()');
}
