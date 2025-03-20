<?php

/**
 * Factory para crear campos personalizados
 *
 * @package SEOContentStructure
 * @subpackage Fields
 */

namespace SEOContentStructure\Fields;

/**
 * Clase para crear instancias de campos personalizados
 */
class FieldFactory
{
    /**
     * Mapeo de tipos de campos a sus clases
     *
     * @var array
     */
    protected $field_types = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_default_field_types();
    }

    /**
     * Registra los tipos de campos predeterminados
     */
    protected function register_default_field_types()
    {
        $this->field_types = array(
            'text'     => 'SEOContentStructure\Fields\TextField',
            'textarea' => 'SEOContentStructure\Fields\TextareaField',
            'number'   => 'SEOContentStructure\Fields\NumberField',
            'email'    => 'SEOContentStructure\Fields\TextField', // Podría ser EmailField en el futuro
            'url'      => 'SEOContentStructure\Fields\TextField', // Podría ser UrlField en el futuro
            'image'    => 'SEOContentStructure\Fields\ImageField',
            'repeater' => 'SEOContentStructure\Fields\RepeaterField',
            'select'   => 'SEOContentStructure\Fields\SelectField',
            'checkbox' => 'SEOContentStructure\Fields\CheckboxField',
            'radio'    => 'SEOContentStructure\Fields\RadioField',
            // Se pueden añadir más tipos en el futuro
        );
    }

    /**
     * Registra un nuevo tipo de campo
     *
     * @param string $type  Tipo de campo (identificador)
     * @param string $class Nombre de la clase completo
     * @return self
     */
    public function register_field_type($type, $class)
    {
        if (class_exists($class)) {
            $this->field_types[$type] = $class;
        }

        return $this;
    }

    /**
     * Obtiene los tipos de campos registrados
     *
     * @return array
     */
    public function get_field_types()
    {
        return $this->field_types;
    }

    /**
     * Crea una instancia de un campo
     *
     * @param array|string $args Argumentos del campo o tipo de campo
     * @return Field|null
     */
    public function create($args)
    {
        // Si es un string, asumir que es el tipo de campo
        if (is_string($args)) {
            $args = array('type' => $args);
        }

        // Verificar que sea un array
        if (! is_array($args)) {
            return null;
        }

        // Obtener el tipo de campo
        $type = isset($args['type']) ? $args['type'] : 'text';

        // Verificar si el tipo está registrado
        if (! isset($this->field_types[$type])) {
            // Si no está registrado, usar texto por defecto
            $type = 'text';
        }

        // Obtener la clase
        $class = $this->field_types[$type];

        // Crear y devolver la instancia
        return new $class($args);
    }

    /**
     * Crea múltiples campos desde un array de configuraciones
     *
     * @param array $fields_config Array de configuraciones de campos
     * @return array Array de objetos Field
     */
    public function create_fields($fields_config)
    {
        $fields = array();

        foreach ($fields_config as $field_config) {
            $field = $this->create($field_config);

            if ($field) {
                $fields[$field->get_id()] = $field;
            }
        }

        return $fields;
    }

    /**
     * Crea un campo de texto
     *
     * @param array $args Argumentos del campo
     * @return TextField
     */
    public function create_text($args)
    {
        $args['type'] = 'text';
        return $this->create($args);
    }

    /**
     * Crea un campo de número
     *
     * @param array $args Argumentos del campo
     * @return NumberField
     */
    public function create_number($args)
    {
        $args['type'] = 'number';
        return $this->create($args);
    }

    /**
     * Crea un campo de textarea
     *
     * @param array $args Argumentos del campo
     * @return TextareaField
     */
    public function create_textarea($args)
    {
        $args['type'] = 'textarea';
        return $this->create($args);
    }

    /**
     * Crea un campo de imagen
     *
     * @param array $args Argumentos del campo
     * @return ImageField
     */
    public function create_image($args)
    {
        $args['type'] = 'image';
        return $this->create($args);
    }

    /**
     * Crea un campo de repeater
     *
     * @param array $args Argumentos del campo
     * @return RepeaterField
     */
    public function create_repeater($args)
    {
        $args['type'] = 'repeater';
        return $this->create($args);
    }

    /**
     * Crea un campo select
     *
     * @param array $args Argumentos del campo
     * @return SelectField
     */
    public function create_select($args)
    {
        $args['type'] = 'select';
        return $this->create($args);
    }

    /**
     * Crea un campo checkbox
     *
     * @param array $args Argumentos del campo
     * @return CheckboxField
     */
    public function create_checkbox($args)
    {
        $args['type'] = 'checkbox';
        return $this->create($args);
    }

    /**
     * Crea un campo radio
     *
     * @param array $args Argumentos del campo
     * @return RadioField
     */
    public function create_radio($args)
    {
        $args['type'] = 'radio';
        return $this->create($args);
    }
}
