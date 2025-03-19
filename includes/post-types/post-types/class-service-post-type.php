<?php

/**
 * Implementación de tipo de contenido para Servicios
 *
 * @package SEOContentStructure
 * @subpackage PostTypes
 */

namespace SEOContentStructure\PostTypes;

use SEOContentStructure\Fields\FieldFactory;

/**
 * Clase para el tipo de contenido de Servicios
 */
class ServicePostType extends PostType
{
    /**
     * Constructor
     *
     * @param string $post_type  Nombre del post type (debería ser 'servicio')
     * @param array  $args       Argumentos adicionales
     * @param array  $taxonomies Taxonomías a registrar
     */
    public function __construct($post_type, $args = array(), $taxonomies = array())
    {
        // Llamar al constructor padre
        parent::__construct($post_type, $args, $taxonomies);

        // Establecer schema type para servicios
        $this->schema_type = 'Service';

        // Registrar campos personalizados para servicios
        $this->register_service_fields();
    }

    /**
     * Registra los campos personalizados para el tipo de servicio
     */
    protected function register_service_fields()
    {
        $field_factory = new FieldFactory();

        // Campo para el tipo de servicio
        $service_type_field = $field_factory->create(array(
            'id'             => 'servicio_tipo',
            'name'           => '_service_type',
            'label'          => __('Tipo de Servicio', 'seo-content-structure'),
            'type'           => 'text',
            'placeholder'    => __('Ej: Consultoría, Asesoría, Mantenimiento', 'seo-content-structure'),
            'instructions'   => __('Indica el tipo o categoría del servicio', 'seo-content-structure'),
            'schema_property' => 'serviceType',
        ));

        // Campo para el precio del servicio
        $price_field = $field_factory->create(array(
            'id'             => 'servicio_precio',
            'name'           => '_service_price',
            'label'          => __('Precio', 'seo-content-structure'),
            'type'           => 'number',
            'placeholder'    => '0.00',
            'instructions'   => __('Precio del servicio (sin símbolo de moneda)', 'seo-content-structure'),
            'schema_property' => 'offers.price',
        ));

        // Campo para la moneda
        $currency_field = $field_factory->create(array(
            'id'             => 'servicio_moneda',
            'name'           => '_price_currency',
            'label'          => __('Moneda', 'seo-content-structure'),
            'type'           => 'select',
            'options'        => array(
                'EUR' => __('Euro (€)', 'seo-content-structure'),
                'USD' => __('Dólar ($)', 'seo-content-structure'),
                'GBP' => __('Libra (£)', 'seo-content-structure'),
            ),
            'default_value'  => 'EUR',
            'schema_property' => 'offers.priceCurrency',
        ));

        // Campo para la disponibilidad
        $availability_field = $field_factory->create(array(
            'id'             => 'servicio_disponibilidad',
            'name'           => '_service_availability',
            'label'          => __('Disponibilidad', 'seo-content-structure'),
            'type'           => 'select',
            'options'        => array(
                'InStock'     => __('Disponible', 'seo-content-structure'),
                'OutOfStock'  => __('No disponible', 'seo-content-structure'),
                'PreOrder'    => __('Reserva previa', 'seo-content-structure'),
            ),
            'default_value'  => 'InStock',
            'schema_property' => 'offers.availability',
        ));

        // Campo para área geográfica de servicio
        $area_field = $field_factory->create(array(
            'id'             => 'servicio_area',
            'name'           => '_area_served',
            'label'          => __('Área de Servicio', 'seo-content-structure'),
            'type'           => 'text',
            'placeholder'    => __('Ej: Madrid, España, Europa', 'seo-content-structure'),
            'schema_property' => 'areaServed',
        ));

        // Campo para imagen destacada del servicio
        $image_field = $field_factory->create(array(
            'id'             => 'servicio_imagen',
            'name'           => '_service_image',
            'label'          => __('Imagen del Servicio', 'seo-content-structure'),
            'type'           => 'image',
            'instructions'   => __('Selecciona una imagen representativa para este servicio', 'seo-content-structure'),
            'schema_property' => 'image',
        ));

        // Características del servicio (repeater)
        $features_field = $field_factory->create(array(
            'id'             => 'servicio_caracteristicas',
            'name'           => '_service_features',
            'label'          => __('Características', 'seo-content-structure'),
            'type'           => 'repeater',
            'button_text'    => __('Añadir característica', 'seo-content-structure'),
            'min_rows'       => 0,
            'max_rows'       => 10,
            'sub_fields'     => array(
                array(
                    'id'    => 'texto',
                    'label' => __('Característica', 'seo-content-structure'),
                    'type'  => 'text',
                ),
                array(
                    'id'    => 'valor',
                    'label' => __('Valor/Detalle', 'seo-content-structure'),
                    'type'  => 'text',
                ),
            ),
        ));

        // Añadir todos los campos al servicio
        $this->add_field($service_type_field);
        $this->add_field($price_field);
        $this->add_field($currency_field);
        $this->add_field($availability_field);
        $this->add_field($area_field);
        $this->add_field($image_field);
        $this->add_field($features_field);
    }
}
