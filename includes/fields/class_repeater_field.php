<?php

/**
 * Campo de tipo repeater para grupos de campos repetibles
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

use SEOContentStructure\Fields\Field;
use SEOContentStructure\Fields\FieldFactory;

/**
 * Clase que implementa un campo de tipo repeater
 */
class RepeaterField extends Field
{
    /**
     * Subcampos dentro del repeater
     *
     * @var array
     */
    protected $sub_fields = array();

    /**
     * Texto del botón para añadir nueva fila
     *
     * @var string
     */
    protected $button_text = '';

    /**
     * Mínimo de filas requeridas
     *
     * @var int
     */
    protected $min_rows = 0;

    /**
     * Máximo de filas permitidas
     *
     * @var int
     */
    protected $max_rows = 0;

    /**
     * Instancia de la fábrica de campos
     *
     * @var FieldFactory
     */
    protected $field_factory;

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'repeater';

        // Configurar propiedades específicas del repeater
        $repeater_defaults = array(
            'button_text' => __('Añadir fila', 'seo-content-structure'),
            'min_rows'    => 0,
            'max_rows'    => 0,
            'sub_fields'  => array(),
        );

        $args = wp_parse_args($args, $repeater_defaults);

        // Inicializar la fábrica de campos
        $this->field_factory = new FieldFactory();

        // Extraer propiedades específicas antes de llamar al constructor padre
        $this->button_text = $args['button_text'];
        $this->min_rows = $args['min_rows'];
        $this->max_rows = $args['max_rows'];
        $sub_fields_config = $args['sub_fields'];

        // Eliminar para que el constructor padre no las procese
        unset($args['sub_fields']);

        // Llamar al constructor padre
        parent::__construct($args);

        // Procesar los subcampos
        $this->process_sub_fields($sub_fields_config);

        // Añadir scripts y estilos para este tipo de campo
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Procesa la configuración de subcampos
     *
     * @param array $sub_fields_config Configuración de subcampos
     */
    protected function process_sub_fields($sub_fields_config)
    {
        if (empty($sub_fields_config) || ! is_array($sub_fields_config)) {
            return;
        }

        foreach ($sub_fields_config as $field_config) {
            // Asegurarse de que tiene un ID
            if (! isset($field_config['id'])) {
                continue;
            }

            // Crear un ID único prefijando el ID del repeater
            $field_id = $this->id . '_' . $field_config['id'];
            $field_config['id'] = $field_id;

            // Asegurarse de que tiene un nombre
            if (! isset($field_config['name'])) {
                $field_config['name'] = $field_id;
            }

            // Crear el campo
            $field = $this->field_factory->create($field_config);

            // Añadir a los subcampos
            if ($field) {
                $this->sub_fields[$field_config['id']] = $field;
            }
        }
    }

    /**
     * Enqueue scripts y estilos para el campo repeater
     */
    public function enqueue_scripts()
    {
        // Solo cargar en las páginas de administración relevantes
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->base, array('post', 'post-new'))) {
            return;
        }

        // Enqueue el script
        wp_enqueue_script(
            'scs-repeater-field',
            SCS_PLUGIN_URL . 'assets/js/repeater-field.js',
            array('jquery', 'jquery-ui-sortable'),
            SCS_VERSION,
            true
        );

        // Enqueue estilos
        wp_enqueue_style(
            'scs-repeater-field',
            SCS_PLUGIN_URL . 'assets/css/repeater-field.css',
            array(),
            SCS_VERSION
        );
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return string JSON con los valores sanitizados
     */
    public function sanitize($value)
    {
        // Si es un string JSON, convertirlo a array
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                // Si no se pudo decodificar, crear un array vacío
                $value = array();
            }
        }

        // Si no es un array, crear uno vacío
        if (! is_array($value)) {
            $value = array();
        }

        // Sanitizar cada subvalor
        foreach ($value as &$row) {
            foreach ($row as $key => &$field_value) {
                // Encontrar el subcampo correspondiente
                $sub_field_id = $this->id . '_' . $key;
                if (isset($this->sub_fields[$sub_field_id])) {
                    $field_value = $this->sub_fields[$sub_field_id]->sanitize($field_value);
                } else {
                    // Si no existe el subcampo, usar sanitize_text_field por defecto
                    $field_value = sanitize_text_field($field_value);
                }
            }
        }

        // Convertir a JSON para almacenar
        return wp_json_encode($value);
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

        // Verificar número mínimo de filas
        $value = $this->get_value();
        $rows = is_string($value) ? json_decode($value, true) : $value;

        if (is_array($rows)) {
            $row_count = count($rows);

            if ($this->min_rows > 0 && $row_count < $this->min_rows) {
                return new \WP_Error(
                    'min_rows',
                    sprintf(
                        __('%s requiere al menos %d filas.', 'seo-content-structure'),
                        $this->label,
                        $this->min_rows
                    )
                );
            }

            if ($this->max_rows > 0 && $row_count > $this->max_rows) {
                return new \WP_Error(
                    'max_rows',
                    sprintf(
                        __('%s permite un máximo de %d filas.', 'seo-content-structure'),
                        $this->label,
                        $this->max_rows
                    )
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

        // Obtener el valor actual
        $value = $this->get_value();

        // Si es un string JSON, convertirlo a array
        $rows = array();
        if (is_string($value) && ! empty($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $rows = $decoded;
            }
        } elseif (is_array($value)) {
            $rows = $value;
        }

        // Si no hay filas o está vacío, agregar una fila en blanco
        if (empty($rows)) {
            $rows = array(array());
        }

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-repeater-wrap" style="width:%s;">',
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

        // Contenedor del repeater
        $html .= sprintf(
            '<div id="%s_container" class="scs-repeater-container" data-field-id="%s" data-min-rows="%d" data-max-rows="%d">',
            esc_attr($field_id),
            esc_attr($field_id),
            intval($this->min_rows),
            intval($this->max_rows)
        );

        // Plantilla para nueva fila (oculta)
        $html .= sprintf(
            '<script type="text/template" id="%s_template" class="scs-repeater-template">',
            esc_attr($field_id)
        );
        $html .= $this->render_row(array(), '{{index}}');
        $html .= '</script>';

        // Encabezados si hay subcampos
        if (! empty($this->sub_fields)) {
            $html .= '<div class="scs-repeater-header">';
            foreach ($this->sub_fields as $sub_field) {
                $html .= sprintf(
                    '<div class="scs-repeater-cell scs-repeater-header-cell">%s</div>',
                    esc_html($sub_field->get_label())
                );
            }
            $html .= '<div class="scs-repeater-cell scs-repeater-actions-cell"></div>';
            $html .= '</div>'; // .scs-repeater-header
        }

        // Renderizar filas existentes
        $html .= '<div class="scs-repeater-rows">';
        foreach ($rows as $index => $row) {
            $html .= $this->render_row($row, $index);
        }
        $html .= '</div>'; // .scs-repeater-rows

        $html .= '</div>'; // .scs-repeater-container

        // Botón para añadir nueva fila
        $html .= sprintf(
            '<button type="button" class="button scs-repeater-add-row" data-target="%s_container">%s</button>',
            esc_attr($field_id),
            esc_html($this->button_text)
        );

        // Campo oculto para guardar todos los valores como JSON
        $html .= sprintf(
            '<input type="hidden" id="%s" name="%s" value=\'%s\' class="scs-repeater-input" />',
            esc_attr($field_id),
            esc_attr($field_name),
            esc_attr(is_string($value) ? $value : wp_json_encode($rows))
        );

        $html .= '</div>'; // .scs-field-wrap

        return $html;
    }

    /**
     * Renderiza una fila del repeater
     *
     * @param array  $row   Datos de la fila
     * @param string $index Índice de la fila
     * @return string HTML de la fila
     */
    protected function render_row($row, $index)
    {
        $html = sprintf(
            '<div class="scs-repeater-row" data-row="%s">',
            esc_attr($index)
        );

        // Renderizar cada subcampo
        foreach ($this->sub_fields as $sub_field) {
            $sub_field_id = $sub_field->get_id();
            $field_key = str_replace($this->id . '_', '', $sub_field_id);

            // Establecer el nombre del campo para que se guarde correctamente
            $sub_field_name = $this->id . '[' . $index . '][' . $field_key . ']';

            // Establecer el valor si existe
            if (isset($row[$field_key])) {
                $sub_field->set_value($row[$field_key]);
            } else {
                $sub_field->set_value(null);
            }

            // Renderizar el subcampo
            $html .= sprintf(
                '<div class="scs-repeater-cell">%s</div>',
                $this->render_sub_field($sub_field, $sub_field_name, $index)
            );
        }

        // Acciones de la fila (mover, eliminar)
        $html .= '<div class="scs-repeater-cell scs-repeater-actions-cell">';

        // Botón de mover (drag handle)
        $html .= '<span class="scs-repeater-sort-handle dashicons dashicons-move"></span>';

        // Botón de eliminar
        $html .= sprintf(
            '<button type="button" class="button scs-repeater-remove-row" title="%s">%s</button>',
            esc_attr__('Eliminar fila', 'seo-content-structure'),
            esc_html__('×', 'seo-content-structure')
        );

        $html .= '</div>'; // .scs-repeater-actions-cell

        $html .= '</div>'; // .scs-repeater-row

        return $html;
    }

    /**
     * Renderiza un subcampo dentro del repeater
     *
     * @param Field  $field    Objeto del subcampo
     * @param string $name     Nombre del input para el subcampo
     * @param string $row_index Índice de la fila
     * @return string HTML del subcampo
     */
    protected function render_sub_field($field, $name, $row_index)
    {
        // Crear una versión simplificada para el repeater
        $field_type = $field->get_type();
        $field_id = $field->get_id() . '_' . $row_index;
        $field_value = $field->get_value();

        $html = '';

        switch ($field_type) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
                $input_type = $field_type;
                $attributes = array(
                    'type'        => $input_type,
                    'id'          => $field_id,
                    'name'        => $name,
                    'value'       => $field_value,
                    'class'       => 'scs-repeater-sub-field',
                    'placeholder' => $field->placeholder,
                );

                $html = sprintf('<input %s />', $this->attributes_to_string($attributes));
                break;

            case 'textarea':
                $attributes = array(
                    'id'          => $field_id,
                    'name'        => $name,
                    'class'       => 'scs-repeater-sub-field',
                    'placeholder' => $field->placeholder,
                    'rows'        => 3,
                );

                $html = sprintf('<textarea %s>%s</textarea>', $this->attributes_to_string($attributes), esc_textarea($field_value));
                break;

            case 'select':
                $attributes = array(
                    'id'    => $field_id,
                    'name'  => $name,
                    'class' => 'scs-repeater-sub-field',
                );

                $html = sprintf('<select %s>', $this->attributes_to_string($attributes));

                foreach ($field->options as $option_value => $option_label) {
                    $selected = selected($field_value, $option_value, false);
                    $html .= sprintf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($option_value),
                        $selected,
                        esc_html($option_label)
                    );
                }

                $html .= '</select>';
                break;

            // Casos para otros tipos de campos...

            default:
                // Para otros tipos de campos no implementados en el repeater
                $html = sprintf(
                    '<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="scs-repeater-sub-field" />',
                    esc_attr($field_id),
                    esc_attr($name),
                    esc_attr($field_value),
                    esc_attr__('Campo no soportado en repeater', 'seo-content-structure')
                );
        }

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

        // Si no hay valor, devolver cadena vacía
        if (empty($value)) {
            return '';
        }

        // Decodificar el JSON
        $rows = array();
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $rows = $decoded;
            }
        } elseif (is_array($value)) {
            $rows = $value;
        }

        // Si no hay filas, devolver cadena vacía
        if (empty($rows)) {
            return '';
        }

        // Generar HTML
        $html = sprintf(
            '<div class="scs-field scs-field-repeater %s">',
            esc_attr($this->css_class)
        );

        // Generar lista
        $html .= '<ul class="scs-repeater-list">';

        foreach ($rows as $row) {
            $html .= '<li class="scs-repeater-item">';

            // Si hay un subcampo de texto, mostrarlo
            if (isset($row['texto'])) {
                $html .= esc_html($row['texto']);
            } elseif (isset($this->sub_fields[$this->id . '_texto'])) {
                // Intentar mostrar todos los subcampos
                foreach ($this->sub_fields as $sub_field) {
                    $field_key = str_replace($this->id . '_', '', $sub_field->get_id());

                    if (isset($row[$field_key])) {
                        $sub_field->set_value($row[$field_key]);
                        $html .= $sub_field->render_frontend();
                    }
                }
            }

            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene los valores del repeater como array
     *
     * @return array
     */
    public function get_rows()
    {
        $value = $this->get_value();

        // Decodificar el JSON si es necesario
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : array();
        }

        return is_array($value) ? $value : array();
    }
}
