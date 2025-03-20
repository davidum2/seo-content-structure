<?php

/**
 * Campo de tipo texto
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo texto
 */
class TextField extends Field
{
    /**
     * Longitud mínima permitida
     *
     * @var int
     */
    protected $min_length = 0;

    /**
     * Longitud máxima permitida
     *
     * @var int
     */
    protected $max_length = 0;

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'text';

        // Configurar propiedades específicas del campo de texto
        $text_defaults = array(
            'min_length' => 0,
            'max_length' => 0,
        );

        $args = wp_parse_args($args, $text_defaults);

        // Llamar al constructor padre
        parent::__construct($args);
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return string
     */
    public function sanitize($value)
    {
        return sanitize_text_field($value);
    }

    /**
     * Valida el valor del campo
     *
     * @return bool|WP_Error True si es válido, WP_Error si no
     */
    public function validate()
    {
        // Primero validar con el método padre
        $validation = parent::validate();
        if (is_wp_error($validation)) {
            return $validation;
        }

        $value = $this->get_value();

        // Si está vacío y no es requerido, está bien
        if (empty($value) && !$this->required) {
            return true;
        }

        // Verificar longitud mínima
        if ($this->min_length > 0 && mb_strlen($value) < $this->min_length) {
            return new \WP_Error(
                'min_length',
                sprintf(
                    __('%s debe tener al menos %d caracteres.', 'seo-content-structure'),
                    $this->label,
                    $this->min_length
                )
            );
        }

        // Verificar longitud máxima
        if ($this->max_length > 0 && mb_strlen($value) > $this->max_length) {
            return new \WP_Error(
                'max_length',
                sprintf(
                    __('%s debe tener como máximo %d caracteres.', 'seo-content-structure'),
                    $this->label,
                    $this->max_length
                )
            );
        }

        return true;
    }

    /**
     * Renderiza el campo en el admin
     *
     * @return string HTML del campo
     */
    public function render_admin()
    {
        // Generar un ID único para este campo
        $field_id = $this->id;
        $field_name = $this->name;

        // Obtener el valor actual
        $value = $this->get_value();

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-text-wrap" style="width:%s;">',
            esc_attr($this->width)
        );

        // Etiqueta
        $html .= sprintf(
            '<label for="%s"><strong>%s</strong>%s</label>',
            esc_attr($field_id),
            esc_html($this->label),
            $this->required ? ' <span class="required">*</span>' : ''
        );

        // Instrucciones
        if (! empty($this->instructions)) {
            $html .= sprintf(
                '<p class="description">%s</p>',
                esc_html($this->instructions)
            );
        }

        // Atributos adicionales para el campo
        $attributes = array(
            'id'          => $field_id,
            'name'        => $field_name,
            'type'        => 'text',
            'value'       => esc_attr($value),
            'class'       => 'scs-field scs-text-field',
            'placeholder' => $this->placeholder
        );

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        if ($this->min_length > 0) {
            $attributes['minlength'] = $this->min_length;
        }

        if ($this->max_length > 0) {
            $attributes['maxlength'] = $this->max_length;
        }

        // Campo de texto
        $html .= sprintf(
            '<input %s />',
            $this->attributes_to_string($attributes)
        );

        $html .= '</div>'; // .scs-field-wrap

        return $html;
    }

    /**
     * Renderiza el campo para el frontend
     *
     * @return string HTML del campo
     */
    public function render_frontend()
    {
        $value = $this->get_value();

        if (empty($value)) {
            return '';
        }

        // Generar el HTML para mostrar el texto
        $html = sprintf(
            '<div class="scs-field scs-field-text %s">',
            esc_attr($this->css_class)
        );

        $html .= esc_html($value);

        $html .= '</div>';

        return $html;
    }
}
