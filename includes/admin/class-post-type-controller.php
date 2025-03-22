<?php

/**
 * Controlador para gestionar tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage Admin
 */

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\PostTypes\PostTypeFactory;
use SEOContentStructure\PostTypes\GenericPostType;

/**
 * Clase que maneja la interfaz y el procesamiento de tipos de contenido personalizados
 */
class PostTypeController implements Registrable
{
    /**
     * Factory de post types
     *
     * @var PostTypeFactory
     */
    protected $post_type_factory;

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
        // Acciones para procesar formularios
        $loader->add_action('admin_post_scs_save_post_type', $this, 'process_save_post_type');
        $loader->add_action('admin_post_scs_delete_post_type', $this, 'process_delete_post_type');

        // Hook para manejar AJAX de validación de slug
        $loader->add_action('wp_ajax_scs_validate_post_type_slug', $this, 'ajax_validate_post_type_slug');
    }

    /**
     * Procesa el guardado de un tipo de contenido personalizado
     */
    public function process_save_post_type()
    {
        // Verificar nonce
        if (!isset($_POST['scs_post_type_nonce']) || !wp_verify_nonce($_POST['scs_post_type_nonce'], 'scs_save_post_type')) {
            wp_die(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Recoger y sanitizar datos del formulario
        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';

        if (empty($post_type)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'error' => __('El slug del tipo de contenido no puede estar vacío.', 'seo-content-structure')
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Sanitizar argumentos
        $args = $this->sanitize_post_type_args($_POST['args'] ?? array());

        // Asegurar que al menos hay etiquetas básicas
        if (empty($args['labels']['singular_name']) || empty($args['labels']['name'])) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'action' => 'new',
                    'error' => __('Los nombres en singular y plural son obligatorios.', 'seo-content-structure')
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Procesar taxonomías existentes
        $taxonomies = array();
        if (isset($_POST['taxonomies']) && is_array($_POST['taxonomies'])) {
            foreach ($_POST['taxonomies'] as $taxonomy) {
                $taxonomy = sanitize_key($taxonomy);
                if (!empty($taxonomy)) {
                    $taxonomies[$taxonomy] = array();
                }
            }
        }

        // Procesar taxonomías personalizadas
        if (isset($_POST['custom_taxonomies']) && is_array($_POST['custom_taxonomies'])) {
            foreach ($_POST['custom_taxonomies'] as $tax_data) {
                if (empty($tax_data['slug']) || empty($tax_data['singular']) || empty($tax_data['plural'])) {
                    continue;
                }

                $tax_slug = sanitize_key($tax_data['slug']);
                $singular = sanitize_text_field($tax_data['singular']);
                $plural = sanitize_text_field($tax_data['plural']);
                $hierarchical = isset($tax_data['hierarchical']) ? (bool) $tax_data['hierarchical'] : true;

                $taxonomies[$tax_slug] = array(
                    'singular' => $singular,
                    'plural' => $plural,
                    'args' => array(
                        'hierarchical' => $hierarchical,
                        'labels' => array(
                            'name' => $plural,
                            'singular_name' => $singular,
                        ),
                    ),
                );
            }
        }

        // Schema type
        $schema_type = isset($_POST['schema_type']) ? sanitize_text_field($_POST['schema_type']) : '';

        // Preparar los datos para guardar
        $post_type_data = array(
            'post_type' => $post_type,
            'args' => $args,
            'taxonomies' => $taxonomies,
            'schema_type' => $schema_type,
        );

        // Guardar tipo de contenido
        $result = $this->post_type_factory->save_post_type($post_type_data);

        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'action' => 'new',
                    'error' => $result->get_error_message(),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Redireccionar con mensaje de éxito
        wp_redirect(add_query_arg(
            array(
                'page' => 'scs-post-types',
                'message' => 'saved',
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Sanitiza los argumentos del tipo de contenido
     *
     * @param array $args Argumentos a sanitizar
     * @return array Argumentos sanitizados
     */
    private function sanitize_post_type_args($args)
    {
        $sanitized = array();

        // Sanitizar etiquetas
        if (isset($args['labels']) && is_array($args['labels'])) {
            $sanitized['labels'] = array();
            foreach ($args['labels'] as $key => $value) {
                $sanitized['labels'][$key] = sanitize_text_field($value);
            }

            // Asegurarse de que se completen todas las etiquetas necesarias
            if (isset($sanitized['labels']['singular_name']) && !isset($sanitized['labels']['name'])) {
                $sanitized['labels']['name'] = $sanitized['labels']['singular_name'] . 's';
            }

            if (isset($sanitized['labels']['name']) && !isset($sanitized['labels']['singular_name'])) {
                $sanitized['labels']['singular_name'] = rtrim($sanitized['labels']['name'], 's');
            }

            // Completar etiquetas secundarias
            $singular = $sanitized['labels']['singular_name'];
            $plural = $sanitized['labels']['name'];

            $default_labels = array(
                'menu_name'             => $plural,
                'name_admin_bar'        => $singular,
                'archives'              => sprintf(__('Archivo de %s', 'seo-content-structure'), $plural),
                'attributes'            => sprintf(__('Atributos de %s', 'seo-content-structure'), $singular),
                'parent_item_colon'     => sprintf(__('%s Superior:', 'seo-content-structure'), $singular),
                'all_items'             => sprintf(__('Todos los %s', 'seo-content-structure'), $plural),
                'add_new_item'          => sprintf(__('Añadir Nuevo %s', 'seo-content-structure'), $singular),
                'add_new'               => __('Añadir Nuevo', 'seo-content-structure'),
                'new_item'              => sprintf(__('Nuevo %s', 'seo-content-structure'), $singular),
                'edit_item'             => sprintf(__('Editar %s', 'seo-content-structure'), $singular),
                'update_item'           => sprintf(__('Actualizar %s', 'seo-content-structure'), $singular),
                'view_item'             => sprintf(__('Ver %s', 'seo-content-structure'), $singular),
                'view_items'            => sprintf(__('Ver %s', 'seo-content-structure'), $plural),
                'search_items'          => sprintf(__('Buscar %s', 'seo-content-structure'), $singular),
                'not_found'             => __('No encontrado', 'seo-content-structure'),
                'not_found_in_trash'    => __('No encontrado en la papelera', 'seo-content-structure'),
                'featured_image'        => __('Imagen Destacada', 'seo-content-structure'),
                'set_featured_image'    => __('Establecer imagen destacada', 'seo-content-structure'),
                'remove_featured_image' => __('Eliminar imagen destacada', 'seo-content-structure'),
                'use_featured_image'    => __('Usar como imagen destacada', 'seo-content-structure'),
                'insert_into_item'      => sprintf(__('Insertar en %s', 'seo-content-structure'), $singular),
                'uploaded_to_this_item' => sprintf(__('Subido a este %s', 'seo-content-structure'), $singular),
                'items_list'            => sprintf(__('Lista de %s', 'seo-content-structure'), $plural),
                'items_list_navigation' => sprintf(__('Navegación de lista de %s', 'seo-content-structure'), $plural),
                'filter_items_list'     => sprintf(__('Filtrar lista de %s', 'seo-content-structure'), $plural),
            );

            // Fusionar las etiquetas personalizadas con las predeterminadas
            $sanitized['labels'] = wp_parse_args($sanitized['labels'], $default_labels);
        }

        // Sanitizar supports
        if (isset($args['supports']) && is_array($args['supports'])) {
            $sanitized['supports'] = array_map('sanitize_key', $args['supports']);
        } else {
            $sanitized['supports'] = array('title', 'editor', 'thumbnail');
        }

        // Sanitizar propiedades booleanas
        $boolean_props = array(
            'public',
            'show_ui',
            'show_in_menu',
            'show_in_admin_bar',
            'show_in_nav_menus',
            'has_archive',
            'hierarchical',
            'show_in_rest',
            'exclude_from_search',
            'publicly_queryable',
            'can_export',
            'active'
        );

        foreach ($boolean_props as $prop) {
            $sanitized[$prop] = isset($args[$prop]) && $args[$prop] ? true : false;
        }

        // Sanitizar otras propiedades
        $text_props = array(
            'menu_icon',
            'menu_position',
            'capability_type',
            'rewrite',
            'query_var'
        );

        foreach ($text_props as $prop) {
            if (isset($args[$prop])) {
                $sanitized[$prop] = sanitize_text_field($args[$prop]);
            }
        }

        // Asegurarse de que tiene configuración básica
        if (!isset($sanitized['public'])) {
            $sanitized['public'] = true;
        }

        if (!isset($sanitized['show_ui'])) {
            $sanitized['show_ui'] = true;
        }

        if (!isset($sanitized['show_in_menu'])) {
            $sanitized['show_in_menu'] = true;
        }

        if (!isset($sanitized['show_in_admin_bar'])) {
            $sanitized['show_in_admin_bar'] = true;
        }

        if (!isset($sanitized['show_in_nav_menus'])) {
            $sanitized['show_in_nav_menus'] = true;
        }

        if (!isset($sanitized['menu_icon'])) {
            $sanitized['menu_icon'] = 'dashicons-admin-post';
        }

        if (!isset($sanitized['capability_type'])) {
            $sanitized['capability_type'] = 'post';
        }

        // Si es un tipo de contenido público, asegurarnos de que sea consultable
        if ($sanitized['public'] && !isset($sanitized['publicly_queryable'])) {
            $sanitized['publicly_queryable'] = true;
        }

        // Asegurarnos de que hay un valor para exclude_from_search
        if (!isset($sanitized['exclude_from_search'])) {
            $sanitized['exclude_from_search'] = !$sanitized['public'];
        }

        return $sanitized;
    }

    /**
     * Procesa la eliminación de un tipo de contenido
     */
    public function process_delete_post_type()
    {
        // Verificar nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'scs_delete_post_type')) {
            wp_die(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Obtener post type
        $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';

        if (empty($post_type)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'error' => __('Tipo de contenido no especificado.', 'seo-content-structure'),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Verificar que no sea un tipo de contenido nativo de WordPress
        $native_post_types = array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part');
        if (in_array($post_type, $native_post_types)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'error' => __('No puedes eliminar tipos de contenido nativos de WordPress.', 'seo-content-structure'),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Eliminar tipo de contenido
        $result = $this->post_type_factory->delete_post_type($post_type);

        if (!$result) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-post-types',
                    'error' => __('Error al eliminar el tipo de contenido.', 'seo-content-structure'),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Redireccionar con mensaje de éxito
        wp_redirect(add_query_arg(
            array(
                'page' => 'scs-post-types',
                'message' => 'deleted',
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Valida si un slug de post type es válido mediante AJAX
     */
    public function ajax_validate_post_type_slug()
    {
        // Verificar nonce
        if (!check_ajax_referer('scs_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Error de seguridad.', 'seo-content-structure'));
        }

        $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';

        if (empty($slug)) {
            wp_send_json_error(__('El slug no puede estar vacío.', 'seo-content-structure'));
        }

        // Verificar longitud
        if (strlen($slug) < 3 || strlen($slug) > 20) {
            wp_send_json_error(__('El slug debe tener entre 3 y 20 caracteres.', 'seo-content-structure'));
        }

        // Verificar si ya existe
        if (post_type_exists($slug)) {
            wp_send_json_error(__('Este tipo de contenido ya existe.', 'seo-content-structure'));
        }

        // Verificar si está en la tabla personalizada
        $post_type_obj = $this->post_type_factory->get_post_type($slug);
        if ($post_type_obj) {
            wp_send_json_error(__('Este tipo de contenido ya está registrado.', 'seo-content-structure'));
        }

        // Todo bien
        wp_send_json_success(__('El slug es válido.', 'seo-content-structure'));
    }
}
