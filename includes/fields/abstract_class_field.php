<?php

/**
 * Clase abstracta para los campos personalizados
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

use SEOContentStructure\Core\Interfaces\Renderable;
use SEOContentStructure\Utilities\Validator;

/**
 * Clase abstracta que todos los tipos de campos deben extender
 */
abstract class Field implements Renderable
{
    /**
     * ID único del campo
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre del campo
     *
     * @var string
     */
    protected $name;

    /**
     * Etiqueta del campo
     *
     * @var string
     */
    protected $label;

    /**
     * Tipo de campo
     *
     * @var string
     */
    protected $type;

    /**
     * Valor por defecto del campo
     *
     * @var mixed
     */
    protected $default_value;

    /**
     * Opciones del campo
     *
     * @var array
     */
    protected $options = array();

    /**
     * Placeholder del campo
     *
     * @var string
     */
    protected $placeholder = '';

    /**
     * Valor del campo
     *
     * @var mixed
     */
    protected $value;

    /**
     * Grupo al que pertenece el campo
     *
     * @var string
     */
    protected $group;

    /**
     * Indicador si el campo es requerido
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Instrucciones del campo
     *
     * @var string
     */
    protected $instructions = '';

    /**
     * Ancho del campo (CSS)
     *
     * @var string
     */
    protected $width = '100%';

    /**
     * Clase CSS del campo
     *
     * @var string
     */
    protected $css_class = '';

    /**
     * Si el campo tiene mapeo para JSON-LD
     *
     * @var string
     */
    protected $schema_property = '';

    /**
     * Validador de campos
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        $this->validator = new Validator();

        // Configurar propiedades desde los argumentos
        $this->set_properties($args);
    }

    /**
     * Establece las propiedades del campo desde un array de argumentos
     *
     * @param array $args Argumentos del campo
     */
    protected function set_properties($args)
    {
        $defaults = array(
            'id'               => uniqid('field_'),
            'name'             => '',
            'label'            => __('Field', 'seo-content-structure'),
            'type'             => 'text',
            'default_value'    => '',
            'options'          => array(),
            'placeholder'      => '',
            'value'            => null,
            'group'            => '',
            'required'         => false,
            'instructions'     => '',
            'width'            => '100%',
            'css_class'        => '',
            'schema_property'  => '',
        );

        $args = wp_parse_args($args, $defaults);

        foreach ($args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Asegurar que el nombre del campo esté establecido
        if (empty($this->name) && ! empty($this->id)) {
            $this->name = $this->id;
        }
    }

    /**
     * Obtiene el ID del campo
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Obtiene el nombre del campo
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Obtiene la etiqueta del campo
     *
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * Obtiene el tipo del campo
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Obtiene el valor del campo
     *
     * @return mixed
     */
    public function get_value()
    {
        return $this->value !== null ? $this->value : $this->default_value;
    }

    /**
     * Establece el valor del campo
     *
     * @param mixed $value Valor del campo
     * @return self
     */
    public function set_value($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Verifica si el campo es requerido
     *
     * @return bool
     */
    public function is_required()
    {
        return $this->required;
    }

    /**
     * Obtiene el grupo al que pertenece el campo
     *
     * @return string
     */
    public function get_group()
    {
        return $this->group;
    }

    /**
     * Obtiene la propiedad de schema JSON-LD asociada
     *
     * @return string
     */
    public function get_schema_property()
    {
        return $this->schema_property;
    }

    /**
     * Establece la propiedad de schema JSON-LD
     *
     * @param string $property Nombre de la propiedad
     * @return self
     */
    public function set_schema_property($property)
    {
        $this->schema_property = $property;
        return $this;
    }

    /**
     * Obtiene los atributos HTML del campo
     *
     * @return array
     */
    protected function get_attributes()
    {
        $attributes = array(
            'id'           => $this->id,
            'name'         => $this->name,
            'class'        => "scs-field scs-field-{$this->type} {$this->css_class}",
            'placeholder'  => $this->placeholder,
        );

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        return $attributes;
    }

    /**
     * Convierte un array de atributos en una cadena HTML
     *
     * @param array $attributes Atributos HTML
     * @return string
     */
    protected function attributes_to_string($attributes)
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if (is_bool($value) && $value) {
                $html .= sprintf(' %s', esc_attr($key));
            } elseif (! is_bool($value)) {
                $html .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
        }

        return $html;
    }

    /**
     * Valida el valor del campo
     *
     * @return bool|WP_Error True si es válido, WP_Error si no
     */
    public function validate()
    {
        // Verificar si es requerido
        if ($this->required && empty($this->value)) {
            return new \WP_Error('field_required', sprintf(__('%s es requerido.', 'seo-content-structure'), $this->label));
        }

        return true;
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return mixed
     */
    public function sanitize($value)
    {
        return sanitize_text_field($value);
    }

    /**
     * Convierte el campo a un array para guardar o usar en la API
     *
     * @return array
     */
    public function to_array()
    {
        return array(
            'id'              => $this->id,
            'name'            => $this->name,
            'label'           => $this->label,
            'type'            => $this->type,
            'default_value'   => $this->default_value,
            'options'         => $this->options,
            'placeholder'     => $this->placeholder,
            'value'           => $this->get_value(),
            'group'           => $this->group,
            'required'        => $this->required,
            'instructions'    => $this->instructions,
            'width'           => $this->width,
            'css_class'       => $this->css_class,
            'schema_property' => $this->schema_property,
        );
    }

    /**
     * Renderiza el campo admin para la edición en backend
     *
     * @return string HTML del campo
     */
    abstract public function render_admin();

    /**
     * Renderiza el campo para mostrar en frontend (sólo visualización)
     *
     * @return string HTML del campo
     */
    abstract public function render_frontend();
}
