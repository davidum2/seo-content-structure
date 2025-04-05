<?php

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\PostTypes\PostTypeFactory;
use WP_Error;

/**
 * Clase que maneja la interfaz de administración
 */
class AdminController implements Registrable
{
    /**
     * Factory de post types
     *
     * @var PostTypeFactory
     */
    protected $post_type_factory;

    /**
     * Mensajes de notificación para mostrar en el admin
     *
     * @var array
     */
    protected $admin_notices = [];

    /**
     * Hook de la página de tipos de contenido
     *
     * @var string
     */
    protected $post_types_page_hook;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->post_type_factory = new PostTypeFactory();
    }

    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // Hooks principales de administración
        $this->register_admin_hooks($loader);

        // Inicialización de controladores
        $this->init_controllers($loader);

        // Procesamiento de formularios y AJAX
        $this->register_form_handlers($loader);
    }

    /**
     * Registra los hooks principales de la administración
     *
     * @param Loader $loader Instancia del cargador
     */
    protected function register_admin_hooks(Loader $loader)
    {
        // Añadir menú de administración
        $loader->add_action('admin_menu', $this, 'add_admin_menu');

        // Cargar scripts y estilos
        $loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

        // Añadir enlaces de acción en la lista de plugins
        $loader->add_filter('plugin_action_links_' . SCS_PLUGIN_BASENAME, $this, 'add_action_links');

        // Añadir notificaciones y mensajes de ayuda
        $loader->add_action('admin_notices', $this, 'admin_notices');
        $loader->add_action('admin_head', $this, 'add_help_tabs');
    }

    /**
     * Inicializa los controladores específicos
     *
     * @param Loader $loader Instancia del cargador
     */
    protected function init_controllers(Loader $loader)
    {
        // Inicializar controlador de grupos de campos
        $field_group_controller = new FieldGroupController();
        $field_group_controller->register($loader);

        // Inicializar controlador de tipos de contenido
        $post_type_controller = new PostTypeController();
        $post_type_controller->register($loader);

        // Inicializar configuraciones generales
        $settings_page = new SettingsPage();
        $settings_page->register($loader);
    }

    /**
     * Registra los manejadores para procesamiento de formularios y AJAX
     *
     * @param Loader $loader Instancia del cargador
     */
    protected function register_form_handlers(Loader $loader)
    {
        // Manejador del formulario para registrar tipos de contenido
        $loader->add_action('admin_post_scs_register_post_type', $this, 'process_post_type_form');

        // Manejador AJAX para validar slugs de tipos de contenido
        $loader->add_action('wp_ajax_scs_validate_post_type_slug', $this, 'ajax_validate_post_type_slug');
    }

    /**
     * Añade el menú de administración
     */
    public function add_admin_menu()
    {
        // Menú principal
        \add_menu_page(
            \__('SEO Content Structure', 'seo-content-structure'),
            \__('SEO Content', 'seo-content-structure'),
            'manage_options',
            'seo-content-structure',
            [$this, 'render_main_page'],
            'dashicons-screenoptions',
            30
        );

        // Submenús
        $this->add_submenus();
    }

    /**
     * Añade los submenús del plugin
     */
    protected function add_submenus()
    {
        // Dashboard
        \add_submenu_page(
            'seo-content-structure',
            \__('Dashboard', 'seo-content-structure'),
            \__('Dashboard', 'seo-content-structure'),
            'manage_options',
            'seo-content-structure',
            [$this, 'render_main_page']
        );

        // Grupos de Campos
        \add_submenu_page(
            'seo-content-structure',
            \__('Grupos de Campos', 'seo-content-structure'),
            \__('Grupos de Campos', 'seo-content-structure'),
            'manage_options',
            'scs-field-groups',
            [$this, 'render_field_groups_page']
        );

        // Tipos de Contenido
        $this->post_types_page_hook = \add_submenu_page(
            'seo-content-structure',
            \__('Tipos de Contenido', 'seo-content-structure'),
            \__('Tipos de Contenido', 'seo-content-structure'),
            'manage_options',
            'scs-post-types',
            [$this, 'render_post_types_page']
        );

        // Editor de Schema
        \add_submenu_page(
            'seo-content-structure',
            \__('Editor de Schema', 'seo-content-structure'),
            \__('Editor de Schema', 'seo-content-structure'),
            'manage_options',
            'scs-schema-editor',
            [$this, 'render_schema_editor_page']
        );

        // Configuración
        \add_submenu_page(
            'seo-content-structure',
            \__('Configuración', 'seo-content-structure'),
            \__('Configuración', 'seo-content-structure'),
            'manage_options',
            'scs-settings',
            [$this, 'render_settings_page']
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

        // Cargar recursos comunes
        $this->enqueue_common_assets();

        // Cargar recursos específicos según la página
        $this->enqueue_page_specific_assets($hook);
    }

    /**
     * Carga recursos comunes para todas las páginas del plugin
     */
    protected function enqueue_common_assets()
    {
        // Estilos generales
        wp_enqueue_style(
            'scs-admin-styles',
            SCS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SCS_VERSION
        );

        // Scripts generales
        wp_enqueue_script(
            'scs-admin-scripts',
            SCS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable', 'jquery-ui-tabs'],
            SCS_VERSION,
            true
        );

        // Localización para scripts
        wp_localize_script(
            'scs-admin-scripts',
            'scs_admin',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('scs_admin_nonce'),
                'strings'  => $this->get_localized_strings(),
            ]
        );
    }

    /**
     * Obtiene strings localizados para los scripts
     *
     * @return array
     */
    protected function get_localized_strings()
    {
        return [
            'confirm_delete' => \__('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.', 'seo-content-structure'),
            'saving'         => \__('Guardando...', 'seo-content-structure'),
            'saved'          => \__('Guardado correctamente', 'seo-content-structure'),
            'error'          => \__('Ha ocurrido un error', 'seo-content-structure'),
            'confirm_delete_field' => \__('¿Estás seguro de que deseas eliminar este campo?', 'seo-content-structure'),
        ];
    }

    /**
     * Carga recursos específicos para cada página del plugin
     *
     * @param string $hook Página actual
     */
    protected function enqueue_page_specific_assets($hook)
    {
        if (strpos($hook, 'scs-field-groups') !== false) {
            $this->enqueue_field_groups_assets();
        } elseif (strpos($hook, 'scs-schema-editor') !== false) {
            $this->enqueue_schema_editor_assets();
        } elseif (strpos($hook, 'scs-post-types') !== false) {
            $this->enqueue_post_types_assets();
        }
    }

    /**
     * Carga recursos para la página de grupos de campos
     */
    protected function enqueue_field_groups_assets()
    {
        wp_enqueue_style(
            'scs-field-types',
            SCS_PLUGIN_URL . 'assets/css/field-types.css',
            ['scs-admin-styles'],
            SCS_VERSION
        );

        // Scripts específicos para campos de tipo imagen
        wp_enqueue_media();

        wp_enqueue_script(
            'scs-field-types',
            SCS_PLUGIN_URL . 'assets/js/field-types.js',
            ['scs-admin-scripts', 'jquery', 'wp-color-picker'],
            SCS_VERSION,
            true
        );
    }

    /**
     * Carga recursos para la página del editor de schema
     */
    protected function enqueue_schema_editor_assets()
    {
        // CodeMirror para editor JSON
        wp_enqueue_code_editor(['type' => 'application/json']);

        wp_enqueue_script(
            'scs-json-ld-builder',
            SCS_PLUGIN_URL . 'assets/js/json-ld-builder.js',
            ['scs-admin-scripts', 'wp-codemirror'],
            SCS_VERSION,
            true
        );
    }

    /**
     * Carga scripts específicos para la página de tipos de contenido
     */
    public function enqueue_post_types_assets()
    {
        // Scripts para la página de tipos de contenido
        wp_enqueue_style('scs-post-types-css', SCS_PLUGIN_URL . 'assets/css/post-types.css', array(), SCS_VERSION);
        wp_enqueue_script('scs-post-types-js', SCS_PLUGIN_URL . 'assets/js/post-types.js', array('jquery', 'jquery-ui-sortable'), SCS_VERSION, true);

        // Localizar script
        wp_localize_script('scs-post-types-js', 'scsPostTypes', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scs_post_types_nonce'),
            'strings' => array(
                'confirmDelete' => \__('¿Estás seguro de que deseas eliminar este elemento?', 'seo-content-structure'),
                'slugExists' => \__('Este slug ya está en uso. Por favor, elige otro.', 'seo-content-structure'),
                'slugInvalid' => \__('El slug solo puede contener letras minúsculas, números y guiones.', 'seo-content-structure'),
            )
        ));
    }

    /**
     * Añade enlaces de acción en la lista de plugins
     *
     * @param array $links Enlaces existentes
     * @return array Enlaces modificados
     */
    public function add_action_links($links)
    {
        $custom_links = [
            '<a href="' . admin_url('admin.php?page=seo-content-structure') . '">' . \__('Dashboard', 'seo-content-structure') . '</a>',
            '<a href="' . admin_url('admin.php?page=scs-settings') . '">' . \__('Configuración', 'seo-content-structure') . '</a>',
        ];

        return array_merge($custom_links, $links);
    }

    /**
     * Muestra las notificaciones administrativas
     */
    public function admin_notices()
    {
        // Mostrar notificaciones almacenadas
        $this->display_admin_notices();

        // Verificar requisitos del plugin
        $this->check_plugin_requirements();
    }

    /**
     * Muestra las notificaciones administrativas almacenadas
     */
    protected function display_admin_notices()
    {
        // Obtener notificaciones de transient si existen
        $stored_notices = get_transient('scs_admin_notices');
        if ($stored_notices && is_array($stored_notices)) {
            $this->admin_notices = array_merge($this->admin_notices, $stored_notices);
            delete_transient('scs_admin_notices');
        }

        // Mostrar todas las notificaciones
        foreach ($this->admin_notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['tipo']),
                esc_html($notice['mensaje'])
            );
        }

        // Limpiar el array de notificaciones
        $this->admin_notices = [];
    }

    /**
     * Verifica los requisitos del plugin y muestra notificaciones
     */
    protected function check_plugin_requirements()
    {
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            echo '<div class="notice notice-error">';
            echo '<p>' . sprintf(
                \__('%s requiere PHP 7.0 o superior. Tu servidor está ejecutando PHP %s.', 'seo-content-structure'),
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
                \__('%s requiere WordPress 5.0 o superior. Tu sitio está ejecutando WordPress %s.', 'seo-content-structure'),
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
        if (!$screen || (strpos($screen->id, 'seo-content-structure') === false && strpos($screen->id, 'scs-') === false)) {
            return;
        }

        // Pestaña general de ayuda
        $screen->add_help_tab([
            'id'      => 'scs_help_overview',
            'title'   => \__('Descripción General', 'seo-content-structure'),
            'content' => '<p>' . \__('SEO Content Structure te permite crear tipos de contenido personalizados con campos avanzados y estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure') . '</p>',
        ]);

        // Pestañas específicas según la página
        $this->add_page_specific_help_tabs($screen);

        // Panel lateral con enlaces
        $this->add_help_sidebar($screen);
    }

    /**
     * Añade pestañas de ayuda específicas según la página
     *
     * @param \WP_Screen $screen La pantalla actual
     */
    protected function add_page_specific_help_tabs($screen)
    {
        if (strpos($screen->id, 'scs-field-groups') !== false) {
            $screen->add_help_tab([
                'id'      => 'scs_help_field_groups',
                'title'   => \__('Grupos de Campos', 'seo-content-structure'),
                'content' => '<p>' . \__('Crea y administra grupos de campos personalizados para tus tipos de contenido.', 'seo-content-structure') . '</p>',
            ]);
        } elseif (strpos($screen->id, 'scs-post-types') !== false) {
            $screen->add_help_tab([
                'id'      => 'scs_help_post_types',
                'title'   => \__('Tipos de Contenido', 'seo-content-structure'),
                'content' => '<p>' . \__('Crea y administra tipos de contenido personalizados para tu sitio.', 'seo-content-structure') . '</p>',
            ]);
        } elseif (strpos($screen->id, 'scs-schema-editor') !== false) {
            $screen->add_help_tab([
                'id'      => 'scs_help_schema',
                'title'   => \__('Editor de Schema', 'seo-content-structure'),
                'content' => '<p>' . \__('Crea y edita estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure') . '</p>',
            ]);
        }
    }

    /**
     * Añade el panel lateral de ayuda
     *
     * @param \WP_Screen $screen La pantalla actual
     */
    protected function add_help_sidebar($screen)
    {
        $screen->set_help_sidebar(
            '<p><strong>' . \__('Para más información:', 'seo-content-structure') . '</strong></p>' .
                '<p><a href="https://ejemplo.com/docs" target="_blank">' . \__('Documentación', 'seo-content-structure') . '</a></p>' .
                '<p><a href="https://ejemplo.com/support" target="_blank">' . \__('Soporte', 'seo-content-structure') . '</a></p>'
        );
    }

    /**
     * Procesa el formulario de creación de tipos de contenido
     */
    public function process_post_type_form()
    {
        // Verificar nonce para seguridad
        if (!isset($_POST['scs_post_type_nonce']) || !wp_verify_nonce($_POST['scs_post_type_nonce'], 'scs_register_post_type')) {
            wp_die(\__('¡Vaya, algo salió mal! Por favor, recarga la página e inténtalo de nuevo.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(\__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Recoger y validar datos
        $post_type_data = $this->get_post_type_data_from_form();

        // Validar datos obligatorios
        if (!$this->validate_post_type_data($post_type_data)) {
            $this->redirect_to_post_types_page();
            return;
        }

        // Preparar argumentos para register_post_type
        $args = $this->prepare_post_type_args($post_type_data);

        // Registrar el post type
        $result = register_post_type($post_type_data['nombre'], $args);

        if (is_wp_error($result)) {
            // Manejar el error de registro
            $this->add_admin_notice('error', \__('Error al registrar el tipo de contenido: ', 'seo-content-structure') . $result->get_error_message());
        } else {
            // Éxito: guardar en la base de datos para persistencia
            $this->save_post_type_to_db($post_type_data['nombre'], $args);
            $this->add_admin_notice('success', \__('Tipo de contenido registrado correctamente.', 'seo-content-structure'));

            // Limpiar caché de tipos de contenido
            delete_transient('scs_post_types_cache');

            // Actualizar reglas de reescritura
            flush_rewrite_rules();
        }

        // Redirigir de vuelta a la página de tipos de contenido
        $this->redirect_to_post_types_page();
    }

    /**
     * Obtiene y sanitiza los datos del formulario de tipos de contenido
     *
     * @return array Datos sanitizados del formulario
     */
    protected function get_post_type_data_from_form()
    {
        return [
            'nombre' => sanitize_key($_POST['nombre'] ?? ''),
            'singular' => sanitize_text_field($_POST['singular'] ?? ''),
            'plural' => sanitize_text_field($_POST['plural'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'public' => isset($_POST['public']),
            'show_ui' => isset($_POST['show_ui']),
            'show_in_menu' => isset($_POST['show_in_menu']),
            'supports' => $this->sanitize_supports_array($_POST['supports'] ?? []),
            'menu_icon' => sanitize_text_field($_POST['menu_icon'] ?? 'dashicons-admin-post'),
            'has_archive' => isset($_POST['has_archive']),
            'hierarchical' => isset($_POST['hierarchical']),
            'show_in_rest' => isset($_POST['show_in_rest']),
            'schema_type' => sanitize_text_field($_POST['schema_type'] ?? '')
        ];
    }

    /**
     * Sanitiza el array de soportes para el tipo de contenido
     *
     * @param array $supports Array de soportes del formulario
     * @return array Array sanitizado
     */
    protected function sanitize_supports_array($supports)
    {
        if (!is_array($supports)) {
            return ['title', 'editor']; // Valores por defecto
        }

        $allowed_supports = [
            'title',
            'editor',
            'author',
            'thumbnail',
            'excerpt',
            'comments',
            'custom-fields',
            'revisions',
            'post-formats'
        ];

        return array_intersect($supports, $allowed_supports);
    }

    /**
     * Valida los datos del tipo de contenido
     *
     * @param array $data Datos del tipo de contenido
     * @return bool True si los datos son válidos
     */
    protected function validate_post_type_data($data)
    {
        // Validar nombre del post type
        if (empty($data['nombre'])) {
            $this->add_admin_notice('error', \__('El nombre del tipo de contenido es obligatorio.', 'seo-content-structure'));
            return false;
        }

        // Validar formato del nombre
        if (!preg_match('/^[a-z0-9_\-]+$/', $data['nombre'])) {
            $this->add_admin_notice('error', \__('El nombre del tipo de contenido solo puede contener letras minúsculas, números, guiones y guiones bajos.', 'seo-content-structure'));
            return false;
        }

        // Validar que ya no exista
        if (post_type_exists($data['nombre'])) {
            $this->add_admin_notice('error', \__('Ya existe un tipo de contenido con ese nombre.', 'seo-content-structure'));
            return false;
        }

        // Validar nombres singular y plural
        if (empty($data['singular']) || empty($data['plural'])) {
            $this->add_admin_notice('error', \__('Los nombres singular y plural son obligatorios.', 'seo-content-structure'));
            return false;
        }

        return true;
    }

    /**
     * Prepara los argumentos para register_post_type
     *
     * @param array $data Datos del tipo de contenido
     * @return array Argumentos para register_post_type
     */
    protected function prepare_post_type_args($data)
    {
        return [
            'labels' => [
                'name' => $data['plural'],
                'singular_name' => $data['singular'],
                'menu_name' => $data['plural'],
                'admin_bar_menu_name' => $data['singular'],
                'add_new' => \__('Añadir Nuevo', 'seo-content-structure') . ' ' . $data['singular'],
                'add_new_item' => \__('Añadir Nuevo', 'seo-content-structure') . ' ' . $data['singular'],
                'edit_item' => \__('Editar', 'seo-content-structure') . ' ' . $data['singular'],
                'new_item' => \__('Nuevo', 'seo-content-structure') . ' ' . $data['singular'],
                'view_item' => \__('Ver', 'seo-content-structure') . ' ' . $data['singular'],
                'search_items' => \__('Buscar', 'seo-content-structure') . ' ' . $data['plural'],
                'not_found' => \__('No se encontraron', 'seo-content-structure') . ' ' . $data['plural'],
                'not_found_in_trash' => \__('No se encontraron', 'seo-content-structure') . ' ' . $data['plural'] . ' en la papelera',
                'all_items' => \__('Todos los', 'seo-content-structure') . ' ' . $data['plural'],
            ],
            'description' => $data['descripcion'],
            'public' => $data['public'],
            'show_ui' => $data['show_ui'],
            'show_in_menu' => $data['show_in_menu'],
            'menu_position' => 20,
            'menu_icon' => $data['menu_icon'],
            'supports' => $data['supports'],
            'has_archive' => $data['has_archive'],
            'hierarchical' => $data['hierarchical'],
            'show_in_rest' => $data['show_in_rest'],
            'rewrite' => ['slug' => $data['nombre']],
            'capability_type' => 'post',
            'schema_type' => $data['schema_type']
        ];
    }

    /**
     * Guarda la configuración del post type en la base de datos
     *
     * @param string $nombre Nombre del post type
     * @param array $args Argumentos del post type
     */
    protected function save_post_type_to_db($nombre, $args)
    {
        // Intenta usar PostTypeFactory primero
        try {
            if (method_exists($this->post_type_factory, 'save_post_type')) {
                $post_type_data = [
                    'post_type' => $nombre,
                    'args' => $args,
                    'taxonomies' => [],
                    'schema_type' => isset($args['schema_type']) ? $args['schema_type'] : ''
                ];

                $this->post_type_factory->save_post_type($post_type_data);
                return;
            }
        } catch (\Exception $e) {
            // Si hay error, continuar con el método alternativo
        }

        // Método alternativo usando options API
        $existing_post_types = get_option('scs_registered_post_types', []);
        $existing_post_types[$nombre] = $args;
        update_option('scs_registered_post_types', $existing_post_types);
    }

    /**
     * Añade un mensaje de notificación para mostrar en el admin
     *
     * @param string $tipo Tipo de notificación (success, error, warning)
     * @param string $mensaje Mensaje a mostrar
     */
    protected function add_admin_notice($tipo, $mensaje)
    {
        $notice = [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
        ];

        // Agregar al array de notificaciones
        $this->admin_notices[] = $notice;

        // Guardar en transient para persistir tras redirección
        $stored_notices = get_transient('scs_admin_notices') ?: [];
        $stored_notices[] = $notice;
        set_transient('scs_admin_notices', $stored_notices, 60); // 1 minuto
    }

    /**
     * Redirige a la página de tipos de contenido
     */
    protected function redirect_to_post_types_page()
    {
        wp_safe_redirect(admin_url('admin.php?page=scs-post-types'));
        exit;
    }

    /**
     * Maneja la validación AJAX de slugs de tipos de contenido
     */
    public function ajax_validate_post_type_slug()
    {
        // Verificar nonce
        check_ajax_referer('scs_admin_nonce', 'nonce');

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(\__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';

        // Validar formato
        if (!preg_match('/^[a-z0-9_\-]+$/', $slug)) {
            wp_send_json_error(\__('El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.', 'seo-content-structure'));
        }

        // Verificar longitud
        if (strlen($slug) < 3 || strlen($slug) > 20) {
            wp_send_json_error(\__('El slug debe tener entre 3 y 20 caracteres.', 'seo-content-structure'));
        }

        // Verificar si ya existe
        if (post_type_exists($slug)) {
            wp_send_json_error(\__('Este tipo de contenido ya existe.', 'seo-content-structure'));
        }

        // Verificar reservados
        $reserved = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];
        if (in_array($slug, $reserved)) {
            wp_send_json_error(\__('Este es un tipo de contenido reservado de WordPress.', 'seo-content-structure'));
        }

        wp_send_json_success(\__('El slug es válido.', 'seo-content-structure'));
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
        error_log('SCS_TRACE: === AdminController::render_post_types_page() INICIO ===');
        error_log('SCS_TRACE: $_GET = ' . print_r($_GET, true));

        try {
            // Verificar si tenemos la factory
            if (!$this->post_type_factory) {
                error_log('SCS_TRACE: Creando instancia de PostTypeFactory en render_post_types_page');
                $this->post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
            } else {
                error_log('SCS_TRACE: Factory ya existe en render_post_types_page');
            }

            $action = isset($_GET['action']) ? \sanitize_text_field($_GET['action']) : 'list';
            $post_type = isset($_GET['post_type']) ? \sanitize_key($_GET['post_type']) : '';

            error_log("SCS_TRACE: Renderizando página para action=$action, post_type=$post_type");

            // Si estamos editando, verificar que el post type esté disponible
            if ($action === 'edit' && !empty($post_type)) {
                $post_type_obj = $this->post_type_factory->get_post_type($post_type);
                if (!$post_type_obj) {
                    error_log("SCS_ERROR: Post type no encontrado en render_post_types_page: $post_type");

                    // Intentar cargar desde BD directamente
                    global $wpdb;
                    $table = $wpdb->prefix . 'scs_post_types';
                    $query = $wpdb->prepare("SELECT * FROM $table WHERE post_type = %s", $post_type);
                    $post_type_data = $wpdb->get_row($query, ARRAY_A);

                    error_log("SCS_TRACE: Datos desde BD en render: " . ($post_type_data ? 'ENCONTRADO' : 'NO ENCONTRADO'));

                    if (!$post_type_data) {
                        echo '<div class="notice notice-error"><p>';
                        echo 'El tipo de contenido solicitado no existe o no se pudo cargar.';
                        echo '</p></div>';

                        // Cargar vista de lista como fallback
                        error_log("SCS_TRACE: Cambiando a vista de listado como fallback");
                        include_once SCS_PLUGIN_DIR . 'includes/admin/views/post-types-list.php';
                        return;
                    }
                } else {
                    error_log("SCS_TRACE: Post type encontrado en render: " . $post_type_obj->get_post_type());
                }
            }

            // Cargar la vista según la acción
            if ($action === 'edit' || $action === 'new') {
                // Vista de edición/creación
                error_log("SCS_TRACE: Comprobando si el archivo post-type-builder.php existe");
                $builder_file = SCS_PLUGIN_DIR . 'includes/admin/views/post-type-builder.php';
                if (file_exists($builder_file)) {
                    error_log("SCS_TRACE: El archivo post-type-builder.php EXISTE");
                } else {
                    error_log("SCS_ERROR: El archivo post-type-builder.php NO EXISTE");
                }

                error_log("SCS_TRACE: Cargando vista post-type-builder.php");
                include_once SCS_PLUGIN_DIR . 'includes/admin/views/post-type-builder.php';
            } else {
                // Vista de listado
                error_log("SCS_TRACE: Comprobando si el archivo post-types-list.php existe");
                $list_file = SCS_PLUGIN_DIR . 'includes/admin/views/post-types-list.php';
                if (file_exists($list_file)) {
                    error_log("SCS_TRACE: El archivo post-types-list.php EXISTE");
                } else {
                    error_log("SCS_ERROR: El archivo post-types-list.php NO EXISTE");
                }

                error_log("SCS_TRACE: Cargando vista post-types-list.php");
                include_once SCS_PLUGIN_DIR . 'includes/admin/views/post-types-list.php';
            }

            error_log('SCS_TRACE: === AdminController::render_post_types_page() FIN ===');
        } catch (\Throwable $e) {
            error_log('SCS_FATAL: Error en render_post_types_page: ' . $e->getMessage());
            error_log('SCS_FATAL: En archivo: ' . $e->getFile() . ' línea ' . $e->getLine());
            error_log('SCS_FATAL: Trace: ' . $e->getTraceAsString());

            echo '<div class="notice notice-error"><p>';
            echo 'Error al cargar la página. Por favor verifica el log de errores.';
            echo '</p></div>';
        }
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
     * Registra un nuevo tipo de contenido personalizado
     *
     * Método para ser utilizado por otras partes del plugin o por desarrolladores externos
     *
     * @param string $post_type_name Nombre único del tipo de contenido
     * @param array $args Argumentos para el tipo de contenido
     * @param array $taxonomies Taxonomías asociadas al tipo de contenido
     * @param string $schema_type Tipo de schema JSON-LD asociado
     * @return int|WP_Error ID del registro o error
     */
    public function register_custom_post_type($post_type_name, $args = [], $taxonomies = [], $schema_type = '')
    {
        // Validar nombre
        if (empty($post_type_name) || !is_string($post_type_name)) {
            return new WP_Error('invalid_post_type', \__('Nombre de tipo de contenido inválido', 'seo-content-structure'));
        }

        // Sanitizar nombre
        $post_type_name = sanitize_key($post_type_name);

        // Verificar si ya existe
        if (post_type_exists($post_type_name)) {
            return new WP_Error('post_type_exists', \__('Este tipo de contenido ya existe', 'seo-content-structure'));
        }

        // Establecer etiquetas por defecto si no se proporcionan
        if (!isset($args['labels']) || empty($args['labels'])) {
            $singular = ucfirst(str_replace('_', ' ', $post_type_name));
            $plural = $singular . 's';

            $args['labels'] = [
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'all_items' => \__('Todos los', 'seo-content-structure') . ' ' . $plural,
                'add_new' => \__('Añadir nuevo', 'seo-content-structure'),
                'add_new_item' => \__('Añadir nuevo', 'seo-content-structure') . ' ' . $singular,
                'edit_item' => \__('Editar', 'seo-content-structure') . ' ' . $singular,
                'new_item' => \__('Nuevo', 'seo-content-structure') . ' ' . $singular,
                'view_item' => \__('Ver', 'seo-content-structure') . ' ' . $singular,
                'search_items' => \__('Buscar', 'seo-content-structure') . ' ' . $plural,
                'not_found' => \__('No se encontraron', 'seo-content-structure') . ' ' . $plural,
                'not_found_in_trash' => \__('No se encontraron', 'seo-content-structure') . ' ' . $plural . ' en la papelera',
            ];
        }

        // Establecer valores por defecto
        $defaults = [
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-post',
        ];

        $args = wp_parse_args($args, $defaults);

        // Registrar el post type
        $registered = register_post_type($post_type_name, $args);

        if (is_wp_error($registered)) {
            return $registered;
        }

        // Registrar taxonomías asociadas si existen
        if (!empty($taxonomies) && is_array($taxonomies)) {
            foreach ($taxonomies as $taxonomy => $tax_args) {
                if (taxonomy_exists($taxonomy)) {
                    register_taxonomy_for_object_type($taxonomy, $post_type_name);
                } else {
                    register_taxonomy($taxonomy, $post_type_name, $tax_args);
                }
            }
        }

        // Guardar en base de datos para persistencia
        return $this->save_post_type_to_db($post_type_name, $args, $taxonomies, $schema_type);
    }

    /**
     * Registra el hook para cargar la página de tipos de contenido
     */
    public function register_load_hook()
    {
        error_log('SCS_TRACE: === AdminController::register_load_hook() INICIO ===');

        // Verificar si ya se registró el hook para evitar duplicados
        if (\did_action('load-' . $this->post_types_page_hook)) {
            error_log('SCS_TRACE: Hook ya registrado para post-types-page, evitando duplicado');
            return;
        }

        // Registra el hook para cuando se cargue la página
        \add_action('load-' . $this->post_types_page_hook, array($this, 'load_post_types_page'));

        error_log('SCS_TRACE: Hook registrado para load-' . $this->post_types_page_hook);
        error_log('SCS_TRACE: === AdminController::register_load_hook() FIN ===');
    }

    /**
     * Callback al cargar la página de tipos de contenido
     */
    public function load_post_types_page()
    {
        error_log('SCS_TRACE: === AdminController::load_post_types_page() INICIO ===');
        error_log('SCS_TRACE: $_GET completo = ' . print_r($_GET, true));
        error_log('SCS_TRACE: post_types_page_hook = ' . $this->post_types_page_hook);

        try {
            // Crear instancia de factory si no existe
            if (!$this->post_type_factory) {
                error_log('SCS_TRACE: Creando instancia de PostTypeFactory');
                $this->post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
            } else {
                error_log('SCS_TRACE: Factory ya existe');
            }

            // Verificar si la tabla existe
            global $wpdb;
            $table_name = $wpdb->prefix . 'scs_post_types';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            error_log('SCS_TRACE: ¿Tabla scs_post_types existe? ' . ($table_exists ? 'SÍ' : 'NO'));

            // Verificar permisos
            if (!current_user_can('manage_options')) {
                error_log('SCS_ERROR: Usuario sin permisos para manage_options');
                wp_die(\__('No tienes permisos para acceder a esta página.', 'seo-content-structure'));
            }

            // Verificar si estamos editando o viendo la lista
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
            $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';

            error_log("SCS_TRACE: Action=$action, post_type=$post_type");

            // Si estamos editando, debemos cargar datos del tipo de contenido
            if ($action === 'edit' && !empty($post_type)) {
                error_log("SCS_TRACE: Modo edición para post_type=$post_type");

                // Verificar que el post type existe
                $post_type_obj = $this->post_type_factory->get_post_type($post_type);

                if (!$post_type_obj) {
                    error_log("SCS_ERROR: Post type no encontrado para edición: $post_type");

                    // Intentar verificar si existe en la base de datos directamente
                    global $wpdb;
                    $table = $wpdb->prefix . 'scs_post_types';
                    $query = $wpdb->prepare("SELECT * FROM $table WHERE post_type = %s", $post_type);
                    error_log("SCS_TRACE: Ejecutando query: $query");
                    $exists_in_db = $wpdb->get_var($query);

                    if ($exists_in_db) {
                        error_log("SCS_ERROR: Post type existe en BD pero no se pudo cargar en factory");
                        error_log("SCS_TRACE: Intentando cargar post type directamente desde BD");

                        // Obtener detalles completos
                        $post_type_data = $wpdb->get_row($query, ARRAY_A);
                        error_log("SCS_TRACE: Datos desde BD: " . print_r($post_type_data, true));
                    } else {
                        error_log("SCS_ERROR: Post type no existe en BD");
                        // Redirigir con mensaje de error
                        wp_redirect(add_query_arg(
                            array('page' => 'scs-post-types', 'error' => urlencode('El tipo de contenido no existe')),
                            admin_url('admin.php')
                        ));
                        exit;
                    }
                } else {
                    error_log("SCS_TRACE: Post type encontrado para edición: " . $post_type_obj->get_post_type());
                    error_log("SCS_TRACE: Clase del objeto: " . get_class($post_type_obj));
                    error_log("SCS_TRACE: Datos del post type: " . print_r($post_type_obj, true));
                }
            }

            // Configurar acciones específicas
            $this->setup_post_types_page_actions();

            error_log('SCS_TRACE: === AdminController::load_post_types_page() FIN ===');
        } catch (\Throwable $e) {
            error_log('SCS_FATAL: Error en load_post_types_page: ' . $e->getMessage());
            error_log('SCS_FATAL: Trace: ' . $e->getTraceAsString());
            wp_die('Ha ocurrido un error al cargar la página. Revisa el log de errores para más detalles.');
        }
    }

    /**
     * Configura acciones específicas para la página de tipos de contenido
     */
    public function setup_post_types_page_actions()
    {
        error_log('SCS_TRACE: === AdminController::setup_post_types_page_actions() INICIO ===');

        // Agregar scripts específicos para la página de post types
        \add_action('admin_enqueue_scripts', array($this, 'enqueue_post_types_assets'));

        // Obtener la acción actual para depuración
        $action = isset($_GET['action']) ? \sanitize_text_field($_GET['action']) : 'list';
        error_log("SCS_TRACE: setup_post_types_page_actions - Acción: $action");

        error_log('SCS_TRACE: === AdminController::setup_post_types_page_actions() FIN ===');
    }
}
