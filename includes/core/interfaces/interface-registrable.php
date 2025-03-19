<?php

/**
 * Interfaz para clases que necesitan registrarse con el cargador
 *
 * @package SEOContentStructure
 * @subpackage Core\Interfaces
 */

namespace SEOContentStructure\Core\Interfaces;

use SEOContentStructure\Core\Loader;

/**
 * Interfaz que deben implementar todas las clases que necesitan
 * registrar hooks con WordPress a través del cargador
 */
interface Registrable
{
    /**
     * Registra los hooks con WordPress
     *
     * @param Loader $loader Instancia del cargador
     */
    public function register(Loader $loader);
}
