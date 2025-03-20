<?php

/**
 * Campo de tipo imagen
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase que implementa un campo de tipo imagen
 */
class ImageField extends Field
{
    /**
     * Tamaño de la imagen por defecto
     *
     * @var string
     */
    protected $size = 'thumbnail';

    /**
     * Permitir múltiples imágenes
     *
     * @var bool
     */
    protected $multiple = false;

    /**
     * Dimensiones mínimas (ancho x alto)
     *
     * @var array
     */
    protected $min_dimensions = array();

    /**
     * Dimensiones máximas (ancho x alto)
     *
     * @var array
     */
    protected $max_dimensions = array();

    /**
     * Constructor
     *
     * @param array $args Argumentos del campo
     */
    public function __construct($args = array())
    {
        // Establecer el tipo de campo
        $args['type'] = 'image';

        // Configurar propiedades específicas del campo de imagen
        $image_defaults = array(
            'size'           => 'thumbnail',
            'multiple'       => false,
            'min_dimensions' => array(),
            'max_dimensions' => array(),
        );

        $args = wp_parse_args($args, $image_defaults);

        // Llamar al constructor padre
        parent::__construct($args);

        // Añadir scripts y estilos específicos para este tipo de campo
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Registra los scripts necesarios para el campo de imagen
     */
    public function enqueue_scripts()
    {
        // Solo cargar en las páginas de administración relevantes
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->base, array('post', 'post-new'))) {
            return;
        }

        // Enqueue los scripts de media de WordPress
        wp_enqueue_media();

        // Enqueue nuestro script personalizado
        wp_enqueue_script(
            'scs-image-field',
            SCS_PLUGIN_URL . 'assets/js/image-field.js',
            array('jquery', 'media-upload'),
            SCS_VERSION,
            true
        );

        // Enqueue nuestros estilos
        wp_enqueue_style(
            'scs-image-field',
            SCS_PLUGIN_URL . 'assets/css/image-field.css',
            array(),
            SCS_VERSION
        );
    }

    /**
     * Sanitiza el valor del campo antes de guardarlo
     *
     * @param mixed $value Valor a sanitizar
     * @return int ID de la imagen
     */
    public function sanitize($value)
    {
        // Para una imagen, el valor debe ser un ID de adjunto válido
        return absint($value);
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

        // Si no hay valor, está bien (a menos que sea requerido, ya validado)
        if (empty($this->value)) {
            return true;
        }

        // Verificar que el ID de la imagen sea válido
        $attachment = get_post($this->value);
        if (! $attachment || 'attachment' !== $attachment->post_type || 'image/' !== substr(get_post_mime_type($attachment), 0, 6)) {
            return new \WP_Error('invalid_image', __('La imagen seleccionada no es válida.', 'seo-content-structure'));
        }

        // Verificar dimensiones mínimas si están establecidas
        if (! empty($this->min_dimensions)) {
            $image_meta = wp_get_attachment_metadata($this->value);

            if (isset($this->min_dimensions['width']) && $image_meta['width'] < $this->min_dimensions['width']) {
                return new \WP_Error('image_too_small', sprintf(
                    __('La imagen debe tener al menos %s píxeles de ancho.', 'seo-content-structure'),
                    $this->min_dimensions['width']
                ));
            }

            if (isset($this->min_dimensions['height']) && $image_meta['height'] < $this->min_dimensions['height']) {
                return new \WP_Error('image_too_small', sprintf(
                    __('La imagen debe tener al menos %s píxeles de alto.', 'seo-content-structure'),
                    $this->min_dimensions['height']
                ));
            }
        }

        // Verificar dimensiones máximas si están establecidas
        if (! empty($this->max_dimensions)) {
            $image_meta = wp_get_attachment_metadata($this->value);

            if (isset($this->max_dimensions['width']) && $image_meta['width'] > $this->max_dimensions['width']) {
                return new \WP_Error('image_too_large', sprintf(
                    __('La imagen debe tener como máximo %s píxeles de ancho.', 'seo-content-structure'),
                    $this->max_dimensions['width']
                ));
            }

            if (isset($this->max_dimensions['height']) && $image_meta['height'] > $this->max_dimensions['height']) {
                return new \WP_Error('image_too_large', sprintf(
                    __('La imagen debe tener como máximo %s píxeles de alto.', 'seo-content-structure'),
                    $this->max_dimensions['height']
                ));
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

        // Verificar si hay una imagen seleccionada
        $has_image = ! empty($value);
        $image_url = '';
        $image_preview = '';

        if ($has_image) {
            $image_url = wp_get_attachment_image_url($value, $this->size);
            if ($image_url) {
                $image_preview = sprintf(
                    '<div class="scs-image-preview" id="%s_preview"><img src="%s" alt="%s" style="max-width:100px; max-height:100px;" /></div>',
                    esc_attr($field_id),
                    esc_url($image_url),
                    esc_attr__('Vista previa de imagen', 'seo-content-structure')
                );
            }
        }

        // Construir el HTML del campo
        $html = sprintf(
            '<div class="scs-field-wrap scs-field-image-wrap" style="width:%s;">',
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

        // Vista previa de la imagen
        $html .= $image_preview;

        // Campo oculto para almacenar el ID de la imagen
        $html .= sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s" class="scs-image-field-input" />',
            esc_attr($field_id),
            esc_attr($field_name),
            esc_attr($value)
        );

        // Botones para seleccionar/eliminar imagen
        $html .= sprintf(
            '<div class="scs-image-field-buttons">
                <button type="button" class="button scs-upload-image-button" data-field-id="%s">%s</button>',
            esc_attr($field_id),
            esc_html__('Seleccionar Imagen', 'seo-content-structure')
        );

        if ($has_image) {
            $html .= sprintf(
                ' <button type="button" class="button scs-remove-image-button" data-field-id="%s">%s</button>',
                esc_attr($field_id),
                esc_html__('Eliminar Imagen', 'seo-content-structure')
            );
        }

        $html .= '</div>'; // .scs-image-field-buttons

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

        // Obtener la URL de la imagen en el tamaño especificado
        $image_url = wp_get_attachment_image_url($value, $this->size);

        if (! $image_url) {
            return '';
        }

        // Obtener el alt de la imagen
        $alt = get_post_meta($value, '_wp_attachment_image_alt', true);
        if (empty($alt)) {
            $alt = get_the_title($value);
        }

        // Generar el HTML para mostrar la imagen
        $html = sprintf(
            '<div class="scs-field scs-field-image %s">',
            esc_attr($this->css_class)
        );

        $html .= sprintf(
            '<img src="%s" alt="%s" class="scs-image" />',
            esc_url($image_url),
            esc_attr($alt)
        );

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene la URL de la imagen
     *
     * @param string $size Tamaño de la imagen (por defecto el establecido en el campo)
     * @return string URL de la imagen o cadena vacía si no hay imagen
     */
    public function get_image_url($size = '')
    {
        $value = $this->get_value();

        if (empty($value)) {
            return '';
        }

        if (empty($size)) {
            $size = $this->size;
        }

        return wp_get_attachment_image_url($value, $size);
    }

    /**
     * Obtiene el objeto completo de la imagen
     *
     * @return array|false Datos de la imagen o false si no hay imagen
     */
    public function get_image_data()
    {
        $value = $this->get_value();

        if (empty($value)) {
            return false;
        }

        $attachment = get_post($value);

        if (! $attachment) {
            return false;
        }

        // Crear un objeto con todos los datos relevantes
        $image_data = array(
            'ID'          => $value,
            'id'          => $value,
            'title'       => $attachment->post_title,
            'filename'    => basename(get_attached_file($value)),
            'url'         => wp_get_attachment_url($value),
            'alt'         => get_post_meta($value, '_wp_attachment_image_alt', true),
            'description' => $attachment->post_content,
            'caption'     => $attachment->post_excerpt,
        );

        // Añadir datos de todos los tamaños disponibles
        $sizes = get_intermediate_image_sizes();
        foreach ($sizes as $size) {
            $image_src = wp_get_attachment_image_src($value, $size);
            if ($image_src) {
                $image_data['sizes'][$size] = array(
                    'url'    => $image_src[0],
                    'width'  => $image_src[1],
                    'height' => $image_src[2]
                );
            }
        }

        return $image_data;
    }
}
