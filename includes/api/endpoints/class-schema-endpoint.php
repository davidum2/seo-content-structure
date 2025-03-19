<?php

/**
 * Endpoint para esquemas JSON-LD
 *
 * @package SEOContentStructure
 * @subpackage API\Endpoints
 */

namespace SEOContentStructure\API\Endpoints;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\API\RestController;
use SEOContentStructure\Schema\SchemaFactory;

/**
 * Clase que implementa el endpoint para esquemas JSON-LD
 */
class SchemaEndpoint implements Registrable
{
    /**
     * Endpoint base
     *
     * @var string
     */
    protected $endpoint_base = 'schema';

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

        // Ruta para obtener tipos de schema disponibles
        register_rest_route($namespace, '/' . $base . '/types', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_schema_types'),
            'permission_callback' => array($this, 'get_schema_permissions_check'),
        ));

        // Ruta para obtener propiedades de un tipo de schema
        register_rest_route($namespace, '/' . $base . '/types/(?P<type>[\w-]+)/properties', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_schema_properties'),
            'permission_callback' => array($this, 'get_schema_permissions_check'),
            'args'     => array(
                'type' => array(
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    }
                ),
            ),
        ));

        // Ruta para obtener el schema para un post
        register_rest_route($namespace, '/' . $base . '/posts/(?P<post_id>[\d]+)', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_post_schema'),
            'permission_callback' => array($this, 'get_schema_permissions_check'),
            'args'     => array(
                'post_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        // Ruta para validar un schema
        register_rest_route($namespace, '/' . $base . '/validate', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'validate_schema'),
            'permission_callback' => array($this, 'get_schema_permissions_check'),
            'args'     => array(
                'type' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    }
                ),
                'schema' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_array($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Verifica permisos para acceder a la API de schema
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool
     */
    public function get_schema_permissions_check($request)
    {
        return current_user_can('edit_posts');
    }

    /**
     * Obtiene los tipos de schema disponibles
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response
     */
    public function get_schema_types($request)
    {
        $schema_factory = new SchemaFactory();
        $types = $schema_factory->get_schema_types_list();

        return rest_ensure_response($types);
    }

    /**
     * Obtiene las propiedades de un tipo de schema
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_schema_properties($request)
    {
        $type = $request['type'];
        $schema_factory = new SchemaFactory();

        if (!$schema_factory->is_schema_type_registered($type)) {
            return new \WP_Error(
                'schema_type_not_found',
                __('Tipo de schema no encontrado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        $schema = $schema_factory->create($type);
        $properties = $schema->get_properties();

        return rest_ensure_response($properties);
    }

    /**
     * Obtiene el schema para un post específico
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function get_post_schema($request)
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

        // Obtener el tipo de schema para este post
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

        $schema_type = $post_type_obj->get_schema_type();

        if (empty($schema_type)) {
            return new \WP_Error(
                'no_schema_type',
                __('Este tipo de contenido no tiene un schema asociado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        // Generar el schema
        $schema_factory = new SchemaFactory();
        $schema = $schema_factory->create_schema_for_post($schema_type, $post);

        return rest_ensure_response($schema);
    }

    /**
     * Valida un schema
     *
     * @param WP_REST_Request $request Datos de la solicitud
     * @return WP_REST_Response|WP_Error
     */
    public function validate_schema($request)
    {
        $type = $request['type'];
        $schema_data = $request['schema'];

        $schema_factory = new SchemaFactory();

        if (!$schema_factory->is_schema_type_registered($type)) {
            return new \WP_Error(
                'schema_type_not_found',
                __('Tipo de schema no encontrado.', 'seo-content-structure'),
                array('status' => 404)
            );
        }

        $schema = $schema_factory->create($type);
        $validation = $schema->validate_schema($schema_data);

        if (is_wp_error($validation)) {
            return $validation;
        }

        return rest_ensure_response(array(
            'valid' => true,
            'message' => __('Schema válido.', 'seo-content-structure'),
        ));
    }
}
