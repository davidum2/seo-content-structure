<?php

/**
 * Página de configuración del plugin
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener opciones actuales
$options = get_option('scs_settings', array());

// Configurar valores por defecto si las opciones no existen
$defaults = array(
    'enable_json_ld'  => 1,
    'enable_rest_api' => 1,
    'admin_bar_menu'  => 1,
    'auto_schema'     => 1,
);

$options = wp_parse_args($options, $defaults);

// Verificar si hay mensaje de éxito
$success_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

?>
<div class="wrap scs-admin-page scs-settings-page">
    <h1><?php echo esc_html__('Configuración', 'seo-content-structure'); ?></h1>

    <?php if (!empty($success_message) && $success_message === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Configuración guardada correctamente.', 'seo-content-structure'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="scs_save_settings">
        <?php wp_nonce_field('scs_save_settings', 'scs_settings_nonce'); ?>

        <div class="scs-settings-tabs">
            <div class="scs-tabs">
                <ul class="scs-tabs-nav">
                    <li class="active"><a href="#general-settings"><?php echo esc_html__('General', 'seo-content-structure'); ?></a></li>
                    <li><a href="#schema-settings"><?php echo esc_html__('Schema', 'seo-content-structure'); ?></a></li>
                    <li><a href="#advanced-settings"><?php echo esc_html__('Avanzado', 'seo-content-structure'); ?></a></li>
                </ul>

                <!-- Pestaña General -->
                <div id="general-settings" class="scs-tab-content">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php echo esc_html__('Ajustes Generales', 'seo-content-structure'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_json_ld"><?php echo esc_html__('JSON-LD Schema', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="enable_json_ld" name="scs_settings[enable_json_ld]" value="1" <?php checked($options['enable_json_ld'], 1); ?>>
                                            <?php echo esc_html__('Activar generación automática de schema JSON-LD para SEO', 'seo-content-structure'); ?>
                                        </label>
                                        <p class="description"><?php echo esc_html__('Inserta automáticamente el código JSON-LD en tus páginas para mejorar el SEO.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="admin_bar_menu"><?php echo esc_html__('Menú en barra admin', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="admin_bar_menu" name="scs_settings[admin_bar_menu]" value="1" <?php checked($options['admin_bar_menu'], 1); ?>>
                                            <?php echo esc_html__('Mostrar accesos rápidos en la barra de administración', 'seo-content-structure'); ?>
                                        </label>
                                        <p class="description"><?php echo esc_html__('Añade un menú en la barra de administración para acceder rápidamente a las funciones del plugin.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Schema -->
                <div id="schema-settings" class="scs-tab-content" style="display: none;">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php echo esc_html__('Configuración de Schema', 'seo-content-structure'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="auto_schema"><?php echo esc_html__('Schema automático', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="auto_schema" name="scs_settings[auto_schema]" value="1" <?php checked($options['auto_schema'], 1); ?>>
                                            <?php echo esc_html__('Generar automáticamente schema JSON-LD basado en el tipo de contenido', 'seo-content-structure'); ?>
                                        </label>
                                        <p class="description"><?php echo esc_html__('Crea automáticamente el schema JSON-LD para cada tipo de contenido según su configuración.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="schema_front_page"><?php echo esc_html__('Schema para página principal', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <select id="schema_front_page" name="scs_settings[schema_front_page]" class="regular-text">
                                            <option value=""><?php echo esc_html__('Ninguno', 'seo-content-structure'); ?></option>
                                            <option value="WebSite" <?php selected(isset($options['schema_front_page']) ? $options['schema_front_page'] : '', 'WebSite'); ?>><?php echo esc_html__('WebSite', 'seo-content-structure'); ?></option>
                                            <option value="Organization" <?php selected(isset($options['schema_front_page']) ? $options['schema_front_page'] : '', 'Organization'); ?>><?php echo esc_html__('Organization', 'seo-content-structure'); ?></option>
                                        </select>
                                        <p class="description"><?php echo esc_html__('Tipo de schema a utilizar en la página principal de tu sitio.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="organization_name"><?php echo esc_html__('Nombre de la organización', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="organization_name" name="scs_settings[organization_name]" value="<?php echo esc_attr(isset($options['organization_name']) ? $options['organization_name'] : get_bloginfo('name')); ?>" class="regular-text">
                                        <p class="description"><?php echo esc_html__('Nombre de tu organización para el schema Organization.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="organization_logo"><?php echo esc_html__('Logo de la organización', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <div class="scs-media-field">
                                            <input type="hidden" id="organization_logo" name="scs_settings[organization_logo]" value="<?php echo esc_attr(isset($options['organization_logo']) ? $options['organization_logo'] : ''); ?>">
                                            <div id="organization_logo_preview" class="scs-image-preview">
                                                <?php if (!empty($options['organization_logo'])) : ?>
                                                    <img src="<?php echo esc_url(wp_get_attachment_image_url($options['organization_logo'], 'thumbnail')); ?>" alt="Logo">
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="button scs-upload-image-button" data-field-id="organization_logo"><?php echo esc_html__('Seleccionar Logo', 'seo-content-structure'); ?></button>
                                            <?php if (!empty($options['organization_logo'])) : ?>
                                                <button type="button" class="button scs-remove-image-button" data-field-id="organization_logo"><?php echo esc_html__('Eliminar Logo', 'seo-content-structure'); ?></button>
                                            <?php endif; ?>
                                            <p class="description"><?php echo esc_html__('Logo de tu organización para el schema Organization. Recomendado: 112x112px mínimo, formato cuadrado.', 'seo-content-structure'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Avanzado -->
                <div id="advanced-settings" class="scs-tab-content" style="display: none;">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php echo esc_html__('Ajustes Avanzados', 'seo-content-structure'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="enable_rest_api"><?php echo esc_html__('API REST', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="enable_rest_api" name="scs_settings[enable_rest_api]" value="1" <?php checked($options['enable_rest_api'], 1); ?>>
                                            <?php echo esc_html__('Habilitar endpoints de API REST', 'seo-content-structure'); ?>
                                        </label>
                                        <p class="description"><?php echo esc_html__('Permite acceder a los campos y schemas mediante la API REST de WordPress.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="cache_expiration"><?php echo esc_html__('Tiempo de caché', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="cache_expiration" name="scs_settings[cache_expiration]" value="<?php echo esc_attr(isset($options['cache_expiration']) ? $options['cache_expiration'] : 24); ?>" min="1" max="168" class="small-text">
                                        <?php echo esc_html__('horas', 'seo-content-structure'); ?>
                                        <p class="description"><?php echo esc_html__('Tiempo que se mantienen en caché los datos del plugin. Un valor más alto mejora el rendimiento pero puede retrasar los cambios.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><?php echo esc_html__('Limpiar datos', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <button type="button" id="scs-clear-cache" class="button"><?php echo esc_html__('Limpiar caché', 'seo-content-structure'); ?></button>
                                        <p class="description"><?php echo esc_html__('Elimina los datos en caché para actualizar la información inmediatamente.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="custom_css"><?php echo esc_html__('CSS personalizado', 'seo-content-structure'); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="custom_css" name="scs_settings[custom_css]" rows="5" class="large-text code"><?php echo esc_textarea(isset($options['custom_css']) ? $options['custom_css'] : ''); ?></textarea>
                                        <p class="description"><?php echo esc_html__('CSS personalizado para los campos en el frontend.', 'seo-content-structure'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Guardar Cambios', 'seo-content-structure'); ?>">
        </p>
    </form>
</div>

<script type="text/javascript">
    // Script para las pestañas y la gestión de imágenes
    (function($) {
        $(document).ready(function() {
            // Manejar las pestañas
            $('.scs-tabs-nav a').on('click', function(e) {
                e.preventDefault();

                // Ocultar todos los contenidos
                $('.scs-tab-content').hide();

                // Mostrar el contenido seleccionado
                $($(this).attr('href')).show();

                // Actualizar clases de pestañas activas
                $('.scs-tabs-nav li').removeClass('active');
                $(this).parent().addClass('active');
            });

            // Limpiar caché
            $('#scs-clear-cache').on('click', function() {
                if (confirm('<?php echo esc_js(__('¿Estás seguro de que deseas limpiar la caché? Esto puede afectar temporalmente al rendimiento.', 'seo-content-structure')); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'scs_clear_cache',
                            nonce: '<?php echo wp_create_nonce('scs_clear_cache'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php echo esc_js(__('Caché limpiada correctamente.', 'seo-content-structure')); ?>');
                            } else {
                                alert('<?php echo esc_js(__('Error al limpiar la caché.', 'seo-content-structure')); ?>');
                            }
                        }
                    });
                }
            });
        });
    })(jQuery);
</script>
