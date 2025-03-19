<?php

/**
 * Clase para validación de datos
 *
 * @package SEOContentStructure
 * @subpackage Utilities
 */

namespace SEOContentStructure\Utilities;

/**
 * Clase que proporciona métodos de validación para diferentes tipos de datos
 */
class Validator
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicialización
    }

    /**
     * Valida que un valor no esté vacío
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function required($value)
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value) || is_object($value)) {
            return !empty($value);
        }

        return $value !== null && $value !== false;
    }

    /**
     * Valida una dirección de correo electrónico
     *
     * @param string $email Email a validar
     * @return bool
     */
    public function email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida una URL
     *
     * @param string $url URL a validar
     * @return bool
     */
    public function url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Valida un valor numérico
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function numeric($value)
    {
        return is_numeric($value);
    }

    /**
     * Valida un número entero
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function integer($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Valida un número decimal
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function decimal($value)
    {
        return is_numeric($value) && strpos($value, '.') !== false;
    }

    /**
     * Valida que un valor sea booleano
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function boolean($value)
    {
        return is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1' ||
            strtolower($value) === 'true' || strtolower($value) === 'false';
    }

    /**
     * Valida que un valor esté en un rango dado
     *
     * @param mixed $value Valor a validar
     * @param mixed $min   Valor mínimo
     * @param mixed $max   Valor máximo
     * @return bool
     */
    public function range($value, $min, $max)
    {
        if (!is_numeric($value)) {
            return false;
        }

        $value = (float) $value;
        return $value >= $min && $value <= $max;
    }

    /**
     * Valida que un texto tenga una longitud dentro de un rango
     *
     * @param string $value Valor a validar
     * @param int    $min   Longitud mínima
     * @param int    $max   Longitud máxima
     * @return bool
     */
    public function length($value, $min = null, $max = null)
    {
        $length = mb_strlen($value);

        if ($min !== null && $length < $min) {
            return false;
        }

        if ($max !== null && $length > $max) {
            return false;
        }

        return true;
    }

    /**
     * Valida que un valor coincida con una expresión regular
     *
     * @param string $value Valor a validar
     * @param string $regex Expresión regular
     * @return bool
     */
    public function regex($value, $regex)
    {
        return preg_match($regex, $value) === 1;
    }

    /**
     * Valida que un valor esté en una lista de valores permitidos
     *
     * @param mixed $value   Valor a validar
     * @param array $allowed Lista de valores permitidos
     * @return bool
     */
    public function inArray($value, $allowed)
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Valida una fecha
     *
     * @param string $date      Fecha a validar
     * @param string $format    Formato de la fecha (default: Y-m-d)
     * @return bool
     */
    public function date($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Valida que una fecha esté en un rango
     *
     * @param string $date  Fecha a validar
     * @param string $min   Fecha mínima
     * @param string $max   Fecha máxima
     * @param string $format Formato de la fecha (default: Y-m-d)
     * @return bool
     */
    public function dateRange($date, $min, $max, $format = 'Y-m-d')
    {
        if (!$this->date($date, $format)) {
            return false;
        }

        $dateObj = \DateTime::createFromFormat($format, $date);
        $minObj = $min ? \DateTime::createFromFormat($format, $min) : null;
        $maxObj = $max ? \DateTime::createFromFormat($format, $max) : null;

        if ($minObj && $dateObj < $minObj) {
            return false;
        }

        if ($maxObj && $dateObj > $maxObj) {
            return false;
        }

        return true;
    }

    /**
     * Valida que un valor sea un array
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function isArray($value)
    {
        return is_array($value);
    }

    /**
     * Valida que un valor corresponda a un ID de post válido
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function postExists($value)
    {
        if (!$this->integer($value) || $value <= 0) {
            return false;
        }

        return get_post($value) !== null;
    }

    /**
     * Valida que un valor corresponda a un ID de usuario válido
     *
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function userExists($value)
    {
        if (!$this->integer($value) || $value <= 0) {
            return false;
        }

        return get_user_by('id', $value) !== false;
    }

    /**
     * Valida que un valor sea hexadecimal (para colores, etc.)
     *
     * @param string $value Valor a validar
     * @return bool
     */
    public function hexColor($value)
    {
        return preg_match('/^#([a-fA-F0-9]{3}){1,2}$/', $value) === 1;
    }

    /**
     * Valida un JSON
     *
     * @param string $value Valor a validar
     * @return bool
     */
    public function json($value)
    {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Valida un slug
     *
     * @param string $value Valor a validar
     * @return bool
     */
    public function slug($value)
    {
        return preg_match('/^[a-z0-9-]+$/', $value) === 1;
    }

    /**
     * Valida que un post type exista
     *
     * @param string $post_type Post type a validar
     * @return bool
     */
    public function postTypeExists($post_type)
    {
        return post_type_exists($post_type);
    }

    /**
     * Valida que una taxonomía exista
     *
     * @param string $taxonomy Taxonomía a validar
     * @return bool
     */
    public function taxonomyExists($taxonomy)
    {
        return taxonomy_exists($taxonomy);
    }

    /**
     * Valida múltiples reglas para un campo
     *
     * @param mixed $value Valor a validar
     * @param array $rules Reglas de validación
     * @return bool|WP_Error True si es válido, WP_Error si no
     */
    public function validate($value, $rules)
    {
        foreach ($rules as $rule => $params) {
            if ($rule === 'message') {
                continue;
            }

            $valid = false;

            if (is_array($params)) {
                $method_params = $params;
                array_unshift($method_params, $value);
                $valid = call_user_func_array(array($this, $rule), $method_params);
            } else if ($params === true) {
                $valid = $this->$rule($value);
            } else {
                $valid = $this->$rule($value, $params);
            }

            if (!$valid) {
                $message = isset($rules['message'][$rule])
                    ? $rules['message'][$rule]
                    : __('El valor no cumple con la regla de validación.', 'seo-content-structure');

                return new \WP_Error('validation_error', $message);
            }
        }

        return true;
    }
}
