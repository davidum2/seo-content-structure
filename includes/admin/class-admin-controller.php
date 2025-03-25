<?php

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\PostTypes\PostTypeFactory;
use SEOContentStructure\Admin\FieldGroupController;
use SEOContentStructure\Admin\PostTypeController;
use SEOContentStructure\Admin\SettingsPage;
use WP_Error;

/**
 * Clase que maneja la interfaz de administración
 */
class AdminController implements Registrable
{
    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
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

        // Inicializar controlador de tipos de contenido
        $post_type_controller = new PostTypeController();
        $post_type_controller->register($loader);

        // Inicializar configuraciones generales
        $settings_page = new SettingsPage();
        $settings_page->register($loader);

        // Añadir enlaces de acción en la lista de plugins
        $loader->add_filter('plugin_action_links_' . SCS_PLUGIN_BASENAME, $this, 'add_action_links');

        // Añadir notificaciones y mensajes de ayuda
        $loader->add_action('admin_notices', $this, 'admin_notices');
        $loader->add_action('admin_head', $this, 'add_help_tabs');

        // Agregamos la acción para procesar el formulario del post type builder.
        $loader->add_action('admin_post_scs_register_post_type', $this, 'process_post_type_form');
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

    /**
     * Procesa el formulario de creación de tipos de contenido.
     *
     * @return void
     */
    public function process_post_type_form()
    {
        // Verificar nonce para seguridad
        if (! isset($_POST['scs_post_type_nonce']) || ! wp_verify_nonce($_POST['scs_post_type_nonce'], 'scs_register_post_type')) {
            wp_die(__('¡Vaya, algo salió mal! Por favor, recarga la página e inténtalo de nuevo.', 'seo-content-structure'));
        }

        // 1. Sanitizar y validar los datos del formulario
        $nombre = sanitize_text_field($_POST['nombre']);
        $singular = sanitize_text_field($_POST['singular']);
        $plural = sanitize_text_field($_POST['plural']);
        $descripcion = sanitize_textarea_field($_POST['descripcion']);
        $public = isset($_POST['public']) ? true : false;
        $show_ui = isset($_POST['show_ui']) ? true : false;
        $show_in_menu = isset($_POST['show_in_menu']) ? true : false;
        $supports = isset($_POST['supports']) ? $_POST['supports'] : array(); // Ya es un array, pero hay que sanearlo.

        // Sanear el array de supports.  Esto asegura que solo se acepten los valores permitidos.
        $supports = array_intersect($supports, array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'post-formats'));

        // Validar que el nombre del post type sea válido (debe ser una cadena y no estar vacío)
        if (empty($nombre) || ! is_string($nombre)) {
            $this->add_admin_notice('error', __('El nombre del tipo de contenido es obligatorio y debe ser una cadena.', 'seo-content-structure'));
            $this->redirect_to_post_types_page();
            return;
        }

        // Validar que singular y plural no estén vacíos
        if (empty($singular) || empty($plural)) {
            $this->add_admin_notice('error', __('Los nombres singular y plural son obligatorios.', 'seo-content-structure'));
            $this->redirect_to_post_types_page();
            return;
        }

        // 2. Construir el array de argumentos para register_post_type
        $args = array(
            'labels' => array(
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'admin_bar_menu_name' => $singular,
                'add_new' => __('Añadir Nuevo', 'seo-content-structure') . ' ' . $singular,
                'add_new_item' => __('Añadir Nuevo', 'seo-content-structure') . ' ' . $singular,
                'edit_item' => __('Editar', 'seo-content-structure') . ' ' . $singular,
                'new_item' => __('Nuevo', 'seo-content-structure') . ' ' . $singular,
                'view_item' => __('Ver', 'seo-content-structure') . ' ' . $singular,
                'search_items' => __('Buscar', 'seo-content-structure') . ' ' . $plural,
                'not_found' => __('No se encontraron', 'seo-content-structure') . ' ' . $plural,
                'not_found_in_trash' => __('No se encontraron', 'seo-content-structure') . ' ' . $plural . ' en la papelera',
                'all_items' => __('Todos los', 'seo-content-structure') . ' ' . $plural,
            ),
            'description' => $descripcion,
            'public' => $public,
            'show_ui' => $show_ui,
            'show_in_menu' => $show_in_menu,
            'menu_position' => 20, // Puedes ajustar la posición en el menú
            'supports' => $supports,
            'has_archive' => true,
            'rewrite' => array('slug' => $nombre), // Usa el nombre como slug por defecto
            'capability_type' => 'post', // Esto es común, pero puedes ajustarlo
        );

        // 3. Registrar el post type
        $result = register_post_type($nombre, $args);

        if (is_wp_error($result)) {
            // Manejar el error de registro
            $this->add_admin_notice('error', __('Error al registrar el tipo de contenido: ', 'seo-content-structure') . $result->get_error_message());
        } else {
            // Éxito: opcionalmente, guardar en la base de datos para persistencia
            $this->save_post_type_to_db($nombre, $args);
            $this->add_admin_notice('success', __('Tipo de contenido registrado correctamente.', 'seo-content-structure'));
        }

        // 4. Redirigir de vuelta a la página de tipos de contenido
        $this->redirect_to_post_types_page();
    }

    /**
     * Guarda la configuración del post type en la base de datos.
     *
     * @param string $nombre Nombre del post type.
     * @param array $args Argumentos del post type.
     * @return void
     */
    private function save_post_type_to_db($nombre, $args)
    {
        // Obtiene los post types existentes
        $existing_post_types = get_option('scs_registered_post_types', array());
        // Agrega el nuevo post type al array
        $existing_post_types[$nombre] = $args;
        // Guarda el array actualizado en la base de datos
        update_option('scs_registered_post_types', $existing_post_types);
    }

    /**
     * Añade un mensaje de notificación a mostrar en el admin.
     *
     * @param string $tipo Tipo de notificación (success, error, warning).
     * @param string $mensaje Mensaje a mostrar.
     * @return void
     */
    private function add_admin_notice($tipo, $mensaje)
    {
        // Usa una variable global para almacenar el mensaje y tipo
        global $scs_admin_notices;
        $scs_admin_notices[] = array(
            'tipo' => $tipo,
            'mensaje' => $mensaje,
        );
    }

    /**
     * Redirige a la página de tipos de contenido.
     *
     * @return void
     */
    private function redirect_to_post_types_page()
    {
        wp_safe_redirect(admin_url('admin.php?page=scs-post-types'));
        exit;
    }

    /**
     * Muestra las notificaciones administrativas almacenadas.
     *
     * Esta función debe ser llamada en el hook 'admin_notices'.
     *
     * @return void
     */
    public function admin_notices()
    {
        global $scs_admin_notices;

        if (isset($scs_admin_notices) && is_array($scs_admin_notices)) {
            foreach ($scs_admin_notices as $noticia) {
                printf(
                    '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                    esc_attr($noticia['tipo']),
                    esc_html($noticia['mensaje'])
                );
            }
            // Limpia el array de notificaciones para no mostrarlas de nuevo.
            $scs_admin_notices = array();
        }
        // Verificar requisitos del plugin
        $this->check_plugin_requirements();
    }

    /**
     * Renderiza la página de tipos de contenido
     */
    public function render_post_types_page()
    {
        // Incluye el formulario para crear/editar post types.
        include_once SCS_PLUGIN_DIR . 'includes/admin/views/post-type-builder.php';

        // Opcionalmente, podrías mostrar aquí la lista de post types registrados desde la BD.
        $registered_post_types = get_option('scs_registered_post_types', array());
        if (! empty($registered_post_types)) {
            echo '<h3>' . __('Tipos de Contenido Registrados', 'seo-content-structure') . '</h3>';
            echo '<ul>';
            foreach ($registered_post_types as $nombre => $args) {
                echo '<li><strong>' . esc_html($nombre) . '</strong>: ' . esc_html($args['labels']['singular_name']) . '</li>';
                // Podrías mostrar más detalles de cada post type aquí.
            }
            echo '</ul>';
        }
    }
}
