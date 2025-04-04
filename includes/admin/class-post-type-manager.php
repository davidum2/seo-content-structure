<?php

namespace SEOContentStructure\Admin;

/**
 * Clase para manejar la administración de Post Types
 */
class PostTypeManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Manejar el guardado de tipos de contenido
        add_action('admin_post_save_custom_post_type', [$this, 'save_custom_post_type']);

        // Manejar la eliminación de tipos de contenido
        add_action('admin_post_delete_custom_post_type', [$this, 'delete_custom_post_type']);
    }

    /**
     * Renderiza la página principal del plugin
     */
    public function render_main_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Estructura de Contenido SEO', 'seo-content-structure'); ?></h1>
            <p><?php echo esc_html__('Bienvenido al plugin de Estructura de Contenido SEO. Utiliza el menú lateral para gestionar tus tipos de contenido personalizados.', 'seo-content-structure'); ?></p>
        </div>
    <?php
    }

    /**
     * Renderiza la página de administración de post types
     */
    public function render_admin_page()
    {
        $saved_post_types = get_option('scs_custom_post_types', []);
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
    ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Tipos de Contenido', 'seo-content-structure'); ?></h1>

            <?php if ($message === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Tipo de contenido guardado correctamente.', 'seo-content-structure'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($message === 'deleted'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Tipo de contenido eliminado correctamente.', 'seo-content-structure'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_custom_post_type', 'scs_post_type_nonce'); ?>
                <input type="hidden" name="action" value="save_custom_post_type">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="post_type_name"><?php echo esc_html__('Nombre del Tipo de Contenido', 'seo-content-structure'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="post_type_name" id="post_type_name" class="regular-text" required>
                            <p class="description"><?php echo esc_html__('Nombre único para el tipo de contenido (ejemplo: libro, producto). Solo letras minúsculas, números y guiones bajos.', 'seo-content-structure'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="post_type_label"><?php echo esc_html__('Etiqueta', 'seo-content-structure'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="post_type_label" id="post_type_label" class="regular-text" required>
                            <p class="description"><?php echo esc_html__('Nombre legible para mostrar en el admin (ej: Libro, Producto)', 'seo-content-structure'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="post_type_plural"><?php echo esc_html__('Etiqueta en plural', 'seo-content-structure'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="post_type_plural" id="post_type_plural" class="regular-text">
                            <p class="description"><?php echo esc_html__('Nombre legible en plural (ej: Libros, Productos). Si se deja vacío, se añadirá una "s" al final.', 'seo-content-structure'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="post_type_supports"><?php echo esc_html__('Características', 'seo-content-structure'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="supports[]" value="title" checked> <?php echo esc_html__('Título', 'seo-content-structure'); ?></label><br>
                                <label><input type="checkbox" name="supports[]" value="editor" checked> <?php echo esc_html__('Editor', 'seo-content-structure'); ?></label><br>
                                <label><input type="checkbox" name="supports[]" value="thumbnail"> <?php echo esc_html__('Imagen destacada', 'seo-content-structure'); ?></label><br>
                                <label><input type="checkbox" name="supports[]" value="excerpt"> <?php echo esc_html__('Extracto', 'seo-content-structure'); ?></label><br>
                                <label><input type="checkbox" name="supports[]" value="custom-fields"> <?php echo esc_html__('Campos personalizados', 'seo-content-structure'); ?></label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="post_type_icon"><?php echo esc_html__('Icono', 'seo-content-structure'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="post_type_icon" id="post_type_icon" class="regular-text" value="dashicons-admin-post">
                            <p class="description"><?php echo esc_html__('Clase de icono de Dashicons (ejemplo: dashicons-admin-post, dashicons-book, dashicons-products)', 'seo-content-structure'); ?></p>
                            <p><a href="https://developer.wordpress.org/resource/dashicons/" target="_blank"><?php echo esc_html__('Ver listado completo de iconos', 'seo-content-structure'); ?></a></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Guardar Tipo de Contenido', 'seo-content-structure')); ?>
            </form>

            <h2><?php echo esc_html__('Tipos de Contenido Existentes', 'seo-content-structure'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Nombre', 'seo-content-structure'); ?></th>
                        <th><?php echo esc_html__('Etiqueta', 'seo-content-structure'); ?></th>
                        <th><?php echo esc_html__('Acciones', 'seo-content-structure'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($saved_post_types)): ?>
                        <tr>
                            <td colspan="3"><?php echo esc_html__('No hay tipos de contenido personalizados.', 'seo-content-structure'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($saved_post_types as $post_type => $data): ?>
                            <tr>
                                <td><?php echo esc_html($post_type); ?></td>
                                <td><?php echo esc_html($data['label']); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <?php wp_nonce_field('delete_custom_post_type', 'scs_delete_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_custom_post_type">
                                        <input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">
                                        <button type="submit" class="button button-small" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este tipo de contenido?', 'seo-content-structure')); ?>')">
                                            <?php echo esc_html__('Eliminar', 'seo-content-structure'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php
    }

    /**
     * Guarda un tipo de contenido personalizado
     */
    public function save_custom_post_type()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        check_admin_referer('save_custom_post_type', 'scs_post_type_nonce');

        $post_type_name = sanitize_key($_POST['post_type_name']);
        $post_type_label = sanitize_text_field($_POST['post_type_label']);
        $post_type_plural = !empty($_POST['post_type_plural']) ? sanitize_text_field($_POST['post_type_plural']) : $post_type_label . 's';
        $supports = isset($_POST['supports']) ? array_map('sanitize_text_field', $_POST['supports']) : ['title', 'editor'];
        $icon = isset($_POST['post_type_icon']) ? sanitize_text_field($_POST['post_type_icon']) : 'dashicons-admin-post';

        // Validar el nombre del post type
        if (empty($post_type_name) || !preg_match('/^[a-z0-9_]+$/', $post_type_name)) {
            wp_die(__('El nombre del tipo de contenido debe contener solo letras minúsculas, números y guiones bajos.', 'seo-content-structure'));
        }

        // Verificar si ya existe un post type con este nombre
        if (post_type_exists($post_type_name)) {
            wp_die(__('Ya existe un tipo de contenido con este nombre.', 'seo-content-structure'));
        }

        // Configuración del post type
        $config = [
            'args' => [
                'labels' => [
                    'name' => $post_type_plural,
                    'singular_name' => $post_type_label,
                    'menu_name' => $post_type_plural,
                ],
                'menu_icon' => $icon,
                'supports' => $supports,
                'has_archive' => true,
                'rewrite' => ['slug' => $post_type_name],
                'show_in_rest' => true,
            ],
            'taxonomies' => [],
        ];

        // Insertar en la tabla wp_scs_post_types
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        $wpdb->insert(
            $table_name,
            [
                'post_type' => $post_type_name,
                'config' => json_encode($config),
                'active' => 1,
            ],
            [
                '%s',
                '%s',
                '%d',
            ]
        );

        // Invalidar cache
        delete_transient('scs_post_types_cache');

        // Crear una instancia temporal de PostTypeFactory para registrar el post type
        $factory = new \SEOContentStructure\PostTypes\PostTypeFactory();

        // Limpiar la caché de reglas de reescritura
        flush_rewrite_rules();

        wp_redirect(add_query_arg('message', 'saved', admin_url('admin.php?page=scs-post-types')));
        exit;
    }

    /**
     * Elimina un tipo de contenido personalizado
     */
    public function delete_custom_post_type()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        check_admin_referer('delete_custom_post_type', 'scs_delete_nonce');

        $post_type = sanitize_key($_POST['post_type']);

        if (empty($post_type)) {
            wp_die(__('No se especificó un tipo de contenido para eliminar.', 'seo-content-structure'));
        }

        // Eliminar de la tabla wp_scs_post_types
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_post_types';

        $wpdb->delete(
            $table_name,
            ['post_type' => $post_type],
            ['%s']
        );

        // Invalidar cache
        delete_transient('scs_post_types_cache');

        // Limpiar la caché de reglas de reescritura
        flush_rewrite_rules();

        wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=scs-post-types')));
        exit;
    }
}
