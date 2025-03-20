<?php

/**
 * Integración con Elementor
 *
 * @package SEOContentStructure
 * @subpackage Integrations
 */

namespace SEOContentStructure\Integrations;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\PostTypes\PostTypeFactory;
use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;

/**
 * Clase para integración con Elementor
 */
class ElementorIntegration implements Registrable
{
    /**
     * Factory de post types
     *
     * @var PostTypeFactory
     */
    protected $post_type_factory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->post_type_factory = new PostTypeFactory();
    }

    /**
     * Registra hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // Registrar los campos personalizados en Elementor
        $loader->add_filter('elementor/dynamic_tags/custom_fields/fields_options', $this, 'register_custom_fields');

        // Registrar las etiquetas dinámicas
        $loader->add_action('elementor/dynamic_tags/register_tags', $this, 'register_dynamic_tags');
    }

    /**
     * Registra los campos personalizados en Elementor
     *
     * @param array $options Opciones actuales de campos
     * @return array Opciones modificadas
     */
    public function register_custom_fields($options)
    {
        // Obtener todos los post types registrados
        $post_types = $this->post_type_factory->get_registered_post_types();

        // Recorrer cada post type y sus campos
        foreach ($post_types as $post_type) {
            $fields = $post_type->get_fields();

            foreach ($fields as $field) {
                $field_name = $field->get_name();
                $field_label = $field->get_label();

                // Añadir el campo a las opciones
                $options[$field_name] = $field_label;
            }
        }

        return $options;
    }

    /**
     * Registra las etiquetas dinámicas para Elementor
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Gestor de etiquetas dinámicas
     */
    public function register_dynamic_tags($dynamic_tags)
    {
        // Solo continuar si Elementor Pro está activo
        if (! class_exists('\ElementorPro\Plugin')) {
            return;
        }

        // Incluir las clases de etiquetas dinámicas
        require_once SCS_PLUGIN_DIR . 'includes/integrations/elementor/class-image-field-tag.php';
        require_once SCS_PLUGIN_DIR . 'includes/integrations/elementor/class-text-field-tag.php';

        // Registrar las etiquetas
        $dynamic_tags->register_tag('SEOContentStructure\Integrations\Elementor\ImageFieldTag');
        $dynamic_tags->register_tag('SEOContentStructure\Integrations\Elementor\TextFieldTag');
    }
}

/**
 * Clase para etiqueta dinámica de campo de imagen en Elementor
 */

namespace SEOContentStructure\Integrations\Elementor;

/**
 * Etiqueta dinámica para campos de imagen
 */
class ImageFieldTag extends \ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag
{
    /**
     * Obtiene el nombre de la etiqueta
     *
     * @return string
     */
    public function get_name()
    {
        return 'scs-image-field';
    }

    /**
     * Obtiene el título de la etiqueta
     *
     * @return string
     */
    public function get_title()
    {
        return __('Imagen de Campo SCS', 'seo-content-structure');
    }

    /**
     * Obtiene el grupo de la etiqueta
     *
     * @return string
     */
    public function get_group()
    {
        return 'site';
    }

    /**
     * Obtiene las categorías
     *
     * @return array
     */
    public function get_categories()
    {
        return [
            \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
        ];
    }

    /**
     * Registra los controles
     */
    protected function register_controls()
    {
        // Factory de post types
        $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $post_types = $post_type_factory->get_registered_post_types();

        // Recopilar campos de imagen de todos los post types
        $image_fields = array();

        foreach ($post_types as $post_type) {
            $fields = $post_type->get_fields();

            foreach ($fields as $field) {
                if ('image' === $field->get_type()) {
                    $field_name = $field->get_name();
                    $field_label = $field->get_label();
                    $post_type_label = $post_type->get_labels()['singular_name'];

                    $image_fields[$field_name] = sprintf(
                        '%s (%s)',
                        $field_label,
                        $post_type_label
                    );
                }
            }
        }

        // Si no hay campos de imagen, agregar un mensaje
        if (empty($image_fields)) {
            $image_fields[''] = __('No hay campos de imagen disponibles', 'seo-content-structure');
        }

        // Añadir control para seleccionar el campo
        $this->add_control(
            'scs_image_field',
            [
                'label'   => __('Campo de Imagen', 'seo-content-structure'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $image_fields,
                'default' => key($image_fields),
            ]
        );
    }

    /**
     * Obtiene el valor de la etiqueta
     *
     * @param array $options Opciones para obtener el valor
     * @return array Datos de la imagen
     */
    public function get_value(array $options = [])
    {
        $field_name = $this->get_settings('scs_image_field');

        if (empty($field_name)) {
            return [];
        }

        $post_id = get_the_ID();
        $attachment_id = get_post_meta($post_id, $field_name, true);

        if (empty($attachment_id)) {
            return [];
        }

        $image_data = [
            'id'  => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
        ];

        return $image_data;
    }
}

/**
 * Clase para etiqueta dinámica de campo de texto en Elementor
 */
class TextFieldTag extends \ElementorPro\Modules\DynamicTags\Tags\Base\Tag
{
    /**
     * Obtiene el nombre de la etiqueta
     *
     * @return string
     */
    public function get_name()
    {
        return 'scs-text-field';
    }

    /**
     * Obtiene el título de la etiqueta
     *
     * @return string
     */
    public function get_title()
    {
        return __('Texto de Campo SCS', 'seo-content-structure');
    }

    /**
     * Obtiene el grupo de la etiqueta
     *
     * @return string
     */
    public function get_group()
    {
        return 'site';
    }

    /**
     * Obtiene las categorías
     *
     * @return array
     */
    public function get_categories()
    {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    /**
     * Registra los controles
     */
    protected function register_controls()
    {
        // Factory de post types
        $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $post_types = $post_type_factory->get_registered_post_types();

        // Recopilar campos de texto de todos los post types
        $text_fields = array();

        foreach ($post_types as $post_type) {
            $fields = $post_type->get_fields();

            foreach ($fields as $field) {
                // Solo campos de texto y número
                if (in_array($field->get_type(), array('text', 'textarea', 'number', 'email', 'url'))) {
                    $field_name = $field->get_name();
                    $field_label = $field->get_label();
                    $post_type_label = $post_type->get_labels()['singular_name'];

                    $text_fields[$field_name] = sprintf(
                        '%s (%s)',
                        $field_label,
                        $post_type_label
                    );
                }
            }
        }

        // Si no hay campos de texto, agregar un mensaje
        if (empty($text_fields)) {
            $text_fields[''] = __('No hay campos de texto disponibles', 'seo-content-structure');
        }

        // Añadir control para seleccionar el campo
        $this->add_control(
            'scs_text_field',
            [
                'label'   => __('Campo de Texto', 'seo-content-structure'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $text_fields,
                'default' => key($text_fields),
            ]
        );
    }

    /**
     * Renderiza la etiqueta
     */
    public function render()
    {
        $field_name = $this->get_settings('scs_text_field');

        if (empty($field_name)) {
            return;
        }

        $post_id = get_the_ID();
        $value = get_post_meta($post_id, $field_name, true);

        if (is_array($value)) {
            $value = wp_json_encode($value);
        }

        echo wp_kses_post($value);
    }
}
