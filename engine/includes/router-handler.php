<?php
/**
 * Обработчик роутинга
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

$routerManager = RouterManager::getInstance();
$routerManager->dispatch();

