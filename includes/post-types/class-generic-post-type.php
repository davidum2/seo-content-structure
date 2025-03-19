<?php

/**
 * Implementación genérica para tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage PostTypes
 */

namespace SEOContentStructure\PostTypes;

/**
 * Clase genérica para tipos de contenido personalizados
 */
class GenericPostType extends PostType
{
    /**
     * Constructor
     *
     * @param string $post_type  Nombre del post type
     * @param array  $args       Argumentos adicionales
     * @param array  $taxonomies Taxonomías a registrar
     */
    public function __construct($post_type, $args = array(), $taxonomies = array())
    {
        // Llamar al constructor padre
        parent::__construct($post_type, $args, $taxonomies);
    }
}
