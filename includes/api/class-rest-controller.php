<?php

/**
 * Controlador principal para la API REST
 *
 * @package SEOContentStructure
 * @subpackage API
 */

namespace SEOContentStructure\API;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\API\Endpoints\FieldsEndpoint;
use SEOContentStructure\API\Endpoints\SchemaEndpoint;

/**
 * Clase que maneja el registro de endpoints de la API REST
 */
class RestController implements Registrable
{
    /**
     * Namespace para los endpoints de la API
     *
     * @var string
     */
    const API_NAMESPACE = 'scs/v1';

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
        // Registrar la API REST
        $loader->add_action('rest_api_init', $this, 'register_endpoints');

        // Agregar endpoints específicos
        $this->register_endpoint_controllers($loader);
    }

    /**
     * Registra los endpoints de la API REST
     */
    public function register_endpoints()
    {
        // Este método se ejecuta automáticamente desde los controladores de endpoints
    }

    /**
     * Registra los controladores para los distintos endpoints
     *
     * @param Loader $loader Instancia del cargador
     */
    private function register_endpoint_controllers($loader)
    {
        // Endpoint de campos
        $fields_endpoint = new FieldsEndpoint();
        $fields_endpoint->register($loader);

        // Endpoint de schema
        $schema_endpoint = new SchemaEndpoint();
        $schema_endpoint->register($loader);
    }

    /**
     * Obtiene el namespace de la API
     *
     * @return string
     */
    public static function get_api_namespace()
    {
        return self::API_NAMESPACE;
    }

    /**
     * Formatea una respuesta de error
     *
     * @param string $code     Código de error
     * @param string $message  Mensaje de error
     * @param int    $status   Código de estado HTTP
     * @return WP_Error
     */
    public static function error_response($code, $message, $status = 400)
    {
        return new \WP_Error($code, $message, array('status' => $status));
    }

    /**
     * Formatea una respuesta exitosa
     *
     * @param mixed $data   Datos a devolver
     * @param array $extra  Datos adicionales para incluir en la respuesta
     * @return array
     */
    public static function success_response($data, $extra = array())
    {
        $response = array(
            'success' => true,
            'data'    => $data,
        );

        return array_merge($response, $extra);
    }

    /**
     * Valida que un usuario tenga los permisos necesarios
     *
     * @param string $capability Capacidad requerida
     * @return bool
     */
    public static function validate_permission($capability = 'manage_options')
    {
        return current_user_can($capability);
    }

    /**
     * Sanitiza los datos de entrada según su tipo
     *
     * @param mixed  $data  Datos a sanitizar
     * @param string $type  Tipo de dato (string, number, boolean, array)
     * @return mixed
     */
    public static function sanitize_param($data, $type = 'string')
    {
        switch ($type) {
            case 'number':
                return is_numeric($data) ? (float) $data : 0;

            case 'integer':
                return is_numeric($data) ? (int) $data : 0;

            case 'boolean':
                return filter_var($data, FILTER_VALIDATE_BOOLEAN);

            case 'array':
                return is_array($data) ? $data : array();

            case 'html':
                return wp_kses_post($data);

            default:
                return sanitize_text_field($data);
        }
    }
}
