<?php

/**
 * Factory para crear tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage PostTypes
 */

namespace SEOContentStructure\PostTypes;

use SEOContentStructure\PostTypes\PostType;
use SEOContentStructure\Fields\FieldFactory;

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
        // Comentado para evitar CPT hardcodeado
        // if (class_exists('\\SEOContentStructure\\PostTypes\\ServicePostType')) {
        //     $this->register_service_post_type();
        // }

        // Cargar tipos de contenido personalizados desde la base de datos
        $this->load_custom_post_types();
    }

    /**
     * Registra el tipo de contenido para servicios
     */
    protected function register_service_post_type()
    {
        // Comprobar si ya existe este post type en la base de datos
        // para evitar duplicados
        if ($this->get_post_type_from_db('servicio')) {
            return;
        }

        $args = array(
            'labels' => array(
                'name'          => __('Servicios', 'seo-content-structure'),
                'singular_name' => __('Servicio', 'seo-content-structure'),
            ),
            'menu_icon' => 'dashicons-hammer',
            'has_archive' => true,
            'rewrite' => array('slug' => 'servicio'),
            'show_in_admin_bar' => true,
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
     * Obtiene información de un tipo de contenido de la base de datos
     *
     * @param string $post_type Nombre del tipo de contenido
     * @return array|false Datos del tipo de contenido o false si no existe
     */
    protected function get_post_type_from_db($post_type)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SEO Content Structure - Table $table_name does not exist");
            return false;
        }

        $post_type_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_type = %s",
                $post_type
            ),
            ARRAY_A
        );

        if (empty($post_type_data)) {
            return false;
        }

        return $post_type_data;
    }

    /**
     * Carga los tipos de contenido personalizados desde la base de datos
     */
    protected function load_custom_post_types()
    {
        // Utilize cache if available
        $cached_post_types = get_transient('scs_post_types_cache');
        if ($cached_post_types !== false) {
            $this->post_types = array_merge($this->post_types, $cached_post_types);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verify if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("Post types table doesn't exist: $table_name");
            return;
        }

        // Get all post types (both active and inactive for management purposes)
        $custom_post_types = $wpdb->get_results(
            "SELECT * FROM $table_name",
            ARRAY_A
        );

        if (empty($custom_post_types)) {
            return;
        }

        $loaded_post_types = array();

        // Register each post type
        foreach ($custom_post_types as $post_type_data) {
            try {
                $config = json_decode($post_type_data['config'], true);

                if (empty($config) || empty($post_type_data['post_type'])) {
                    error_log("Skipping post type with empty config or name");
                    continue;
                }

                $post_type_name = $post_type_data['post_type'];
                $args = isset($config['args']) ? $config['args'] : array();
                $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : array();
                $schema_type = isset($config['schema_type']) ? $config['schema_type'] : '';

                // Ensure required defaults are set
                $args = $this->ensure_post_type_defaults($args, $post_type_name);

                // Create post type instance (using generic)
                $post_type = new GenericPostType($post_type_name, $args, $taxonomies);

                // Set schema type if it exists
                if (!empty($schema_type)) {
                    $post_type->set_schema_type($schema_type);
                }

                $post_type->set_is_active(!empty($post_type_data['active']));

                // Register post type if active
                $loaded_post_types[$post_type_name] = $post_type;
            } catch (\Exception $e) {
                error_log("Error loading post type: " . $e->getMessage());
                error_log("Error trace: " . $e->getTraceAsString());
            }
        }

        // Merge with existing post types (prioritizing new ones)
        $this->post_types = array_merge($this->post_types, $loaded_post_types);

        // Store in cache for future loads
        set_transient('scs_post_types_cache', $loaded_post_types, HOUR_IN_SECONDS);
    }

    /**
     * Asegura que los argumentos del post type tengan valores por defecto necesarios
     *
     * @param array $args Argumentos originales
     * @param string $post_type_name Nombre del post type
     * @return array Argumentos con valores por defecto
     */
    private function ensure_post_type_defaults($args, $post_type_name)
    {
        $defaults = array(
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'rewrite' => array('slug' => $post_type_name),
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        );

        return wp_parse_args($args, $defaults);
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

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SEO Content Structure - Attempting to create post_types table");
            $this->create_post_types_table();
        }

        // Verificar que el post type sea válido
        if (!preg_match('/^[a-z0-9_\-]+$/', $data['post_type'])) {
            return new \WP_Error('invalid_post_type', __('El slug debe contener solo letras minúsculas, números, guiones y guiones bajos.', 'seo-content-structure'));
        }

        // Verificar que no sea un tipo de contenido nativo de WordPress
        $native_post_types = array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part');
        if (in_array($data['post_type'], $native_post_types)) {
            return new \WP_Error('reserved_post_type', __('No puedes usar un tipo de contenido reservado de WordPress.', 'seo-content-structure'));
        }

        // Verificar que es un nombre válido para un post type
        if (strlen($data['post_type']) < 1 || strlen($data['post_type']) > 20) {
            return new \WP_Error('invalid_post_type_length', __('El nombre del tipo de contenido debe tener entre 1 y 20 caracteres.', 'seo-content-structure'));
        }

        // Preparar la configuración
        $config = array(
            'args'       => isset($data['args']) ? $data['args'] : array(),
            'taxonomies' => isset($data['taxonomies']) ? $data['taxonomies'] : array(),
            'schema_type' => isset($data['schema_type']) ? $data['schema_type'] : '',
        );

        // Asegurar que las configuraciones esenciales estén presentes
        if (!isset($config['args']['labels']['singular_name']) || !isset($config['args']['labels']['name'])) {
            return new \WP_Error('missing_labels', __('Los nombres singular y plural son requeridos.', 'seo-content-structure'));
        }

        // Marcar como activo por defecto si no se especifica
        if (!isset($config['args']['active'])) {
            $config['args']['active'] = true;
        }

        // Asegurar que show_in_admin_bar está establecido a true
        $config['args']['show_in_admin_bar'] = true;

        // Asegurar que rewrite está configurado correctamente
        if (!isset($config['args']['rewrite']) || !is_array($config['args']['rewrite'])) {
            $config['args']['rewrite'] = array('slug' => $data['post_type']);
        }

        $save_data = array(
            'post_type' => $data['post_type'],
            'config'    => wp_json_encode($config),
            'active'    => isset($data['args']['active']) && $data['args']['active'] ? 1 : 0,
        );

        // Determinar si es una actualización o inserción
        $existing = $this->get_post_type_from_db($data['post_type']);

        try {
            if ($existing) {
                // Actualizar
                $result = $wpdb->update(
                    $table_name,
                    $save_data,
                    array('id' => $existing['id']),
                    array('%s', '%s', '%d'),
                    array('%d')
                );

                if ($result === false) {
                    error_log("Error updating post type in database: " . $wpdb->last_error);
                    return new \WP_Error('db_error', __('Error al actualizar el tipo de contenido.', 'seo-content-structure'));
                }

                $post_type_id = $existing['id'];
                error_log("Updated post type {$data['post_type']} with ID: $post_type_id");
            } else {
                // Insertar
                $result = $wpdb->insert(
                    $table_name,
                    $save_data,
                    array('%s', '%s', '%d')
                );

                if ($result === false) {
                    error_log("Error inserting post type in database: " . $wpdb->last_error);
                    return new \WP_Error('db_error', __('Error al insertar el tipo de contenido.', 'seo-content-structure'));
                }

                $post_type_id = $wpdb->insert_id;
                error_log("Inserted new post type {$data['post_type']} with ID: $post_type_id");
            }
        } catch (\Exception $e) {
            error_log("Exception while saving post type: " . $e->getMessage());
            return new \WP_Error('exception', __('Error al guardar el tipo de contenido: ', 'seo-content-structure') . $e->getMessage());
        }

        // Limpiar caché
        delete_transient('scs_post_types_cache');

        // Limpiar reglas de rewriting para el nuevo post type
        flush_rewrite_rules();

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

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SEO Content Structure - Table $table_name does not exist");
            return false;
        }

        try {
            $result = $wpdb->delete(
                $table_name,
                array('post_type' => $post_type),
                array('%s')
            );

            if ($result === false) {
                error_log("Error deleting post type: " . $wpdb->last_error);
                return false;
            }

            error_log("Successfully deleted post type: $post_type");
        } catch (\Exception $e) {
            error_log("Exception while deleting post type: " . $e->getMessage());
            return false;
        }

        // Limpiar caché
        delete_transient('scs_post_types_cache');

        // Limpiar reglas de rewriting
        flush_rewrite_rules();

        return true;
    }

    /**
     * Crea la tabla de tipos de contenido si no existe
     *
     * @return bool
     */
    protected function create_post_types_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verificar si la tabla ya existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            error_log("Table $table_name already exists");
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_type varchar(50) NOT NULL,
            config longtext NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_type (post_type)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);

        // Verificar que la tabla se creó correctamente
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("Failed to create table $table_name");
            error_log("dbDelta result: " . print_r($result, true));
            return false;
        }

        error_log("Successfully created table $table_name");
        return true;
    }
}
