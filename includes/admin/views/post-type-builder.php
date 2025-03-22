<?php

/**
 * Página para crear y editar tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener controlador de tipos de contenido
$post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();

// Determinar modo (nuevo o edición)
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';

// Por defecto mostramos la lista de tipos de contenido
$is_edit_mode = ($action === 'edit' && !empty($post_type)) || $action === 'new';

// Obtener datos del tipo de contenido si estamos en modo edición
$post_type_obj = null;
$post_type_data = array();
if ($action === 'edit' && !empty($post_type)) {
    $post_type_obj = $post_type_factory->get_post_type($post_type);

    if ($post_type_obj) {
        $post_type_data = array(
            'post_type' => $post_type_obj->get_post_type(),
            'args' => $post_type_obj->get_args(),
            'taxonomies' => $post_type_obj->get_taxonomies(),
            'fields' => $post_type_obj->get_fields(),
            'schema_type' => $post_type_obj->get_schema_type()
        );
    }
}

// Lista de taxonomías disponibles
$taxonomies = \SEOContentStructure\Utilities\Helper::get_taxonomies(true);

// Tipos de schema disponibles
$schema_types = \SEOContentStructure\Utilities\Helper::get_schema_types();

// Tipos de campos disponibles
$field_types = \SEOContentStructure\Utilities\Helper::get_field_types();

// Verificar si hay mensaje de error
$error_message = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

// Verificar si hay mensaje de éxito
$success_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

?>
<div class="wrap scs-admin-page scs-post-types-page">
    <?php if (!$is_edit_mode) : ?>
        <!-- LISTADO DE TIPOS DE CONTENIDO -->
        <h1 class="wp-heading-inline"><?php echo esc_html__('Tipos de Contenido', 'seo-content-structure'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=new')); ?>" class="page-title-action"><?php echo esc_html__('Añadir Nuevo', 'seo-content-structure'); ?></a>
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
                        echo esc_html__('Tipo de contenido guardado correctamente.', 'seo-content-structure');
                    } elseif ($success_message === 'deleted') {
                        echo esc_html__('Tipo de contenido eliminado correctamente.', 'seo-content-structure');
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="scs-post-types-list">
            <?php $registered_post_types = $post_type_factory->get_registered_post_types(); ?>

            <?php if (empty($registered_post_types)) : ?>
                <div class="scs-no-items-message">
                    <p><?php echo esc_html__('No se encontraron tipos de contenido personalizados.', 'seo-content-structure'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=new')); ?>" class="button button-primary"><?php echo esc_html__('Crear el primero', 'seo-content-structure'); ?></a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped scs-post-types-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-title column-primary"><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Taxonomías', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Campos', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Schema', 'seo-content-structure'); ?></th>
                            <th scope="col"><?php echo esc_html__('Estado', 'seo-content-structure'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registered_post_types as $pt) :
                            // Saltamos los tipos de contenido nativos de WordPress
                            if (in_array($pt->get_post_type(), array('post', 'page', 'attachment'))) {
                                continue;
                            }
                        ?>
                            <tr>
                                <td class="column-title column-primary">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=edit&post_type=' . $pt->get_post_type())); ?>" class="row-title">
                                            <?php echo esc_html($pt->get_args()['labels']['singular_name']); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=edit&post_type=' . $pt->get_post_type())); ?>">
                                                <?php echo esc_html__('Editar', 'seo-content-structure'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=scs_delete_post_type&post_type=' . $pt->get_post_type()), 'scs_delete_post_type', 'nonce')); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este tipo de contenido?', 'seo-content-structure')); ?>')">
                                                <?php echo esc_html__('Eliminar', 'seo-content-structure'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($pt->get_post_type()); ?></td>
                                <td>
                                    <?php
                                    $pt_taxonomies = $pt->get_taxonomies();
                                    if (!empty($pt_taxonomies)) {
                                        $tax_names = array();
                                        foreach ($pt_taxonomies as $tax_name => $tax_args) {
                                            $tax_names[] = isset($tax_args['labels']['singular_name']) ? $tax_args['labels']['singular_name'] : $tax_name;
                                        }
                                        echo esc_html(implode(', ', $tax_names));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo count($pt->get_fields()); ?></td>
                                <td><?php echo $pt->get_schema_type() ? esc_html($pt->get_schema_type()) : '—'; ?></td>
                                <td>
                                    <?php if (isset($pt->get_args()['active']) && $pt->get_args()['active']) : ?>
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
        <!-- FORMULARIO DE EDICIÓN DE TIPO DE CONTENIDO -->
        <?php if ($action === 'new') : ?>
            <h1><?php echo esc_html__('Añadir Tipo de Contenido', 'seo-content-structure'); ?></h1>
        <?php else : ?>
            <h1><?php echo esc_html__('Editar Tipo de Contenido', 'seo-content-structure'); ?></h1>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form id="scs-post-type-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="scs_save_post_type">
            <input type="hidden" name="post_type_id" value="<?php echo esc_attr($post_type); ?>">
            <?php wp_nonce_field('scs_save_post_type', 'scs_post_type_nonce'); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Información Básica', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="singular_name"><?php echo esc_html__('Nombre Singular', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="singular_name" name="args[labels][singular_name]" value="<?php echo isset($post_type_data['args']['labels']['singular_name']) ? esc_attr($post_type_data['args']['labels']['singular_name']) : ''; ?>" class="regular-text" required>
                                            <p class="description"><?php echo esc_html__('Nombre en singular para el tipo de contenido (ej. Producto).', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="name"><?php echo esc_html__('Nombre Plural', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="name" name="args[labels][name]" value="<?php echo isset($post_type_data['args']['labels']['name']) ? esc_attr($post_type_data['args']['labels']['name']) : ''; ?>" class="regular-text" required>
                                            <p class="description"><?php echo esc_html__('Nombre en plural para el tipo de contenido (ej. Productos).', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="post_type"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="post_type" name="post_type" value="<?php echo esc_attr($post_type); ?>" class="regular-text" <?php echo $action === 'edit' ? 'readonly' : 'required'; ?>>
                                            <p class="description"><?php echo esc_html__('Identificador único para el tipo de contenido. Solo letras minúsculas, números y guiones (ej. producto).', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="menu_icon"><?php echo esc_html__('Icono de Menú', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="menu_icon" name="args[menu_icon]" value="<?php echo isset($post_type_data['args']['menu_icon']) ? esc_attr($post_type_data['args']['menu_icon']) : 'dashicons-admin-post'; ?>" class="regular-text">
                                            <p class="description"><?php echo esc_html__('Clase de dashicon (ej. dashicons-cart) o URL a una imagen.', 'seo-content-structure'); ?></p>
                                            <div class="scs-dashicons-list">
                                                <button type="button" class="button" id="show-dashicons"><?php echo esc_html__('Mostrar Dashicons', 'seo-content-structure'); ?></button>
                                                <div class="scs-dashicons-selector" style="display:none;">
                                                    <!-- Los dashicons se cargarán vía JavaScript -->
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php echo esc_html__('Características', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="args[public]" value="1" <?php checked(isset($post_type_data['args']['public']) && $post_type_data['args']['public']); ?>>
                                                <?php echo esc_html__('Público', 'seo-content-structure'); ?>
                                            </label><br>
                                            <label>
                                                <input type="checkbox" name="args[has_archive]" value="1" <?php checked(isset($post_type_data['args']['has_archive']) && $post_type_data['args']['has_archive']); ?>>
                                                <?php echo esc_html__('Página de Archivo', 'seo-content-structure'); ?>
                                            </label><br>
                                            <label>
                                                <input type="checkbox" name="args[hierarchical]" value="1" <?php checked(isset($post_type_data['args']['hierarchical']) && $post_type_data['args']['hierarchical']); ?>>
                                                <?php echo esc_html__('Jerárquico (como páginas)', 'seo-content-structure'); ?>
                                            </label><br>
                                            <label>
                                                <input type="checkbox" name="args[show_in_rest]" value="1" <?php checked(!isset($post_type_data['args']['show_in_rest']) || $post_type_data['args']['show_in_rest']); ?>>
                                                <?php echo esc_html__('Mostrar en REST API', 'seo-content-structure'); ?>
                                            </label><br>
                                            <label>
                                                <input type="checkbox" name="args[active]" value="1" <?php checked(!isset($post_type_data['args']['active']) || $post_type_data['args']['active']); ?>>
                                                <?php echo esc_html__('Activo', 'seo-content-structure'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Características Soportadas', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Selecciona qué características nativas de WordPress estarán disponibles para este tipo de contenido.', 'seo-content-structure'); ?></p>

                                <?php
                                $supports = isset($post_type_data['args']['supports']) ? $post_type_data['args']['supports'] : array('title', 'editor', 'thumbnail');
                                if (!is_array($supports)) {
                                    $supports = array('title', 'editor', 'thumbnail');
                                }

                                $available_supports = array(
                                    'title' => __('Título', 'seo-content-structure'),
                                    'editor' => __('Editor', 'seo-content-structure'),
                                    'thumbnail' => __('Imagen destacada', 'seo-content-structure'),
                                    'excerpt' => __('Extracto', 'seo-content-structure'),
                                    'author' => __('Autor', 'seo-content-structure'),
                                    'comments' => __('Comentarios', 'seo-content-structure'),
                                    'trackbacks' => __('Trackbacks', 'seo-content-structure'),
                                    'revisions' => __('Revisiones', 'seo-content-structure'),
                                    'custom-fields' => __('Campos personalizados', 'seo-content-structure'),
                                    'page-attributes' => __('Atributos de página', 'seo-content-structure'),
                                    'post-formats' => __('Formatos de entrada', 'seo-content-structure')
                                );
                                ?>

                                <div class="scs-supports-options">
                                    <?php foreach ($available_supports as $support_key => $support_label) : ?>
                                        <label>
                                            <input type="checkbox" name="args[supports][]" value="<?php echo esc_attr($support_key); ?>" <?php checked(in_array($support_key, $supports)); ?>>
                                            <?php echo esc_html($support_label); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Taxonomías', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Selecciona las taxonomías nativas o crea nuevas para este tipo de contenido.', 'seo-content-structure'); ?></p>

                                <div class="scs-taxonomies-list">
                                    <h4><?php echo esc_html__('Taxonomías Existentes', 'seo-content-structure'); ?></h4>
                                    <?php
                                    $current_taxonomies = array_keys($post_type_data['taxonomies'] ?? array());
                                    foreach ($taxonomies as $tax_name => $tax_label) :
                                    ?>
                                        <label>
                                            <input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr($tax_name); ?>" <?php checked(in_array($tax_name, $current_taxonomies)); ?>>
                                            <?php echo esc_html($tax_label); ?> (<?php echo esc_html($tax_name); ?>)
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>

                                <div class="scs-custom-taxonomies">
                                    <h4><?php echo esc_html__('Crear Nueva Taxonomía', 'seo-content-structure'); ?></h4>
                                    <button type="button" id="scs-add-taxonomy-button" class="button"><?php echo esc_html__('Añadir Nueva Taxonomía', 'seo-content-structure'); ?></button>

                                    <div id="scs-taxonomies-container">
                                        <?php
                                        // Mostrar taxonomías personalizadas existentes
                                        $custom_taxonomies = array_filter($post_type_data['taxonomies'] ?? array(), function ($tax_name) use ($taxonomies) {
                                            return !array_key_exists($tax_name, $taxonomies);
                                        }, ARRAY_FILTER_USE_KEY);

                                        $index = 0;
                                        foreach ($custom_taxonomies as $tax_name => $tax_data) :
                                        ?>
                                            <div class="scs-taxonomy-item">
                                                <h4><?php echo esc_html__('Nueva Taxonomía', 'seo-content-structure'); ?></h4>
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="custom_tax_<?php echo $index; ?>_singular"><?php echo esc_html__('Nombre Singular', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="custom_tax_<?php echo $index; ?>_singular" name="custom_taxonomies[<?php echo $index; ?>][singular]" value="<?php echo isset($tax_data['singular']) ? esc_attr($tax_data['singular']) : ''; ?>" class="regular-text" required>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="custom_tax_<?php echo $index; ?>_plural"><?php echo esc_html__('Nombre Plural', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="custom_tax_<?php echo $index; ?>_plural" name="custom_taxonomies[<?php echo $index; ?>][plural]" value="<?php echo isset($tax_data['plural']) ? esc_attr($tax_data['plural']) : ''; ?>" class="regular-text" required>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="custom_tax_<?php echo $index; ?>_slug"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="text" id="custom_tax_<?php echo $index; ?>_slug" name="custom_taxonomies[<?php echo $index; ?>][slug]" value="<?php echo esc_attr($tax_name); ?>" class="regular-text" required>
                                                            <p class="description"><?php echo esc_html__('Identificador único para la taxonomía.', 'seo-content-structure'); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                        </th>
                                                        <td>
                                                            <label>
                                                                <input type="checkbox" name="custom_taxonomies[<?php echo $index; ?>][hierarchical]" value="1" <?php checked(isset($tax_data['args']['hierarchical']) && $tax_data['args']['hierarchical']); ?>>
                                                                <?php echo esc_html__('Jerárquica (como categorías)', 'seo-content-structure'); ?>
                                                            </label>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <button type="button" class="button remove-taxonomy"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                            </div>
                                        <?php
                                            $index++;
                                        endforeach;
                                        ?>
                                    </div>

                                    <!-- Template para taxonomías personalizadas -->
                                    <script type="text/template" id="scs-taxonomy-template">
                                        <div class="scs-taxonomy-item">
                                            <h4><?php echo esc_html__('Nueva Taxonomía', 'seo-content-structure'); ?></h4>
                                            <table class="form-table">
                                                <tr>
                                                    <th scope="row">
                                                        <label for="custom_tax_{index}_singular"><?php echo esc_html__('Nombre Singular', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="custom_tax_{index}_singular" name="custom_taxonomies[{index}][singular]" class="regular-text" required>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="custom_tax_{index}_plural"><?php echo esc_html__('Nombre Plural', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="custom_tax_{index}_plural" name="custom_taxonomies[{index}][plural]" class="regular-text" required>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="custom_tax_{index}_slug"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" id="custom_tax_{index}_slug" name="custom_taxonomies[{index}][slug]" class="regular-text" required>
                                                        <p class="description"><?php echo esc_html__('Identificador único para la taxonomía.', 'seo-content-structure'); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label><?php echo esc_html__('Opciones', 'seo-content-structure'); ?></label>
                                                    </th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" name="custom_taxonomies[{index}][hierarchical]" value="1" checked>
                                                            <?php echo esc_html__('Jerárquica (como categorías)', 'seo-content-structure'); ?>
                                                        </label>
                                                    </td>
                                                </tr>
                                            </table>
                                            <button type="button" class="button remove-taxonomy"><?php echo esc_html__('Eliminar', 'seo-content-structure'); ?></button>
                                        </div>
                                    </script>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Schema JSON-LD', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Configura la estructura de datos JSON-LD para este tipo de contenido.', 'seo-content-structure'); ?></p>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="schema_type"><?php echo esc_html__('Tipo de Schema', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <select id="schema_type" name="schema_type" class="regular-text">
                                                <option value=""><?php echo esc_html__('Ninguno', 'seo-content-structure'); ?></option>
                                                <?php foreach ($schema_types as $type => $label) : ?>
                                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($post_type_data['schema_type'] ?? '', $type); ?>>
                                                        <?php echo esc_html($type); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description"><?php echo esc_html__('Tipo de estructura de datos para generar el código JSON-LD.', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                </table>
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
                                            <input type="submit" name="save" class="button button-primary button-large" value="<?php echo esc_attr__('Guardar Tipo de Contenido', 'seo-content-structure'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php echo esc_html__('Información', 'seo-content-structure'); ?></h2>
                            </div>
                            <div class="inside">
                                <p><?php echo esc_html__('Al crear un tipo de contenido personalizado, podrás:', 'seo-content-structure'); ?></p>
                                <ul>
                                    <li><?php echo esc_html__('Definir su nombre y características', 'seo-content-structure'); ?></li>
                                    <li><?php echo esc_html__('Asociarle taxonomías existentes o nuevas', 'seo-content-structure'); ?></li>
                                    <li><?php echo esc_html__('Usar campos personalizados para estructurar datos', 'seo-content-structure'); ?></li>
                                    <li><?php echo esc_html__('Optimizar el SEO con esquemas JSON-LD', 'seo-content-structure'); ?></li>
                                </ul>
                                <p><?php echo esc_html__('Una vez creado, deberás configurar los campos personalizados utilizando los Grupos de Campos.', 'seo-content-structure'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script type="text/javascript">
            (function($) {
                // Contador para nuevas taxonomías
                var taxonomyIndex = <?php echo $index; ?>;

                // Añadir nueva taxonomía
                $('#scs-add-taxonomy-button').on('click', function() {
                    var template = $('#scs-taxonomy-template').html();
                    var newTaxonomy = template.replace(/{index}/g, taxonomyIndex);
                    $('#scs-taxonomies-container').append(newTaxonomy);
                    taxonomyIndex++;
                });

                // Eliminar taxonomía
                $(document).on('click', '.remove-taxonomy', function() {
                    $(this).closest('.scs-taxonomy-item').remove();
                });

                // Generar slug automáticamente
                $('#singular_name').on('blur', function() {
                    if ($('#post_type').val() === '' || $('#action').val() === 'new') {
                        var slug = $(this).val().toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/\s+/g, '-');
                        $('#post_type').val(slug);
                    }
                });

                // Mostrar/ocultar selector de dashicons
                $('#show-dashicons').on('click', function() {
                    var $selector = $('.scs-dashicons-selector');

                    if ($selector.is(':empty')) {
                        // Cargar dashicons solo la primera vez
                        var dashicons = [
                            'dashicons-admin-appearance', 'dashicons-admin-collapse', 'dashicons-admin-comments',
                            'dashicons-admin-customizer', 'dashicons-admin-generic', 'dashicons-admin-home',
                            'dashicons-admin-links', 'dashicons-admin-media', 'dashicons-admin-multisite',
                            'dashicons-admin-network', 'dashicons-admin-page', 'dashicons-admin-plugins',
                            'dashicons-admin-post', 'dashicons-admin-settings', 'dashicons-admin-site',
                            'dashicons-admin-tools', 'dashicons-admin-users', 'dashicons-album',
                            'dashicons-align-center', 'dashicons-align-left', 'dashicons-align-none',
                            'dashicons-align-right', 'dashicons-analytics', 'dashicons-archive',
                            'dashicons-arrow-down', 'dashicons-arrow-down-alt', 'dashicons-arrow-down-alt2',
                            'dashicons-arrow-left', 'dashicons-arrow-left-alt', 'dashicons-arrow-left-alt2',
                            'dashicons-arrow-right', 'dashicons-arrow-right-alt', 'dashicons-arrow-right-alt2',
                            'dashicons-arrow-up', 'dashicons-arrow-up-alt', 'dashicons-arrow-up-alt2',
                            'dashicons-art', 'dashicons-awards', 'dashicons-backup', 'dashicons-book',
                            'dashicons-book-alt', 'dashicons-businessperson', 'dashicons-calendar',
                            'dashicons-calendar-alt', 'dashicons-camera', 'dashicons-cart', 'dashicons-category',
                            'dashicons-chart-area', 'dashicons-chart-bar', 'dashicons-chart-line',
                            'dashicons-chart-pie', 'dashicons-clipboard', 'dashicons-clock', 'dashicons-cloud',
                            'dashicons-code-standards', 'dashicons-color-picker', 'dashicons-controls-back',
                            'dashicons-controls-forward', 'dashicons-controls-pause', 'dashicons-controls-play',
                            'dashicons-controls-repeat', 'dashicons-controls-skipback', 'dashicons-controls-skipforward',
                            'dashicons-controls-volumeoff', 'dashicons-controls-volumeon', 'dashicons-dashboard',
                            'dashicons-desktop', 'dashicons-dismiss', 'dashicons-download', 'dashicons-edit',
                            'dashicons-editor-aligncenter', 'dashicons-editor-alignleft', 'dashicons-editor-alignright',
                            'dashicons-editor-break', 'dashicons-editor-code', 'dashicons-editor-contract',
                            'dashicons-editor-customchar', 'dashicons-editor-expand', 'dashicons-editor-help',
                            'dashicons-editor-indent', 'dashicons-editor-insertmore', 'dashicons-editor-justify',
                            'dashicons-editor-kitchensink', 'dashicons-editor-ol', 'dashicons-editor-outdent',
                            'dashicons-editor-paste-text', 'dashicons-editor-paste-word', 'dashicons-editor-quote',
                            'dashicons-editor-removeformatting', 'dashicons-editor-rtl', 'dashicons-editor-spellcheck',
                            'dashicons-editor-textcolor', 'dashicons-editor-ul', 'dashicons-editor-underline',
                            'dashicons-editor-unlink', 'dashicons-editor-video', 'dashicons-email',
                            'dashicons-email-alt', 'dashicons-email-alt2', 'dashicons-excerpt-view',
                            'dashicons-external', 'dashicons-facebook', 'dashicons-facebook-alt',
                            'dashicons-feedback', 'dashicons-filter', 'dashicons-flag', 'dashicons-food',
                            'dashicons-format-aside', 'dashicons-format-audio', 'dashicons-format-chat',
                            'dashicons-format-gallery', 'dashicons-format-image', 'dashicons-format-quote',
                            'dashicons-format-status', 'dashicons-format-video', 'dashicons-forms',
                            'dashicons-googleplus', 'dashicons-grid-view', 'dashicons-groups',
                            'dashicons-hammer', 'dashicons-heart', 'dashicons-hidden', 'dashicons-id',
                            'dashicons-id-alt', 'dashicons-image-crop', 'dashicons-image-filter',
                            'dashicons-image-flip-horizontal', 'dashicons-image-flip-vertical',
                            'dashicons-image-rotate', 'dashicons-image-rotate-left', 'dashicons-image-rotate-right',
                            'dashicons-images-alt', 'dashicons-images-alt2', 'dashicons-index-card',
                            'dashicons-info', 'dashicons-laptop', 'dashicons-layout', 'dashicons-leftright',
                            'dashicons-lightbulb', 'dashicons-list-view', 'dashicons-location',
                            'dashicons-location-alt', 'dashicons-lock', 'dashicons-marker', 'dashicons-media-archive',
                            'dashicons-media-audio', 'dashicons-media-code', 'dashicons-media-default',
                            'dashicons-media-document', 'dashicons-media-interactive', 'dashicons-media-spreadsheet',
                            'dashicons-media-text', 'dashicons-media-video', 'dashicons-megaphone',
                            'dashicons-menu', 'dashicons-microphone', 'dashicons-migrate', 'dashicons-minus',
                            'dashicons-money', 'dashicons-money-alt', 'dashicons-move', 'dashicons-nametag',
                            'dashicons-networking', 'dashicons-no', 'dashicons-no-alt', 'dashicons-palmtree',
                            'dashicons-paperclip', 'dashicons-performance', 'dashicons-phone', 'dashicons-playlist-audio',
                            'dashicons-playlist-video', 'dashicons-plus', 'dashicons-plus-alt', 'dashicons-portfolio',
                            'dashicons-post-status', 'dashicons-pressthis', 'dashicons-products', 'dashicons-randomize',
                            'dashicons-redo', 'dashicons-rest-api', 'dashicons-rss', 'dashicons-schedule',
                            'dashicons-screenoptions', 'dashicons-search', 'dashicons-share', 'dashicons-share-alt',
                            'dashicons-share-alt2', 'dashicons-shield', 'dashicons-shield-alt', 'dashicons-shortcode',
                            'dashicons-slides', 'dashicons-smartphone', 'dashicons-smiley', 'dashicons-sort',
                            'dashicons-sos', 'dashicons-star-empty', 'dashicons-star-filled', 'dashicons-star-half',
                            'dashicons-sticky', 'dashicons-store', 'dashicons-tablet', 'dashicons-tag',
                            'dashicons-tagcloud', 'dashicons-testimonial', 'dashicons-text', 'dashicons-thumbs-down',
                            'dashicons-thumbs-up', 'dashicons-tickets', 'dashicons-tickets-alt', 'dashicons-translation',
                            'dashicons-trash', 'dashicons-twitter', 'dashicons-undo', 'dashicons-universal-access',
                            'dashicons-universal-access-alt', 'dashicons-unlock', 'dashicons-update',
                            'dashicons-upload', 'dashicons-vault', 'dashicons-video-alt', 'dashicons-video-alt2',
                            'dashicons-video-alt3', 'dashicons-visibility', 'dashicons-warning', 'dashicons-welcome-add-page',
                            'dashicons-welcome-comments', 'dashicons-welcome-learn-more', 'dashicons-welcome-view-site',
                            'dashicons-welcome-widgets-menus', 'dashicons-welcome-write-blog', 'dashicons-wordpress',
                            'dashicons-wordpress-alt', 'dashicons-yes', 'dashicons-yes-alt'
                        ];

                        var html = '<div class="dashicons-grid">';
                        for (var i = 0; i < dashicons.length; i++) {
                            html += '<div class="dashicon-item" data-icon="' + dashicons[i] + '">';
                            html += '<span class="dashicons ' + dashicons[i] + '"></span>';
                            html += '</div>';
                        }
                        html += '</div>';

                        $selector.html(html);
                    }

                    $selector.slideToggle();
                });

                // Seleccionar un dashicon
                $(document).on('click', '.dashicon-item', function() {
                    var icon = $(this).data('icon');
                    $('#menu_icon').val(icon);
                    $('.scs-dashicons-selector').slideUp();
                });
            })(jQuery);
        </script>
    <?php endif; ?>
</div>
