<?php

/**
 * Página para crear y editar esquemas JSON-LD
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Detectar acción
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$schema_id = isset($_GET['schema_id']) ? absint($_GET['schema_id']) : 0;
$schema_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

// Por defecto mostramos el editor o la lista
$is_edit_mode = ($action === 'edit' && $schema_id > 0) || $action === 'new';

// Obtener tipos de Schema disponibles
$schema_types = \SEOContentStructure\Utilities\Helper::get_schema_types();

// Si estamos en modo nuevo y se proporcionó un tipo, validarlo
if ($action === 'new' && !empty($schema_type) && !isset($schema_types[$schema_type])) {
    $schema_type = '';
}

// Verificar si hay mensaje de error
$error_message = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

// Verificar si hay mensaje de éxito
$success_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

?>
<div class="wrap scs-admin-page scs-schema-editor-page">
    <?php if (!$is_edit_mode) : ?>
        <!-- LISTADO DE SCHEMAS -->
        <h1 class="wp-heading-inline"><?php echo esc_html__('Editor de Schema', 'seo-content-structure'); ?></h1>

        <div class="scs-schema-type-selector">
            <h2><?php echo esc_html__('Selecciona un tipo de Schema para crear:', 'seo-content-structure'); ?></h2>

            <div class="scs-schema-types-grid">
                <?php foreach ($schema_types as $type => $label) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-schema-editor&action=new&type=' . $type)); ?>" class="scs-schema-type-card">
                        <div class="scs-schema-type-icon">
                            <span class="dashicons dashicons-editor-code"></span>
                        </div>
                        <div class="scs-schema-type-info">
                            <h3><?php echo esc_html($type); ?></h3>
                            <p><?php echo esc_html__('Crear un nuevo schema de tipo', 'seo-content-structure'); ?> <?php echo esc_html($type); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="scs-schema-intro">
                <h3><?php echo esc_html__('¿Qué es Schema JSON-LD?', 'seo-content-structure'); ?></h3>
                <p><?php echo esc_html__('Schema JSON-LD es un formato de datos estructurados que los motores de búsqueda utilizan para entender mejor el contenido de tu sitio web y mostrar resultados enriquecidos en las páginas de resultados.', 'seo-content-structure'); ?></p>
                <p><?php echo esc_html__('Con el Editor de Schema puedes crear, editar y gestionar estructuras JSON-LD para mejorar el SEO de tu sitio y aumentar la visibilidad en los resultados de búsqueda.', 'seo-content-structure'); ?></p>
                <p><a href="https://schema.org/docs/full.html" target="_blank"><?php echo esc_html__('Más información sobre Schema.org', 'seo-content-structure'); ?></a></p>
            </div>
        </div>

    <?php else : ?>
        <!-- EDITOR DE SCHEMA -->
        <?php if ($action === 'new') : ?>
            <h1><?php echo esc_html__('Crear Nuevo Schema', 'seo-content-structure'); ?>: <?php echo esc_html($schema_type); ?></h1>
        <?php else : ?>
            <h1><?php echo esc_html__('Editar Schema', 'seo-content-structure'); ?></h1>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <div id="scs-json-ld-builder" class="scs-schema-editor">
            <div id="scs-json-ld-form" class="scs-schema-editor-form">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Pestañas para alternar entre editor visual y código -->
                            <div class="scs-tabs">
                                <ul class="scs-tabs-nav">
                                    <li><a href="#scs-visual-editor"><?php echo esc_html__('Editor Visual', 'seo-content-structure'); ?></a></li>
                                    <li><a href="#scs-code-editor"><?php echo esc_html__('Editor de Código', 'seo-content-structure'); ?></a></li>
                                </ul>

                                <!-- Editor Visual -->
                                <div id="scs-visual-editor" class="scs-tab-content">
                                    <div class="postbox">
                                        <div class="postbox-header">
                                            <h2 class="hndle"><?php echo esc_html__('Propiedades del Schema', 'seo-content-structure'); ?></h2>
                                        </div>
                                        <div class="inside">
                                            <div id="scs-schema-properties" class="scs-schema-properties-container">
                                                <p><?php echo esc_html__('Cargando propiedades...', 'seo-content-structure'); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="scs-schema-actions">
                                        <button type="button" id="scs-generate-schema" class="button button-primary"><?php echo esc_html__('Generar Schema', 'seo-content-structure'); ?></button>
                                        <button type="button" id="scs-validate-schema" class="button"><?php echo esc_html__('Validar Schema', 'seo-content-structure'); ?></button>
                                    </div>
                                </div>

                                <!-- Editor de Código -->
                                <div id="scs-code-editor" class="scs-tab-content">
                                    <div class="postbox">
                                        <div class="postbox-header">
                                            <h2 class="hndle"><?php echo esc_html__('Código JSON-LD', 'seo-content-structure'); ?></h2>
                                        </div>
                                        <div class="inside">
                                            <textarea id="scs-schema-editor" class="scs-code-editor">{
  "@context": "https://schema.org",
  "@type": "<?php echo esc_js($schema_type); ?>",
  "name": "",
  "description": ""
}</textarea>

                                            <div class="scs-schema-actions">
                                                <button type="button" id="scs-format-json" class="button"><?php echo esc_html__('Formatear JSON', 'seo-content-structure'); ?></button>
                                                <button type="button" id="scs-load-from-editor" class="button"><?php echo esc_html__('Cargar en Editor Visual', 'seo-content-structure'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista previa del Schema -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle"><?php echo esc_html__('Vista Previa', 'seo-content-structure'); ?></h2>
                                </div>
                                <div class="inside">
                                    <pre id="scs-schema-output" class="scs-schema-preview">
{
  "@context": "https://schema.org",
  "@type": "<?php echo esc_js($schema_type); ?>",
  "name": "",
  "description": ""
}
</pre>
                                </div>
                            </div>
                        </div>

                        <div id="postbox-container-1" class="postbox-container">
                            <div id="submitdiv" class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle"><?php echo esc_html__('Guardar', 'seo-content-structure'); ?></h2>
                                </div>
                                <div class="inside">
                                    <div class="submitbox" id="submitpost">
                                        <div id="major-publishing-actions">
                                            <div id="publishing-action">
                                                <input type="button" id="scs-save-schema" name="save" class="button button-primary button-large" value="<?php echo esc_attr__('Guardar Schema', 'seo-content-structure'); ?>">
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle"><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></h2>
                                </div>
                                <div class="inside">
                                    <p>
                                        <label for="scs-schema-type">
                                            <strong><?php echo esc_html__('Tipo de Schema:', 'seo-content-structure'); ?></strong>
                                        </label>
                                        <select id="scs-schema-type" class="widefat">
                                            <?php foreach ($schema_types as $type => $label) : ?>
                                                <option value="<?php echo esc_attr($type); ?>" <?php selected($schema_type, $type); ?>><?php echo esc_html($type); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>

                                    <p>
                                        <label for="scs-schema-title">
                                            <strong><?php echo esc_html__('Título:', 'seo-content-structure'); ?></strong>
                                        </label>
                                        <input type="text" id="scs-schema-title" class="widefat" value="" placeholder="<?php echo esc_attr__('Título para identificar este schema', 'seo-content-structure'); ?>">
                                    </p>

                                    <p>
                                        <label>
                                            <input type="checkbox" id="scs-schema-active" checked="checked">
                                            <strong><?php echo esc_html__('Activo', 'seo-content-structure'); ?></strong>
                                        </label>
                                    </p>
                                </div>
                            </div>

                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle"><?php echo esc_html__('Referencias', 'seo-content-structure'); ?></h2>
                                </div>
                                <div class="inside">
                                    <ul>
                                        <li><a href="https://schema.org/<?php echo esc_attr($schema_type); ?>" target="_blank"><?php echo esc_html__('Documentación de', 'seo-content-structure'); ?> <?php echo esc_html($schema_type); ?></a></li>
                                        <li><a href="https://search.google.com/test/rich-results" target="_blank"><?php echo esc_html__('Herramienta de prueba de Google', 'seo-content-structure'); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    // Script básico para cargar las propiedades del Schema seleccionado
    (function($) {
        $(document).ready(function() {
            if ($('#scs-schema-type').length) {
                $('#scs-schema-type').on('change', function() {
                    var selectedType = $(this).val();
                    // Aquí se cargarían las propiedades del schema seleccionado mediante AJAX
                    $('#scs-schema-properties').html('<p>Cargando propiedades para ' + selectedType + '...</p>');

                    // Actualizar también el textarea del editor
                    var currentJson = JSON.parse($('#scs-schema-editor').val() || '{"@context": "https://schema.org"}');
                    currentJson['@type'] = selectedType;
                    $('#scs-schema-editor').val(JSON.stringify(currentJson, null, 2));
                });
            }
        });
    })(jQuery);
</script>
