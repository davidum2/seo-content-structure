<?php

/**
 * Interfaz para objetos que pueden ser renderizados
 *
 * @package SEOContentStructure
 * @subpackage Core\Interfaces
 */

namespace SEOContentStructure\Core\Interfaces;

/**
 * Interfaz que deben implementar todas las clases que pueden ser renderizadas
 */
interface Renderable
{
    /**
     * Renderiza el elemento en el panel de administración
     *
     * @return string HTML del elemento
     */
    public function render_admin();

    /**
     * Renderiza el elemento para el frontend
     *
     * @return string HTML del elemento
     */
    public function render_frontend();
}
