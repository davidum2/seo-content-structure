<?php

/**
 * Implementación de Schema para Service (Servicio)
 *
 * @package SEOContentStructure
 * @subpackage Schema\Types
 */

namespace SEOContentStructure\Schema\Types;

use SEOContentStructure\Schema\AbstractSchema;

/**
 * Clase para el schema Service
 */
class ServiceSchema extends AbstractSchema
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('Service');
    }

    /**
     * Obtiene las propiedades disponibles para el schema
     *
     * @return array
     */
    public function get_properties()
    {
        return array(
            'name' => array(
                'label' => __('Nombre', 'seo-content-structure'),
                'description' => __('Nombre del servicio', 'seo-content-structure'),
                'type' => 'text',
                'required' => true,
            ),
            'description' => array(
                'label' => __('Descripción', 'seo-content-structure'),
                'description' => __('Descripción del servicio', 'seo-content-structure'),
                'type' => 'textarea',
                'required' => false,
            ),
            'image' => array(
                'label' => __('Imagen', 'seo-content-structure'),
                'description' => __('Imagen representativa del servicio', 'seo-content-structure'),
                'type' => 'image',
                'required' => false,
            ),
            'serviceType' => array(
                'label' => __('Tipo de Servicio', 'seo-content-structure'),
                'description' => __('Categoría o tipo de servicio', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'provider' => array(
                'label' => __('Proveedor', 'seo-content-structure'),
                'description' => __('Organización o persona que proporciona el servicio', 'seo-content-structure'),
                'type' => 'object',
                'properties' => array(
                    'name' => array(
                        'label' => __('Nombre', 'seo-content-structure'),
                        'type' => 'text',
                        'required' => true,
                    ),
                    'url' => array(
                        'label' => __('URL', 'seo-content-structure'),
                        'type' => 'url',
                        'required' => false,
                    ),
                ),
                'required' => false,
            ),
            'areaServed' => array(
                'label' => __('Área de Servicio', 'seo-content-structure'),
                'description' => __('Área geográfica donde se ofrece el servicio', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'offers' => array(
                'label' => __('Oferta', 'seo-content-structure'),
                'description' => __('Oferta o precio del servicio', 'seo-content-structure'),
                'type' => 'object',
                'properties' => array(
                    'price' => array(
                        'label' => __('Precio', 'seo-content-structure'),
                        'type' => 'number',
                        'required' => true,
                    ),
                    'priceCurrency' => array(
                        'label' => __('Moneda', 'seo-content-structure'),
                        'type' => 'text',
                        'required' => true,
                    ),
                    'availability' => array(
                        'label' => __('Disponibilidad', 'seo-content-structure'),
                        'type' => 'select',
                        'options' => array(
                            'InStock' => __('Disponible', 'seo-content-structure'),
                            'OutOfStock' => __('No disponible', 'seo-content-structure'),
                            'PreOrder' => __('Pre-orden', 'seo-content-structure'),
                        ),
                        'required' => false,
                    ),
                ),
                'required' => false,
            ),
            'url' => array(
                'label' => __('URL', 'seo-content-structure'),
                'description' => __('URL del servicio', 'seo-content-structure'),
                'type' => 'url',
                'required' => false,
            ),
        );
    }

    /**
     * Genera el schema para un post
     *
     * @param int|WP_Post $post Post ID o objeto post
     * @return array
     */
    public function generate_schema($post)
    {
        $post = get_post($post);
        if (!$post) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post) ?: $post->post_content),
            'url' => get_permalink($post),
        );

        // Imagen destacada
        if (has_post_thumbnail($post)) {
            $schema['image'] = wp_get_attachment_url(get_post_thumbnail_id($post));
        }

        // Proveedor (organización)
        $schema['provider'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
        );

        // Obtener metadatos adicionales
        $service_type = get_post_meta($post->ID, '_service_type', true);
        if (!empty($service_type)) {
            $schema['serviceType'] = $service_type;
        }

        $area_served = get_post_meta($post->ID, '_area_served', true);
        if (!empty($area_served)) {
            $schema['areaServed'] = $area_served;
        }

        // Oferta/precio
        $price = get_post_meta($post->ID, '_service_price', true);
        if (!empty($price)) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => get_post_meta($post->ID, '_price_currency', true) ?: 'EUR',
            );

            $availability = get_post_meta($post->ID, '_service_availability', true);
            if (!empty($availability)) {
                $schema['offers']['availability'] = 'https://schema.org/' . $availability;
            }
        }

        // Filtro para modificar el schema antes de retornarlo
        return apply_filters('scs_service_schema_output', $schema, $post);
    }

    /**
     * Valida que el schema tenga las propiedades requeridas
     *
     * @param array $schema Datos del schema a validar
     * @return bool|WP_Error True si es válido, WP_Error si hay errores
     */
    public function validate_schema($schema)
    {
        // Verificar propiedad requerida: name
        if (empty($schema['name'])) {
            return new \WP_Error(
                'missing_required_property',
                __('La propiedad "name" es requerida para el schema Service.', 'seo-content-structure')
            );
        }

        // Si hay ofertas, verificar precio y moneda
        if (isset($schema['offers']) && is_array($schema['offers'])) {
            if (empty($schema['offers']['price'])) {
                return new \WP_Error(
                    'missing_required_property',
                    __('La propiedad "price" es requerida en la oferta.', 'seo-content-structure')
                );
            }

            if (empty($schema['offers']['priceCurrency'])) {
                return new \WP_Error(
                    'missing_required_property',
                    __('La propiedad "priceCurrency" es requerida en la oferta.', 'seo-content-structure')
                );
            }
        }

        return true;
    }
}
