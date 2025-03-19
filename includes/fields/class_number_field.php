<?php

/**
 * Campo de tipo número
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo número
 */
class NumberField extends Field
{
    /**
     * Valor mínimo permitido
     *
     * @var float
     */
    protected $min = '';

    /**
     * Valor máximo permitido
     *
     * @var float
     */
    protected $max = '';

    /**
     * Incremento del valor
     *
     * @var float
     */
    protected $step = 1;

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'number';

        // Configurar propiedades específicas del campo de número
        $number_defaults = array(
            'min'  => '',
            'max'  => '',
            'step' => 1,
        );

        $args = wp_parse_args($args, $number_defaults);

        // Llamar al constructor padre
        parent::__construct($args);
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return float|string
     */
    public function sanitize($value)
    {
        // Si está vacío, devolver cadena vacía
        if ('' === $value) {
            return '';
        }

        // Asegurarse de que sea un número
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Convertir a float para procesar
        $value = (float) $value;

        // Aplicar restricciones de min/max
        if ('' !== $this->min && $value < $this->min) {
            $value = $this->min;
        }

        if ('' !== $this->max && $value > $this->max) {
            $value = $this->max;
        }

        return $value;
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
        if ('' === $value && !$this->required) {
            return true;
        }

        // Verificar que sea un número
        if (!is_numeric($value)) {
            return new \WP_Error(
                'not_a_number',
                sprintf(
                    __('%s debe ser un número.', 'seo-content-structure'),
                    $this->label
                )
            );
        }

        // Convertir a float para validar
        $value = (float) $value;

        // Verificar valor mínimo
        if ('' !== $this->min && $value < $this->min) {
            return new \WP_Error(
                'min_value',
                sprintf(
                    __('%s debe ser mayor o igual a %s.', 'seo-content-structure'),
                    $this->label,
                    $this->min
                )
            );
        }

        // Verificar valor máximo
        if ('' !== $this->max && $value > $this->max) {
            return new \WP_Error(
                'max_value',
                sprintf(
                    __('%s debe ser menor o igual a %s.', 'seo-content-structure'),
                    $this->label,
                    $this->max
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
            '<div class="scs-field-wrap scs-field-number-wrap" style="width:%s;">',
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
            'type'        => 'number',
            'value'       => esc_attr($value),
            'class'       => 'scs-field scs-number-field',
            'placeholder' => $this->placeholder,
            'step'        => $this->step
        );

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        if ('' !== $this->min) {
            $attributes['min'] = $this->min;
        }

        if ('' !== $this->max) {
            $attributes['max'] = $this->max;
        }

        // Campo de número
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

        if ('' === $value) {
            return '';
        }

        // Generar el HTML para mostrar el número
        $html = sprintf(
            '<div class="scs-field scs-field-number %s">',
            esc_attr($this->css_class)
        );

        $html .= esc_html($value);

        $html .= '</div>';

        return $html;
    }

    /**
     * Formatea el número según un formato específico
     *
     * @param int    $decimals       Número de decimales
     * @param string $decimal_point  Separador de decimales
     * @param string $thousands_sep  Separador de miles
     * @return string
     */
    public function format_number($decimals = 0, $decimal_point = '.', $thousands_sep = ',')
    {
        $value = $this->get_value();

        if ('' === $value) {
            return '';
        }

        return number_format($value, $decimals, $decimal_point, $thousands_sep);
    }
}
