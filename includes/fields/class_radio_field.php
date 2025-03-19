<?php

/**
 * Campo de tipo radio
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo radio
 */
class RadioField extends Field
{
    /**
     * Opciones del campo radio
     *
     * @var array
     */
    protected $options = array();

    /**
     * Disposición de las opciones (horizontal, vertical)
     *
     * @var string
     */
    protected $layout = 'vertical';

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'radio';

        // Configurar propiedades específicas del campo radio
        $radio_defaults = array(
            'options' => array(),
            'layout'  => 'vertical',
        );

        $args = wp_parse_args($args, $radio_defaults);

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
        return isset($this->options[$value]) ? $value : '';
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

        // Verificar que el valor exista en las opciones
        if (!empty($value) && !isset($this->options[$value])) {
            return new \WP_Error(
                'invalid_option',
                __('La opción seleccionada no es válida.', 'seo-content-structure')
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
            '<div class="scs-field-wrap scs-field-radio-wrap" style="width:%s;">',
            esc_attr($this->width)
        );

        // Etiqueta
        $html .= sprintf(
            '<label><strong>%s</strong>%s</label>',
            esc_html($this->label),
            $this->required ? ' <span class="required">*</span>' : ''
        );

        // Instrucciones
        if (!empty($this->instructions)) {
            $html .= sprintf(
                '<p class="description">%s</p>',
                esc_html($this->instructions)
            );
        }

        // Contenedor de opciones
        $html .= sprintf(
            '<div class="scs-radio-options scs-radio-layout-%s">',
            esc_attr($this->layout)
        );

        // Opciones radio
        $i = 0;
        foreach ($this->options as $option_value => $option_label) {
            $option_id = $field_id . '_' . $i;
            $html .= sprintf(
                '<div class="scs-radio-option">
                    <input type="radio" id="%s" name="%s" value="%s" class="scs-field scs-radio-field" %s>
                    <label for="%s">%s</label>
                </div>',
                esc_attr($option_id),
                esc_attr($field_name),
                esc_attr($option_value),
                checked($value, $option_value, false),
                esc_attr($option_id),
                esc_html($option_label)
            );
            $i++;
        }

        $html .= '</div>'; // .scs-radio-options
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

        if (empty($value) || !isset($this->options[$value])) {
            return '';
        }

        // Generar el HTML para mostrar la opción seleccionada
        $html = sprintf(
            '<div class="scs-field scs-field-radio %s">',
            esc_attr($this->css_class)
        );

        $html .= esc_html($this->options[$value]);

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene el texto de la opción seleccionada
     *
     * @return string
     */
    public function get_selected_option_text()
    {
        $value = $this->get_value();
        return isset($this->options[$value]) ? $this->options[$value] : '';
    }
}
