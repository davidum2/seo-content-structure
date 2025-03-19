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
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('SCS_VERSION', '1.0.0');
define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SCS_PLUGIN_FILE', __FILE__);

// Cargar interfaces esenciales
require_once SCS_PLUGIN_DIR . 'includes/core/interfaces/interface-registrable.php';
require_once SCS_PLUGIN_DIR . 'includes/core/interfaces/interface-fieldable.php';
require_once SCS_PLUGIN_DIR . 'includes/core/interfaces/interface-renderable.php';

// Cargar clases base
require_once SCS_PLUGIN_DIR . 'includes/core/class-loader.php';
require_once SCS_PLUGIN_DIR . 'includes/core/class-i18n.php';
require_once SCS_PLUGIN_DIR . 'includes/core/class-plugin.php';

// Cargar clases de campos
require_once SCS_PLUGIN_DIR . 'includes/fields/abstract_class_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_field_factory.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_text_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_text_area_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_number_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_select_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_radio_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_select_checkox_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_image_field.php';
require_once SCS_PLUGIN_DIR . 'includes/fields/class_repeater_field.php';

// Cargar administración
require_once SCS_PLUGIN_DIR . 'includes/admin/class-admin-controller.php';
require_once SCS_PLUGIN_DIR . 'includes/admin/class-field-group-controller.php';
require_once SCS_PLUGIN_DIR . 'includes/admin/class-settings-page.php';

// Cargar tipos de contenido
require_once SCS_PLUGIN_DIR . 'includes/post-types/abstract-class-post-type.php';
require_once SCS_PLUGIN_DIR . 'includes/post-types/class-generic-post-type.php';
require_once SCS_PLUGIN_DIR . 'includes/post-types/class-post-type-factory.php';
require_once SCS_PLUGIN_DIR . 'includes/post-types/post-types/class-service-post-type.php';

// Cargar utilidades
require_once SCS_PLUGIN_DIR . 'includes/utilities/class-validator.php';
require_once SCS_PLUGIN_DIR . 'includes/utilities/class_helper.php';
require_once SCS_PLUGIN_DIR . 'includes/utilities/functions.php';

// Función para renderizar la página de dashboard
function scs_render_dashboard_page()
{
?>
    <div class="wrap scs-admin-page scs-dashboard">
        <h1><?php echo esc_html__('SEO Content Structure Dashboard', 'seo-content-structure'); ?></h1>

        <div class="scs-dashboard-content">
            <div class="scs-dashboard-widgets">
                <div class="scs-dashboard-widget">
                    <h3><?php echo esc_html__('Bienvenido a SEO Content Structure', 'seo-content-structure'); ?></h3>
                    <p><?php echo esc_html__('Este plugin te permite crear tipos de contenido personalizados con campos avanzados y estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure'); ?></p>
                </div>

                <div class="scs-dashboard-widget">
                    <h3><?php echo esc_html__('Primeros Pasos', 'seo-content-structure'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('Crear grupos de campos para organizar tus datos', 'seo-content-structure'); ?></li>
                        <li><?php echo esc_html__('Configurar tipos de contenido personalizados', 'seo-content-structure'); ?></li>
                        <li><?php echo esc_html__('Utilizar el editor de schema para mejorar el SEO', 'seo-content-structure'); ?></li>
                    </ul>
                </div>

                <div class="scs-dashboard-widget">
                    <h3><?php echo esc_html__('Recursos', 'seo-content-structure'); ?></h3>
                    <p><?php echo esc_html__('Consulta nuestra documentación para aprender a usar todas las funciones del plugin.', 'seo-content-structure'); ?></p>
                </div>
            </div>
        </div>
    </div>
<?php
}

// Función para renderizar la página de grupos de campos
function scs_render_field_groups_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html__('Grupos de Campos', 'seo-content-structure'); ?></h1>
        <p><?php echo esc_html__('Administra tus grupos de campos personalizados.', 'seo-content-structure'); ?></p>
    </div>
<?php
}

// Función para renderizar la página de tipos de contenido
function scs_render_post_types_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html__('Tipos de Contenido', 'seo-content-structure'); ?></h1>
        <p><?php echo esc_html__('Administra tus tipos de contenido personalizados.', 'seo-content-structure'); ?></p>
    </div>
<?php
}

// Función para renderizar la página de editor de schema
function scs_render_schema_editor_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html__('Editor de Schema', 'seo-content-structure'); ?></h1>
        <p><?php echo esc_html__('Crea y edita esquemas JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure'); ?></p>
    </div>
<?php
}

// Función para renderizar la página de configuración
function scs_render_settings_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html__('Configuración', 'seo-content-structure'); ?></h1>
        <p><?php echo esc_html__('Configura el comportamiento general del plugin.', 'seo-content-structure'); ?></p>
    </div>
<?php
}

// Función para registrar los menús de administración
function scs_register_admin_menu()
{
    error_log('Registrando menú de administración');

    // Menú principal
    add_menu_page(
        __('SEO Content Structure', 'seo-content-structure'),
        __('SEO Content', 'seo-content-structure'),
        'manage_options',
        'seo-content-structure',
        'scs_render_dashboard_page',
        'dashicons-screenoptions',
        30
    );

    // Submenú: Página principal
    add_submenu_page(
        'seo-content-structure',
        __('Dashboard', 'seo-content-structure'),
        __('Dashboard', 'seo-content-structure'),
        'manage_options',
        'seo-content-structure',
        'scs_render_dashboard_page'
    );

    // Submenú: Grupos de campos
    add_submenu_page(
        'seo-content-structure',
        __('Grupos de Campos', 'seo-content-structure'),
        __('Grupos de Campos', 'seo-content-structure'),
        'manage_options',
        'scs-field-groups',
        'scs_render_field_groups_page'
    );

    // Submenú: Tipos de contenido
    add_submenu_page(
        'seo-content-structure',
        __('Tipos de Contenido', 'seo-content-structure'),
        __('Tipos de Contenido', 'seo-content-structure'),
        'manage_options',
        'scs-post-types',
        'scs_render_post_types_page'
    );

    // Submenú: Editor de Schema
    add_submenu_page(
        'seo-content-structure',
        __('Editor de Schema', 'seo-content-structure'),
        __('Editor de Schema', 'seo-content-structure'),
        'manage_options',
        'scs-schema-editor',
        'scs_render_schema_editor_page'
    );

    // Submenú: Configuración
    add_submenu_page(
        'seo-content-structure',
        __('Configuración', 'seo-content-structure'),
        __('Configuración', 'seo-content-structure'),
        'manage_options',
        'scs-settings',
        'scs_render_settings_page'
    );

    error_log('Menú de administración registrado correctamente');
}

// Registrar el menú de administración
add_action('admin_menu', 'scs_register_admin_menu');

// Cargar estilos de administración
function scs_enqueue_admin_styles($hook)
{
    // Solo cargar en las páginas del plugin
    if (strpos($hook, 'seo-content-structure') === false && strpos($hook, 'scs-') === false) {
        return;
    }

    wp_enqueue_style(
        'scs-admin-styles',
        SCS_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        SCS_VERSION
    );
}
add_action('admin_enqueue_scripts', 'scs_enqueue_admin_styles');

// Intentar inicializar el plugin completo (opcional por ahora)
add_action('plugins_loaded', 'scs_init_plugin', 20);
function scs_init_plugin()
{
    try {
        error_log('Inicialización del plugin SEO Content Structure comenzada');

        // Carga de traducciones
        load_plugin_textdomain('seo-content-structure', false, dirname(SCS_PLUGIN_BASENAME) . '/languages');

        error_log('Plugin inicializado correctamente');
    } catch (Exception $e) {
        error_log('Error al inicializar el plugin: ' . $e->getMessage());
    }
}

// Hooks de activación y desactivación
register_activation_hook(__FILE__, 'scs_activate_plugin');
function scs_activate_plugin()
{
    // Crear tablas personalizadas si son necesarias
    require_once SCS_PLUGIN_DIR . 'includes/core/class-activator.php';
    SEOContentStructure\Core\Activator::activate();

    // Limpiar caché de permalinks
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'scs_deactivate_plugin');
function scs_deactivate_plugin()
{
    // Limpiar caché y realizar otras tareas de limpieza
    require_once SCS_PLUGIN_DIR . 'includes/core/class-deactivator.php';
    SEOContentStructure\Core\Deactivator::deactivate();

    // Limpiar caché de permalinks
    flush_rewrite_rules();
}
