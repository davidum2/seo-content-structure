<?php

/**
 * Clase para manejar los shortcodes del plugin
 *
 * @package SEOContentStructure
 * @subpackage Utilities
 */

namespace SEOContentStructure\Utilities;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;

/**
 * Clase que implementa los shortcodes del plugin
 */
class Shortcodes implements Registrable
{
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
        // Registrar shortcodes
        $loader->add_shortcode('scs_field', $this, 'field_shortcode');
        $loader->add_shortcode('scs_schema', $this, 'schema_shortcode');
        $loader->add_shortcode('scs_post_list', $this, 'post_list_shortcode');
    }

    /**
     * Shortcode para mostrar el valor de un campo personalizado
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function field_shortcode($atts)
    {
        $defaults = array(
            'name' => '',
            'post_id' => get_the_ID(),
            'default' => '',
            'format' => 'html',
        );

        $atts = shortcode_atts($defaults, $atts, 'scs_field');

        // Si no se especifica un nombre de campo, devolver vacío
        if (empty($atts['name'])) {
            return '';
        }

        // Obtener el valor del campo
        $value = get_post_meta($atts['post_id'], $atts['name'], true);

        // Si no hay valor, utilizar el valor por defecto
        if (empty($value)) {
            return $atts['default'];
        }

        // Formatear el valor según el tipo de campo
        $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $post_type_obj = $post_type_factory->get_post_type(get_post_type($atts['post_id']));

        if ($post_type_obj) {
            $field = $post_type_obj->get_field($atts['name']);

            if ($field) {
                $field->set_value($value);

                if ($atts['format'] === 'html') {
                    return $field->render_frontend();
                }
            }
        }

        // Para formato texto plano o si no se encuentra el campo
        if (is_array($value) || is_object($value)) {
            return wp_json_encode($value);
        }

        return esc_html($value);
    }

    /**
     * Shortcode para mostrar un schema JSON-LD
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function schema_shortcode($atts)
    {
        $defaults = array(
            'type' => '',
            'post_id' => get_the_ID(),
        );

        $atts = shortcode_atts($defaults, $atts, 'scs_schema');

        // Si no se especifica un tipo de schema, intentar obtenerlo del post type
        if (empty($atts['type'])) {
            $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
            $post_type_obj = $post_type_factory->get_post_type(get_post_type($atts['post_id']));

            if ($post_type_obj) {
                $atts['type'] = $post_type_obj->get_schema_type();
            }
        }

        // Si aún no hay tipo de schema, devolver vacío
        if (empty($atts['type'])) {
            return '';
        }

        // Generar el schema
        $schema_factory = new \SEOContentStructure\Schema\SchemaFactory();
        $schema_html = $schema_factory->generate_schema_script($atts['type'], $atts['post_id'], true);

        return $schema_html;
    }

    /**
     * Shortcode para mostrar una lista de posts con campos personalizados
     *
     * @param array $atts Atributos del shortcode
     * @return string
     */
    public function post_list_shortcode($atts)
    {
        $defaults = array(
            'post_type' => 'post',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'title,excerpt',
            'layout' => 'list',
            'category' => '',
            'taxonomy' => '',
            'term' => '',
        );

        $atts = shortcode_atts($defaults, $atts, 'scs_post_list');

        // Convertir campos a array
        $fields = explode(',', $atts['fields']);
        $fields = array_map('trim', $fields);

        // Preparar argumentos para la consulta
        $args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );

        // Filtrar por categoría si se especifica
        if (!empty($atts['category'])) {
            $args['category_name'] = $atts['category'];
        }

        // Filtrar por taxonomía si se especifica
        if (!empty($atts['taxonomy']) && !empty($atts['term'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $atts['taxonomy'],
                    'field'    => 'slug',
                    'terms'    => $atts['term'],
                ),
            );
        }

        // Ejecutar la consulta
        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return '<p>' . __('No se encontraron posts.', 'seo-content-structure') . '</p>';
        }

        // Iniciar el buffer de salida
        ob_start();

        // Determinar la clase de layout
        $layout_class = 'scs-post-list-' . esc_attr($atts['layout']);

        echo '<div class="scs-post-list ' . esc_attr($layout_class) . '">';

        while ($query->have_posts()) {
            $query->the_post();

            echo '<div class="scs-post-item">';

            // Mostrar los campos solicitados
            foreach ($fields as $field) {
                switch ($field) {
                    case 'title':
                        echo '<h3 class="scs-post-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
                        break;

                    case 'excerpt':
                        echo '<div class="scs-post-excerpt">' . wp_kses_post(get_the_excerpt()) . '</div>';
                        break;

                    case 'thumbnail':
                        if (has_post_thumbnail()) {
                            echo '<div class="scs-post-thumbnail">';
                            echo '<a href="' . esc_url(get_permalink()) . '">';
                            the_post_thumbnail('medium');
                            echo '</a>';
                            echo '</div>';
                        }
                        break;

                    case 'date':
                        echo '<div class="scs-post-date">' . esc_html(get_the_date()) . '</div>';
                        break;

                    case 'author':
                        echo '<div class="scs-post-author">' . esc_html(get_the_author()) . '</div>';
                        break;

                    default:
                        // Intentar obtener como campo personalizado
                        $custom_value = get_post_meta(get_the_ID(), $field, true);
                        if (!empty($custom_value)) {
                            echo '<div class="scs-post-custom scs-post-' . esc_attr($field) . '">';

                            if (is_array($custom_value) || is_object($custom_value)) {
                                echo wp_kses_post(wp_json_encode($custom_value));
                            } else {
                                echo wp_kses_post($custom_value);
                            }

                            echo '</div>';
                        }
                        break;
                }
            }

            echo '</div>'; // .scs-post-item
        }

        echo '</div>'; // .scs-post-list

        // Restaurar datos originales
        wp_reset_postdata();

        // Devolver el contenido del buffer
        return ob_get_clean();
    }
}
