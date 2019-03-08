<?php


namespace TheCodingMachine\Graphqlite\Bundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class GraphqliteRouteLoader extends Loader
{
    private $isLoaded = false;

    public function load($resource, $type = null)
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "graphqlite" loader twice');
        }

        $routes = new RouteCollection();

        // prepare a new route
        $path = '/graphql';
        $defaults = [
            '_controller' => 'TheCodingMachine\Graphqlite\Bundle\Controller\GraphqliteController::handleRequest',
        ];
        $route = new Route($path, $defaults);

        // add the new route to the route collection
        $routeName = 'graphqliteRoute';
        $routes->add($routeName, $route);

        $this->isLoaded = true;

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'graphqlite' === $type;
    }
}
