<?php

/**
 * Registra todas las acciones y filtros para el plugin
 *
 * @package SEOContentStructure
 * @subpackage Core
 */

namespace SEOContentStructure\Core;

/**
 * Clase que maneja el registro de hooks en WordPress
 */
class Loader
{
    /**
     * Array de acciones registradas por el plugin
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Array de filtros registrados por el plugin
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Array de shortcodes registrados por el plugin
     *
     * @var array
     */
    protected $shortcodes = array();

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Inicializar las propiedades
    }

    /**
     * Añade una acción a la colección para ser registrada con WordPress
     *
     * @param string   $hook          El nombre de la acción de WordPress
     * @param object   $component     Instancia del objeto donde existe el callback
     * @param string   $callback      El nombre de la función callback
     * @param int      $priority      Opcional. La prioridad. Por defecto es 10
     * @param int      $accepted_args Opcional. Número de argumentos. Por defecto es 1
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añade un filtro a la colección para ser registrado con WordPress
     *
     * @param string   $hook          El nombre del filtro de WordPress
     * @param object   $component     Instancia del objeto donde existe el callback
     * @param string   $callback      El nombre de la función callback
     * @param int      $priority      Opcional. La prioridad. Por defecto es 10
     * @param int      $accepted_args Opcional. Número de argumentos. Por defecto es 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añade un shortcode a la colección para ser registrado con WordPress
     *
     * @param string   $tag           El nombre del shortcode
     * @param object   $component     Instancia del objeto donde existe el callback
     * @param string   $callback      El nombre de la función callback
     */
    public function add_shortcode($tag, $component, $callback)
    {
        $this->shortcodes = $this->add_shortcode_item($this->shortcodes, $tag, $component, $callback);
    }

    /**
     * Función utilitaria que es usada para añadir un nuevo elemento a la colección
     * para ser registrado con WordPress
     *
     * @param array    $hooks         Colección de hooks (por referencia)
     * @param string   $hook          El nombre del hook de WordPress
     * @param object   $component     Instancia del objeto donde existe el callback
     * @param string   $callback      El nombre de la función callback
     * @param int      $priority      Prioridad del hook
     * @param int      $accepted_args Número de argumentos aceptados
     * @return array                  Colección de hooks con el nuevo hook añadido
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Función utilitaria para añadir shortcode
     *
     * @param array    $shortcodes    Colección de shortcodes
     * @param string   $tag           Tag del shortcode
     * @param object   $component     Instancia del objeto donde existe el callback
     * @param string   $callback      El nombre de la función callback
     * @return array                  Colección de shortcodes con el nuevo shortcode añadido
     */
    private function add_shortcode_item($shortcodes, $tag, $component, $callback)
    {
        $shortcodes[] = array(
            'tag'           => $tag,
            'component'     => $component,
            'callback'      => $callback,
        );

        return $shortcodes;
    }

    /**
     * Registra los hooks con WordPress
     */
    public function run()
    {
        // Registrar los filtros
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Registrar las acciones
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Registrar los shortcodes
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'],
                array($shortcode['component'], $shortcode['callback'])
            );
        }
    }
}
