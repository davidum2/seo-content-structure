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

// <<<--- LOG INICIO VISTA --- >>>
error_log('SCS_TRACE: === Vista post-type-builder.php INICIO ===');

// Obtener controlador de tipos de contenido
try {
    error_log('SCS_TRACE: Vista - Intentando instanciar PostTypeFactory');
    $post_type_factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
    error_log('SCS_TRACE: Vista - PostTypeFactory instanciada correctamente');
} catch (\Throwable $e) {
    error_log('SCS_FATAL: Vista - Error al instanciar PostTypeFactory: ' . $e->getMessage());
    echo '<div class="notice notice-error"><p>' . esc_html__('Error crítico al cargar la factory de tipos de contenido.', 'seo-content-structure') . '</p></div>';
    return;
}

// Determinar modo (nuevo o edición)
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';

error_log("SCS_TRACE: Vista - Modo: $action, Post Type: $post_type");

// Por defecto mostramos la lista de tipos de contenido
$is_edit_mode = ($action === 'edit' && !empty($post_type)) || $action === 'new';

// Obtener datos del tipo de contenido si estamos en modo edición
$post_type_obj = null;
$post_type_data = array();
// Establecer defaults para modo 'new' o si falla la carga
$post_type_defaults = [
    'args' => [
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'has_archive' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'active' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-admin-post',
        'labels' => ['singular_name' => '', 'name' => '']
    ],
    'taxonomies' => [],
    'fields' => [],
    'schema_type' => ''
];

if ($action === 'edit' && !empty($post_type)) {
    error_log("SCS_TRACE: Vista - Intentando obtener post type: $post_type");
    $post_type_obj = $post_type_factory->get_post_type($post_type);

    if ($post_type_obj) {
        error_log("SCS_TRACE: Vista - Post type encontrado correctamente en factory");

        // Depurar tipo de objeto
        error_log("SCS_TRACE: Vista - Clase del objeto recuperado: " . get_class($post_type_obj));

        // Fusionar datos guardados con defaults para asegurar que todas las claves existan
        $db_args = $post_type_obj->get_args();
        error_log("SCS_TRACE: Vista - Args recuperados: " . print_r($db_args, true));

        $post_type_data = array(
            'post_type' => $post_type_obj->get_post_type(),
            // Asegurar que los booleanos se carguen correctamente
            'args' => wp_parse_args($db_args, $post_type_defaults['args']),
            'taxonomies' => $post_type_obj->get_taxonomies(),
            'fields' => $post_type_obj->get_fields(),
            'schema_type' => $post_type_obj->get_schema_type()
        );

        // Forzar booleanos correctos desde $db_args si existen
        foreach (['public', 'show_ui', 'show_in_menu', 'has_archive', 'hierarchical', 'show_in_rest', 'active'] as $bool_key) {
            if (isset($db_args[$bool_key])) {
                $post_type_data['args'][$bool_key] = filter_var($db_args[$bool_key], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (isset($db_args['supports'])) {
            $post_type_data['args']['supports'] = $db_args['supports'];
        }
        if (isset($db_args['menu_icon'])) {
            $post_type_data['args']['menu_icon'] = $db_args['menu_icon'];
        }
        if (isset($db_args['labels'])) {
            $post_type_data['args']['labels'] = $db_args['labels'];
        }
    } else {
        error_log("SCS_ERROR: Vista - Post type NO encontrado en factory: $post_type");

        // Intentar buscar en la base de datos directamente
        global $wpdb;
        $table = $wpdb->prefix . 'scs_post_types';
        $query = $wpdb->prepare("SELECT * FROM $table WHERE post_type = %s", $post_type);
        $result = $wpdb->get_row($query, ARRAY_A);

        if ($result) {
            error_log("SCS_TRACE: Vista - Post type encontrado en DB pero no en factory. ID: " . $result['id']);

            // Procesar datos desde DB
            $config = isset($result['config']) ? $result['config'] : '';
            if (!empty($config)) {
                // Intentar deserializar
                $config_data = maybe_unserialize($config);

                // Intentar JSON si es string
                if (is_string($config_data)) {
                    $json_data = json_decode($config_data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $config_data = $json_data;
                    } else {
                        $unserialized = @unserialize($config_data);
                        if ($unserialized !== false || $config_data === 'b:0;') {
                            $config_data = $unserialized;
                        }
                    }
                }

                error_log("SCS_TRACE: Vista - Config procesado desde DB: " . print_r($config_data, true));

                if (is_array($config_data)) {
                    $post_type_data = [
                        'post_type' => $post_type,
                        'args' => isset($config_data['args']) && is_array($config_data['args'])
                            ? $config_data['args']
                            : $post_type_defaults['args'],
                        'taxonomies' => isset($config_data['taxonomies']) && is_array($config_data['taxonomies'])
                            ? $config_data['taxonomies']
                            : [],
                        'fields' => isset($config_data['fields']) && is_array($config_data['fields'])
                            ? $config_data['fields']
                            : [],
                        'schema_type' => isset($config_data['schema_type']) ? $config_data['schema_type'] : ''
                    ];

                    // Asegurar active desde BD
                    $post_type_data['args']['active'] = isset($result['active']) ? (bool)$result['active'] : true;

                    error_log("SCS_TRACE: Vista - Datos preparados desde DB para edición");
                }
            }
        } else {
            error_log("SCS_ERROR: Vista - Post type no encontrado en DB ni en factory");
        }

        // Si no se pudo cargar el objeto (ej. error en DB), usar defaults
        if (empty($post_type_data) || !isset($post_type_data['args']) || !is_array($post_type_data['args'])) {
            error_log("SCS_ERROR: Vista - Usando defaults porque no se pudo cargar datos");
            $post_type_data = $post_type_defaults;
            $post_type_data['post_type'] = $post_type; // Mantener el slug si venía en URL
        }
    }
} else if ($action === 'new') {
    error_log("SCS_TRACE: Vista - Inicializando datos para nuevo post type");
    $post_type_data = $post_type_defaults;
}

// Verificar que los datos necesarios existen
error_log("SCS_TRACE: Vista - Verificando estructura de datos de post type");
if (!isset($post_type_data['args']) || !is_array($post_type_data['args'])) {
    error_log("SCS_ERROR: Vista - Datos del post type incorrectos, args no es un array o no existe");
    $post_type_data['args'] = $post_type_defaults['args'];
}
if (!isset($post_type_data['args']['labels']) || !is_array($post_type_data['args']['labels'])) {
    error_log("SCS_ERROR: Vista - Labels no es un array o no existe");
    $post_type_data['args']['labels'] = $post_type_defaults['args']['labels'];
}

// Lista de taxonomías disponibles
try {
    $taxonomies = \SEOContentStructure\Utilities\Helper::get_taxonomies(true);
} catch (\Throwable $e) {
    error_log("SCS_ERROR: Vista - Error al cargar taxonomías: " . $e->getMessage());
    $taxonomies = [];
}

// Tipos de schema disponibles
try {
    $schema_types = \SEOContentStructure\Utilities\Helper::get_schema_types();
} catch (\Throwable $e) {
    error_log("SCS_ERROR: Vista - Error al cargar tipos de schema: " . $e->getMessage());
    $schema_types = [];
}

// Tipos de campos disponibles
try {
    $field_types = \SEOContentStructure\Utilities\Helper::get_field_types();
} catch (\Throwable $e) {
    error_log("SCS_ERROR: Vista - Error al cargar tipos de campos: " . $e->getMessage());
    $field_types = [];
}

// Verificar si hay mensaje de error
$error_message = isset($_GET['error']) ? sanitize_text_field(urldecode($_GET['error'])) : '';

// Verificar si hay mensaje de éxito
$success_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

error_log("SCS_TRACE: Vista - Iniciando renderizado HTML");

?>
<div class="wrap scs-admin-page scs-post-types-page">
    <?php if (!$is_edit_mode) : ?>
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
            <?php
            // Asegurarse de que $registered_post_types es un array antes de usarlo
            $registered_post_types_raw = $post_type_factory->get_registered_post_types();
            $registered_post_types = is_array($registered_post_types_raw) ? $registered_post_types_raw : [];

            // Depurar post types registrados
            error_log("SCS_TRACE: Vista - Total post types registrados: " . count($registered_post_types));
            foreach ($registered_post_types as $pt_key => $pt_obj) {
                error_log("SCS_TRACE: Vista - Post type[$pt_key]: " . $pt_obj->get_post_type());
            }

            // Filtrar CPTs nativos para no mostrarlos en la lista editable
            $custom_post_types_only = array_filter($registered_post_types, function ($pt) {
                return !in_array($pt->get_post_type(), array('post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'e-floating-buttons', 'elementor_library'));
            });
            ?>

            <?php if (empty($custom_post_types_only)) : ?>
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
                        <?php foreach ($custom_post_types_only as $pt) : ?>
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
                                <td>
                                    <?php
                                    $pt_taxonomies = $pt->get_taxonomies();
                                    if (!empty($pt_taxonomies) && is_array($pt_taxonomies)) {
                                        $tax_names = array();
                                        foreach ($pt_taxonomies as $tax_name => $tax_args) {
                                            // Intentar obtener la etiqueta singular, si no, usar el nombre
                                            $tax_obj = get_taxonomy($tax_name);
                                            $tax_names[] = $tax_obj ? $tax_obj->labels->singular_name : (isset($tax_args['labels']['singular_name']) ? $tax_args['labels']['singular_name'] : $tax_name);
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
                                    <?php
                                    // Usar is_active() que debería obtener el valor de la DB
                                    $is_active = method_exists($pt, 'is_active') ? $pt->is_active() : (isset($pt->get_args()['active']) && $pt->get_args()['active']);
                                    ?>
                                    <?php if ($is_active) : ?>
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
        <?php if ($action === 'new') : ?>
            <h1><?php echo esc_html__('Añadir Tipo de Contenido', 'seo-content-structure'); ?></h1>
        <?php else : ?>
            <h1><?php echo esc_html__('Editar Tipo de Contenido', 'seo-content-structure'); ?>: <?php echo esc_html(isset($post_type_data['args']['labels']['singular_name']) ? $post_type_data['args']['labels']['singular_name'] : $post_type); ?></h1>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form id="scs-post-type-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="scs_save_post_type">
            <input type="hidden" name="current_post_type_slug" value="<?php echo esc_attr($action === 'edit' ? ($post_type_data['post_type'] ?? $post_type) : ''); ?>">
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
                                            <input type="text" id="singular_name" name="args[labels][singular_name]" value="<?php echo esc_attr($post_type_data['args']['labels']['singular_name']); ?>" class="regular-text" required>
                                            <p class="description"><?php echo esc_html__('Nombre en singular para el tipo de contenido (ej. Producto).', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="name"><?php echo esc_html__('Nombre Plural', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="name" name="args[labels][name]" value="<?php echo esc_attr($post_type_data['args']['labels']['name']); ?>" class="regular-text" required>
                                            <p class="description"><?php echo esc_html__('Nombre en plural para el tipo de contenido (ej. Productos).', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="post_type"><?php echo esc_html__('Slug', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="post_type" name="post_type" value="<?php echo esc_attr($action === 'edit' ? ($post_type_data['post_type'] ?? $post_type) : ''); ?>" class="regular-text" <?php echo $action === 'edit' ? 'readonly' : 'required'; ?>>
                                            <p class="description"><?php echo esc_html__('Identificador único para el tipo de contenido. Solo letras minúsculas, números y guiones (ej. producto). No se puede cambiar después de crear.', 'seo-content-structure'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="menu_icon"><?php echo esc_html__('Icono de Menú', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="menu_icon" name="args[menu_icon]" value="<?php echo esc_attr($post_type_data['args']['menu_icon']); ?>" class="regular-text">
                                            <p class="description"><?php echo esc_html__('Clase de dashicon (ej. dashicons-cart) o URL a una imagen.', 'seo-content-structure'); ?></p>
                                            <div class="scs-dashicons-list">
                                                <button type="button" class="button" id="show-dashicons"><?php echo esc_html__('Mostrar Dashicons', 'seo-content-structure'); ?></button>
                                                <div class="scs-dashicons-selector" style="display:none;">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php echo esc_html__('Visibilidad y Características', 'seo-content-structure'); ?></label>
                                        </th>
                                        <td>
                                            <fieldset>
                                                <legend class="screen-reader-text"><span><?php echo esc_html__('Visibilidad y Características', 'seo-content-structure'); ?></span></legend>
                                                <label>
                                                    <input type="checkbox" name="args[public]" value="1" <?php checked(filter_var($post_type_data['args']['public'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Público', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Controla si los posts son visibles públicamente en el frontend y en consultas.', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[show_ui]" value="1" <?php checked(filter_var($post_type_data['args']['show_ui'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Mostrar UI', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Genera una interfaz de usuario en el admin para gestionar este CPT (generalmente igual que "Público").', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[show_in_menu]" value="1" <?php checked(filter_var($post_type_data['args']['show_in_menu'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Mostrar en Menú', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Muestra el tipo de contenido en el menú lateral del administrador.', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[has_archive]" value="1" <?php checked(filter_var($post_type_data['args']['has_archive'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Página de Archivo', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Permite una página de archivo para este CPT (ej. tusitio.com/terapias/).', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[hierarchical]" value="1" <?php checked(filter_var($post_type_data['args']['hierarchical'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Jerárquico (como páginas)', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Permite relaciones padre/hijo entre las entradas.', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[show_in_rest]" value="1" <?php checked(filter_var($post_type_data['args']['show_in_rest'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Mostrar en REST API', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Hace que el CPT y sus entradas sean accesibles a través de la API REST de WordPress.', 'seo-content-structure'); ?></p>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="args[active]" value="1" <?php checked(filter_var($post_type_data['args']['active'], FILTER_VALIDATE_BOOLEAN)); ?>>
                                                    <?php echo esc_html__('Activo', 'seo-content-structure'); ?>
                                                    <p class="description" style="margin-left: 20px;"><?php echo esc_html__('Desmarcar para desactivar este CPT sin eliminarlo.', 'seo-content-structure'); ?></p>
                                                </label>
                                            </fieldset>
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
                                // ** Refactorizado: usar el valor de $post_type_data **
                                $supports = $post_type_data['args']['supports'];
                                if (!is_array($supports)) { // Asegurar que sea un array
                                    $supports = $post_type_defaults['args']['supports'];
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
                                    'custom-fields' => __('Campos personalizados (Metadatos nativos)', 'seo-content-structure'),
                                    'page-attributes' => __('Atributos de página (orden y jerarquía)', 'seo-content-structure'),
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
                                    // ** Refactorizado: usar el valor de $post_type_data **
                                    $current_taxonomies = array_keys($post_type_data['taxonomies']);
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
                                        // ** Refactorizado: usar el valor de $post_type_data **
                                        $custom_taxonomies = array_filter($post_type_data['taxonomies'], function ($tax_name) use ($taxonomies) {
                                            return !array_key_exists($tax_name, $taxonomies);
                                        }, ARRAY_FILTER_USE_KEY);

                                        $index = 0;
                                        if (is_array($custom_taxonomies)) {
                                            foreach ($custom_taxonomies as $tax_name => $tax_data) :
                                                $tax_args = $tax_data['args'] ?? [];
                                                $tax_labels = $tax_args['labels'] ?? [];
                                                $singular = $tax_labels['singular_name'] ?? ($tax_data['singular'] ?? ''); // Compatibilidad
                                                $plural = $tax_labels['name'] ?? ($tax_data['plural'] ?? ''); // Compatibilidad
                                                $hierarchical = $tax_args['hierarchical'] ?? true;
                                        ?>
                                                <div class="scs-taxonomy-item">
                                                    <h4><?php echo esc_html__('Nueva Taxonomía', 'seo-content-structure'); ?></h4>
                                                    <table class="form-table">
                                                        <tr>
                                                            <th scope="row">
                                                                <label for="custom_tax_<?php echo $index; ?>_singular"><?php echo esc_html__('Nombre Singular', 'seo-content-structure'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text" id="custom_tax_<?php echo $index; ?>_singular" name="custom_taxonomies[<?php echo $index; ?>][singular]" value="<?php echo esc_attr($singular); ?>" class="regular-text" required>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">
                                                                <label for="custom_tax_<?php echo $index; ?>_plural"><?php echo esc_html__('Nombre Plural', 'seo-content-structure'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text" id="custom_tax_<?php echo $index; ?>_plural" name="custom_taxonomies[<?php echo $index; ?>][plural]" value="<?php echo esc_attr($plural); ?>" class="regular-text" required>
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
                                                                    <input type="checkbox" name="custom_taxonomies[<?php echo $index; ?>][hierarchical]" value="1" <?php checked(filter_var($hierarchical, FILTER_VALIDATE_BOOLEAN)); ?>>
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
                                        } // end if is_array
                                        ?>
                                    </div>

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
                                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($post_type_data['schema_type'], $type); ?>>
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
                // Asegurar que el objeto $post_type_data['taxonomies'] existe y es un array
                <?php
                $php_index = 0;
                if (isset($post_type_data['taxonomies']) && is_array($post_type_data['taxonomies'])) {
                    $custom_taxonomies_for_js = array_filter($post_type_data['taxonomies'], function ($tax_name) use ($taxonomies) {
                        return !array_key_exists($tax_name, $taxonomies);
                    }, ARRAY_FILTER_USE_KEY);
                    $php_index = count($custom_taxonomies_for_js);
                }
                ?>
                var taxonomyIndex = <?php echo $php_index; ?>; // Usar el índice calculado en PHP

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

                // Generar slug automáticamente solo si está vacío y es acción 'new'
                $('#singular_name').on('blur', function() {
                    var $slugInput = $('#post_type');
                    var currentAction = '<?php echo esc_js($action); ?>'; // Obtener la acción PHP
                    // Solo autogenerar si es nuevo Y el campo slug está vacío
                    if (currentAction === 'new' && $slugInput.val() === '') {
                        var singularName = $(this).val();
                        if (singularName) {
                            var slug = singularName.toLowerCase()
                                .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // Quitar acentos
                                .replace(/[^a-z0-9\s-]/g, '') // Quitar caracteres no alfanuméricos excepto espacios y guiones
                                .replace(/\s+/g, '-') // Reemplazar espacios con guiones
                                .replace(/-+/g, '-'); // Reemplazar múltiples guiones con uno solo
                            // Limitar longitud (opcional)
                            slug = slug.substring(0, 20);
                            $slugInput.val(slug);
                        }
                    }
                });

                // Función para cargar y mostrar dashicons
                function loadAndShowDashicons() {
                    var $selector = $('.scs-dashicons-selector');
                    if ($selector.is(':empty')) {
                        $selector.html('<p>Cargando iconos...</p>'); // Mensaje de carga
                        // Lista de Dashicons (puedes expandirla si es necesario)
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
                        $.each(dashicons, function(i, iconClass) {
                            html += '<div class="dashicon-item" data-icon="' + iconClass + '" title="' + iconClass + '">';
                            html += '<span class="dashicons ' + iconClass + '"></span>';
                            html += '</div>';
                        });
                        html += '</div>';

                        $selector.html(html); // Reemplazar mensaje de carga con la rejilla
                    }
                    $selector.slideToggle(); // Mostrar/ocultar
                }

                // Mostrar/ocultar selector de dashicons
                $('#show-dashicons').on('click', loadAndShowDashicons);

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
