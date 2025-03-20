<?php

/**
 * Campo de tipo checkbox
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo checkbox
 */
class CheckboxField extends Field
{
    /**
     * Texto mostrado junto al checkbox
     *
     * @var string
     */
    protected $checkbox_label = '';

    /**
     * Valor cuando está marcado
     *
     * @var string
     */
    protected $checked_value = '1';

    /**
     * Valor cuando no está marcado
     *
     * @var string
     */
    protected $unchecked_value = '0';

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'checkbox';

        // Configurar propiedades específicas del campo checkbox
        $checkbox_defaults = array(
            'checkbox_label'  => '',
            'checked_value'   => '1',
            'unchecked_value' => '0',
        );

        $args = wp_parse_args($args, $checkbox_defaults);

        // Si no se establece un checkbox_label, usar el label general
        if (empty($args['checkbox_label']) && !empty($args['label'])) {
            $args['checkbox_label'] = $args['label'];
        }

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
        return $value == $this->checked_value ? $this->checked_value : $this->unchecked_value;
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
        $is_checked = $value == $this->checked_value;

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-checkbox-wrap" style="width:%s;">',
            esc_attr($this->width)
        );

        // Etiqueta principal (opcional para checkbox)
        if (!empty($this->label) && $this->label !== $this->checkbox_label) {
            $html .= sprintf(
                '<label><strong>%s</strong>%s</label>',
                esc_html($this->label),
                $this->required ? ' <span class="required">*</span>' : ''
            );
        }

        // Instrucciones
        if (!empty($this->instructions)) {
            $html .= sprintf(
                '<p class="description">%s</p>',
                esc_html($this->instructions)
            );
        }

        // Campo oculto para el valor no marcado
        $html .= sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            esc_attr($field_name),
            esc_attr($this->unchecked_value)
        );

        // Campo checkbox
        $html .= '<div class="scs-checkbox-container">';
        $html .= sprintf(
            '<input type="checkbox" id="%s" name="%s" value="%s" class="scs-field scs-checkbox-field" %s />',
            esc_attr($field_id),
            esc_attr($field_name),
            esc_attr($this->checked_value),
            checked($is_checked, true, false)
        );

        // Etiqueta del checkbox
        $html .= sprintf(
            '<label for="%s">%s%s</label>',
            esc_attr($field_id),
            esc_html($this->checkbox_label),
            $this->required ? ' <span class="required">*</span>' : ''
        );
        $html .= '</div>'; // .scs-checkbox-container

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
        $is_checked = $value == $this->checked_value;

        // Generar el HTML para mostrar el estado del checkbox
        $html = sprintf(
            '<div class="scs-field scs-field-checkbox %s">',
            esc_attr($this->css_class)
        );

        if ($is_checked) {
            $html .= sprintf(
                '<span class="scs-checkbox-checked">%s</span>',
                esc_html($this->checkbox_label)
            );
        } else {
            $html .= sprintf(
                '<span class="scs-checkbox-unchecked">%s</span>',
                esc_html($this->checkbox_label)
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Verifica si el checkbox está marcado
     *
     * @return bool
     */
    public function is_checked()
    {
        return $this->get_value() == $this->checked_value;
    }
}
