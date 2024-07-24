<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->post('/api/register', 'Auth::register');
$routes->post('/api/login', 'Auth::login');

$routes->get('/api/kategori', 'Kategori::daftarKategori');

$routes->get('/api/produk', 'Produk::daftarProduk');

$routes->get('/api/produk/favorit', 'Produk::produkFavorit');
$routes->post('/api/produk/favorit', 'Produk::switchFavorit');

$routes->get('/api/notifikasi', 'Notifikasi::notifikasiPengguna');

$routes->get('/api/profile', 'Profile::currentUser');
$routes->post('/api/profile/setting', 'Profile::pengaturanAkun');

$routes->get('/api/pesanan', 'Pesanan::daftarPesanan');
$routes->post('/api/checkout', 'Pesanan::checkout');
$routes->post('/api/success-payment', 'Pesanan::suksesPayment');
$routes->post('/api/pesanan/selesai', 'Pesanan::selesaiPesanan');

$routes->get('/admin/api/kategori', 'AdminKategori::list');
$routes->post('/admin/api/kategori/create', 'AdminKategori::create');
$routes->post('/admin/api/kategori/update', 'AdminKategori::update');
$routes->delete('/admin/api/kategori/(:num)', 'AdminKategori::delete/$1');

$routes->get('/admin/api/produk', 'AdminProduk::list');
$routes->post('/admin/api/produk/create', 'AdminProduk::create');
$routes->post('/admin/api/produk/update', 'AdminProduk::update');
$routes->delete('/admin/api/produk/(:num)', 'AdminProduk::delete/$1');

$routes->get('/admin/api/pesanan', 'AdminPesanan::daftarPesanan');
$routes->post('/admin/api/pesanan/kirim', 'AdminPesanan::kirimPesanan');
$routes->post('/admin/api/pesanan/selesai', 'AdminPesanan::selesaiPesanan');