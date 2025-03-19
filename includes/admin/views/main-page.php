<?php

/**
 * Página principal del plugin
 *
 * @package SEOContentStructure
 * @subpackage Admin\Views
 */

// Evitar acceso directo
if (! defined('ABSPATH')) {
    exit;
}

// Obtener datos para el dashboard
$post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
$registered_post_types = $post_type_factory->get_registered_post_types();
$post_type_count = count($registered_post_types);

$field_group_controller = new \SEOContentStructure\Admin\FieldGroupController();
$field_groups = $field_group_controller->get_field_groups();
$field_group_count = count($field_groups);

$schema_types = array(
    'Service',
    'Product',
    'Organization',
    'LocalBusiness',
    'Person',
    'Event',
    'Article',
    'Recipe',
    'FAQ'
);

?>
<div class="wrap scs-admin-page scs-dashboard">
    <h1><?php echo esc_html__('SEO Content Structure Dashboard', 'seo-content-structure'); ?></h1>

    <div class="scs-admin-notices">
        <?php
        // Mensaje de bienvenida solo para nuevas instalaciones
        $first_run = get_option('scs_first_run', true);
        if ($first_run) {
            update_option('scs_first_run', false);
        ?>
            <div class="scs-welcome-panel">
                <h2><?php echo esc_html__('¡Bienvenido a SEO Content Structure!', 'seo-content-structure'); ?></h2>
                <p class="about-description"><?php echo esc_html__('Este plugin te permite crear tipos de contenido personalizados con campos avanzados y estructuras JSON-LD para mejorar el SEO de tu sitio.', 'seo-content-structure'); ?></p>
                <div class="welcome-panel-column-container">
                    <div class="welcome-panel-column">
                        <h3><?php echo esc_html__('Primeros pasos', 'seo-content-structure'); ?></h3>
                        <ul>
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups')); ?>" class="button button-primary"><?php echo esc_html__('Crear Grupo de Campos', 'seo-content-structure'); ?></a></li>
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types')); ?>" class="button"><?php echo esc_html__('Crear Tipo de Contenido', 'seo-content-structure'); ?></a></li>
                        </ul>
                    </div>
                    <div class="welcome-panel-column">
                        <h3><?php echo esc_html__('Más acciones', 'seo-content-structure'); ?></h3>
                        <ul>
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=scs-schema-editor')); ?>"><?php echo esc_html__('Editor de Schema', 'seo-content-structure'); ?></a></li>
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=scs-settings')); ?>"><?php echo esc_html__('Configuración', 'seo-content-structure'); ?></a></li>
                        </ul>
                    </div>
                    <div class="welcome-panel-column welcome-panel-last">
                        <h3><?php echo esc_html__('Recursos', 'seo-content-structure'); ?></h3>
                        <ul>
                            <li><a href="https://schema.org/docs/full.html" target="_blank"><?php echo esc_html__('Referencias de Schema.org', 'seo-content-structure'); ?></a></li>
                            <li><a href="https://developers.google.com/search/docs/guides/intro-structured-data" target="_blank"><?php echo esc_html__('Guía de Google para datos estructurados', 'seo-content-structure'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>

    <div class="scs-dashboard-content">
        <div class="scs-dashboard-widgets">
            <!-- Estadísticas -->
            <div class="scs-dashboard-widget scs-stats-widget">
                <h3><?php echo esc_html__('Resumen', 'seo-content-structure'); ?></h3>
                <div class="scs-stats-grid">
                    <div class="scs-stat-item">
                        <div class="scs-stat-number"><?php echo esc_html($post_type_count); ?></div>
                        <div class="scs-stat-label"><?php echo esc_html__('Tipos de Contenido', 'seo-content-structure'); ?></div>
                    </div>
                    <div class="scs-stat-item">
                        <div class="scs-stat-number"><?php echo esc_html($field_group_count); ?></div>
                        <div class="scs-stat-label"><?php echo esc_html__('Grupos de Campos', 'seo-content-structure'); ?></div>
                    </div>
                    <div class="scs-stat-item">
                        <div class="scs-stat-number"><?php echo count($schema_types); ?></div>
                        <div class="scs-stat-label"><?php echo esc_html__('Tipos de Schema', 'seo-content-structure'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Accesos rápidos -->
            <div class="scs-dashboard-widget scs-quick-actions-widget">
                <h3><?php echo esc_html__('Acciones Rápidas', 'seo-content-structure'); ?></h3>
                <div class="scs-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=new')); ?>" class="scs-quick-action-button">
                        <span class="dashicons dashicons-forms"></span>
                        <?php echo esc_html__('Nuevo Grupo de Campos', 'seo-content-structure'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=new')); ?>" class="scs-quick-action-button">
                        <span class="dashicons dashicons-admin-post"></span>
                        <?php echo esc_html__('Nuevo Tipo de Contenido', 'seo-content-structure'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-schema-editor&action=new')); ?>" class="scs-quick-action-button">
                        <span class="dashicons dashicons-editor-code"></span>
                        <?php echo esc_html__('Nuevo Schema', 'seo-content-structure'); ?>
                    </a>
                </div>
            </div>

            <!-- Tipos de contenido registrados -->
            <div class="scs-dashboard-widget scs-post-types-widget">
                <h3><?php echo esc_html__('Tipos de Contenido Registrados', 'seo-content-structure'); ?></h3>
                <div class="scs-registered-items">
                    <?php if (empty($registered_post_types)) : ?>
                        <p class="scs-no-items"><?php echo esc_html__('No hay tipos de contenido registrados.', 'seo-content-structure'); ?></p>
                    <?php else : ?>
                        <table class="scs-items-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></th>
                                    <th><?php echo esc_html__('Slug', 'seo-content-structure'); ?></th>
                                    <th><?php echo esc_html__('Schema Type', 'seo-content-structure'); ?></th>
                                    <th><?php echo esc_html__('Campos', 'seo-content-structure'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registered_post_types as $post_type) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types&action=edit&post_type=' . $post_type->get_post_type())); ?>">
                                                <?php echo esc_html($post_type->get_labels()['singular_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($post_type->get_post_type()); ?></td>
                                        <td><?php echo esc_html($post_type->get_schema_type() ?: '-'); ?></td>
                                        <td><?php echo count($post_type->get_fields()); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <p class="scs-dashboard-action-link">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-post-types')); ?>">
                        <?php echo esc_html__('Ver todos los tipos de contenido', 'seo-content-structure'); ?> →
                    </a>
                </p>
            </div>

            <!-- Grupos de campos -->
            <div class="scs-dashboard-widget scs-field-groups-widget">
                <h3><?php echo esc_html__('Grupos de Campos Recientes', 'seo-content-structure'); ?></h3>
                <div class="scs-registered-items">
                    <?php if (empty($field_groups)) : ?>
                        <p class="scs-no-items"><?php echo esc_html__('No hay grupos de campos registrados.', 'seo-content-structure'); ?></p>
                    <?php else : ?>
                        <table class="scs-items-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Título', 'seo-content-structure'); ?></th>
                                    <th><?php echo esc_html__('Ubicación', 'seo-content-structure'); ?></th>
                                    <th><?php echo esc_html__('Campos', 'seo-content-structure'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Mostrar solo los 5 más recientes
                                $recent_field_groups = array_slice($field_groups, 0, 5);
                                foreach ($recent_field_groups as $group) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups&action=edit&group_id=' . $group['id'])); ?>">
                                                <?php echo esc_html($group['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php
                                            if (! empty($group['location'])) {
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
                                        <td><?php echo ! empty($group['fields']) ? count($group['fields']) : 0; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <p class="scs-dashboard-action-link">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-field-groups')); ?>">
                        <?php echo esc_html__('Ver todos los grupos de campos', 'seo-content-structure'); ?> →
                    </a>
                </p>
            </div>

            <!-- Tipos de Schema -->
            <div class="scs-dashboard-widget scs-schema-types-widget">
                <h3><?php echo esc_html__('Tipos de Schema Disponibles', 'seo-content-structure'); ?></h3>
                <div class="scs-schema-types">
                    <ul class="scs-schema-types-list">
                        <?php foreach ($schema_types as $schema_type) : ?>
                            <li class="scs-schema-type-item">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=scs-schema-editor&action=new&type=' . $schema_type)); ?>">
                                    <span class="dashicons dashicons-editor-code"></span>
                                    <?php echo esc_html($schema_type); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p class="scs-dashboard-action-link">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=scs-schema-editor')); ?>">
                        <?php echo esc_html__('Ir al editor de Schema', 'seo-content-structure'); ?> →
                    </a>
                </p>
            </div>

            <!-- Ayuda y recursos -->
            <div class="scs-dashboard-widget scs-help-widget">
                <h3><?php echo esc_html__('Ayuda y Recursos', 'seo-content-structure'); ?></h3>
                <div class="scs-help-resources">
                    <div class="scs-help-item">
                        <h4><span class="dashicons dashicons-book"></span> <?php echo esc_html__('Documentación', 'seo-content-structure'); ?></h4>
                        <p><?php echo esc_html__('Consulta nuestra documentación completa para aprender a usar todas las funciones del plugin.', 'seo-content-structure'); ?></p>
                        <a href="https://ejemplo.com/docs" target="_blank" class="button button-secondary"><?php echo esc_html__('Ver documentación', 'seo-content-structure'); ?></a>
                    </div>

                    <div class="scs-help-item">
                        <h4><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Soporte', 'seo-content-structure'); ?></h4>
                        <p><?php echo esc_html__('¿Necesitas ayuda? Ponte en contacto con nuestro equipo de soporte.', 'seo-content-structure'); ?></p>
                        <a href="https://ejemplo.com/support" target="_blank" class="button button-secondary"><?php echo esc_html__('Contactar soporte', 'seo-content-structure'); ?></a>
                    </div>

                    <div class="scs-help-item">
                        <h4><span class="dashicons dashicons-welcome-learn-more"></span> <?php echo esc_html__('Tutoriales', 'seo-content-structure'); ?></h4>
                        <p><?php echo esc_html__('Aprende con nuestros tutoriales paso a paso para sacar el máximo provecho del plugin.', 'seo-content-structure'); ?></p>
                        <a href="https://ejemplo.com/tutorials" target="_blank" class="button button-secondary"><?php echo esc_html__('Ver tutoriales', 'seo-content-structure'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
