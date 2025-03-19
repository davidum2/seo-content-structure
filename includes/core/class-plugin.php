<?php

/**
 * Clase principal del plugin que maneja la inicialización y configuración
 *
 * @package SEOContentStructure
 * @subpackage Core
 */

namespace SEOContentStructure\Core;

use SEOContentStructure\Admin\AdminController;
use SEOContentStructure\API\RestController;
use SEOContentStructure\Integrations\ElementorIntegration;
use SEOContentStructure\Integrations\WooCommerceIntegration;
use SEOContentStructure\PostTypes\PostTypeFactory;

/**
 * Clase principal del plugin
 */
if (!defined('SCS_PLUGIN_DIR')) {
    define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

class Plugin
{
    /**
     * Instancia del cargador que coordina los hooks del plugin
     *
     * @var Loader
     */
    protected $loader;

    /**
     * Instancia única de esta clase (singleton)
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->loader = new Loader();
    }

    /**
     * Obtiene la instancia única del plugin
     *
     * @return Plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializa el plugin
     */
    public function init()
    {
        // Inicializar internacionalización
        $i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');

        // Inicializar la administración del plugin
        $this->init_admin();

        // Inicializar API REST
        $this->init_api();

        // Inicializar integraciones
        $this->init_integrations();

        // Inicializar tipos de contenido personalizados
        $this->init_post_types();

        // Inicializar shortcodes
        $this->init_shortcodes();

        // Ejecutar el cargador
        $this->loader->run();
    }

    /**
     * Inicializa la parte de administración del plugin
     */
    private function init_admin()
    {
        if (is_admin()) {
            error_log('Inicializando controlador de administración');
            $admin_controller = new AdminController();
            $admin_controller->register($this->loader);
        }
    }

    /**
     * Inicializa la API REST
     */
    private function init_api()
    {
        $rest_controller = new RestController();
        $rest_controller->register($this->loader);
    }

    /**
     * Inicializa las integraciones con otros plugins
     */
    private function init_integrations()
    {
        // Integración con Elementor (si está activo)
        if (did_action('elementor/loaded')) {
            $elementor_integration = new ElementorIntegration();
            $elementor_integration->register($this->loader);
        }

        // // Integración con WooCommerce (si está activo)
        // if (class_exists('WooCommerce')) {
        //     $woocommerce_integration = new WooCommerceIntegration();
        //     $woocommerce_integration->register($this->loader);
        // }
    }

    /**
     * Inicializa los tipos de contenido personalizados
     */
    private function init_post_types()
    {
        $post_type_factory = new PostTypeFactory();
        $post_types = $post_type_factory->get_registered_post_types();

        foreach ($post_types as $post_type) {
            $post_type->register($this->loader);
        }
    }

    /**
     * Inicializa los shortcodes
     */
    private function init_shortcodes()
    {
        // Inicializar los shortcodes del plugin
        require_once SCS_PLUGIN_DIR . 'includes/utilities/class-shortcodes.php';
        $shortcodes = new \SEOContentStructure\Utilities\Shortcodes();
        $shortcodes->register($this->loader);
    }

    /**
     * Obtiene el cargador del plugin
     *
     * @return Loader
     */
    public function get_loader()
    {
        return $this->loader;
    }
}
