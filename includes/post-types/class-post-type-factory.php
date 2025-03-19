<?php

/**
 * Factory para crear tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage PostTypes
 */

namespace SEOContentStructure\PostTypes;

use SEOContentStructure\PostTypes\PostType;

/**
 * Clase para crear instancias de tipos de contenido
 */
class PostTypeFactory
{
    /**
     * Tipos de contenido registrados
     *
     * @var array
     */
    protected $post_types = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_default_post_types();
    }

    /**
     * Registra los tipos de contenido predeterminados
     */
    protected function register_default_post_types()
    {
        // Registrar el tipo de servicio
        $this->register_service_post_type();

        // Cargar tipos de contenido personalizados desde la base de datos
        $this->load_custom_post_types();
    }

    /**
     * Registra el tipo de contenido para servicios
     */
    protected function register_service_post_type()
    {
        $args = array(
            'labels' => array(
                'name'          => __('Servicios', 'seo-content-structure'),
                'singular_name' => __('Servicio', 'seo-content-structure'),
            ),
            'menu_icon' => 'dashicons-hammer',
        );

        $taxonomies = array(
            'servicio_categoria' => array(
                'singular' => __('Categoría', 'seo-content-structure'),
                'plural'   => __('Categorías', 'seo-content-structure'),
                'args'     => array(
                    'hierarchical' => true,
                ),
            ),
            'servicio_etiqueta' => array(
                'singular' => __('Etiqueta', 'seo-content-structure'),
                'plural'   => __('Etiquetas', 'seo-content-structure'),
                'args'     => array(
                    'hierarchical' => false,
                ),
            ),
        );

        $service = new ServicePostType('servicio', $args, $taxonomies);
        $this->post_types['servicio'] = $service;
    }

    /**
     * Carga los tipos de contenido personalizados desde la base de datos
     */
    protected function load_custom_post_types()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }

        // Obtener todos los tipos de contenido activos
        $custom_post_types = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE active = 1",
            ARRAY_A
        );

        if (empty($custom_post_types)) {
            return;
        }

        // Registrar cada tipo de contenido
        foreach ($custom_post_types as $post_type_data) {
            $config = json_decode($post_type_data['config'], true);

            if (empty($config) || empty($post_type_data['post_type'])) {
                continue;
            }

            $post_type_name = $post_type_data['post_type'];
            $args = isset($config['args']) ? $config['args'] : array();
            $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : array();

            // Crear instancia de post type (usando genérico por ahora)
            $post_type = new GenericPostType($post_type_name, $args, $taxonomies);

            // Establecer campos si existen
            if (isset($config['fields']) && is_array($config['fields'])) {
                // Crear instancias de campos
                $field_factory = new \SEOContentStructure\Fields\FieldFactory();
                $fields = $field_factory->create_fields($config['fields']);
                $post_type->set_fields($fields);
            }

            // Establecer schema type si existe
            if (isset($config['schema_type'])) {
                $post_type->set_schema_type($config['schema_type']);
            }

            // Registrar el post type
            $this->post_types[$post_type_name] = $post_type;
        }
    }

    /**
     * Registra un nuevo tipo de contenido
     *
     * @param PostType $post_type Instancia del tipo de contenido
     * @return self
     */
    public function register_post_type(PostType $post_type)
    {
        $this->post_types[$post_type->get_post_type()] = $post_type;
        return $this;
    }

    /**
     * Obtiene todos los tipos de contenido registrados
     *
     * @return array
     */
    public function get_registered_post_types()
    {
        return $this->post_types;
    }

    /**
     * Obtiene un tipo de contenido específico por su nombre
     *
     * @param string $post_type Nombre del tipo de contenido
     * @return PostType|null
     */
    public function get_post_type($post_type)
    {
        return isset($this->post_types[$post_type]) ? $this->post_types[$post_type] : null;
    }

    /**
     * Guarda un tipo de contenido personalizado en la base de datos
     *
     * @param array $data Datos del tipo de contenido
     * @return int|WP_Error ID del tipo de contenido o error
     */
    public function save_post_type($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Validar datos básicos
        if (empty($data['post_type'])) {
            return new \WP_Error('empty_post_type', __('El slug del tipo de contenido no puede estar vacío.', 'seo-content-structure'));
        }

        // Verificar que el post type sea válido
        if (!preg_match('/^[a-z0-9_\-]+$/', $data['post_type'])) {
            return new \WP_Error('invalid_post_type', __('El slug debe contener solo letras minúsculas, números, guiones y guiones bajos.', 'seo-content-structure'));
        }

        // Preparar datos para guardar
        $post_type_name = sanitize_key($data['post_type']);
        $config = array(
            'args'       => isset($data['args']) ? $data['args'] : array(),
            'taxonomies' => isset($data['taxonomies']) ? $data['taxonomies'] : array(),
            'fields'     => isset($data['fields']) ? $data['fields'] : array(),
            'schema_type' => isset($data['schema_type']) ? sanitize_text_field($data['schema_type']) : '',
        );

        $save_data = array(
            'post_type' => $post_type_name,
            'config'    => wp_json_encode($config),
            'active'    => isset($data['active']) ? 1 : 0,
        );

        // Determinar si es una actualización o inserción
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_type = %s",
                $post_type_name
            )
        );

        if ($existing) {
            // Actualizar
            $result = $wpdb->update(
                $table_name,
                $save_data,
                array('id' => $existing),
                array('%s', '%s', '%d'),
                array('%d')
            );

            if ($result === false) {
                return new \WP_Error('db_error', __('Error al actualizar el tipo de contenido.', 'seo-content-structure'));
            }

            $post_type_id = $existing;
        } else {
            // Insertar
            $result = $wpdb->insert(
                $table_name,
                $save_data,
                array('%s', '%s', '%d')
            );

            if ($result === false) {
                return new \WP_Error('db_error', __('Error al insertar el tipo de contenido.', 'seo-content-structure'));
            }

            $post_type_id = $wpdb->insert_id;
        }

        // Limpiar caché
        delete_transient('scs_post_types_cache');

        return $post_type_id;
    }

    /**
     * Elimina un tipo de contenido personalizado de la base de datos
     *
     * @param string $post_type Nombre del tipo de contenido
     * @return bool
     */
    public function delete_post_type($post_type)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        $result = $wpdb->delete(
            $table_name,
            array('post_type' => $post_type),
            array('%s')
        );

        // Limpiar caché
        delete_transient('scs_post_types_cache');

        return $result !== false;
    }
}
