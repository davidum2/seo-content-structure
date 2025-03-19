<?php

/**
 * Clase para construir y manejar estructuras JSON-LD para SEO
 *
 * @package SEOContentStructure
 * @subpackage Utilities
 */

namespace SEOContentStructure\Utilities;

/**
 * Clase para construir y manejar estructuras JSON-LD
 */
class JsonLdBuilder
{
    /**
     * Datos base del JSON-LD
     *
     * @var array
     */
    protected $data = array();

    /**
     * Lista de tipos admitidos con sus propiedades
     *
     * @var array
     */
    protected $schema_types = array(
        'Service' => array(
            'name',
            'description',
            'image',
            'provider',
            'serviceType',
            'areaServed',
            'serviceOutput',
            'offers',
            'aggregateRating',
            'review',
            'availableChannel',
            'hasOfferCatalog',
            'hoursAvailable',
            'potentialAction',
            'termsOfService',
            'additionalType',
            'alternateName',
            'sameAs',
            'url',
            'identifier',
        ),
        'Product' => array(
            'name',
            'description',
            'image',
            'brand',
            'offers',
            'sku',
            'gtin',
            'gtin8',
            'gtin13',
            'gtin14',
            'mpn',
            'color',
            'material',
            'productID',
            'review',
            'aggregateRating',
            'additionalType',
            'alternateName',
            'category',
            'hasMerchantReturnPolicy',
            'height',
            'width',
            'depth',
            'weight',
            'model',
            'url',
        ),
        'Organization' => array(
            'name',
            'description',
            'logo',
            'url',
            'address',
            'contactPoint',
            'email',
            'telephone',
            'faxNumber',
            'sameAs',
            'founder',
            'foundingDate',
            'foundingLocation',
            'member',
            'numberOfEmployees',
            'location',
            'areaServed',
            'award',
            'taxID',
            'vatID',
            'duns',
            'leiCode',
        ),
        'Person' => array(
            'name',
            'givenName',
            'familyName',
            'additionalName',
            'jobTitle',
            'worksFor',
            'alumniOf',
            'address',
            'email',
            'telephone',
            'faxNumber',
            'url',
            'image',
            'gender',
            'birthDate',
            'birthPlace',
            'deathDate',
            'deathPlace',
            'nationality',
            'sameAs',
            'award',
            'knowsLanguage',
            'memberOf',
            'spouse',
            'parent',
            'children',
            'colleague',
            'follows',
            'knows',
        ),
        'LocalBusiness' => array(
            'name',
            'description',
            'image',
            'logo',
            'address',
            'telephone',
            'email',
            'url',
            'priceRange',
            'openingHours',
            'openingHoursSpecification',
            'hasMap',
            'geo',
            'department',
            'branchOf',
            'currenciesAccepted',
            'paymentAccepted',
            'areaServed',
            'review',
            'aggregateRating',
            'sameAs',
            'taxID',
            'vatID',
            'foundingDate',
            'numberOfEmployees',
            'award',
            'naics',
            'isicV4',
        ),
        'Event' => array(
            'name',
            'description',
            'startDate',
            'endDate',
            'location',
            'performer',
            'organizer',
            'eventStatus',
            'eventAttendanceMode',
            'offers',
            'image',
            'url',
            'duration',
            'maximumAttendeeCapacity',
            'remainingAttendeeCapacity',
            'inLanguage',
            'about',
            'sponsor',
            'typicalAgeRange',
            'doorTime',
            'aggregateRating',
            'review',
            'recordedIn',
            'superEvent',
            'subEvent',
        ),
    );

    /**
     * Constructor
     *
     * @param string $type Tipo de esquema a crear (Service, Product, etc.)
     */
    public function __construct($type = 'Service')
    {
        // Verificar si el tipo es válido
        if (! isset($this->schema_types[$type])) {
            $type = 'Service'; // Tipo por defecto
        }

        // Inicializar estructura base
        $this->data = array(
            '@context' => 'https://schema.org',
            '@type'    => $type,
        );
    }

    /**
     * Establece una propiedad en el schema
     *
     * @param string $property Nombre de la propiedad
     * @param mixed  $value    Valor de la propiedad
     * @return self
     */
    public function set_property($property, $value)
    {
        if (empty($value)) {
            return $this;
        }

        $this->data[$property] = $value;
        return $this;
    }

    /**
     * Establece múltiples propiedades a la vez
     *
     * @param array $properties Arreglo asociativo de propiedades y valores
     * @return self
     */
    public function set_properties($properties)
    {
        if (! is_array($properties)) {
            return $this;
        }

        foreach ($properties as $property => $value) {
            $this->set_property($property, $value);
        }

        return $this;
    }

    /**
     * Obtiene una propiedad específica
     *
     * @param string $property Nombre de la propiedad
     * @return mixed
     */
    public function get_property($property)
    {
        return isset($this->data[$property]) ? $this->data[$property] : null;
    }

    /**
     * Elimina una propiedad del schema
     *
     * @param string $property Nombre de la propiedad
     * @return self
     */
    public function remove_property($property)
    {
        if (isset($this->data[$property])) {
            unset($this->data[$property]);
        }

        return $this;
    }

    /**
     * Obtiene el tipo actual de schema
     *
     * @return string
     */
    public function get_type()
    {
        return $this->data['@type'];
    }

    /**
     * Cambia el tipo de schema
     *
     * @param string $type Nuevo tipo de schema
     * @return self
     */
    public function set_type($type)
    {
        if (isset($this->schema_types[$type])) {
            $this->data['@type'] = $type;
        }

        return $this;
    }

    /**
     * Añade una imagen al schema
     *
     * @param int|string $image_id ID de la imagen o URL
     * @return self
     */
    public function add_image($image_id)
    {
        if (empty($image_id)) {
            return $this;
        }

        // Si es un ID de adjunto
        if (is_numeric($image_id)) {
            $image_url = wp_get_attachment_url($image_id);
            if (! $image_url) {
                return $this;
            }

            $this->data['image'] = $image_url;
        } else {
            // Asumimos que es una URL
            $this->data['image'] = $image_id;
        }

        return $this;
    }

    /**
     * Añade información de ofertas al schema
     *
     * @param float  $price       Precio
     * @param string $currency    Código de moneda (USD, EUR, etc.)
     * @param string $availability Disponibilidad (InStock, OutOfStock, etc.)
     * @return self
     */
    public function add_offer($price, $currency = 'USD', $availability = 'InStock')
    {
        if (empty($price)) {
            return $this;
        }

        $offer = array(
            '@type'         => 'Offer',
            'price'         => $price,
            'priceCurrency' => $currency,
            'availability'  => 'https://schema.org/' . $availability,
        );

        $this->data['offers'] = $offer;

        return $this;
    }

    /**
     * Añade información de organización al schema
     *
     * @param string $name    Nombre de la organización
     * @param string $url     URL de la organización
     * @param array  $address Arreglo con la dirección
     * @return self
     */
    public function add_organization($name, $url = '', $address = array())
    {
        if (empty($name)) {
            return $this;
        }

        $organization = array(
            '@type' => 'Organization',
            'name'  => $name,
        );

        if (! empty($url)) {
            $organization['url'] = $url;
        }

        if (! empty($address) && is_array($address)) {
            $organization['address'] = array_merge(
                array('@type' => 'PostalAddress'),
                $address
            );
        }

        $this->data['provider'] = $organization;

        return $this;
    }

    /**
     * Añade valoraciones agregadas al schema
     *
     * @param float $rating      Puntuación promedio
     * @param int   $review_count Número de valoraciones
     * @return self
     */
    public function add_aggregate_rating($rating, $review_count)
    {
        if (empty($rating) || empty($review_count)) {
            return $this;
        }

        $this->data['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => (float) $rating,
            'reviewCount' => (int) $review_count,
        );

        return $this;
    }

    /**
     * Añade una revisión al schema
     *
     * @param string $author      Nombre del autor
     * @param string $review_body Texto de la revisión
     * @param float  $rating      Puntuación
     * @return self
     */
    public function add_review($author, $review_body, $rating = null)
    {
        if (empty($author) || empty($review_body)) {
            return $this;
        }

        $review = array(
            '@type'      => 'Review',
            'author'     => array(
                '@type' => 'Person',
                'name'  => $author,
            ),
            'reviewBody' => $review_body,
        );

        if (! empty($rating)) {
            $review['reviewRating'] = array(
                '@type'       => 'Rating',
                'ratingValue' => (float) $rating,
            );
        }

        // Si ya hay reviews, añadir a la lista
        if (isset($this->data['review']) && is_array($this->data['review'])) {
            $this->data['review'][] = $review;
        } else {
            $this->data['review'] = array($review);
        }

        return $this;
    }

    /**
     * Establece la ubicación geográfica
     *
     * @param float $latitude  Latitud
     * @param float $longitude Longitud
     * @return self
     */
    public function set_geo_coordinates($latitude, $longitude)
    {
        if (empty($latitude) || empty($longitude)) {
            return $this;
        }

        $this->data['geo'] = array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $latitude,
            'longitude' => (float) $longitude,
        );

        return $this;
    }

    /**
     * Obtiene el JSON-LD como array
     *
     * @return array
     */
    public function get_array()
    {
        return $this->data;
    }

    /**
     * Obtiene el JSON-LD como string
     *
     * @param bool $pretty Formatear el JSON
     * @return string
     */
    public function get_json($pretty = false)
    {
        return wp_json_encode($this->data, $pretty ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Obtiene el script HTML para el JSON-LD
     *
     * @param bool $pretty Formatear el JSON
     * @return string
     */
    public function get_script($pretty = false)
    {
        $json = $this->get_json($pretty);

        return sprintf(
            '<script type="application/ld+json">%s</script>',
            $json
        );
    }

    /**
     * Imprime el script HTML para el JSON-LD
     *
     * @param bool $pretty Formatear el JSON
     */
    public function print_script($pretty = false)
    {
        echo $this->get_script($pretty);
    }

    /**
     * Genera un JSON-LD para un post de tipo servicio
     *
     * @param int $post_id ID del post
     * @return self
     */
    public static function from_service($post_id)
    {
        $builder = new self('Service');

        $post = get_post($post_id);
        if (! $post || 'servicio' !== $post->post_type) {
            return $builder;
        }

        // Datos básicos
        $builder->set_property('name', get_post_meta($post_id, '_dd_nombre_servicio', true) ?: get_the_title($post_id));
        $builder->set_property('description', wp_strip_all_tags($post->post_content));
        $builder->set_property('url', get_permalink($post_id));

        // Imagen
        $hero_image_id = get_post_meta($post_id, '_dd_hero_imagen', true);
        if ($hero_image_id) {
            $builder->add_image($hero_image_id);
        }

        // Precio
        $precio = get_post_meta($post_id, '_dd_precio', true);
        if ($precio) {
            $builder->add_offer($precio, 'EUR');
        }

        // Organización
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $builder->add_organization($site_name, $site_url);

        return $builder;
    }

    /**
     * Genera y devuelve un schema JSON-LD para un post de tipo servicio
     *
     * @param int  $post_id ID del post
     * @param bool $as_html Si es true, devuelve el HTML con el script
     * @return string|array
     */
    public static function get_service_schema($post_id, $as_html = true)
    {
        $builder = self::from_service($post_id);

        if ($as_html) {
            return $builder->get_script();
        }

        return $builder->get_array();
    }

    /**
     * Valida el schema JSON-LD según el tipo especificado
     *
     * @return bool|WP_Error True si es válido, WP_Error si hay errores
     */
    public function validate()
    {
        $type = $this->get_type();

        // Verificar que el tipo sea válido
        if (! isset($this->schema_types[$type])) {
            return new \WP_Error('invalid_schema_type', __('Tipo de schema no válido.', 'seo-content-structure'));
        }

        // Verificar propiedades requeridas según el tipo
        $required_properties = array('name');
        $missing_properties = array();

        foreach ($required_properties as $property) {
            if (! isset($this->data[$property]) || empty($this->data[$property])) {
                $missing_properties[] = $property;
            }
        }

        if (! empty($missing_properties)) {
            return new \WP_Error(
                'missing_required_properties',
                sprintf(
                    __('Faltan propiedades requeridas: %s', 'seo-content-structure'),
                    implode(', ', $missing_properties)
                )
            );
        }

        return true;
    }
}
