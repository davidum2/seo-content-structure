<?php

/**
 * Página de configuración del plugin
 *
 * @package SEOContentStructure
 * @subpackage Admin
 */

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;

/**
 * Clase que maneja la página de configuración del plugin
 */
class SettingsPage implements Registrable
{
    /**
     * Grupo de opciones
     *
     * @var string
     */
    protected $option_group = 'scs_settings';

    /**
     * Nombre de la página
     *
     * @var string
     */
    protected $page_name = 'scs-settings';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicialización
    }

    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // Registrar ajustes
        $loader->add_action('admin_init', $this, 'register_settings');

        // Procesar formulario de ajustes
        $loader->add_action('admin_post_scs_save_settings', $this, 'process_save_settings');
    }

    /**
     * Registra los ajustes del plugin
     */
    public function register_settings()
    {
        register_setting(
            $this->option_group,
            'scs_settings',
            array($this, 'sanitize_settings')
        );

        // Sección general
        add_settings_section(
            'scs_general_settings',
            __('Ajustes generales', 'seo-content-structure'),
            array($this, 'render_general_section'),
            $this->page_name
        );

        // Campo para habilitar JSON-LD
        add_settings_field(
            'enable_json_ld',
            __('JSON-LD Schema', 'seo-content-structure'),
            array($this, 'render_checkbox_field'),
            $this->page_name,
            'scs_general_settings',
            array(
                'id' => 'enable_json_ld',
                'desc' => __('Activar generación automática de schema JSON-LD para SEO', 'seo-content-structure'),
            )
        );

        // Campo para habilitar REST API
        add_settings_field(
            'enable_rest_api',
            __('API REST', 'seo-content-structure'),
            array($this, 'render_checkbox_field'),
            $this->page_name,
            'scs_general_settings',
            array(
                'id' => 'enable_rest_api',
                'desc' => __('Habilitar endpoints de API REST para acceder a campos y schemas', 'seo-content-structure'),
            )
        );

        // Campo para habilitar menú en barra de admin
        add_settings_field(
            'admin_bar_menu',
            __('Menú en barra admin', 'seo-content-structure'),
            array($this, 'render_checkbox_field'),
            $this->page_name,
            'scs_general_settings',
            array(
                'id' => 'admin_bar_menu',
                'desc' => __('Mostrar accesos rápidos en la barra de administración', 'seo-content-structure'),
            )
        );

        // Sección de esquemas
        add_settings_section(
            'scs_schema_settings',
            __('Configuración de Schema', 'seo-content-structure'),
            array($this, 'render_schema_section'),
            $this->page_name
        );

        // Campo para esquema automático
        add_settings_field(
            'auto_schema',
            __('Schema automático', 'seo-content-structure'),
            array($this, 'render_checkbox_field'),
            $this->page_name,
            'scs_schema_settings',
            array(
                'id' => 'auto_schema',
                'desc' => __('Generar automáticamente schema JSON-LD basado en el tipo de contenido', 'seo-content-structure'),
            )
        );
    }

    /**
     * Renderiza la sección general
     */
    public function render_general_section()
    {
        echo '<p>' . __('Configura los ajustes generales del plugin.', 'seo-content-structure') . '</p>';
    }

    /**
     * Renderiza la sección de esquemas
     */
    public function render_schema_section()
    {
        echo '<p>' . __('Configura cómo se generan los esquemas JSON-LD.', 'seo-content-structure') . '</p>';
    }

    /**
     * Renderiza un campo de tipo checkbox
     *
     * @param array $args Argumentos del campo
     */
    public function render_checkbox_field($args)
    {
        $options = get_option('scs_settings');
        $id = $args['id'];
        $checked = isset($options[$id]) ? $options[$id] : 0;

        echo '<label>';
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="scs_settings[' . esc_attr($id) . ']" value="1" ' . checked(1, $checked, false) . ' />';
        echo ' ' . esc_html($args['desc']);
        echo '</label>';
    }

    /**
     * Sanitiza los ajustes antes de guardarlos
     *
     * @param array $input Datos del formulario
     * @return array Datos sanitizados
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Campos de tipo checkbox
        $checkboxes = array('enable_json_ld', 'enable_rest_api', 'admin_bar_menu', 'auto_schema');
        foreach ($checkboxes as $checkbox) {
            $sanitized[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }

        return $sanitized;
    }

    /**
     * Procesa el formulario de ajustes
     */
    public function process_save_settings()
    {
        // Verificar nonce
        if (!isset($_POST['scs_settings_nonce']) || !wp_verify_nonce($_POST['scs_settings_nonce'], 'scs_save_settings')) {
            wp_die(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Guardar ajustes
        if (isset($_POST['scs_settings'])) {
            update_option('scs_settings', $this->sanitize_settings($_POST['scs_settings']));
        }

        // Redireccionar
        wp_redirect(add_query_arg(array(
            'page' => 'scs-settings',
            'message' => 'saved',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_page()
    {
        // Comprobar permisos
        if (!current_user_can('manage_options')) {
            return;
        }

        // Guardar ajustes
        if (isset($_POST['submit'])) {
            check_admin_referer('scs_save_settings', 'scs_settings_nonce');
            update_option('scs_settings', $this->sanitize_settings($_POST['scs_settings']));
            echo '<div class="notice notice-success"><p>' . __('Ajustes guardados correctamente.', 'seo-content-structure') . '</p></div>';
        }

        // Obtener opciones actuales
        $options = get_option('scs_settings');
?>
        <div class="wrap scs-admin-page scs-settings-page">
            <h1><?php echo esc_html__('Configuración', 'seo-content-structure'); ?></h1>

            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page_name);
                submit_button();
                ?>
            </form>

            <div class="scs-settings-info">
                <h3><?php echo esc_html__('Información adicional', 'seo-content-structure'); ?></h3>
                <p><?php echo esc_html__('Para más información sobre cómo usar este plugin, consulta nuestra documentación.', 'seo-content-structure'); ?></p>
                <p><a href="https://ejemplo.com/docs" target="_blank" class="button button-secondary"><?php echo esc_html__('Ver documentación', 'seo-content-structure'); ?></a></p>
            </div>
        </div>
<?php
    }
}
