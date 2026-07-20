<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Auth
$routes->get('/', 'Home::index');
$routes->post('/auth', 'Home::auth');
$routes->get('/logout', 'Home::logout');

// Client
$routes->get('/client/dashboard', 'Client::dashboard');
$routes->post('/client/proceder-operation', 'Client::procederOperation');
$routes->post('/client/proceder-transfert', 'Client::procederTransfert');
$routes->get('/client/frais-tranche/(:num)', 'Client::getFraisTranche/$1');
$routes->get('/client/logout', 'Client::logout');

// Admin
$routes->get('/admin/dashboard', 'Admin::dashboard');

$routes->get('/admin/prefixes', 'Admin::prefixes');
$routes->post('/admin/prefixes/ajouter', 'Admin::ajouterPrefixe');
$routes->post('/admin/prefixes/supprimer', 'Admin::supprimerPrefixe');

$routes->get('/admin/baremes', 'Admin::baremes');
$routes->post('/admin/baremes/sauvegarder', 'Admin::sauvegarderBareme');
$routes->get('/admin/baremes/modifier/(:num)', 'Admin::modifierBareme/$1');
$routes->post('/admin/baremes/supprimer', 'Admin::supprimerBareme');

$routes->get('/admin/logout', 'Admin::logout');
