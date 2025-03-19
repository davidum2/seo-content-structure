<?php

/**
 * Define la funcionalidad de internacionalización
 *
 * @package SEOContentStructure
 * @subpackage Core
 */

namespace SEOContentStructure\Core;

/**
 * Clase que maneja la internacionalización del plugin
 */
class I18n
{
    /**
     * Registra los hooks necesarios para la internacionalización
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'seo-content-structure',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
