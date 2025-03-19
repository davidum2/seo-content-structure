<?php

/**
 * Clase abstracta para tipos de schema JSON-LD
 *
 * @package SEOContentStructure
 * @subpackage Schema
 */

namespace SEOContentStructure\Schema;

/**
 * Clase abstracta para implementación de schemas
 */
abstract class AbstractSchema
{
    /**
     * Tipo de schema
     *
     * @var string
     */
    protected $type;

    /**
     * Constructor
     *
     * @param string $type Tipo de schema
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Obtiene el tipo de schema
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Obtiene las propiedades disponibles para el schema
     *
     * @return array
     */
    abstract public function get_properties();

    /**
     * Genera el schema para un post
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @return array
     */
    abstract public function generate_schema($post);

    /**
     * Valida que el schema tenga las propiedades requeridas
     *
     * @param array $schema Datos del schema a validar
     * @return bool|WP_Error True si es válido, WP_Error si hay errores
     */
    abstract public function validate_schema($schema);

    /**
     * Obtiene el schema como array
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @return array
     */
    public function get_schema_array($post)
    {
        return $this->generate_schema($post);
    }

    /**
     * Obtiene el schema como JSON
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @param bool $pretty Formato de salida amigable
     * @return string
     */
    public function get_schema_json($post, $pretty = false)
    {
        $schema = $this->get_schema_array($post);
        return wp_json_encode($schema, $pretty ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Obtiene el schema como script HTML
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @param bool $pretty Formato de salida amigable
     * @return string
     */
    public function get_schema_script($post, $pretty = false)
    {
        $json = $this->get_schema_json($post, $pretty);
        return sprintf(
            '<script type="application/ld+json">%s</script>',
            $json
        );
    }

    /**
     * Imprime el schema como script HTML
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @param bool $pretty Formato de salida amigable
     */
    public function print_schema_script($post, $pretty = false)
    {
        echo $this->get_schema_script($post, $pretty);
    }

    /**
     * Obtiene las propiedades disponibles para un tipo de schema
     *
     * @param string $schema_type Tipo de schema
     * @return array
     */
    public static function get_schema_properties($schema_type)
    {
        $factory = new SchemaFactory();
        $schema = $factory->create($schema_type);

        if ($schema) {
            return $schema->get_properties();
        }

        return array();
    }

    /**
     * Verifica si una propiedad es requerida para el schema
     *
     * @param string $property Nombre de la propiedad
     * @return bool
     */
    public function is_property_required($property)
    {
        $properties = $this->get_properties();
        return isset($properties[$property]) && !empty($properties[$property]['required']);
    }

    /**
     * Obtiene el tipo de campo para una propiedad
     *
     * @param string $property Nombre de la propiedad
     * @return string
     */
    public function get_property_field_type($property)
    {
        $properties = $this->get_properties();
        return isset($properties[$property]) ? $properties[$property]['type'] : 'text';
    }
}
