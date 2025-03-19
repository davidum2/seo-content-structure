<?php

/**
 * Se ejecuta durante la activación del plugin
 *
 * @package SEOContentStructure
 * @subpackage Core
 */

namespace SEOContentStructure\Core;

/**
 * Clase que maneja la activación del plugin
 */
class Activator
{
    /**
     * Método que se ejecuta durante la activación
     */
    public static function activate()
    {
        // Crear tablas personalizadas si son necesarias
        self::create_tables();

        // Registrar opciones predeterminadas
        self::register_default_options();

        // Crear directorio de caché si no existe
        self::create_cache_directory();
    }

    /**
     * Crea las tablas personalizadas en la base de datos
     */
    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla para grupos de campos
        $table_field_groups = $wpdb->prefix . 'scs_field_groups';

        // SQL para crear la tabla de grupos de campos
        $sql_field_groups = "CREATE TABLE {$table_field_groups} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            location longtext,
            active tinyint(1) DEFAULT 1,
            fields longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        // Tabla para tipos de contenido personalizados
        $table_post_types = $wpdb->prefix . 'scs_post_types';

        // SQL para crear la tabla de tipos de contenido
        $sql_post_types = "CREATE TABLE {$table_post_types} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_type varchar(50) NOT NULL,
            config longtext NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_type (post_type)
        ) {$charset_collate};";

        // Tabla para esquemas JSON-LD
        $table_schemas = $wpdb->prefix . 'scs_schemas';

        // SQL para crear la tabla de esquemas
        $sql_schemas = "CREATE TABLE {$table_schemas} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            config longtext NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        // Cargar la clase dbDelta para crear tablas
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Crear las tablas
        dbDelta($sql_field_groups);
        dbDelta($sql_post_types);
        dbDelta($sql_schemas);
    }

    /**
     * Registra las opciones predeterminadas
     */
    private static function register_default_options()
    {
        // Versión del plugin
        add_option('scs_version', SCS_VERSION);

        // Opciones generales
        add_option('scs_settings', array(
            'enable_json_ld' => 1,
            'enable_rest_api' => 1,
            'admin_bar_menu' => 1,
            'auto_schema' => 1,
        ));

        // Marcar como primera ejecución
        add_option('scs_first_run', true);
    }

    /**
     * Crea el directorio de caché si no existe
     */
    private static function create_cache_directory()
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/scs-cache';

        if (! file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);

            // Crear un archivo index.php para proteger el directorio
            $index_file = $cache_dir . '/index.php';
            if (! file_exists($index_file)) {
                $handle = fopen($index_file, 'w');
                fwrite($handle, '<?php // Silence is golden');
                fclose($handle);
            }
        }
    }
}
