<?php

/**
 * Campo de tipo fecha
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo fecha
 */
class DateField extends Field
{
    /**
     * Formato de fecha (php date format)
     *
     * @var string
     */
    protected $date_format = 'Y-m-d';

    /**
     * Fecha mínima permitida
     *
     * @var string
     */
    protected $min_date = '';

    /**
     * Fecha máxima permitida
     *
     * @var string
     */
    protected $max_date = '';

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'date';

        // Configurar propiedades específicas del campo de fecha
        $date_defaults = array(
            'date_format' => 'Y-m-d',
            'min_date'    => '',
            'max_date'    => '',
        );

        $args = wp_parse_args($args, $date_defaults);

        // Llamar al constructor padre
        parent::__construct($args);

        // Añadir scripts y estilos específicos para este tipo de campo
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Registra los scripts necesarios para el campo de fecha
     */
    public function enqueue_scripts()
    {
        // Solo cargar en las páginas de administración relevantes
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->base, array('post', 'post-new'))) {
            return;
        }

        // Enqueue el script de datepicker de jQuery UI
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue el CSS de jQuery UI
        wp_enqueue_style(
            'jquery-ui-css',
            'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css',
            array(),
            '1.13.2'
        );

        // Script personalizado para inicializar datepicker
        wp_add_inline_script('jquery-ui-datepicker', '
            jQuery(document).ready(function($) {
                $(".scs-date-field").datepicker({
                    dateFormat: "yy-mm-dd",
                    changeMonth: true,
                    changeYear: true
                });
            });
        ');
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return string Fecha sanitizada
     */
    public function sanitize($value)
    {
        // Si está vacío, retornar cadena vacía
        if (empty($value)) {
            return '';
        }

        // Intentar convertir a formato fecha
        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return '';
        }

        // Formatear según el formato configurado
        return date($this->date_format, $timestamp);
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

        // Si no hay valor y no es requerido, está bien
        if (empty($value) && !$this->required) {
            return true;
        }

        // Verificar formato de fecha
        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return new \WP_Error(
                'invalid_date',
                __('La fecha no tiene un formato válido.', 'seo-content-structure')
            );
        }

        // Validar fecha mínima
        if (!empty($this->min_date)) {
            $min_timestamp = strtotime($this->min_date);
            if ($timestamp < $min_timestamp) {
                return new \WP_Error(
                    'date_too_early',
                    sprintf(
                        __('La fecha debe ser posterior a %s.', 'seo-content-structure'),
                        date($this->date_format, $min_timestamp)
                    )
                );
            }
        }

        // Validar fecha máxima
        if (!empty($this->max_date)) {
            $max_timestamp = strtotime($this->max_date);
            if ($timestamp > $max_timestamp) {
                return new \WP_Error(
                    'date_too_late',
                    sprintf(
                        __('La fecha debe ser anterior a %s.', 'seo-content-structure'),
                        date($this->date_format, $max_timestamp)
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

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-date-wrap" style="width:%s;">',
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
            'class'       => 'scs-field scs-date-field',
            'placeholder' => $this->placeholder ?: __('Seleccionar fecha...', 'seo-content-structure')
        );

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        if (!empty($this->min_date)) {
            $attributes['min'] = $this->min_date;
        }

        if (!empty($this->max_date)) {
            $attributes['max'] = $this->max_date;
        }

        // Campo de fecha
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

        // Formatear la fecha para visualización
        $timestamp = strtotime($value);
        $formatted_date = date_i18n(get_option('date_format'), $timestamp);

        // Generar el HTML para mostrar la fecha
        $html = sprintf(
            '<div class="scs-field scs-field-date %s">',
            esc_attr($this->css_class)
        );

        $html .= esc_html($formatted_date);

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene la fecha como objeto DateTime
     *
     * @return DateTime|null
     */
    public function get_date_object()
    {
        $value = $this->get_value();

        if (empty($value)) {
            return null;
        }

        return new \DateTime($value);
    }

    /**
     * Formatea la fecha según un formato específico
     *
     * @param string $format Formato de fecha
     * @return string
     */
    public function format_date($format = '')
    {
        $value = $this->get_value();

        if (empty($value)) {
            return '';
        }

        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return '';
        }

        $format = empty($format) ? $this->date_format : $format;
        return date($format, $timestamp);
    }
}
