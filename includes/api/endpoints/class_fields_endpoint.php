<?php

/**
 * Endpoint para campos personalizados
 *
 * @package SEOContentStructure
 * @subpackage API\Endpoints
 */

namespace SEOContentStructure\API\Endpoints;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\API\RestController;
use SEOContentStructure\Admin\FieldGroupController;
use SEOContentStructure\Fields\FieldFactory;

/**
 * Clase que implementa el endpoint para campos personalizados
 */
class FieldsEndpoint implements Registrable
{
    /**
     * Endpoint base
     *
     * @var string
     */
    protected $endpoint_base = 'fields';

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
        $loader->add_action('rest_api_init', $this, 'register_routes');
    }

    /**
     * Registra las rutas del endpoint
     */
    public function register_routes()
    {
        $namespace = RestController::get_api_namespace();
        $base = $this->endpoint_base;

        // Ruta para obtener todos los campos
        register_rest_route($namespace, '/' . $base, array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_fields'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
        ));

        // Ruta para obtener un campo específico
        register_rest_route($namespace, '/' . $base . '/(?P<id>[\w-]+)', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_field'),
            'permission_callback' => array($this, 'get_field_permissions_check'),
            'args'     => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    }
                ),
            ),
        ));

        // Ruta para obtener campos de un grupo
        register_rest_route($namespace, '/field-groups/(?P<group_id>[\d]+)/fields', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_group_fields'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
            'args'     => array(
                'group_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        // Ruta para obtener campos de un post type
        register_rest_route($namespace, '/post-types/(?P<post_type>[\w-]+)/fields', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_post_type_fields'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
            'args'     => array(
                'post_type' => array(
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    }
                ),
            ),
        ));

        // Ruta para obtener valores de campos para un post
        register_rest_route($namespace, '/posts/(?P<post_id>[\d]+)/fields', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_post_field_values'),
            'permission_callback' => array($this, 'get_post_field_values_permissions_check'),
            'args'     => array(
                'post_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Verifica permisos para obtener campos
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool
     */
    public function get_fields_permissions_check($request)
    {
        return current_user_can('edit_posts');
    }

    /**
     * Verifica permisos para obtener un campo específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool
     */
    public function get_field_permissions_check($request)
    {
        return current_user_can('edit_posts');
    }

    /**
     * Verifica permisos para obtener valores de campos de un post
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool
     */
    public function get_post_field_values_permissions_check($request)
    {
        $post_id = $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return false;
        }

        return current_user_can('edit_post', $post_id);
    }

    /**
     * Obtiene todos los campos
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response
     */
    public function get_fields($request)
    {
        $field_controller = new FieldGroupController();
        $field_groups = $field_controller->get_field_groups();

        $fields = array();

        foreach ($field_groups as $group) {
            if (isset($group['fields']) && is_array($group['fields'])) {
                foreach ($group['fields'] as $field) {
                    $fields[] = $field;
                }
            }
        }

        return rest_ensure_response($fields);
    }

    /**
     * Obtiene un campo específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_field($request)
    {
        $field_id = $request['id'];
        $field_controller = new FieldGroupController();
        $field_groups = $field_controller->get_field_groups();

        foreach ($field_groups as $group) {
            if (isset($group['fields']) && is_array($group['fields'])) {
                foreach ($group['fields'] as $field) {
                    if ($field['id'] === $field_id) {
                        return rest_ensure_response($field);
                    }
                }
            }
        }

        return new \WP_Error(
            'field_not_found',
            __('Campo no encontrado.', 'seo-content-structure'),
            array('status' => 404)
        );
    }

    /**
     * Obtiene campos de un grupo específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_group_fields($request)
    {
        $group_id = $request['group_id'];
        $field_controller = new FieldGroupController();
        $field_group = $field_controller->get_field_group($group_id);

        if (!$field_group) {
            return new \WP_Error(
                'group_not_found',
                __('Grupo de campos no encontrado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        if (!isset($field_group['fields']) || !is_array($field_group['fields'])) {
            return rest_ensure_response(array());
        }

        return rest_ensure_response($field_group['fields']);
    }

    /**
     * Obtiene campos de un post type específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_post_type_fields($request)
    {
        $post_type = $request['post_type'];
        $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $post_type_obj = $post_type_factory->get_post_type($post_type);

        if (!$post_type_obj) {
            return new \WP_Error(
                'post_type_not_found',
                __('Tipo de contenido no encontrado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        $fields = $post_type_obj->get_fields();
        $field_data = array();

        foreach ($fields as $field) {
            $field_data[] = $field->to_array();
        }

        return rest_ensure_response($field_data);
    }

    /**
     * Obtiene valores de campos para un post específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_post_field_values($request)
    {
        $post_id = $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error(
                'post_not_found',
                __('Post no encontrado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        $post_type = $post->post_type;
        $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $post_type_obj = $post_type_factory->get_post_type($post_type);

        if (!$post_type_obj) {
            return new \WP_Error(
                'post_type_not_found',
                __('Tipo de contenido no reconocido.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        $fields = $post_type_obj->get_fields();
        $field_values = array();

        foreach ($fields as $field) {
            $field_name = $field->get_name();
            $meta_value = get_post_meta($post_id, $field_name, true);
            $field->set_value($meta_value);

            $field_values[$field_name] = array(
                'id'    => $field->get_id(),
                'name'  => $field_name,
                'label' => $field->get_label(),
                'type'  => $field->get_type(),
                'value' => $meta_value,
            );
        }

        return rest_ensure_response($field_values);
    }
}
