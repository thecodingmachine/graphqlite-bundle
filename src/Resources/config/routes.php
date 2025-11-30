<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use TheCodingMachine\GraphQLite\Bundle\Controller\GraphQLiteController;

return function (RoutingConfigurator $routes): void {
    $routes->import(
        \sprintf('%s::loadRoutes', GraphQLiteController::class),
        'service',
    );
};
