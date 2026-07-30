<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->match(['GET', 'POST'], '/', 'Legacy::dispatch');
$routes->match(['GET', 'POST'], '(:any)', 'Legacy::dispatch/$1');
