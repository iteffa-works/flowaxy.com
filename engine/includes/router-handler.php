<?php
/**
 * Обработчик роутинга
 * 
 * @package Engine\Includes
 */

declare(strict_types=1);

$centralRouter = CentralRouter::getInstance();
$centralRouter->dispatch();

