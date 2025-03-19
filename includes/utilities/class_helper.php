<?php

/**
 * Clase con funciones de ayuda para el plugin
 *
 * @package SEOContentStructure
 * @subpackage Utilities
 */

namespace SEOContentStructure\Utilities;

/**
 * Clase que proporciona métodos de ayuda para diversas funcionalidades
 */
class Helper
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicialización
    }

    /**
     * Genera un slug único para identificadores
     *
     * @param string $text Texto a convertir en slug
     * @return string
     */
    public static function generate_slug($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Formatea un nombre de campo para mostrar
     *
     * @param string $field_name Nombre del campo
     * @return string
     */
    public static function format_field_name($field_name)
    {
        $field_name = str_replace(array('_', '-'), ' ', $field_name);
        return ucwords($field_name);
    }

    /**
     * Devuelve una lista de post types disponibles
     *
     * @param bool $include_defaults Incluir post types por defecto (post, page)
     * @return array
     */
    public static function get_post_types($include_defaults = true)
    {
        $args = array(
            'public' => true,
        );

        if (!$include_defaults) {
            $args['_builtin'] = false;
        }

        $post_types = get_post_types($args, 'objects');
        $output = array();

        foreach ($post_types as $post_type) {
            $output[$post_type->name] = $post_type->labels->singular_name;
        }

        return $output;
    }

    /**
     * Devuelve una lista de taxonomías disponibles
     *
     * @param bool $include_defaults Incluir taxonomías por defecto (category, tag)
     * @return array
     */
    public static function get_taxonomies($include_defaults = true)
    {
        $args = array(
            'public' => true,
        );

        if (!$include_defaults) {
            $args['_builtin'] = false;
        }

        $taxonomies = get_taxonomies($args, 'objects');
        $output = array();

        foreach ($taxonomies as $taxonomy) {
            $output[$taxonomy->name] = $taxonomy->labels->singular_name;
        }

        return $output;
    }

    /**
     * Devuelve una lista de roles de usuario
     *
     * @return array
     */
    public static function get_user_roles()
    {
        global $wp_roles;
        $roles = array();

        foreach ($wp_roles->roles as $key => $role) {
            $roles[$key] = $role['name'];
        }

        return $roles;
    }

    /**
     * Convierte un array asociativo a opciones para un select
     *
     * @param array  $array    Array asociativo
     * @param string $selected Valor seleccionado
     * @return string
     */
    public static function array_to_options($array, $selected = '')
    {
        $options = '';

        foreach ($array as $value => $label) {
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($value, $selected, false),
                esc_html($label)
            );
        }

        return $options;
    }

    /**
     * Formatea un mensaje de error para mostrar
     *
     * @param WP_Error|string $error Error o mensaje
     * @return string
     */
    public static function format_error($error)
    {
        if (is_wp_error($error)) {
            $message = $error->get_error_message();
        } else {
            $message = $error;
        }

        return sprintf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Formatea un mensaje de éxito para mostrar
     *
     * @param string $message Mensaje
     * @return string
     */
    public static function format_success($message)
    {
        return sprintf(
            '<div class="notice notice-success"><p>%s</p></div>',
            esc_html($message)
        );
    }

    /**
     * Obtiene los tipos de campos disponibles
     *
     * @return array
     */
    public static function get_field_types()
    {
        $field_factory = new \SEOContentStructure\Fields\FieldFactory();
        return $field_factory->get_field_types();
    }

    /**
     * Obtiene los tipos de schema disponibles
     *
     * @return array
     */
    public static function get_schema_types()
    {
        $schema_factory = new \SEOContentStructure\Schema\SchemaFactory();
        return $schema_factory->get_schema_types_list();
    }

    /**
     * Verifica si un plugin está activo
     *
     * @param string $plugin Nombre del plugin (ej: woocommerce/woocommerce.php)
     * @return bool
     */
    public static function is_plugin_active($plugin)
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($plugin);
    }

    /**
     * Comprueba si una URL es válida
     *
     * @param string $url URL a validar
     * @return bool
     */
    public static function is_valid_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Obtiene la URL base del plugin
     *
     * @return string
     */
    public static function get_plugin_url()
    {
        return SCS_PLUGIN_URL;
    }

    /**
     * Obtiene la ruta base del plugin
     *
     * @return string
     */
    public static function get_plugin_path()
    {
        return SCS_PLUGIN_DIR;
    }

    /**
     * Obtiene una configuración del plugin
     *
     * @param string $key     Clave de configuración
     * @param mixed  $default Valor por defecto
     * @return mixed
     */
    public static function get_option($key, $default = null)
    {
        $options = get_option('scs_settings', array());

        if (isset($options[$key])) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * Establece una configuración del plugin
     *
     * @param string $key   Clave de configuración
     * @param mixed  $value Valor
     * @return bool
     */
    public static function update_option($key, $value)
    {
        $options = get_option('scs_settings', array());
        $options[$key] = $value;

        return update_option('scs_settings', $options);
    }

    /**
     * Limpia la caché del plugin
     *
     * @return bool
     */
    public static function clear_cache()
    {
        delete_transient('scs_post_types_cache');
        delete_transient('scs_field_groups_cache');
        delete_transient('scs_schemas_cache');

        // Limpiar archivos de caché
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/scs-cache';

        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== 'index.php') {
                    unlink($file);
                }
            }
        }

        return true;
    }
}
