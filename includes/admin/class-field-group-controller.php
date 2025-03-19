<?php

/**
 * Controlador para grupos de campos
 *
 * @package SEOContentStructure
 * @subpackage Admin
 */

namespace SEOContentStructure\Admin;

use SEOContentStructure\Core\Interfaces\Registrable;
use SEOContentStructure\Core\Loader;
use SEOContentStructure\Fields\FieldFactory;
use SEOContentStructure\Utilities\Helper;

/**
 * Clase para manejar grupos de campos personalizados
 */
class FieldGroupController implements Registrable
{
    /**
     * Nombre de la tabla de grupos de campos
     *
     * @var string
     */
    protected $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'scs_field_groups';
    }

    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader)
    {
        // Acciones para procesar formularios
        $loader->add_action('admin_post_scs_save_field_group', $this, 'process_save_field_group');
        $loader->add_action('admin_post_scs_delete_field_group', $this, 'process_delete_field_group');

        // Registro de meta boxes
        $loader->add_action('add_meta_boxes', $this, 'register_field_group_meta_boxes');

        // Guardar valores de meta boxes
        $loader->add_action('save_post', $this, 'save_field_group_values');

        // AJAX para previsualizar campos
        $loader->add_action('wp_ajax_scs_preview_field', $this, 'ajax_preview_field');
    }

    /**
     * Obtiene todos los grupos de campos
     *
     * @return array
     */
    public function get_field_groups()
    {
        global $wpdb;

        // Verificar si hay caché
        $groups = get_transient('scs_field_groups_cache');
        if ($groups !== false) {
            return $groups;
        }

        $groups = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY id DESC",
            ARRAY_A
        );

        if (!$groups) {
            return array();
        }

        // Procesar cada grupo
        foreach ($groups as &$group) {
            $group['fields'] = json_decode($group['fields'], true);
            $group['location'] = json_decode($group['location'], true);
        }

        // Guardar en caché
        set_transient('scs_field_groups_cache', $groups, HOUR_IN_SECONDS);

        return $groups;
    }

    /**
     * Obtiene un grupo de campos específico
     *
     * @param int $group_id ID del grupo
     * @return array|false
     */
    public function get_field_group($group_id)
    {
        global $wpdb;

        $group = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $group_id
            ),
            ARRAY_A
        );

        if (!$group) {
            return false;
        }

        // Decodificar campos y ubicación
        $group['fields'] = json_decode($group['fields'], true);
        $group['location'] = json_decode($group['location'], true);

        return $group;
    }

    /**
     * Guarda un grupo de campos
     *
     * @param array $data Datos del grupo
     * @return int|WP_Error ID del grupo o error
     */
    public function save_field_group($data)
    {
        global $wpdb;

        // Validar datos básicos
        if (empty($data['title'])) {
            return new \WP_Error('empty_title', __('El título no puede estar vacío.', 'seo-content-structure'));
        }

        // Preparar datos para guardar
        $save_data = array(
            'title' => sanitize_text_field($data['title']),
            'active' => isset($data['active']) ? 1 : 0,
        );

        // Procesar ubicación
        if (isset($data['location']) && is_array($data['location'])) {
            $location = array();

            foreach ($data['location'] as $loc) {
                $location[] = array(
                    'post_type' => sanitize_text_field($loc['post_type']),
                );
            }

            $save_data['location'] = wp_json_encode($location);
        } else {
            $save_data['location'] = wp_json_encode(array());
        }

        // Procesar campos
        if (isset($data['fields']) && is_array($data['fields'])) {
            $fields = array();

            foreach ($data['fields'] as $field) {
                // Validar campo
                if (empty($field['id']) || empty($field['type'])) {
                    continue;
                }

                $sanitized_field = array(
                    'id' => sanitize_key($field['id']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_text_field($field['type']),
                );

                // Conservar otras propiedades
                foreach ($field as $key => $value) {
                    if (!isset($sanitized_field[$key])) {
                        $sanitized_field[$key] = is_array($value) ? $value : sanitize_text_field($value);
                    }
                }

                $fields[] = $sanitized_field;
            }

            $save_data['fields'] = wp_json_encode($fields);
        } else {
            $save_data['fields'] = wp_json_encode(array());
        }

        // Determinar si es una actualización o inserción
        if (!empty($data['id'])) {
            // Actualizar
            $result = $wpdb->update(
                $this->table_name,
                $save_data,
                array('id' => $data['id']),
                array('%s', '%d', '%s', '%s'),
                array('%d')
            );

            if ($result === false) {
                return new \WP_Error('db_error', __('Error al actualizar el grupo de campos.', 'seo-content-structure'));
            }

            $group_id = $data['id'];
        } else {
            // Insertar
            $result = $wpdb->insert(
                $this->table_name,
                $save_data,
                array('%s', '%d', '%s', '%s')
            );

            if ($result === false) {
                return new \WP_Error('db_error', __('Error al insertar el grupo de campos.', 'seo-content-structure'));
            }

            $group_id = $wpdb->insert_id;
        }

        // Limpiar caché
        delete_transient('scs_field_groups_cache');

        return $group_id;
    }

    /**
     * Elimina un grupo de campos
     *
     * @param int $group_id ID del grupo
     * @return bool
     */
    public function delete_field_group($group_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $group_id),
            array('%d')
        );

        // Limpiar caché
        delete_transient('scs_field_groups_cache');

        return $result !== false;
    }

    /**
     * Procesa el guardado de un grupo de campos desde el formulario
     */
    public function process_save_field_group()
    {
        // Verificar nonce
        if (!isset($_POST['scs_field_group_nonce']) || !wp_verify_nonce($_POST['scs_field_group_nonce'], 'scs_save_field_group')) {
            wp_die(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Procesar datos del formulario
        $data = array(
            'id' => isset($_POST['group_id']) ? absint($_POST['group_id']) : 0,
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'active' => isset($_POST['active']) ? 1 : 0,
        );

        // Procesar ubicación
        if (isset($_POST['location']) && is_array($_POST['location'])) {
            $data['location'] = $_POST['location'];
        }

        // Procesar campos
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            $data['fields'] = $_POST['fields'];
        }

        // Guardar grupo
        $result = $this->save_field_group($data);

        // Redireccionar
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-field-groups',
                    'action' => empty($data['id']) ? 'new' : 'edit',
                    'group_id' => $data['id'],
                    'error' => urlencode($result->get_error_message()),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'scs-field-groups',
                'action' => 'edit',
                'group_id' => $result,
                'message' => 'saved',
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Procesa la eliminación de un grupo de campos
     */
    public function process_delete_field_group()
    {
        // Verificar nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'scs_delete_field_group')) {
            wp_die(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Obtener ID del grupo
        $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0;

        if (!$group_id) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-field-groups',
                    'error' => __('ID de grupo inválido.', 'seo-content-structure'),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        // Eliminar grupo
        $result = $this->delete_field_group($group_id);

        // Redireccionar
        if (!$result) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'scs-field-groups',
                    'error' => __('Error al eliminar el grupo.', 'seo-content-structure'),
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'scs-field-groups',
                'message' => 'deleted',
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Registra meta boxes para los grupos de campos
     */
    public function register_field_group_meta_boxes()
    {
        // Obtener todos los grupos de campos
        $groups = $this->get_field_groups();

        if (empty($groups)) {
            return;
        }

        // Para cada grupo, obtener los post types donde se debe mostrar
        foreach ($groups as $group) {
            // Verificar si el grupo está activo
            if (empty($group['active'])) {
                continue;
            }

            // Obtener post types
            $post_types = array();
            if (!empty($group['location']) && is_array($group['location'])) {
                foreach ($group['location'] as $location) {
                    if (!empty($location['post_type'])) {
                        $post_types[] = $location['post_type'];
                    }
                }
            }

            // Si no hay post types, continuar
            if (empty($post_types)) {
                continue;
            }

            // Registrar meta box para cada post type
            foreach ($post_types as $post_type) {
                add_meta_box(
                    'scs_field_group_' . $group['id'],
                    $group['title'],
                    array($this, 'render_field_group_meta_box'),
                    $post_type,
                    'normal',
                    'high',
                    array('group' => $group)
                );
            }
        }
    }

    /**
     * Renderiza el contenido de un meta box para un grupo de campos
     *
     * @param WP_Post $post    Objeto post
     * @param array   $metabox Datos del metabox
     */
    public function render_field_group_meta_box($post, $metabox)
    {
        // Obtener grupo
        $group = $metabox['args']['group'];

        // Verificar si hay campos
        if (empty($group['fields']) || !is_array($group['fields'])) {
            echo '<p>' . esc_html__('No hay campos definidos para este grupo.', 'seo-content-structure') . '</p>';
            return;
        }

        // Crear fábrica de campos
        $field_factory = new FieldFactory();

        // Nonce para seguridad
        wp_nonce_field('scs_save_field_group_' . $group['id'], 'scs_field_group_nonce_' . $group['id']);

        echo '<div class="scs-field-group-container">';

        // Renderizar cada campo
        foreach ($group['fields'] as $field_data) {
            // Crear el campo
            $field = $field_factory->create($field_data);

            if (!$field) {
                continue;
            }

            // Obtener el valor actual
            $meta_value = get_post_meta($post->ID, $field->get_name(), true);
            if ('' !== $meta_value) {
                $field->set_value($meta_value);
            }

            // Renderizar el campo
            echo $field->render_admin();
        }

        echo '</div>';
    }

    /**
     * Guarda los valores de los campos personalizados
     *
     * @param int $post_id ID del post
     */
    public function save_field_group_values($post_id)
    {
        // Verificar si es guardado automático
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Obtener grupos para el post type actual
        $post_type = get_post_type($post_id);
        $groups = $this->get_field_groups();

        if (empty($groups)) {
            return;
        }

        // Crear fábrica de campos
        $field_factory = new FieldFactory();

        // Procesar cada grupo
        foreach ($groups as $group) {
            // Verificar si el grupo aplica para este post type
            $applies = false;
            if (!empty($group['location']) && is_array($group['location'])) {
                foreach ($group['location'] as $location) {
                    if (!empty($location['post_type']) && $location['post_type'] === $post_type) {
                        $applies = true;
                        break;
                    }
                }
            }

            if (!$applies) {
                continue;
            }

            // Verificar nonce
            $nonce_name = 'scs_field_group_nonce_' . $group['id'];
            if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], 'scs_save_field_group_' . $group['id'])) {
                continue;
            }

            // Procesar campos
            if (empty($group['fields']) || !is_array($group['fields'])) {
                continue;
            }

            foreach ($group['fields'] as $field_data) {
                // Crear el campo
                $field = $field_factory->create($field_data);

                if (!$field) {
                    continue;
                }

                $field_name = $field->get_name();

                // Si el campo existe en el formulario
                if (isset($_POST[$field_name])) {
                    $value = $_POST[$field_name];

                    // Sanitizar el valor
                    $sanitized_value = $field->sanitize($value);

                    // Actualizar el valor en la base de datos
                    update_post_meta($post_id, $field_name, $sanitized_value);
                }
            }
        }
    }

    /**
     * Maneja la solicitud AJAX para previsualizar un campo
     */
    public function ajax_preview_field()
    {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scs_preview_field')) {
            wp_send_json_error(__('Acceso no autorizado.', 'seo-content-structure'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'seo-content-structure'));
        }

        // Obtener datos del campo
        $field_data = isset($_POST['field']) ? $_POST['field'] : array();

        if (empty($field_data) || empty($field_data['type'])) {
            wp_send_json_error(__('Datos de campo inválidos.', 'seo-content-structure'));
        }

        // Crear fábrica de campos
        $field_factory = new FieldFactory();

        // Crear el campo
        $field = $field_factory->create($field_data);

        if (!$field) {
            wp_send_json_error(__('No se pudo crear el campo.', 'seo-content-structure'));
        }

        // Renderizar el campo
        $html = $field->render_admin();

        wp_send_json_success(array(
            'html' => $html,
        ));
    }

    /**
     * Obtiene los campos de los grupos asignados a un post type
     *
     * @param string $post_type Tipo de post
     * @return array
     */
    public function get_fields_for_post_type($post_type)
    {
        $groups = $this->get_field_groups();
        $fields = array();

        if (empty($groups)) {
            return $fields;
        }

        // Crear fábrica de campos
        $field_factory = new FieldFactory();

        foreach ($groups as $group) {
            // Verificar si el grupo está activo
            if (empty($group['active'])) {
                continue;
            }

            // Verificar si el grupo aplica para este post type
            $applies = false;
            if (!empty($group['location']) && is_array($group['location'])) {
                foreach ($group['location'] as $location) {
                    if (!empty($location['post_type']) && $location['post_type'] === $post_type) {
                        $applies = true;
                        break;
                    }
                }
            }

            if (!$applies) {
                continue;
            }

            // Procesar campos
            if (empty($group['fields']) || !is_array($group['fields'])) {
                continue;
            }

            foreach ($group['fields'] as $field_data) {
                // Crear el campo
                $field = $field_factory->create($field_data);

                if (!$field) {
                    continue;
                }

                $fields[$field->get_id()] = $field;
            }
        }

        return $fields;
    }
}
