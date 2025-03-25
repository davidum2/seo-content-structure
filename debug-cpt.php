<?php

/**
 * Script de depuración para Custom Post Types
 *
 * Este archivo debe colocarse en la raíz del plugin (mismo nivel que seo-content-structure.php)
 * Accede a él desde: tu-sitio.com/wp-content/plugins/seo-content-structure/debug-cpt.php
 */

// Iniciar el buffer de salida
ob_start();

// Requerir wp-load.php para tener acceso a WordPress
$wp_load_file = dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
if (file_exists($wp_load_file)) {
    require_once($wp_load_file);
} else {
    die('No se pudo cargar WordPress');
}

// Verificar si el usuario está logueado y tiene permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página');
}

// Definir constantes si no están definidas
if (!defined('SCS_PLUGIN_DIR')) {
    define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('SCS_PLUGIN_URL')) {
    define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

echo '<h1>Depuración de Custom Post Types</h1>';

// Función para probar la tabla de la base de datos
function test_db_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scs_post_types';

    echo '<h2>Verificando tabla de la base de datos</h2>';

    // Verificar si la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    echo 'Tabla ' . $table_name . ': ' . ($table_exists ? '<span style="color:green">EXISTE</span>' : '<span style="color:red">NO EXISTE</span>');

    if (!$table_exists) {
        echo '<p><strong>Solución:</strong> Desactiva y vuelve a activar el plugin para crear la tabla.</p>';
        return;
    }

    // Obtener la estructura de la tabla
    echo '<h3>Estructura de la tabla</h3>';
    $structure = $wpdb->get_results("DESCRIBE $table_name");

    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>';

    foreach ($structure as $column) {
        echo '<tr>';
        echo '<td>' . $column->Field . '</td>';
        echo '<td>' . $column->Type . '</td>';
        echo '<td>' . $column->Null . '</td>';
        echo '<td>' . $column->Key . '</td>';
        echo '<td>' . $column->Default . '</td>';
        echo '<td>' . $column->Extra . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    // Obtener registros de la tabla
    echo '<h3>Registros en la tabla</h3>';
    $records = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($records)) {
        echo '<p>No hay registros en la tabla.</p>';
        return;
    }

    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Post Type</th><th>Config</th><th>Activo</th><th>Fecha de creación</th><th>Fecha de actualización</th></tr>';

    foreach ($records as $record) {
        echo '<tr>';
        echo '<td>' . $record->id . '</td>';
        echo '<td>' . $record->post_type . '</td>';
        echo '<td><pre>' . htmlspecialchars(substr($record->config, 0, 100)) . (strlen($record->config) > 100 ? '...' : '') . '</pre></td>';
        echo '<td>' . $record->active . '</td>';
        echo '<td>' . $record->created_at . '</td>';
        echo '<td>' . $record->updated_at . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

// Función para probar la caché de transients
function test_cache()
{
    echo '<h2>Verificando caché de transients</h2>';

    $cached_post_types = get_transient('scs_post_types_cache');

    if ($cached_post_types !== false) {
        echo '<p>Transient <code>scs_post_types_cache</code> encontrado.</p>';
        echo '<p>Contenido:</p><pre>' . print_r($cached_post_types, true) . '</pre>';

        echo '<p><a href="?clear_cache=1" class="button">Limpiar caché</a></p>';
    } else {
        echo '<p>No hay transient <code>scs_post_types_cache</code> almacenado.</p>';
    }
}

// Función para probar el registro de Custom Post Types
function test_post_types()
{
    echo '<h2>Verificando tipos de contenido registrados</h2>';

    // Todos los post types registrados en WordPress
    $registered_post_types = get_post_types(array(), 'objects');

    echo '<h3>Post Types en WordPress</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Nombre</th><th>Etiqueta</th><th>Público</th><th>Custom</th></tr>';

    foreach ($registered_post_types as $post_type => $object) {
        if (!in_array($post_type, array('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block'))) {
            echo '<tr>';
            echo '<td>' . $post_type . '</td>';
            echo '<td>' . $object->labels->singular_name . '</td>';
            echo '<td>' . ($object->public ? 'Sí' : 'No') . '</td>';
            echo '<td>' . (!in_array($post_type, array('post', 'page', 'attachment')) ? 'Sí' : 'No') . '</td>';
            echo '</tr>';
        }
    }

    echo '</table>';

    // Post types del plugin
    echo '<h3>Post Types del plugin (PostTypeFactory)</h3>';

    try {
        // Intentar cargar la clase PostTypeFactory
        $class_exists = class_exists('\\SEOContentStructure\\PostTypes\\PostTypeFactory');

        if (!$class_exists) {
            echo '<p style="color:red">La clase PostTypeFactory no existe o no se ha cargado.</p>';
            return;
        }

        $factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
        $plugin_post_types = $factory->get_registered_post_types();

        if (empty($plugin_post_types)) {
            echo '<p>No hay tipos de contenido registrados por el plugin.</p>';
            return;
        }

        echo '<table border="1" cellpadding="5">';
        echo '<tr><th>Nombre</th><th>Etiqueta</th><th>Schema Type</th><th>Campos</th></tr>';

        foreach ($plugin_post_types as $post_type) {
            echo '<tr>';
            echo '<td>' . $post_type->get_post_type() . '</td>';
            echo '<td>' . $post_type->get_args()['labels']['singular_name'] . '</td>';
            echo '<td>' . ($post_type->get_schema_type() ? $post_type->get_schema_type() : '-') . '</td>';
            echo '<td>' . count($post_type->get_fields()) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } catch (Exception $e) {
        echo '<p style="color:red">Error al intentar usar PostTypeFactory: ' . $e->getMessage() . '</p>';
    }
}

// Función para probar el envío del formulario manualmente
function test_form_submission()
{
    echo '<h2>Prueba de envío del formulario</h2>';

    // Si ya se envió el formulario de prueba
    if (isset($_POST['test_cpt_submit'])) {
        try {
            // Preparar datos para un tipo de contenido de prueba
            $test_post_type = 'test_' . time();

            // Configurar datos básicos
            $post_type_data = array(
                'post_type' => $test_post_type,
                'args' => array(
                    'labels' => array(
                        'name' => 'Tests',
                        'singular_name' => 'Test',
                    ),
                    'public' => true,
                    'has_archive' => true,
                    'show_in_rest' => true,
                    'supports' => array('title', 'editor', 'thumbnail'),
                    'menu_icon' => 'dashicons-admin-tools',
                    'active' => 1
                ),
                'taxonomies' => array(),
                'schema_type' => 'Article'
            );

            // Guardar el tipo de contenido
            $factory = new \SEOContentStructure\PostTypes\PostTypeFactory();
            $result = $factory->save_post_type($post_type_data);

            if (is_wp_error($result)) {
                echo '<p style="color:red">Error al guardar el tipo de contenido: ' . $result->get_error_message() . '</p>';
            } else {
                echo '<p style="color:green">Tipo de contenido guardado correctamente con ID: ' . $result . '</p>';
                echo '<p>Se ha creado un tipo de contenido de prueba llamado <code>' . $test_post_type . '</code>.</p>';
                echo '<p>Verifica en la sección anterior si aparece en la lista de tipos de contenido registrados.</p>';

                // Limpiar caché
                delete_transient('scs_post_types_cache');
                echo '<p>Caché limpiada.</p>';

                // Refrescar reglas de reescritura
                flush_rewrite_rules();
                echo '<p>Reglas de reescritura actualizadas.</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color:red">Excepción: ' . $e->getMessage() . '</p>';
        }
    } else {
        // Mostrar el formulario de prueba
        echo '<p>Usa este formulario para probar la creación de un tipo de contenido mediante el código directamente:</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="test_cpt_submit" value="1">';
        echo '<button type="submit" class="button button-primary">Crear tipo de contenido de prueba</button>';
        echo '</form>';
    }
}

// Limpiar caché si se solicita
if (isset($_GET['clear_cache'])) {
    delete_transient('scs_post_types_cache');
    echo '<div style="background-color: #dff0d8; color: #3c763d; padding: 15px; border: 1px solid #d6e9c6; border-radius: 4px; margin-bottom: 20px;">Caché limpiada correctamente.</div>';
}

// Ejecutar pruebas
test_db_table();
test_cache();
test_post_types();
test_form_submission();

// Añadir algunos enlaces útiles
echo '<hr>';
echo '<h2>Enlaces útiles</h2>';
echo '<ul>';
echo '<li><a href="' . admin_url('admin.php?page=scs-post-types') . '">Página de tipos de contenido</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=scs-post-types&action=new') . '">Crear nuevo tipo de contenido</a></li>';
echo '<li><a href="' . admin_url('plugins.php') . '">Plugins (para desactivar/activar)</a></li>';
echo '</ul>';

// Finalizar la página
echo '<hr>';
echo '<p><em>Script de depuración completado.</em></p>';

// Obtener y mostrar la salida
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depuración de Custom Post Types</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #444;
            margin: 20px;
            line-height: 1.5;
        }

        h1 {
            color: #23282d;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        h2 {
            color: #23282d;
            margin-top: 30px;
        }

        h3 {
            color: #23282d;
            margin-top: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: auto;
        }

        .button {
            display: inline-block;
            background-color: #0085ba;
            color: #fff;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 3px;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            background-color: #006799;
        }
    </style>
</head>

<body>
    <?php echo $output; ?>
</body>

</html>
