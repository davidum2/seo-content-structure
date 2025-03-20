<?php

/**
 * Campo de tipo select
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo select
 */
class SelectField extends Field
{
    /**
     * Opciones del campo select
     *
     * @var array
     */
    protected $options = array();

    /**
     * Permite múltiples selecciones
     *
     * @var bool
     */
    protected $multiple = false;

    /**
     * Permite selección vacía
     *
     * @var bool
     */
    protected $allow_null = false;

    /**
     * Texto para la opción vacía
     *
     * @var string
     */
    protected $empty_text = '';

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'select';

        // Configurar propiedades específicas del campo select
        $select_defaults = array(
            'options'    => array(),
            'multiple'   => false,
            'allow_null' => false,
            'empty_text' => __('- Seleccionar -', 'seo-content-structure'),
        );

        $args = wp_parse_args($args, $select_defaults);

        // Llamar al constructor padre
        parent::__construct($args);
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return string|array
     */
    public function sanitize($value)
    {
        // Si es selección múltiple
        if ($this->multiple) {
            if (!is_array($value)) {
                $value = array($value);
            }

            // Filtrar valores válidos
            $valid_values = array();
            foreach ($value as $item) {
                if (isset($this->options[$item])) {
                    $valid_values[] = $item;
                }
            }

            return $valid_values;
        } else {
            // Selección única
            return isset($this->options[$value]) ? $value : '';
        }
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

        // Para selección múltiple
        if ($this->multiple) {
            if (!is_array($value)) {
                return new \WP_Error(
                    'invalid_value',
                    __('Valor no válido para selección múltiple.', 'seo-content-structure')
                );
            }

            // Verificar que todos los valores existan en las opciones
            foreach ($value as $item) {
                if (!isset($this->options[$item])) {
                    return new \WP_Error(
                        'invalid_option',
                        __('Una o más opciones seleccionadas no son válidas.', 'seo-content-structure')
                    );
                }
            }
        } else {
            // Selección única
            if (!empty($value) && !isset($this->options[$value])) {
                return new \WP_Error(
                    'invalid_option',
                    __('La opción seleccionada no es válida.', 'seo-content-structure')
                );
            }
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

        // Preparar el nombre para selección múltiple
        if ($this->multiple) {
            $field_name .= '[]';
        }

        // Obtener el valor actual
        $value = $this->get_value();

        // Asegurar que para selección múltiple el valor sea un array
        if ($this->multiple && !is_array($value)) {
            $value = empty($value) ? array() : array($value);
        }

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-select-wrap" style="width:%s;">',
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
            'id'    => $field_id,
            'name'  => $field_name,
            'class' => 'scs-field scs-select-field'
        );

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        if ($this->multiple) {
            $attributes['multiple'] = 'multiple';
        }

        // Campo select
        $html .= sprintf('<select %s>', $this->attributes_to_string($attributes));

        // Opción vacía
        if ($this->allow_null || empty($this->options)) {
            $html .= sprintf(
                '<option value="">%s</option>',
                esc_html($this->empty_text)
            );
        }

        // Opciones del select
        foreach ($this->options as $option_value => $option_label) {
            if ($this->multiple) {
                $selected = in_array($option_value, $value) ? ' selected="selected"' : '';
            } else {
                $selected = selected($value, $option_value, false);
            }

            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($option_value),
                $selected,
                esc_html($option_label)
            );
        }

        $html .= '</select>';

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

        // Generar el HTML para mostrar la selección
        $html = sprintf(
            '<div class="scs-field scs-field-select %s">',
            esc_attr($this->css_class)
        );

        if ($this->multiple) {
            if (!is_array($value)) {
                $value = array($value);
            }

            $html .= '<ul class="scs-select-list">';
            foreach ($value as $item) {
                if (isset($this->options[$item])) {
                    $html .= sprintf(
                        '<li>%s</li>',
                        esc_html($this->options[$item])
                    );
                }
            }
            $html .= '</ul>';
        } else {
            if (isset($this->options[$value])) {
                $html .= esc_html($this->options[$value]);
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene el texto de la opción seleccionada
     *
     * @return string|array
     */
    public function get_selected_option_text()
    {
        $value = $this->get_value();

        if (empty($value)) {
            return '';
        }

        if ($this->multiple) {
            if (!is_array($value)) {
                $value = array($value);
            }

            $selected_options = array();
            foreach ($value as $item) {
                if (isset($this->options[$item])) {
                    $selected_options[] = $this->options[$item];
                }
            }

            return $selected_options;
        } else {
            return isset($this->options[$value]) ? $this->options[$value] : '';
        }
    }
}
