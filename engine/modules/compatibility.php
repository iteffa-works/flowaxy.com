<?php
/**
 * Функции обратной совместимости для модулей
 * 
 * @package Engine\Modules
 * @version 1.0.0
 */

/**
 * Глобальная функция для получения экземпляра MediaManager (обратная совместимость)
 * 
 * @return Media
 */
if (!function_exists('mediaManager')) {
    function mediaManager() {
        return mediaModule();
    }
}

