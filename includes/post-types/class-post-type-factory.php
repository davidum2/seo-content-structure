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
        error_log('SCS_TRACE: PostTypeFactory - Iniciando constructor');
        $this->register_default_post_types();
        error_log('SCS_TRACE: PostTypeFactory - Constructor completado, post types cargados: ' . count($this->post_types));
    }

    /**
     * Registra los tipos de contenido predeterminados
     */
    protected function register_default_post_types()
    {
        error_log('SCS_TRACE: PostTypeFactory - Registrando tipos de contenido predeterminados');
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
        error_log("SCS_TRACE: PostTypeFactory - Intentando obtener post type '$post_type' de la base de datos");

        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SCS_ERROR: PostTypeFactory - La tabla '$table_name' no existe");
            return false;
        }

        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE post_type = %s LIMIT 1", $post_type);
        error_log("SCS_TRACE: PostTypeFactory - SQL: $sql");

        $result = $wpdb->get_row($sql, ARRAY_A);

        if ($result) {
            error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' encontrado en BD (ID: {$result['id']})");
            return $result;
        } else {
            error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' NO encontrado en BD. Último error SQL: " . $wpdb->last_error);
            return false;
        }
    }

    /**
     * Carga los tipos de contenido personalizados desde la base de datos
     */
    protected function load_custom_post_types()
    {
        error_log('SCS_TRACE: PostTypeFactory - Iniciando carga de post types desde la base de datos');

        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log('SCS_ERROR: PostTypeFactory - La tabla de post types no existe, intentando crearla');
            $this->create_post_types_table();
            return;
        }

        $sql = "SELECT * FROM $table_name WHERE active = 1";
        $results = $wpdb->get_results($sql, ARRAY_A);

        error_log('SCS_TRACE: PostTypeFactory - Post types encontrados en BD: ' . count($results) . ' registros activos');

        if (!$results) {
            error_log('SCS_TRACE: PostTypeFactory - No se encontraron post types en la BD o hubo un error: ' . $wpdb->last_error);
            return;
        }

        foreach ($results as $row) {
            try {
                error_log("SCS_TRACE: PostTypeFactory - Procesando post type '{$row['post_type']}' (ID: {$row['id']})");

                $config = json_decode($row['config'], true);
                if (!$config || !is_array($config)) {
                    error_log("SCS_ERROR: PostTypeFactory - JSON inválido en config del post type '{$row['post_type']}': " . $row['config']);
                    continue;
                }

                // Obtener argumentos, taxonomías y tipo de esquema
                $args = isset($config['args']) ? $config['args'] : array();
                $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : array();
                $schema_type = isset($config['schema_type']) ? $config['schema_type'] : '';

                // Asegurar que sea activo según campo de BD (prioridad)
                $args['active'] = (bool) $row['active'];

                // Registrar el tipo de contenido
                $post_type = $this->create_post_type_instance($row['post_type'], $args, $taxonomies, $schema_type);
                if ($post_type) {
                    $this->post_types[$row['post_type']] = $post_type;
                    error_log("SCS_TRACE: PostTypeFactory - Post type '{$row['post_type']}' registrado correctamente");
                }
            } catch (\Throwable $e) {
                error_log("SCS_ERROR: PostTypeFactory - Error al cargar post type '{$row['post_type']}': " . $e->getMessage());
            }
        }

        error_log('SCS_TRACE: PostTypeFactory - Finalizada carga de post types, total cargados: ' . count($this->post_types));
    }

    /**
     * Crea la tabla de tipos de contenido si no existe
     */
    public function create_post_types_table()
    {
        error_log('SCS_TRACE: PostTypeFactory - Intentando crear tabla de post types');

        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_type varchar(50) NOT NULL,
            config longtext NOT NULL,
            active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_type (post_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);

        error_log('SCS_TRACE: PostTypeFactory - Resultado de crear tabla: ' . print_r($result, true));
    }

    /**
     * Crea una instancia de tipo de contenido basado en su configuración
     *
     * @param string $post_type_name Nombre del post type
     * @param array $args Argumentos del post type
     * @param array $taxonomies Taxonomías del post type
     * @param string $schema_type Tipo de esquema
     * @return PostType Instancia del post type
     */
    protected function create_post_type_instance($post_type_name, $args, $taxonomies, $schema_type = '')
    {
        error_log("SCS_TRACE: PostTypeFactory - Creando instancia para post type '$post_type_name'");

        // Asegurar argumentos correctos
        $args = $this->ensure_post_type_defaults($args, $post_type_name);

        // Crear instancia de GenericPostType
        try {
            $post_type = new GenericPostType($post_type_name, $args, $taxonomies);

            // Establecer tipo de esquema si existe
            if (!empty($schema_type)) {
                $post_type->set_schema_type($schema_type);
            }

            error_log("SCS_TRACE: PostTypeFactory - Instancia para '$post_type_name' creada correctamente");
            return $post_type;
        } catch (\Throwable $e) {
            error_log("SCS_ERROR: PostTypeFactory - Error al crear instancia de '$post_type_name': " . $e->getMessage());
            return null;
        }
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
        error_log("SCS_TRACE: PostTypeFactory - Registrando post type '{$post_type->get_post_type()}'");
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
        error_log('SCS_TRACE: PostTypeFactory - Obteniendo todos los post types registrados, total: ' . count($this->post_types));
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
        error_log("SCS_TRACE: PostTypeFactory - Intentando obtener post type '$post_type'");

        // 1. Verificar en la caché interna
        if (isset($this->post_types[$post_type])) {
            error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' encontrado en memoria");
            return $this->post_types[$post_type];
        }

        error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' NO encontrado en memoria, buscando en BD");

        // 2. Intentar cargar desde la base de datos
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'scs_post_types';

            // Verificar si la tabla existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                error_log("SCS_TRACE: PostTypeFactory - Tabla $table_name no existe, no se puede buscar post type");
                return null;
            }

            // Buscar el post type en la BD
            $query = $wpdb->prepare("SELECT * FROM $table_name WHERE post_type = %s LIMIT 1", $post_type);
            error_log("SCS_TRACE: PostTypeFactory - Ejecutando query: $query");

            $result = $wpdb->get_row($query, ARRAY_A);
            if (!$result) {
                error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' no encontrado en BD");
                return null;
            }

            error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' encontrado en BD, cargando");

            // Deserializar configuración
            $config = maybe_unserialize($result['config']);

            // Si es un string, intentar JSON
            if (is_string($config)) {
                $json_config = json_decode($config, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config = $json_config;
                } else {
                    // Intentar deserializar PHP
                    $config = @unserialize($config);
                    if ($config === false && $config !== 'b:0;') {
                        error_log("SCS_ERROR: PostTypeFactory - Error al deserializar config para $post_type");
                        return null;
                    }
                }
            }

            // Verificar estructura mínima
            if (!is_array($config)) {
                error_log("SCS_ERROR: PostTypeFactory - Configuración no es un array para $post_type");
                return null;
            }

            // Obtener args, taxonomies y schema
            $args = isset($config['args']) ? $config['args'] : [];
            $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : [];
            $schema_type = isset($config['schema_type']) ? $config['schema_type'] : '';

            // Manejar caso de args como string
            if (is_string($args)) {
                $args_json = json_decode($args, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $args = $args_json;
                } else {
                    $args = @unserialize($args);
                    if ($args === false && $args !== 'b:0;') {
                        $args = [];
                    }
                }
            }

            // Crear instancia y guardar en memoria
            $post_type_obj = $this->create_post_type_instance($post_type, $args, $taxonomies, $schema_type);
            if ($post_type_obj) {
                $this->post_types[$post_type] = $post_type_obj;
                error_log("SCS_TRACE: PostTypeFactory - Post type '$post_type' cargado desde BD y añadido a memoria");
                return $post_type_obj;
            } else {
                error_log("SCS_ERROR: PostTypeFactory - No se pudo crear instancia para post type '$post_type'");
            }
        } catch (\Throwable $e) {
            error_log("SCS_ERROR: PostTypeFactory - Error al cargar post type '$post_type' desde BD: " . $e->getMessage());
        }

        error_log("SCS_ERROR: PostTypeFactory - No se pudo encontrar post type '$post_type'");
        return null;
    }

    /**
     * Guarda un tipo de contenido personalizado en la base de datos
     *
     * @param array $data Datos del tipo de contenido
     * @return int|WP_Error ID del tipo de contenido o error
     */
    public function save_post_type($data)
    {
        error_log("SCS_TRACE: PostTypeFactory - Iniciando guardado de post type: " . (isset($data['post_type']) ? $data['post_type'] : 'undefined'));

        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        // Validar datos básicos
        if (empty($data['post_type'])) {
            error_log("SCS_ERROR: PostTypeFactory - Intento de guardar post type sin slug");
            return new \WP_Error('empty_post_type', __('El slug del tipo de contenido no puede estar vacío.', 'seo-content-structure'));
        }

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SCS_ERROR: PostTypeFactory - Tabla de post types no existe, creándola");
            $this->create_post_types_table();
        }

        // Verificar que el post type sea válido
        if (!preg_match('/^[a-z0-9_\-]+$/', $data['post_type'])) {
            error_log("SCS_ERROR: PostTypeFactory - Slug de post type inválido: {$data['post_type']}");
            return new \WP_Error('invalid_post_type', __('El slug debe contener solo letras minúsculas, números, guiones y guiones bajos.', 'seo-content-structure'));
        }

        // Verificar que no sea un tipo de contenido nativo de WordPress
        $native_post_types = array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part');
        if (in_array($data['post_type'], $native_post_types)) {
            error_log("SCS_ERROR: PostTypeFactory - Intento de usar post type reservado: {$data['post_type']}");
            return new \WP_Error('reserved_post_type', __('No puedes usar un tipo de contenido reservado de WordPress.', 'seo-content-structure'));
        }

        // Verificar que es un nombre válido para un post type
        if (strlen($data['post_type']) < 1 || strlen($data['post_type']) > 20) {
            error_log("SCS_ERROR: PostTypeFactory - Longitud de slug inválida: " . strlen($data['post_type']));
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
            error_log("SCS_ERROR: PostTypeFactory - Faltan labels requeridos para el post type");
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

        error_log("SCS_TRACE: PostTypeFactory - Datos preparados para guardar: post_type={$save_data['post_type']}, active={$save_data['active']}");

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
     * Carga los tipos de contenido desde la base de datos
     */
    private function load_post_types_from_db()
    {
        error_log('SCS_TRACE: PostTypeFactory - Iniciando carga de post types desde BD');

        // Verificar si la tabla existe primero
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("SCS_TRACE: PostTypeFactory - La tabla {$table_name} no existe, creándola");
            $this->create_post_types_table();
            return; // No hay datos para cargar aún
        }

        // Obtener todos los tipos de contenido activos
        try {
            $post_types = $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1", ARRAY_A);
            error_log('SCS_TRACE: PostTypeFactory - Registros encontrados en BD: ' . count($post_types ?: []));

            if (empty($post_types)) {
                error_log('SCS_TRACE: PostTypeFactory - No se encontraron post types en la base de datos');
                return;
            }
        } catch (\Throwable $e) {
            error_log('SCS_ERROR: PostTypeFactory - Error al consultar la base de datos: ' . $e->getMessage());
            return;
        }

        // Recorrer tipos de contenido
        foreach ($post_types as $row) {
            try {
                error_log("SCS_TRACE: PostTypeFactory - Procesando post type desde BD: {$row['post_type']}");

                // Deserializar la configuración
                $config = maybe_unserialize($row['config']);

                // Si config es un string, intentar decodificar como JSON
                if (is_string($config)) {
                    $json_config = json_decode($config, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $config = $json_config;
                        error_log("SCS_TRACE: PostTypeFactory - Config deserializado correctamente como JSON");
                    } else {
                        error_log("SCS_ERROR: PostTypeFactory - Error al decodificar JSON: " . json_last_error_msg());
                        // Intentar unserialize de PHP como fallback
                        $config = @unserialize($config);
                        if ($config === false && $config !== 'b:0;') {
                            error_log("SCS_ERROR: PostTypeFactory - Error al deserializar config para {$row['post_type']}");
                            continue; // Saltar este post type
                        }
                    }
                }

                // Verificar estructura mínima
                if (!is_array($config)) {
                    error_log("SCS_ERROR: PostTypeFactory - Configuración no es un array para {$row['post_type']}");
                    continue;
                }

                // Obtener los argumentos del post type
                $args = isset($config['args']) ? $config['args'] : [];

                // Manejar caso de args como string (posible error de serialización)
                if (is_string($args)) {
                    $args_json = json_decode($args, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $args = $args_json;
                    } else {
                        $args = @unserialize($args);
                        if ($args === false && $args !== 'b:0;') {
                            error_log("SCS_ERROR: PostTypeFactory - Error al deserializar args para {$row['post_type']}");
                            $args = []; // Usar array vacío como fallback
                        }
                    }
                }

                // Asegurar que args sea un array
                if (!is_array($args)) {
                    error_log("SCS_ERROR: PostTypeFactory - Args no es un array para {$row['post_type']}, reiniciando");
                    $args = [];
                }

                // Obtener taxonomías
                $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : [];

                // Lo mismo para taxonomías
                if (is_string($taxonomies)) {
                    $taxonomies_json = json_decode($taxonomies, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $taxonomies = $taxonomies_json;
                    } else {
                        $taxonomies = @unserialize($taxonomies);
                        if ($taxonomies === false && $taxonomies !== 'b:0;') {
                            $taxonomies = [];
                        }
                    }
                }

                // Asegurar array
                if (!is_array($taxonomies)) {
                    $taxonomies = [];
                }

                // Obtener tipo de schema
                $schema_type = isset($config['schema_type']) ? $config['schema_type'] : '';

                // Asegurar que sea activo según campo de BD (prioridad)
                $args['active'] = isset($row['active']) ? (bool) $row['active'] : true;

                // Registrar el tipo de contenido si todo está bien
                if (!empty($row['post_type'])) {
                    $post_type = $this->create_post_type_instance($row['post_type'], $args, $taxonomies, $schema_type);
                    if ($post_type) {
                        $this->post_types[$row['post_type']] = $post_type;
                        error_log("SCS_TRACE: PostTypeFactory - Post type '{$row['post_type']}' registrado correctamente");
                    } else {
                        error_log("SCS_ERROR: PostTypeFactory - No se pudo crear instancia para '{$row['post_type']}'");
                    }
                } else {
                    error_log("SCS_ERROR: PostTypeFactory - Nombre de post type vacío, saltando registro");
                }
            } catch (\Throwable $e) {
                error_log("SCS_ERROR: PostTypeFactory - Error al cargar post type: " . $e->getMessage());
                error_log("SCS_ERROR: PostTypeFactory - Archivo: " . $e->getFile() . " línea " . $e->getLine());
            }
        }

        error_log('SCS_TRACE: PostTypeFactory - Finalizada carga de post types, total cargados: ' . count($this->post_types));
    }
}
