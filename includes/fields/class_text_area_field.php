<?php

/**
 * Campo de tipo textarea
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo textarea
 */
class TextareaField extends Field
{
    /**
     * Número de filas del textarea
     *
     * @var int
     */
    protected $rows = 5;

    /**
     * Número de columnas del textarea
     *
     * @var int
     */
    protected $cols = 50;

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
        $args['type'] = 'textarea';

        // Configurar propiedades específicas del campo textarea
        $textarea_defaults = array(
            'rows'       => 5,
            'cols'       => 50,
            'min_length' => 0,
            'max_length' => 0,
        );

        $args = wp_parse_args($args, $textarea_defaults);

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
        return sanitize_textarea_field($value);
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
            '<div class="scs-field-wrap scs-field-textarea-wrap" style="width:%s;">',
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
            'class'       => 'scs-field scs-textarea-field',
            'placeholder' => $this->placeholder,
            'rows'        => $this->rows,
            'cols'        => $this->cols
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

        // Campo textarea
        $html .= sprintf(
            '<textarea %s>%s</textarea>',
            $this->attributes_to_string($attributes),
            esc_textarea($value)
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
            '<div class="scs-field scs-field-textarea %s">',
            esc_attr($this->css_class)
        );

        $html .= wpautop(esc_html($value));

        $html .= '</div>';

        return $html;
    }
}
