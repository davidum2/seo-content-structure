<?php

/**
 * Vista para mostrar la lista de tipos de contenido personalizados
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Asegurarse de que tenemos la factory
$post_type_factory = isset($this->post_type_factory) ? $this->post_type_factory : new \SEOContentStructure\PostTypes\PostTypeFactory();

// Obtener todos los tipos de contenido registrados
$registered_post_types = $post_type_factory->get_registered_post_types();

// Filtrar CPTs nativos para no mostrarlos en la lista editable
$custom_post_types_only = array_filter($registered_post_types, function ($pt) {
    $native_types = array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation');
    return !in_array($pt->get_post_type(), $native_types);
});

// Verificar si hay mensaje de error o éxito
$error_message = isset($_GET['error']) ? \sanitize_text_field($_GET['error']) : '';
$success_message = isset($_GET['message']) ? \sanitize_text_field($_GET['message']) : '';

?>
<div class="wrap scs-admin-page scs-post-types-page">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Tipos de Contenido Personalizados', 'seo-content-structure'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=new')); ?>" class="page-title-action"><?php echo esc_html__('Añadir Nuevo', 'seo-content-structure'); ?></a>
    <hr class="wp-header-end">

    <?php if (!empty($error_message)) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="scs-post-types-list">
        <?php if (empty($custom_post_types_only)) : ?>
            <div class="scs-no-items-message">
                <p><?php echo esc_html__('No se encontraron tipos de contenido personalizados.', 'seo-content-structure'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=new')); ?>" class="button button-primary"><?php echo esc_html__('Crear el primero', 'seo-content-structure'); ?></a>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th scope="col" class="column-title column-primary"><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></th>
                        <th scope="col"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></th>
                        <th scope="col"><?php echo esc_html__('Schema', 'seo-content-structure'); ?></th>
                        <th scope="col"><?php echo esc_html__('Estado', 'seo-content-structure'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_post_types_only as $pt) :
                        $is_active = isset($pt->get_args()['active']) ? (bool)$pt->get_args()['active'] : true;
                        $schema_type = method_exists($pt, 'get_schema_type') ? $pt->get_schema_type() : '';
                    ?>
                        <tr>
                            <td class="column-title column-primary">
                                <strong>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=edit&post_type=' . $pt->get_post_type())); ?>" class="row-title">
                                        <?php echo esc_html(isset($pt->get_args()['labels']['singular_name']) ? $pt->get_args()['labels']['singular_name'] : $pt->get_post_type()); ?>
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
                            <td><?php echo esc_html($schema_type ?: '-'); ?></td>
                            <td><?php echo $is_active ? '<span class="dashicons dashicons-yes" style="color:green;"></span>' : '<span class="dashicons dashicons-no" style="color:red;"></span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
