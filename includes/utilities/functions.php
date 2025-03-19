<?php

/**
 * Funciones de utilidad para el plugin
 *
 * @package SEOContentStructure
 * @subpackage Utilities
 */

// Evitar acceso directo
if (! defined('ABSPATH')) {
    exit;
}



/**
 * Obtiene el valor de un campo personalizado para un post
 *
 * @param string $field_name Nombre del campo
 * @param int    $post_id    ID del post (opcional)
 * @return mixed
 */
function scs_get_field($field_name, $post_id = null)
{
    // Si no se proporciona un ID, usar el post actual
    if (null === $post_id) {
        $post_id = get_the_ID();
    }

    // Si no hay post ID válido, retornar null
    if (! $post_id) {
        return null;
    }

    // Obtener el valor del campo
    return get_post_meta($post_id, $field_name, true);
}

/**
 * Obtiene la URL de una imagen desde un campo de tipo imagen
 *
 * @param string $field_name Nombre del campo
 * @param string $size       Tamaño de la imagen (thumbnail, medium, large, full)
 * @param int    $post_id    ID del post (opcional)
 * @return string URL de la imagen o cadena vacía
 */
function scs_get_image_url($field_name, $size = 'full', $post_id = null)
{
    $attachment_id = scs_get_field($field_name, $post_id);

    if (! $attachment_id) {
        return '';
    }

    return wp_get_attachment_image_url($attachment_id, $size);
}

/**
 * Muestra el valor de un campo personalizado
 *
 * @param string $field_name Nombre del campo
 * @param int    $post_id    ID del post (opcional)
 * @param bool   $echo       Si true, imprime el valor; si false, lo retorna
 * @return mixed
 */
function scs_the_field($field_name, $post_id = null, $echo = true)
{
    $value = scs_get_field($field_name, $post_id);

    if ($echo) {
        echo esc_html($value);
    }

    return $value;
}

/**
 * Verifica si un valor está vacío (incluyendo arrays y strings)
 *
 * @param mixed $value Valor a verificar
 * @return bool
 */
function scs_is_empty($value)
{
    if (is_array($value)) {
        return empty($value);
    }

    return '' === $value || null === $value;
}

/**
 * Obtiene el objeto post type
 *
 * @param string $post_type Nombre del post type
 * @return PostType|null
 */
function scs_get_post_type($post_type)
{
    $factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
    return $factory->get_post_type($post_type);
}
