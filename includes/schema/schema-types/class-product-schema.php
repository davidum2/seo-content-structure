<?php

/**
 * Implementación de Schema para Product (Producto)
 *
 * @package SEOContentStructure
 * @subpackage Schema\Types
 */

namespace SEOContentStructure\Schema\Types;

use SEOContentStructure\Schema\AbstractSchema;

/**
 * Clase para el schema Product
 */
class ProductSchema extends AbstractSchema
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('Product');
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
                'description' => __('Nombre del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => true,
            ),
            'description' => array(
                'label' => __('Descripción', 'seo-content-structure'),
                'description' => __('Descripción del producto', 'seo-content-structure'),
                'type' => 'textarea',
                'required' => false,
            ),
            'image' => array(
                'label' => __('Imagen', 'seo-content-structure'),
                'description' => __('Imagen representativa del producto', 'seo-content-structure'),
                'type' => 'image',
                'required' => false,
            ),
            'brand' => array(
                'label' => __('Marca', 'seo-content-structure'),
                'description' => __('Marca del producto', 'seo-content-structure'),
                'type' => 'object',
                'properties' => array(
                    'name' => array(
                        'label' => __('Nombre', 'seo-content-structure'),
                        'type' => 'text',
                        'required' => true,
                    ),
                ),
                'required' => false,
            ),
            'sku' => array(
                'label' => __('SKU', 'seo-content-structure'),
                'description' => __('Código SKU del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'gtin13' => array(
                'label' => __('GTIN / EAN', 'seo-content-structure'),
                'description' => __('Código GTIN-13 / EAN del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'mpn' => array(
                'label' => __('MPN', 'seo-content-structure'),
                'description' => __('Número de pieza del fabricante', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'offers' => array(
                'label' => __('Oferta', 'seo-content-structure'),
                'description' => __('Oferta o precio del producto', 'seo-content-structure'),
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
                            'InStock' => __('En stock', 'seo-content-structure'),
                            'OutOfStock' => __('Fuera de stock', 'seo-content-structure'),
                            'PreOrder' => __('Pre-orden', 'seo-content-structure'),
                            'BackOrder' => __('Pedido pendiente', 'seo-content-structure'),
                            'LimitedAvailability' => __('Disponibilidad limitada', 'seo-content-structure'),
                            'SoldOut' => __('Agotado', 'seo-content-structure'),
                            'Discontinued' => __('Descontinuado', 'seo-content-structure'),
                        ),
                        'required' => false,
                    ),
                    'priceValidUntil' => array(
                        'label' => __('Precio válido hasta', 'seo-content-structure'),
                        'type' => 'date',
                        'required' => false,
                    ),
                    'url' => array(
                        'label' => __('URL', 'seo-content-structure'),
                        'type' => 'url',
                        'required' => false,
                    ),
                ),
                'required' => true,
            ),
            'category' => array(
                'label' => __('Categoría', 'seo-content-structure'),
                'description' => __('Categoría del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'color' => array(
                'label' => __('Color', 'seo-content-structure'),
                'description' => __('Color del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'material' => array(
                'label' => __('Material', 'seo-content-structure'),
                'description' => __('Material del producto', 'seo-content-structure'),
                'type' => 'text',
                'required' => false,
            ),
            'weight' => array(
                'label' => __('Peso', 'seo-content-structure'),
                'description' => __('Peso del producto', 'seo-content-structure'),
                'type' => 'object',
                'properties' => array(
                    'value' => array(
                        'label' => __('Valor', 'seo-content-structure'),
                        'type' => 'number',
                        'required' => true,
                    ),
                    'unitCode' => array(
                        'label' => __('Unidad', 'seo-content-structure'),
                        'type' => 'select',
                        'options' => array(
                            'KGM' => __('Kilogramos', 'seo-content-structure'),
                            'GRM' => __('Gramos', 'seo-content-structure'),
                            'LBR' => __('Libras', 'seo-content-structure'),
                        ),
                        'required' => true,
                    ),
                ),
                'required' => false,
            ),
            'url' => array(
                'label' => __('URL', 'seo-content-structure'),
                'description' => __('URL del producto', 'seo-content-structure'),
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
            '@type' => 'Product',
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post) ?: $post->post_content),
            'url' => get_permalink($post),
        );

        // Imagen destacada
        if (has_post_thumbnail($post)) {
            $schema['image'] = wp_get_attachment_url(get_post_thumbnail_id($post));
        }

        // Obtener metadatos adicionales
        $brand_name = get_post_meta($post->ID, '_product_brand', true);
        if (!empty($brand_name)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand_name,
            );
        }

        $sku = get_post_meta($post->ID, '_product_sku', true);
        if (!empty($sku)) {
            $schema['sku'] = $sku;
        }

        $gtin = get_post_meta($post->ID, '_product_gtin', true);
        if (!empty($gtin)) {
            $schema['gtin13'] = $gtin;
        }

        $mpn = get_post_meta($post->ID, '_product_mpn', true);
        if (!empty($mpn)) {
            $schema['mpn'] = $mpn;
        }

        // Categoría
        $category = get_post_meta($post->ID, '_product_category', true);
        if (!empty($category)) {
            $schema['category'] = $category;
        } else {
            // Intentar obtener de taxonomías
            $terms = get_the_terms($post->ID, 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                $schema['category'] = $terms[0]->name;
            }
        }

        // Atributos adicionales
        $color = get_post_meta($post->ID, '_product_color', true);
        if (!empty($color)) {
            $schema['color'] = $color;
        }

        $material = get_post_meta($post->ID, '_product_material', true);
        if (!empty($material)) {
            $schema['material'] = $material;
        }

        // Oferta/precio
        $price = get_post_meta($post->ID, '_product_price', true);
        if (!empty($price)) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => get_post_meta($post->ID, '_price_currency', true) ?: 'EUR',
            );

            $availability = get_post_meta($post->ID, '_product_availability', true);
            if (!empty($availability)) {
                $schema['offers']['availability'] = 'https://schema.org/' . $availability;
            }

            $price_valid_until = get_post_meta($post->ID, '_price_valid_until', true);
            if (!empty($price_valid_until)) {
                $schema['offers']['priceValidUntil'] = $price_valid_until;
            }
        }

        // Filtro para modificar el schema antes de retornarlo
        return apply_filters('scs_product_schema_output', $schema, $post);
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
                __('La propiedad "name" es requerida para el schema Product.', 'seo-content-structure')
            );
        }

        // Verificar ofertas (requerido)
        if (empty($schema['offers'])) {
            return new \WP_Error(
                'missing_required_property',
                __('La propiedad "offers" es requerida para el schema Product.', 'seo-content-structure')
            );
        }

        // Si hay ofertas, verificar precio y moneda
        if (is_array($schema['offers'])) {
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
