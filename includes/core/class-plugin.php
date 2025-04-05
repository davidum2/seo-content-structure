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
use SEOContentStructure\Core\Loader;
use SEOContentStructure\Core\I18n;
use SEOContentStructure\Integrations\ElementorIntegration;
use SEOContentStructure\PostTypes\PostTypeFactory;
use SEOContentStructure\Utilities\Shortcodes;

/**
 * Clase principal del plugin
 */
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
        try {
            error_log('SCS_TRACE: === Plugin::init() START ===');

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

            // Verificar y reparar tablas si es necesario
            $this->check_and_repair_post_types_table();

            // Ejecutar el cargador
            $this->loader->run();

            error_log('SCS_TRACE: === Plugin::init() END ===');

            return true;
        } catch (\Throwable $e) {
            error_log('SCS_FATAL: Error in Plugin::init(): ' . $e->getMessage());
            error_log('SCS_FATAL: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Inicializa la parte de administración del plugin
     */
    private function init_admin()
    {
        if (is_admin()) {
            try {
                $admin_controller = new AdminController();
                $admin_controller->register($this->loader);
            } catch (\Throwable $e) {
                error_log("Error al inicializar el controlador de administración: " . $e->getMessage());
                error_log("Traza: " . $e->getTraceAsString());
            }
        }
    }

    /**
     * Inicializa la API REST
     */
    private function init_api()
    {
        try {
            $rest_controller = new RestController();
            $rest_controller->register($this->loader);
        } catch (\Throwable $e) {
            error_log("Error al inicializar el controlador REST: " . $e->getMessage());
        }
    }

    /**
     * Inicializa las integraciones con otros plugins
     */
    private function init_integrations()
    {
        try {
            // Integración con Elementor (si está activo)
            // Comenta estas líneas:
            /*
        if (did_action('elementor/loaded')) {
            // Verificar si Elementor Pro está activo antes de usar sus clases
            if (class_exists('\ElementorPro\Plugin')) {
                 $elementor_integration = new \SEOContentStructure\Integrations\ElementorIntegration();
                 $elementor_integration->register($this->loader);
            } else {
                 // Opcional: Registrar un aviso si Elementor Pro no está activo pero se intenta usar la integración
                 // error_log('SCS Plugin: Elementor Pro no está activo, integración no cargada.');
            }
        }
        */

            // Integración con WooCommerce (si está activo)
            // ... (código de WooCommerce si existe) ...
        } catch (\Throwable $e) {
            error_log("Error al inicializar integraciones: " . $e->getMessage());
        }
    }
    /**
     * Inicializa los tipos de contenido personalizados
     */
    private function init_post_types()
    {
        try {
            $post_type_factory = new PostTypeFactory();
            $post_types = $post_type_factory->get_registered_post_types();

            foreach ($post_types as $post_type) {
                $post_type->register($this->loader);
            }
        } catch (\Throwable $e) {
            error_log("Error al inicializar tipos de contenido: " . $e->getMessage());
        }
    }

    public function register_post_types_direct()
    {
        try {
            $post_type_factory = new PostTypeFactory();
            $post_types = $post_type_factory->get_registered_post_types();

            foreach ($post_types as $post_type) {
                // Llamada directa al método sin pasar por el loader
                $post_type->register_post_type();

                // Registrar taxonomías si existen
                if (!empty($post_type->get_taxonomies())) {
                    $post_type->register_taxonomies();
                }
            }
        } catch (\Throwable $e) {
            error_log("Error al registrar tipos de contenido: " . $e->getMessage());
        }
    }

    /**
     * Inicializa los shortcodes
     */
    private function init_shortcodes()
    {
        try {
            $shortcodes = new Shortcodes();
            $shortcodes->register($this->loader);
        } catch (\Throwable $e) {
            error_log("Error al inicializar shortcodes: " . $e->getMessage());
        }
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

    /**
     * Verifica y repara la tabla de post types si es necesario
     */
    public function check_and_repair_post_types_table()
    {
        error_log("SCS_TRACE: Plugin - Verificando tabla de post types");

        // Intentar crear/verificar tabla de post types
        try {
            if (class_exists('\SEOContentStructure\PostTypes\PostTypeFactory')) {
                $factory = new \SEOContentStructure\PostTypes\PostTypeFactory();

                // Si factory tiene el método para crear tablas, llamarlo
                if (method_exists($factory, 'create_post_types_table')) {
                    error_log("SCS_TRACE: Plugin - Llamando a create_post_types_table desde verificador");
                    $factory->create_post_types_table();
                } else {
                    error_log("SCS_ERROR: Plugin - Método create_post_types_table no encontrado en factory");
                }
            } else {
                error_log("SCS_ERROR: Plugin - Clase PostTypeFactory no encontrada");
            }
        } catch (\Throwable $e) {
            error_log("SCS_ERROR: Plugin - Error al verificar/reparar tabla: " . $e->getMessage());
        }
    }
}
