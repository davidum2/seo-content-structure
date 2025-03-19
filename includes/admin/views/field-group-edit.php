<?php

/**
 * Página de edición de grupos de campos
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener controlador de grupos de campos
$field_group_controller = new \SEOContentStructure\Admin\FieldGroupController();

// Determinar modo (nuevo o edición)
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0;

// Por defecto mostramos la lista de grupos
$is_edit_mode = ($action === 'edit' && $group_id > 0) || $action === 'new';

// Si estamos en edición, obtener datos del grupo
$field_group = null;
if ($action === 'edit' && $group_id > 0) {
    $field_group = $field_group_controller->get_field_group($group_id);
}

// Lista de tipos de post disponibles
$post_types = \SEOContentStructure\Utilities\Helper::get_post_types(true);

// Tipos de campos disponibles
$field_types = \SEOContentStructure\Utilities\Helper::get_field_types();

// Formatear tipos de campos para interfaz
$field_types_options = array();
foreach ($field_types as $type => $class) {
    $type_name = ucfirst(str_replace('_', ' ', $type));
    $field_types_options[$type] = $type_name;
}

// Verificar si hay mensaje de error
$error_message = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

// Verificar si hay mensaje de éxito
$success_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

?>
<div class="wrap scs-admin-page scs-field-groups-page">
    <?php if (!$is_edit_mode) : ?>
        <!-- LISTADO DE GRUPOS DE CAMPOS -->
        <h1 class="wp-heading-inline"><?php echo esc_html__('Grupos de Campos', 'seo-content-structure'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=new')); ?>" class="page-title-action"><?php echo esc_html__('Añadir Nuevo', 'seo-content-structure'); ?></a>
        <hr class="wp-header-end">

        <?php if (!empty($error_message)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    if ($success_message === 'saved') {
                        echo esc_html__('Grupo de campos guardado correctamente.', 'seo-content-structure');
                    } elseif ($success_message === 'deleted') {
                        echo esc_html__('Grupo de campos eliminado correctamente.', 'seo-content-structure');
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="scs-field-groups-list">
            <?php $field_groups = $field_group_controller->get_field_groups(); ?>

            <?php if (empty($field_groups)) : ?>
                <div class="scs-no-items-message">
                    <p><?php echo esc_html__('No se encontraron grupos de campos.', 'seo-content-structure'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=new')); ?>" class="button button-primary"><?php echo esc_html__('Crear el primero', 'seo-content-structure'); ?></a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped scs-field-groups-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-title column-primary"><?php echo esc_html__('Título', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Ubicación', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Campos', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Estado', 'seo-content-structure'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($field_groups as $group) : ?>
                            <tr>
                                <td class="column-title column-primary">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=edit&group_id=' . $group['id'])); ?>" class="row-title">
                                            <?php echo esc_html($group['title']); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=edit&group_id=' . $group['id'])); ?>">
                                                <?php echo esc_html__('Editar', 'seo-content-structure'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=scs_delete_field_group&group_id=' . $group['id']), 'scs_delete_field_group', 'nonce')); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este grupo de campos?', 'seo-content-structure')); ?>')">
                                                <?php echo esc_html__('Eliminar', 'seo-content-structure'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($group['location'])) {
                                        $locations = array();
                                        foreach ($group['location'] as $location) {
                                            if (isset($location['post_type'])) {
                                                $post_type_obj = get_post_type_object($location['post_type']);
                                                $locations[] = $post_type_obj ? $post_type_obj->labels->singular_name : $location['post_type'];
                                            }
                                        }
                                        echo esc_html(implode(', ', $locations));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo !empty($group['fields']) ? count($group['fields']) : 0; ?></td>
                                <td>
                                    <?php if (isset($group['active']) && $group['active']) : ?>
                                        <span class="scs-status-active"><?php echo esc_html__('Activo', 'seo-content-structure'); ?></span>
                                    <?php else : ?>
                                        <span class="scs-status-inactive"><?php echo esc_html__('Inactivo', 'seo-content-structure'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <!-- FORMULARIO DE EDICIÓN DE GRUPO DE CAMPOS -->
        <?php if ($action === 'new') : ?>
            <h1><?php echo esc_html__('Añadir Grupo de Campos', 'seo-content-structure'); ?></h1>
        <?php else : ?>
            <h1><?php echo esc_html__('Editar Grupo de Campos', 'seo-content-structure'); ?></h1>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form id="scs-field-group-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="scs_save_field_group">
            <input type="hidden" name="group_id" value="<?php echo $field_group ? esc_attr($field_group['id']) : ''; ?>">
            <?php wp_nonce_field('scs_save_field_group', 'scs_field_group_nonce'); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Configuración del Grupo', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="title"><?php echo esc_html__('Título', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="title" name="title" value="<?php echo $field_group ? esc_attr($field_group['title']) : ''; ?>" class="regular-text" required>
                                            <p class="description"><?php echo esc_html__('El título del grupo de campos tal como aparecerá en el editor.', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php echo esc_html__('Estado', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="active" value="1" <?php checked(!empty($field_group) && !empty($field_group['active'])); ?>>
                                                <?php echo esc_html__('Activo', 'seo-content-structure'); ?>
                                            </label>
                                            <p class="description"><?php echo esc_html__('Si está desactivado, no se mostrará en el editor.', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Ubicación', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Selecciona dónde se mostrará este grupo de campos.', 'seo-content-structure'); ?></p>

                                <div class="scs-locations-list">
                                    <?php
                                    $locations = !empty($field_group) && !empty($field_group['location']) ? $field_group['location'] : array(array('post_type' => 'post'));
                                    $location_index = 0;
                                    foreach ($locations as $location) :
                                        $post_type = isset($location['post_type']) ? $location['post_type'] : 'post';
                                    ?>
                                        <div class="scs-location-item">
                                            <select name="location[<?php echo esc_attr($location_index); ?>][post_type]" class="scs-location-post-type">
                                                <?php foreach ($post_types as $post_type_name => $post_type_label) : ?>
                                                    <option value="<?php echo esc_attr($post_type_name); ?>" <?php selected($post_type, $post_type_name); ?>>
                                                        <?php echo esc_html($post_type_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button type="button" class="button scs-remove-location-button"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                        </div>
                                    <?php
                                        $location_index++;
                                    endforeach;
                                    ?>
                                </div>

                                <button type="button" id="scs-add-location-button" class="button"><?php echo esc_html__('Añadir Ubicación', 'seo-content-structure'); ?></button>

                                <!-- Template para nuevas ubicaciones -->
                                <script type="text/template" id="scs-location-template">
                                    <div class="scs-location-item">
                                        <select name="location[{index}][post_type]" class="scs-location-post-type">
                                            <?php foreach ($post_types as $post_type_name => $post_type_label) : ?>
                                                <option value="<?php echo esc_attr($post_type_name); ?>">
                                                    <?php echo esc_html($post_type_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="button" class="button scs-remove-location-button"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                    </div>
                                </script>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Campos', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Configura los campos personalizados para este grupo.', 'seo-content-structure'); ?></p>

                                <div class="scs-fields-list">
                                    <?php
                                    $fields = !empty($field_group) && !empty($field_group['fields']) ? $field_group['fields'] : array();
                                    $field_index = 0;
                                    foreach ($fields as $field) :
                                        $field_type = isset($field['type']) ? $field['type'] : 'text';
                                        $field_id = isset($field['id']) ? $field['id'] : 'field_' . uniqid();
                                        $field_label = isset($field['label']) ? $field['label'] : '';
                                    ?>
                                        <div class="scs-field-item" data-field-index="<?php echo esc_attr($field_index); ?>">
                                            <div class="scs-field-header">
                                                <div class="scs-field-sort-handle dashicons dashicons-move"></div>
                                                <div class="scs-field-title"><?php echo esc_html($field_label); ?></div>
                                                <div class="scs-field-type"><?php echo esc_html(isset($field_types_options[$field_type]) ? $field_types_options[$field_type] : $field_type); ?></div>
                                                <div class="scs-field-actions">
                                                    <button type="button" class="button scs-edit-field-button"><?php echo esc_html__('Editar', 'seo-content-structure'); ?></button>
                                                    <button type="button" class="button scs-remove-field-button"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                                </div>
                                            </div>
                                            <div class="scs-field-content" style="display:none;">
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][label]"><?php echo esc_html__('Etiqueta', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="fields[<?php echo esc_attr($field_index); ?>][label]" name="fields[<?php echo esc_attr($field_index); ?>][label]" value="<?php echo esc_attr($field_label); ?>" class="regular-text" required>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][id]"><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="fields[<?php echo esc_attr($field_index); ?>][id]" name="fields[<?php echo esc_attr($field_index); ?>][id]" value="<?php echo esc_attr($field_id); ?>" class="regular-text" required>
                                                            <p class="description"><?php echo esc_html__('Identificador único para el campo. Solo letras, números y guiones bajos.', 'seo-content-structure'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][type]"><?php echo esc_html__('Tipo', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <select id="fields[<?php echo esc_attr($field_index); ?>][type]" name="fields[<?php echo esc_attr($field_index); ?>][type]" class="scs-field-type-select">
                                                                <?php foreach ($field_types_options as $type_value => $type_label) : ?>
                                                                    <option value="<?php echo esc_attr($type_value); ?>" <?php selected($field_type, $type_value); ?>>
                                                                        <?php echo esc_html($type_label); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][instructions]"><?php echo esc_html__('Instrucciones', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <textarea id="fields[<?php echo esc_attr($field_index); ?>][instructions]" name="fields[<?php echo esc_attr($field_index); ?>][instructions]" rows="3" class="large-text"><?php echo isset($field['instructions']) ? esc_textarea($field['instructions']) : ''; ?></textarea>
                                                            <p class="description"><?php echo esc_html__('Instrucciones para los usuarios sobre este campo.', 'seo-content-structure'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <label>
                                                                <input type="checkbox" name="fields[<?php echo esc_attr($field_index); ?>][required]" value="1" <?php checked(!empty($field['required'])); ?>>
                                                                <?php echo esc_html__('Requerido', 'seo-content-structure'); ?>
                                                            </label>
                                                        </td>
                                                    </tr>
                                                    <!-- Campo específico para Select, Checkbox, Radio -->
                                                    <tr class="scs-field-option scs-field-option-select scs-field-option-checkbox scs-field-option-radio" style="display:<?php echo ($field_type === 'select' || $field_type === 'checkbox' || $field_type === 'radio') ? 'table-row' : 'none'; ?>;">
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][options]"><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <textarea id="fields[<?php echo esc_attr($field_index); ?>][options]" name="fields[<?php echo esc_attr($field_index); ?>][options]" rows="5" class="large-text"><?php
                                                                                                                                                                                                                            if (!empty($field['options']) && is_array($field['options'])) {
                                                                                                                                                                                                                                $options_text = '';
                                                                                                                                                                                                                                foreach ($field['options'] as $option_value => $option_label) {
                                                                                                                                                                                                                                    $options_text .= $option_value . ' : ' . $option_label . "\n";
                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                echo esc_textarea(trim($options_text));
                                                                                                                                                                                                                            }
                                                                                                                                                                                                                            ?></textarea>
                                                            <p class="description"><?php echo esc_html__('Introduce las opciones en formato "valor : etiqueta", una por línea.', 'seo-content-structure'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <!-- Campo específico para Image -->
                                                    <tr class="scs-field-option scs-field-option-image" style="display:<?php echo ($field_type === 'image') ? 'table-row' : 'none'; ?>;">
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][size]"><?php echo esc_html__('Tamaño predeterminado', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <select id="fields[<?php echo esc_attr($field_index); ?>][size]" name="fields[<?php echo esc_attr($field_index); ?>][size]">
                                                                <option value="thumbnail" <?php selected(!empty($field['size']) && $field['size'] === 'thumbnail'); ?>><?php echo esc_html__('Miniatura', 'seo-content-structure'); ?></option>
                                                                <option value="medium" <?php selected(!empty($field['size']) && $field['size'] === 'medium'); ?>><?php echo esc_html__('Mediana', 'seo-content-structure'); ?></option>
                                                                <option value="large" <?php selected(!empty($field['size']) && $field['size'] === 'large'); ?>><?php echo esc_html__('Grande', 'seo-content-structure'); ?></option>
                                                                <option value="full" <?php selected(!empty($field['size']) && $field['size'] === 'full'); ?>><?php echo esc_html__('Completa', 'seo-content-structure'); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <!-- Campos específicos para Number -->
                                                    <tr class="scs-field-option scs-field-option-number" style="display:<?php echo ($field_type === 'number') ? 'table-row' : 'none'; ?>;">
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][min]"><?php echo esc_html__('Valor mínimo', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="fields[<?php echo esc_attr($field_index); ?>][min]" name="fields[<?php echo esc_attr($field_index); ?>][min]" value="<?php echo isset($field['min']) ? esc_attr($field['min']) : ''; ?>" step="any" class="small-text">
                                                        </td>
                                                    </tr>
                                                    <tr class="scs-field-option scs-field-option-number" style="display:<?php echo ($field_type === 'number') ? 'table-row' : 'none'; ?>;">
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][max]"><?php echo esc_html__('Valor máximo', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="fields[<?php echo esc_attr($field_index); ?>][max]" name="fields[<?php echo esc_attr($field_index); ?>][max]" value="<?php echo isset($field['max']) ? esc_attr($field['max']) : ''; ?>" step="any" class="small-text">
                                                        </td>
                                                    </tr>
                                                    <tr class="scs-field-option scs-field-option-number" style="display:<?php echo ($field_type === 'number') ? 'table-row' : 'none'; ?>;">
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][step]"><?php echo esc_html__('Incremento', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="fields[<?php echo esc_attr($field_index); ?>][step]" name="fields[<?php echo esc_attr($field_index); ?>][step]" value="<?php echo isset($field['step']) ? esc_attr($field['step']) : '1'; ?>" step="any" class="small-text">
                                                        </td>
                                                    </tr>
                                                    <!-- Campo para Schema Property -->
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="fields[<?php echo esc_attr($field_index); ?>][schema_property]"><?php echo esc_html__('Propiedad Schema', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="fields[<?php echo esc_attr($field_index); ?>][schema_property]" name="fields[<?php echo esc_attr($field_index); ?>][schema_property]" value="<?php echo isset($field['schema_property']) ? esc_attr($field['schema_property']) : ''; ?>" class="regular-text">
                                                            <p class="description"><?php echo esc_html__('Propiedad de schema.org a la que mapear este campo (ej: name, description, image).', 'seo-content-structure'); ?></p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <!-- Campos ocultos para mantener otros valores -->
                                            <?php
                                            // Preserve other values that might not be in the form
                                            foreach ($field as $key => $value) {
                                                if (!in_array($key, array('id', 'label', 'type', 'instructions', 'required', 'options', 'size', 'min', 'max', 'step', 'schema_property'))) {
                                                    if (is_array($value)) {
                                                        $value = json_encode($value);
                                                    }
                                                    echo '<input type="hidden" name="fields[' . esc_attr($field_index) . '][' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php
                                        $field_index++;
                                    endforeach;
                                    ?>
                                </div>

                                <button type="button" id="scs-add-field-button" class="button"><?php echo esc_html__('Añadir Campo', 'seo-content-structure'); ?></button>

                                <!-- Template para nuevos campos -->
                                <script type="text/template" id="scs-field-template">
                                    <div class="scs-field-item" data-field-index="{index}">
                                        <div class="scs-field-header">
                                            <div class="scs-field-sort-handle dashicons dashicons-move"></div>
                                            <div class="scs-field-title"><?php echo esc_html__('Nuevo Campo', 'seo-content-structure'); ?></div>
                                            <div class="scs-field-type"><?php echo esc_html__('Texto', 'seo-content-structure'); ?></div>
                                            <div class="scs-field-actions">
                                                <button type="button" class="button scs-edit-field-button"><?php echo esc_html__('Editar', 'seo-content-structure'); ?></button>
                                                <button type="button" class="button scs-remove-field-button"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                            </div>
                                        </div>
                                        <div class="scs-field-content">
                                            <table class="form-table">
                                                <tr>
                                                    <th scope="row">
                                                        <label for="fields[{index}][label]"><?php echo esc_html__('Etiqueta', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="fields[{index}][label]" name="fields[{index}][label]" value="<?php echo esc_html__('Nuevo Campo', 'seo-content-structure'); ?>" class="regular-text" required>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="fields[{index}][id]"><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="fields[{index}][id]" name="fields[{index}][id]" value="field_<?php echo uniqid(); ?>" class="regular-text" required>
                                                        <p class="description"><?php echo esc_html__('Identificador único para el campo. Solo letras, números y guiones bajos.', 'seo-content-structure'); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="fields[{index}][type]"><?php echo esc_html__('Tipo', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <select id="fields[{index}][type]" name="fields[{index}][type]" class="scs-field-type-select">
                                                            <?php foreach ($field_types_options as $type_value => $type_label) : ?>
                                                                <option value="<?php echo esc_attr($type_value); ?>">
                                                                    <?php echo esc_html($type_label); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="fields[{index}][instructions]"><?php echo esc_html__('Instrucciones', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <textarea id="fields[{index}][instructions]" name="fields[{index}][instructions]" rows="3" class="large-text"></textarea>
                                                        <p class="description"><?php echo esc_html__('Instrucciones para los usuarios sobre este campo.', 'seo-content-structure'); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" name="fields[{index}][required]" value="1">
                                                            <?php echo esc_html__('Requerido', 'seo-content-structure'); ?>
                                                        </label>
                                                    </td>
                                                </tr>
                                                <!-- Campo específico para Select, Checkbox, Radio -->
                                                <tr class="scs-field-option scs-field-option-select scs-field-option-checkbox scs-field-option-radio" style="display:none;">
                                                    <th scope="row">
                                                        <label for="fields[{index}][options]"><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <textarea id="fields[{index}][options]" name="fields[{index}][options]" rows="5" class="large-text"></textarea>
                                                        <p class="description"><?php echo esc_html__('Introduce las opciones en formato "valor : etiqueta", una por línea.', 'seo-content-structure'); ?></p>
                                                    </td>
                                                </tr>
                                                <!-- Campo específico para Image -->
                                                <tr class="scs-field-option scs-field-option-image" style="display:none;">
                                                    <th scope="row">
                                                        <label for="fields[{index}][size]"><?php echo esc_html__('Tamaño predeterminado', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <select id="fields[{index}][size]" name="fields[{index}][size]">
                                                            <option value="thumbnail"><?php echo esc_html__('Miniatura', 'seo-content-structure'); ?></option>
                                                            <option value="medium"><?php echo esc_html__('Mediana', 'seo-content-structure'); ?></option>
                                                            <option value="large"><?php echo esc_html__('Grande', 'seo-content-structure'); ?></option>
                                                            <option value="full"><?php echo esc_html__('Completa', 'seo-content-structure'); ?></option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <!-- Campos específicos para Number -->
                                                <tr class="scs-field-option scs-field-option-number" style="display:none;">
                                                    <th scope="row">
                                                        <label for="fields[{index}][min]"><?php echo esc_html__('Valor mínimo', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="number" id="fields[{index}][min]" name="fields[{index}][min]" step="any" class="small-text">
                                                    </td>
                                                </tr>
                                                <tr class="scs-field-option scs-field-option-number" style="display:none;">
                                                    <th scope="row">
                                                        <label for="fields[{index}][max]"><?php echo esc_html__('Valor máximo', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="number" id="fields[{index}][max]" name="fields[{index}][max]" step="any" class="small-text">
                                                    </td>
                                                </tr>
                                                <tr class="scs-field-option scs-field-option-number" style="display:none;">
                                                    <th scope="row">
                                                        <label for="fields[{index}][step]"><?php echo esc_html__('Incremento', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="number" id="fields[{index}][step]" name="fields[{index}][step]" value="1" step="any" class="small-text">
                                                    </td>
                                                </tr>
                                                <!-- Campo para Schema Property -->
                                                <tr>
                                                    <th scope="row">
                                                        <label for="fields[{index}][schema_property]"><?php echo esc_html__('Propiedad Schema', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="fields[{index}][schema_property]" name="fields[{index}][schema_property]" class="regular-text">
                                                        <p class="description"><?php echo esc_html__('Propiedad de schema.org a la que mapear este campo (ej: name, description, image).', 'seo-content-structure'); ?></p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </script>
                            </div>
                        </div>
                    </div>

                    <div id="postbox-container-1" class="postbox-container">
                        <div id="submitdiv" class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Publicar', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <input type="submit" name="save" class="button button-primary button-large" value="<?php echo esc_attr__('Guardar Grupo de Campos', 'seo-content-structure'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
