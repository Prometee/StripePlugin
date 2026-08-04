<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $container->import('services/contexts.php');
    $container->import('services/mockers.php');
    $container->import('services/pages.php');
};
