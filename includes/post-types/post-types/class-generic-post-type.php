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
        error_log("SCS_TRACE: GenericPostType::__construct - Iniciando para post_type: $post_type");

        try {
            // Llamar al constructor padre
            parent::__construct($post_type, $args, $taxonomies);
            error_log("SCS_TRACE: GenericPostType::__construct - Constructor padre ejecutado correctamente");

            // Verificar estructura de args tras constructor padre
            if (isset($this->args) && is_array($this->args)) {
                error_log("SCS_TRACE: GenericPostType::__construct - Args después de constructor padre: " . print_r($this->args, true));
            } else {
                error_log("SCS_ERROR: GenericPostType::__construct - Args no es un array válido después del constructor padre");
            }

            error_log("SCS_TRACE: GenericPostType::__construct - Completado para post_type: $post_type");
        } catch (\Throwable $e) {
            error_log("SCS_ERROR: GenericPostType::__construct - Error al crear post_type '$post_type': " . $e->getMessage());
            throw $e; // Re-lanzar para manejo en nivel superior
        }
    }
}
