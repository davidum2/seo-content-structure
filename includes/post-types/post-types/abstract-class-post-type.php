<?php

/**
 * Clase abstracta para tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage PostTypes
 */

namespace SEOContentStructure\PostTypes;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\Fields\Field;

/**
 * Clase abstracta para post types
 */
abstract class PostType implements Registrable
{
    /**
     * Nombre del post type
     *
     * @var string
     */
    protected $post_type;

    /**
     * Etiquetas para el post type
     *
     * @var array
     */
    protected $labels = array();

    /**
     * Argumentos para registrar el post type
     *
     * @var array
     */
    protected $args = array();

    /**
     * Taxonomías asociadas al post type
     *
     * @var array
     */
    protected $taxonomies = array();

    /**
     * Campos personalizados asociados al post type
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Tipo de schema JSON-LD asociado
     *
     * @var string
     */
    protected $schema_type = '';

    /**
     * Si el tipo de contenido está activo
     *
     * @var bool
     */
    protected $is_active = true;

    /**
     * Constructor
     *
     * @param string $post_type  Nombre del post type
     * @param array  $args       Argumentos adicionales
     * @param array  $taxonomies Taxonomías a registrar
     */
    public function __construct($post_type, $args = array(), $taxonomies = array())
    {
        $this->post_type = $post_type;
        $this->args = $args;
        $this->taxonomies = $taxonomies;

        // Establecer estado activo desde args si existe
        if (isset($args['active'])) {
            $this->is_active = (bool) $args['active'];
        }

        // Configuración predeterminada
        $this->set_default_args();
        $this->set_labels();
    }

    /**
     * Establece argumentos predeterminados para el post type
     */
    protected function set_default_args()
    {
        $defaults = array(
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,  // Asegurar que esto sea true
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-admin-post',
            'can_export'          => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'rewrite'             => array('slug' => $this->post_type), // Asegurar reescritura URL correcta
            'active'              => $this->is_active, // Incluir estado activo en args
        );

        $this->args = wp_parse_args($this->args, $defaults);

        // Asegurar que el rewrite tenga slug
        if (is_array($this->args['rewrite']) && !isset($this->args['rewrite']['slug'])) {
            $this->args['rewrite']['slug'] = $this->post_type;
        }
    }

    /**
     * Establece las etiquetas del post type
     */
    protected function set_labels()
    {
        $singular = ucfirst(str_replace('_', ' ', $this->post_type));
        $plural = $singular . 's';

        if (isset($this->args['singular'])) {
            $singular = $this->args['singular'];
            unset($this->args['singular']);
        }

        if (isset($this->args['plural'])) {
            $plural = $this->args['plural'];
            unset($this->args['plural']);
        }

        $default_labels = array(
            'name'                  => $plural,
            'singular_name'         => $singular,
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

        $this->labels = isset($this->args['labels']) ? wp_parse_args($this->args['labels'], $default_labels) : $default_labels;

        $this->args['labels'] = $this->labels;
    }

    /**
     * Establece si el tipo de contenido está activo
     *
     * @param bool $is_active Estado activo
     * @return self
     */
    public function set_is_active($is_active)
    {
        $this->is_active = (bool) $is_active;
        $this->args['active'] = $this->is_active;
        return $this;
    }

    /**
     * Verifica si el tipo de contenido está activo
     *
     * @return bool
     */
    public function is_active()
    {
        return $this->is_active;
    }

    /**
     * Obtiene el nombre del post type
     *
     * @return string
     */
    public function get_post_type()
    {
        return $this->post_type;
    }

    /**
     * Obtiene las etiquetas del post type
     *
     * @return array
     */
    public function get_labels()
    {
        return $this->labels;
    }

    /**
     * Obtiene los argumentos del post type
     *
     * @return array
     */
    public function get_args()
    {
        return $this->args;
    }

    /**
     * Obtiene las taxonomías asociadas
     *
     * @return array
     */
    public function get_taxonomies()
    {
        return $this->taxonomies;
    }

    /**
     * Establece los campos personalizados asociados al post type
     *
     * @param array $fields Array de objetos Field
     * @return self
     */
    public function set_fields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Añade un campo personalizado al post type
     *
     * @param Field $field Objeto de campo
     * @return self
     */
    public function add_field(Field $field)
    {
        $this->fields[$field->get_id()] = $field;
        return $this;
    }

    /**
     * Obtiene los campos personalizados
     *
     * @return array
     */
    public function get_fields()
    {
        return $this->fields;
    }

    /**
     * Obtiene un campo específico por su ID
     *
     * @param string $field_id ID del campo
     * @return Field|null
     */
    public function get_field($field_id)
    {
        return isset($this->fields[$field_id]) ? $this->fields[$field_id] : null;
    }

    /**
     * Establece el tipo de schema JSON-LD
     *
     * @param string $schema_type Tipo de schema
     * @return self
     */
    public function set_schema_type($schema_type)
    {
        $this->schema_type = $schema_type;
        return $this;
    }

    /**
     * Obtiene el tipo de schema JSON-LD
     *
     * @return string
     */
    public function get_schema_type()
    {
        return $this->schema_type;
    }

    /**
     * Registra el post type con WordPress
     */
    public function register_post_type()
    {
        // Solo registrar si está activo
        if (!$this->is_active) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Post type {$this->post_type} no se registró porque está inactivo");
            }
            return;
        }

        // Asegurar que tenemos un array de argumentos válido
        $args = $this->args;
        if (!isset($args['labels']) || !is_array($args['labels'])) {
            $this->set_labels();
            $args = $this->args;
        }

        register_post_type($this->post_type, $args);

        // Verificar registro
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (post_type_exists($this->post_type)) {
                error_log("Post type {$this->post_type} registrado correctamente");
            } else {
                error_log("ERROR: Post type {$this->post_type} NO se registró correctamente");
            }
        }
    }

    /**
     * Registra las taxonomías asociadas con WordPress
     */
    public function register_taxonomies()
    {
        // Solo registrar si el post type está activo
        if (!$this->is_active) {
            return;
        }

        foreach ($this->taxonomies as $taxonomy => $args) {
            if (! taxonomy_exists($taxonomy)) {
                $labels = isset($args['labels']) ? $args['labels'] : array();
                $tax_args = isset($args['args']) ? $args['args'] : array();

                $singular = isset($args['singular']) ? $args['singular'] : ucfirst(str_replace('_', ' ', $taxonomy));
                $plural = isset($args['plural']) ? $args['plural'] : $singular . 's';

                $default_labels = array(
                    'name'                       => $plural,
                    'singular_name'              => $singular,
                    'menu_name'                  => $plural,
                    'all_items'                  => sprintf(__('Todos los %s', 'seo-content-structure'), $plural),
                    'parent_item'                => sprintf(__('%s Superior', 'seo-content-structure'), $singular),
                    'parent_item_colon'          => sprintf(__('%s Superior:', 'seo-content-structure'), $singular),
                    'new_item_name'              => sprintf(__('Nuevo Nombre de %s', 'seo-content-structure'), $singular),
                    'add_new_item'               => sprintf(__('Añadir Nuevo %s', 'seo-content-structure'), $singular),
                    'edit_item'                  => sprintf(__('Editar %s', 'seo-content-structure'), $singular),
                    'update_item'                => sprintf(__('Actualizar %s', 'seo-content-structure'), $singular),
                    'view_item'                  => sprintf(__('Ver %s', 'seo-content-structure'), $singular),
                    'separate_items_with_commas' => sprintf(__('Separar %s con comas', 'seo-content-structure'), $plural),
                    'add_or_remove_items'        => sprintf(__('Añadir o quitar %s', 'seo-content-structure'), $plural),
                    'choose_from_most_used'      => sprintf(__('Elegir de los %s más usados', 'seo-content-structure'), $plural),
                    'popular_items'              => sprintf(__('%s Populares', 'seo-content-structure'), $plural),
                    'search_items'               => sprintf(__('Buscar %s', 'seo-content-structure'), $plural),
                    'not_found'                  => __('No encontrado', 'seo-content-structure'),
                    'no_terms'                   => sprintf(__('No hay %s', 'seo-content-structure'), $plural),
                    'items_list'                 => sprintf(__('Lista de %s', 'seo-content-structure'), $plural),
                    'items_list_navigation'      => sprintf(__('Navegación de lista de %s', 'seo-content-structure'), $plural),
                );

                $labels = wp_parse_args($labels, $default_labels);

                $default_args = array(
                    'labels'            => $labels,
                    'hierarchical'      => true,
                    'public'            => true,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'show_in_nav_menus' => true,
                    'show_tagcloud'     => true,
                    'show_in_rest'      => true,
                );

                $tax_args = wp_parse_args($tax_args, $default_args);

                register_taxonomy($taxonomy, $this->post_type, $tax_args);
            } else {
                // Si la taxonomía ya existe, solo registrar para este post type
                register_taxonomy_for_object_type($taxonomy, $this->post_type);
            }
        }
    }

    /**
     * Registra meta boxes para los campos personalizados
     */
    public function register_meta_boxes()
    {
        // No registrar meta boxes si el post type está inactivo
        if (!$this->is_active || empty($this->fields)) {
            return;
        }

        // Agrupar campos por grupo
        $field_groups = array();

        foreach ($this->fields as $field) {
            $group = $field->get_group() ?: 'default';

            if (! isset($field_groups[$group])) {
                $field_groups[$group] = array();
            }

            $field_groups[$group][] = $field;
        }

        // Registrar una meta box por cada grupo
        foreach ($field_groups as $group => $fields) {
            $group_id = sanitize_key($group);
            $group_label = 'default' === $group ? __('Campos Personalizados', 'seo-content-structure') : $group;

            add_meta_box(
                "scs_{$this->post_type}_{$group_id}_meta_box",
                $group_label,
                array($this, 'render_meta_box'),
                $this->post_type,
                'normal',
                'high',
                array('fields' => $fields, 'group' => $group)
            );
        }
    }

    /**
     * Renderiza el contenido de un meta box
     *
     * @param WP_Post $post    Objeto post actual
     * @param array   $metabox Información de la meta box
     */
    public function render_meta_box($post, $metabox)
    {
        // Verificar que hay campos
        if (! isset($metabox['args']['fields']) || empty($metabox['args']['fields'])) {
            echo '<p>' . esc_html__('No hay campos definidos para este grupo.', 'seo-content-structure') . '</p>';
            return;
        }

        // Nonce para seguridad
        wp_nonce_field("scs_{$this->post_type}_meta_box", "scs_{$this->post_type}_meta_box_nonce");

        // Mostrar los campos
        echo '<div class="scs-fields-container">';

        foreach ($metabox['args']['fields'] as $field) {
            // Obtener el valor actual
            $meta_value = get_post_meta($post->ID, $field->get_name(), true);
            if ('' !== $meta_value) {
                $field->set_value($meta_value);
            }

            // Renderizar el campo
            echo $field->render_admin();
        }

        echo '</div>';
    }

    /**
     * Guarda los valores de los campos personalizados
     *
     * @param int $post_id ID del post
     */
    public function save_meta_values($post_id)
    {
        // No procesar si el post type está inactivo
        if (!$this->is_active) {
            return;
        }

        // Verificar nonce
        if (
            ! isset($_POST["scs_{$this->post_type}_meta_box_nonce"]) ||
            ! wp_verify_nonce($_POST["scs_{$this->post_type}_meta_box_nonce"], "scs_{$this->post_type}_meta_box")
        ) {
            return;
        }

        // Verificar si es guardado automático
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        $post_type_object = get_post_type_object($this->post_type);
        if (! current_user_can($post_type_object->cap->edit_post, $post_id)) {
            return;
        }

        // Guardar cada campo
        foreach ($this->fields as $field) {
            $field_name = $field->get_name();

            // Si el campo existe en el formulario
            if (isset($_POST[$field_name])) {
                $value = $_POST[$field_name];

                // Sanitizar el valor
                $sanitized_value = $field->sanitize($value);

                // Actualizar el valor en la base de datos
                update_post_meta($post_id, $field_name, $sanitized_value);
            }
        }
    }

    /**
     * Registra el schema JSON-LD para este post type
     */
    public function register_schema()
    {
        // No registrar schema si el post type está inactivo
        if (!$this->is_active || empty($this->schema_type)) {
            return;
        }

        add_action('wp_head', array($this, 'print_schema'));
    }

    /**
     * Imprime el schema JSON-LD en el head
     */
    public function print_schema()
    {
        // Solo imprimir en páginas de detalle de este post type
        if (! is_singular($this->post_type)) {
            return;
        }

        $post_id = get_the_ID();

        // Obtener y imprimir el schema
        $schema_builder = new \SEOContentStructure\Utilities\JsonLdBuilder($this->schema_type);

        // Datos básicos
        $schema_builder->set_property('name', get_the_title($post_id));
        $schema_builder->set_property('description', wp_strip_all_tags(get_the_content()));
        $schema_builder->set_property('url', get_permalink($post_id));

        // Imagen destacada
        if (has_post_thumbnail($post_id)) {
            $schema_builder->add_image(get_post_thumbnail_id($post_id));
        }

        // Mapear valores de campos al schema
        foreach ($this->fields as $field) {
            $schema_property = $field->get_schema_property();

            if (! empty($schema_property)) {
                $value = get_post_meta($post_id, $field->get_name(), true);

                if (! empty($value)) {
                    $schema_builder->set_property($schema_property, $value);
                }
            }
        }

        // Filtro para modificar el schema antes de imprimirlo
        $schema_builder = apply_filters("scs_schema_{$this->post_type}", $schema_builder, $post_id);

        // Imprimir el schema
        $schema_builder->print_script(true);
    }

    /**
     * Registra los campos personalizados con la REST API
     */
    public function register_rest_fields()
    {
        // No registrar campos REST si el post type está inactivo
        if (!$this->is_active || empty($this->fields)) {
            return;
        }

        foreach ($this->fields as $field) {
            $field_name = $field->get_name();

            register_rest_field($this->post_type, $field_name, array(
                'get_callback'    => function ($post) use ($field_name) {
                    return get_post_meta($post['id'], $field_name, true);
                },
                'update_callback' => function ($value, $post) use ($field, $field_name) {
                    $sanitized_value = $field->sanitize($value);
                    return update_post_meta($post->ID, $field_name, $sanitized_value);
                },
                'schema'          => array(
                    'description' => $field->get_label(),
                    'type'        => $this->get_rest_schema_type($field->get_type()),
                    'context'     => array('view', 'edit'),
                ),
            ));
        }
    }

    /**
     * Convierte el tipo de campo a tipo de schema REST
     *
     * @param string $field_type Tipo de campo
     * @return string
     */
    protected function get_rest_schema_type($field_type)
    {
        $map = array(
            'text'     => 'string',
            'textarea' => 'string',
            'number'   => 'number',
            'email'    => 'string',
            'url'      => 'string',
            'image'    => 'integer',
            'repeater' => 'array',
            'select'   => 'string',
            'checkbox' => 'boolean',
            'radio'    => 'string',
            'date'     => 'string',
            'time'     => 'string',
            'datetime' => 'string',
            'color'    => 'string',
        );

        return isset($map[$field_type]) ? $map[$field_type] : 'string';
    }

    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // No registrar hooks si el post type está inactivo
        if (!$this->is_active) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("No se registran hooks para {$this->post_type} porque está inactivo");
            }
            return;
        }

        // Registrar el post type
        $loader->add_action('init', $this, 'register_post_type');

        // Registrar taxonomías
        if (! empty($this->taxonomies)) {
            $loader->add_action('init', $this, 'register_taxonomies');
        }

        // Registrar meta boxes para campos personalizados
        if (! empty($this->fields)) {
            $loader->add_action('add_meta_boxes', $this, 'register_meta_boxes');
            $loader->add_action('save_post_' . $this->post_type, $this, 'save_meta_values');
        }

        // Registrar schema JSON-LD
        if (! empty($this->schema_type)) {
            $loader->add_action('init', $this, 'register_schema');
        }

        // Registrar campos con la REST API
        if (! empty($this->fields)) {
            $loader->add_action('rest_api_init', $this, 'register_rest_fields');
        }
    }
}
