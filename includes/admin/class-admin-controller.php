<?php

/**
 * Controlador de la administración del plugin
 *
 * @package SEOContentStructure
 * @subpackage Admin
 */

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\PostTypes\PostTypeFactory;
use SEOContentStructure\Admin\FieldGroupController;

/**
 * Clase que maneja la interfaz de administración
 */
class AdminController implements Registrable
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicialización
    }

    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // Añadir menú de administración
        $loader->add_action('admin_menu', $this, 'add_admin_menu');

        // Cargar scripts y estilos
        $loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

        // Inicializar controlador de grupos de campos si es necesario
        $field_group_controller = new FieldGroupController();
        $field_group_controller->register($loader);

        // Inicializar configuraciones generales
        $settings_page = new SettingsPage();
        $settings_page->register($loader);

        // Añadir enlaces de acción en la lista de plugins
        $loader->add_filter('plugin_action_links_' . SCS_PLUGIN_BASENAME, $this, 'add_action_links');

        // Añadir notificaciones y mensajes de ayuda
        $loader->add_action('admin_notices', $this, 'admin_notices');
        $loader->add_action('admin_head', $this, 'add_help_tabs');
    }

    /**
     * Añade el menú de administración
     */
    public function add_admin_menu()
    {
        // Menú principal
        add_menu_page(
            __('SEO Content Structure', 'seo-content-structure'),
            __('SEO Content', 'seo-content-structure'),
            'manage_options',
            'seo-content-structure',
            array($this, 'render_main_page'),
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
            array($this, 'render_main_page')
        );

        // Submenú: Grupos de campos
        add_submenu_page(
            'seo-content-structure',
            __('Grupos de Campos', 'seo-content-structure'),
            __('Grupos de Campos', 'seo-content-structure'),
            'manage_options',
            'scs-field-groups',
            array($this, 'render_field_groups_page')
        );

        // Submenú: Constructor de Types
        add_submenu_page(
            'seo-content-structure',
            __('Tipos de Contenido', 'seo-content-structure'),
            __('Tipos de Contenido', 'seo-content-structure'),
            'manage_options',
            'scs-post-types',
            array($this, 'render_post_types_page')
        );

        // Submenú: Constructor de Schema
        add_submenu_page(
            'seo-content-structure',
            __('Editor de Schema', 'seo-content-structure'),
            __('Editor de Schema', 'seo-content-structure'),
            'manage_options',
            'scs-schema-editor',
            array($this, 'render_schema_editor_page')
        );

        // Submenú: Configuración
        add_submenu_page(
            'seo-content-structure',
            __('Configuración', 'seo-content-structure'),
            __('Configuración', 'seo-content-structure'),
            'manage_options',
            'scs-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Carga los archivos CSS y JS para la administración
     *
     * @param string $hook Página actual
     */
    public function enqueue_admin_assets($hook)
    {
        // Solo cargar en las páginas del plugin
        if (strpos($hook, 'seo-content-structure') === false && strpos($hook, 'scs-') === false) {
            return;
        }

        // Estilos generales de administración
        wp_enqueue_style(
            'scs-admin-styles',
            SCS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SCS_VERSION
        );

        // Scripts de administración
        wp_enqueue_script(
            'scs-admin-scripts',
            SCS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-tabs'),
            SCS_VERSION,
            true
        );

        // Localización para scripts
        wp_localize_script(
            'scs-admin-scripts',
            'scs_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('scs_admin_nonce'),
                'strings'  => array(
                    'confirm_delete' => __('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.', 'seo-content-structure'),
                    'saving'         => __('Guardando...', 'seo-content-structure'),
                    'saved'          => __('Guardado correctamente', 'seo-content-structure'),
                    'error'          => __('Ha ocurrido un error', 'seo-content-structure'),
                    'confirm_delete_field' => __('¿Estás seguro de que deseas eliminar este campo?', 'seo-content-structure'),
                ),
            )
        );

        // Cargar estilos específicos según la página
        if (strpos($hook, 'scs-field-groups') !== false) {
            // Si estamos en la página de grupos de campos, cargar también los estilos para campos
            wp_enqueue_style(
                'scs-field-types',
                SCS_PLUGIN_URL . 'assets/css/field-types.css',
                array('scs-admin-styles'),
                SCS_VERSION
            );

            // Scripts específicos para campo de tipo imagen
            wp_enqueue_media();

            wp_enqueue_script(
                'scs-field-types',
                SCS_PLUGIN_URL . 'assets/js/field-types.js',
                array('scs-admin-scripts', 'jquery', 'wp-color-picker'),
                SCS_VERSION,
                true
            );
        } elseif (strpos($hook, 'scs-schema-editor') !== false) {
            // CodeMirror para editor JSON
            wp_enqueue_code_editor(array('type' => 'application/json'));

            wp_enqueue_script(
                'scs-json-ld-builder',
                SCS_PLUGIN_URL . 'assets/js/json-ld-builder.js',
                array('scs-admin-scripts', 'wp-codemirror'),
                SCS_VERSION,
                true
            );
        }
    }

    /**
     * Añade enlaces de acción en la lista de plugins
     *
     * @param array $links Enlaces existentes
     * @return array Enlaces modificados
     */
    public function add_action_links($links)
    {
        $custom_links = array(
            '<a href="' . admin_url('admin.php?page=seo-content-structure') . '">' . __('Dashboard', 'seo-content-structure') . '</a>',
            '<a href="' . admin_url('admin.php?page=scs-settings') . '">' . __('Configuración', 'seo-content-structure') . '</a>',
        );

        return array_merge($custom_links, $links);
    }

    /**
     * Muestra notificaciones administrativas
     */
    public function admin_notices()
    {
        // Verificar si hay notificaciones en la sesión
        if (isset($_GET['scs_notice'])) {
            $notice_type = isset($_GET['scs_notice_type']) ? sanitize_text_field($_GET['scs_notice_type']) : 'success';
            $message = '';

            switch ($_GET['scs_notice']) {
                case 'saved':
                    $message = __('Los cambios se han guardado correctamente.', 'seo-content-structure');
                    break;

                case 'error':
                    $message = __('Ha ocurrido un error al guardar los cambios.', 'seo-content-structure');
                    $notice_type = 'error';
                    break;

                case 'deleted':
                    $message = __('Elemento eliminado correctamente.', 'seo-content-structure');
                    break;
            }

            if (! empty($message)) {
                printf(
                    '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                    esc_attr($notice_type),
                    esc_html($message)
                );
            }
        }

        // Verificar requisitos del plugin
        $this->check_plugin_requirements();
    }

    /**
     * Verifica los requisitos del plugin y muestra notificaciones
     */
    private function check_plugin_requirements()
    {
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            echo '<div class="notice notice-error">';
            echo '<p>' . sprintf(
                __('%s requiere PHP 7.0 o superior. Tu servidor está ejecutando PHP %s.', 'seo-content-structure'),
                '<strong>SEO Content Structure</strong>',
                PHP_VERSION
            ) . '</p>';
            echo '</div>';
        }

        // Verificar versión de WordPress
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            echo '<div class="notice notice-error">';
            echo '<p>' . sprintf(
                __('%s requiere WordPress 5.0 o superior. Tu sitio está ejecutando WordPress %s.', 'seo-content-structure'),
                '<strong>SEO Content Structure</strong>',
                $wp_version
            ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Añade pestañas de ayuda en las páginas del plugin
     */
    public function add_help_tabs()
    {
        $screen = get_current_screen();

        // Solo en páginas del plugin
        if (! $screen || strpos($screen->id, 'seo-content-structure') === false && strpos($screen->id, 'scs-') === false) {
            return;
        }

        // Pestaña general de ayuda
        $screen->add_help_tab(array(
            'id'      => 'scs_help_overview',
            'title'   => __('Descripción General', 'seo-content-structure'),
            'content' => '<p>' . __('SEO Content Structure te permite crear tipos de contenido personalizados con campos avanzados y estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure') . '</p>',
        ));

        // Pestaña de ayuda específica según la página
        if (strpos($screen->id, 'scs-field-groups') !== false) {
            $screen->add_help_tab(array(
                'id'      => 'scs_help_field_groups',
                'title'   => __('Grupos de Campos', 'seo-content-structure'),
                'content' => '<p>' . __('Crea y administra grupos de campos personalizados para tus tipos de contenido.', 'seo-content-structure') . '</p>',
            ));
        } elseif (strpos($screen->id, 'scs-post-types') !== false) {
            $screen->add_help_tab(array(
                'id'      => 'scs_help_post_types',
                'title'   => __('Tipos de Contenido', 'seo-content-structure'),
                'content' => '<p>' . __('Crea y administra tipos de contenido personalizados para tu sitio.', 'seo-content-structure') . '</p>',
            ));
        } elseif (strpos($screen->id, 'scs-schema-editor') !== false) {
            $screen->add_help_tab(array(
                'id'      => 'scs_help_schema',
                'title'   => __('Editor de Schema', 'seo-content-structure'),
                'content' => '<p>' . __('Crea y edita estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure') . '</p>',
            ));
        }

        // Añadir panel lateral con enlaces
        $screen->set_help_sidebar(
            '<p><strong>' . __('Para más información:', 'seo-content-structure') . '</strong></p>' .
                '<p><a href="https://ejemplo.com/docs" target="_blank">' . __('Documentación', 'seo-content-structure') . '</a></p>' .
                '<p><a href="https://ejemplo.com/support" target="_blank">' . __('Soporte', 'seo-content-structure') . '</a></p>'
        );
    }

    /**
     * Renderiza la página principal
     */
    public function render_main_page()
    {
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/main-page.php';
    }

    /**
     * Renderiza la página de grupos de campos
     */
    public function render_field_groups_page()
    {
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/field-group-edit.php';
    }

    /**
     * Renderiza la página de tipos de contenido
     */
    public function render_post_types_page()
    {
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/post-type-builder.php';
    }

    /**
     * Renderiza la página del editor de schema
     */
    public function render_schema_editor_page()
    {
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/schema-editor-page.php';
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page()
    {
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
}
