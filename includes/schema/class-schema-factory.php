<?php

/**
 * Factory para crear instancias de schemas
 *
 * @package SEOContentStructure
 * @subpackage Schema
 */

namespace SEOContentStructure\Schema;

/**
 * Clase para crear instancias de schemas
 */
class SchemaFactory
{
    /**
     * Tipos de schemas disponibles
     *
     * @var array
     */
    protected $schema_types = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_default_schema_types();
    }

    /**
     * Registra los tipos de schema predeterminados
     */
    protected function register_default_schema_types()
    {
        $this->schema_types = array(
            'Service'        => 'SEOContentStructure\Schema\Types\ServiceSchema',
            'Product'        => 'SEOContentStructure\Schema\Types\ProductSchema',
            'Organization'   => 'SEOContentStructure\Schema\Types\OrganizationSchema',
            'LocalBusiness'  => 'SEOContentStructure\Schema\Types\LocalBusinessSchema',
            'Person'         => 'SEOContentStructure\Schema\Types\PersonSchema',
            'Event'          => 'SEOContentStructure\Schema\Types\EventSchema',
            'Article'        => 'SEOContentStructure\Schema\Types\ArticleSchema',
            'Recipe'         => 'SEOContentStructure\Schema\Types\RecipeSchema',
            'FAQ'            => 'SEOContentStructure\Schema\Types\FAQSchema',
        );
    }

    /**
     * Registra un nuevo tipo de schema
     *
     * @param string $type  Tipo de schema
     * @param string $class Nombre de la clase completo
     * @return self
     */
    public function register_schema_type($type, $class)
    {
        if (class_exists($class)) {
            $this->schema_types[$type] = $class;
        }

        return $this;
    }

    /**
     * Obtiene los tipos de schema registrados
     *
     * @return array
     */
    public function get_schema_types()
    {
        return $this->schema_types;
    }

    /**
     * Crea una instancia de schema
     *
     * @param string $type Tipo de schema
     * @return AbstractSchema|null
     */
    public function create($type)
    {
        // Verificar si el tipo está registrado
        if (!isset($this->schema_types[$type])) {
            return null;
        }

        // Obtener la clase
        $class = $this->schema_types[$type];

        // Verificar que la clase exista
        if (!class_exists($class)) {
            return null;
        }

        // Crear y devolver la instancia
        return new $class();
    }

    /**
     * Obtiene una lista de los tipos de schema disponibles para selección
     *
     * @return array
     */
    public function get_schema_types_list()
    {
        $types = array();
        foreach (array_keys($this->schema_types) as $type) {
            $types[$type] = $type;
        }
        return $types;
    }

    /**
     * Verifica si un tipo de schema está registrado
     *
     * @param string $type Tipo de schema
     * @return bool
     */
    public function is_schema_type_registered($type)
    {
        return isset($this->schema_types[$type]);
    }

    /**
     * Elimina un tipo de schema registrado
     *
     * @param string $type Tipo de schema
     * @return self
     */
    public function unregister_schema_type($type)
    {
        if (isset($this->schema_types[$type])) {
            unset($this->schema_types[$type]);
        }

        return $this;
    }

    /**
     * Crea un schema para un post específico
     *
     * @param string $type Tipo de schema
     * @param int|WP_Post $post Post ID o objeto post
     * @return array Schema generado o array vacío
     */
    public function create_schema_for_post($type, $post)
    {
        $schema = $this->create($type);

        if ($schema) {
            return $schema->generate_schema($post);
        }

        return array();
    }

    /**
     * Genera el HTML script para un schema en un post
     *
     * @param string $type Tipo de schema
     * @param int|WP_Post $post Post ID o objeto post
     * @param bool $pretty Formatear el JSON
     * @return string HTML script o cadena vacía
     */
    public function generate_schema_script($type, $post, $pretty = false)
    {
        $schema = $this->create($type);

        if ($schema) {
            return $schema->get_schema_script($post, $pretty);
        }

        return '';
    }
}
