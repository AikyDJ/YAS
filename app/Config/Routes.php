<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Auth
$routes->get('/', 'Home::index');
$routes->post('/', 'Home::index');

// Client
$routes->get('/client/dashboard', 'Client::dashboard');
$routes->post('/client/proceder-operation', 'Client::procederOperation');
$routes->post('/client/proceder-transfert', 'Client::procederTransfert');

// Admin
$routes->get('/admin/dashboard', 'Admin::dashboard');

$routes->get('/admin/prefixes', 'Admin::prefixes');
$routes->post('/admin/prefixes/ajouter', 'Admin::ajouterPrefixe');
$routes->get('/admin/prefixes/supprimer/(:num)', 'Admin::supprimerPrefixe/$1');

$routes->get('/admin/baremes', 'Admin::baremes');
$routes->post('/admin/baremes/sauvegarder', 'Admin::sauvegarderBareme');
$routes->get('/admin/baremes/modifier/(:num)', 'Admin::modifierBareme/$1');
$routes->get('/admin/baremes/supprimer/(:num)', 'Admin::supprimerBareme/$1');

$routes->get('/admin/logout', 'Admin::logout');
