<?php

/**
 * Se ejecuta durante la desactivación del plugin
 *
 * @package SEOContentStructure
 * @subpackage Core
 */

namespace SEOContentStructure\Core;

/**
 * Clase que maneja la desactivación del plugin
 */
class Deactivator
{
    /**
     * Método que se ejecuta durante la desactivación
     */
    public static function deactivate()
    {
        // Limpiar opciones transitorias
        self::clean_transients();

        // Limpiar caché
        self::clean_cache();
    }

    /**
     * Limpia las opciones transitorias
     */
    private static function clean_transients()
    {
        delete_transient('scs_post_types_cache');
        delete_transient('scs_field_groups_cache');
        delete_transient('scs_schemas_cache');
    }

    /**
     * Limpia el directorio de caché
     */
    private static function clean_cache()
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/scs-cache';

        if (file_exists($cache_dir)) {
            // Eliminar todos los archivos excepto index.php
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== 'index.php') {
                    unlink($file);
                }
            }
        }
    }
}
