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

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Función de utilidad para logs
function scs_log($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('SCS Debug: ' . $message);
    }
}

// Definir constantes del plugin
define('SCS_VERSION', '1.0.0');
define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SCS_PLUGIN_FILE', __FILE__);

scs_log('Plugin inicializándose - constantes definidas');

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

    // Convertir namespace a ruta de directorio
    $path_parts = explode('\\', $relative_class);
    $class_name = array_pop($path_parts); // Obtener el último elemento (nombre de la clase)
    $namespace = implode('\\', $path_parts);

    // Convertir CamelCase a kebab-case para el nombre del archivo
    $file_name = 'class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name));

    // Construir la ruta completa
    $file = SCS_PLUGIN_DIR . 'includes/';

    // Añadir partes del directorio (si existen)
    if (!empty($path_parts)) {
        // Corrección: Reemplazar 'posttypes' con 'post-types'
        foreach ($path_parts as &$part) {
            if ($part === 'posttypes') {
                $part = 'post-types';
            }
        }
        $file .= strtolower(implode('/', $path_parts)) . '/';
    }

    // Añadir el nombre del archivo
    $file .= $file_name . '.php';

    // Registro de la ruta construida
    scs_log('Autoloader: Buscando archivo ' . $file . ' para la clase ' . $class);

    // Cargar el archivo si existe
    if (file_exists($file)) {
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
        scs_log('Autoloader: Buscando archivo ' . $file . ' para la clase ' . $class);

        if (file_exists($file)) {
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
        scs_log('Autoloader: Buscando archivo ' . $file . ' para la clase ' . $class);

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Registro de error solo si llegamos a este punto
    scs_log('Autoloader: No se pudo encontrar el archivo para la clase ' . $class);
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
    scs_log('>>> INICIO scs_init_plugin()');

    // Carga de traducciones
    load_plugin_textdomain('seo-content-structure', false, dirname(SCS_PLUGIN_BASENAME) . '/languages');
    scs_log('Traducciones cargadas');

    // Inicializar el plugin principal
    try {
        scs_log('Intentando crear instancia de Plugin');

        // Verificar que las clases clave existen
        $core_files = [
            SCS_PLUGIN_DIR . 'includes/core/class-plugin.php',
            SCS_PLUGIN_DIR . 'includes/core/class-loader.php'
        ];

        foreach ($core_files as $file) {
            if (!file_exists($file)) {
                throw new \Exception("Archivo crítico no encontrado: " . $file);
            }
        }

        // Crear instancia del plugin
        $plugin = new \SEOContentStructure\Core\Plugin();
        scs_log('Instancia de Plugin creada correctamente');

        scs_log('Llamando al método init() de Plugin');
        $plugin->init();
        scs_log('Plugin inicializado correctamente');
    } catch (\Throwable $e) {
        // Log error
        scs_log('ERROR: ' . $e->getMessage());
        scs_log('Traza: ' . $e->getTraceAsString());

        // Show admin notice
        add_action('admin_notices', function () use ($e) {
?>
            <div class="notice notice-error">
                <p><?php echo esc_html__('Error al inicializar SEO Content Structure:', 'seo-content-structure'); ?> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
<?php
        });
    }

    scs_log('<<< FIN scs_init_plugin()');
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
