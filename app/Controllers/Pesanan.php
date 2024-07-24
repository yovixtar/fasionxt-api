<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\PembayaranModel;
use App\Models\NotifikasiModel;
use App\Models\PesananItemModel;
use App\Models\PesananModel;
use App\Models\ProdukModel;
use App\Models\PenggunaModel;
use CodeIgniter\API\ResponseTrait;
use Config\Token;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;

class Pesanan extends BaseController
{
    use ResponseTrait;
    protected $pembayaranModel, $pesananModel, $pesananItemModel, $produkModel, $penggunaModel, $notifikasiModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->pesananModel = new PesananModel();
        $this->pesananItemModel = new PesananItemModel();
        $this->produkModel = new ProdukModel();
        $this->penggunaModel = new PenggunaModel();
        $this->notifikasiModel = new NotifikasiModel();
    }

    public function checkout(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;
            $catatan = $this->request->getPost('catatan');
            $total = $this->request->getPost('total');

            $user = $this->penggunaModel->find($userId);

            if (empty($user['nama']) || empty($user['alamat'])) {
                return $this->messageResponse('Nama dan alamat harus diisi.', self::HTTP_BAD_REQUEST);
            }

            $pesananData = [
                'id_pengguna' => $userId,
                'total' => $total,
                'status' => 'belum bayar',
                'catatan' => $catatan,
            ];

            $this->pesananModel->insert($pesananData);
            $pesananId = $this->pesananModel->insertID();

            $itemsJson = $this->request->getPost('items');
            $items = json_decode($itemsJson, true);

            foreach ($items as $item) {
                $itemData = [
                    'id_pesanan' => $pesananId,
                    'id_produk' => $item['product']['id'],
                    'jumlah' => $item['quantity'],
                    'ukuran' => $item['size']
                ];
                $this->pesananItemModel->insert($itemData);

                // Kurangi stok produk
                $produk = $this->produkModel->find($item['product']['id']);
                if ($produk['stok'] < $item['quantity']) {
                    return $this->messageResponse('Stok produk tidak mencukupi.', self::HTTP_BAD_REQUEST);
                }
                $this->produkModel->update($item['product']['id'], ['stok' => $produk['stok'] - $item['quantity']]);
            }

            // Ambil data pesanan yang baru diinsert
            $pesanan = $this->pesananModel->find($pesananId);
            $pesananItems = $this->pesananItemModel->where('id_pesanan', $pesananId)->findAll();

            // Format response untuk menyertakan detail produk
            $pesanan['items'] = array_map(function ($item) {
                $produk = $this->produkModel->find($item['id_produk']);
                return [
                    'id_produk' => $item['id_produk'],
                    'nama_produk' => $produk['nama'],
                    'harga' => $produk['harga'],
                    'jumlah' => $item['jumlah'],
                    'ukuran' => $item['ukuran']
                ];
            }, $pesananItems);


            $judulNotif = 'Checkout Berhasil';
            $deskNotif = 'Pesanan Anda berhasil dibuat. Silakan lanjutkan pembayaran.';

            // Tambahkan notifikasi selamat datang
            $notifikasiData = [
                'id_pengguna' => $userId,
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif,
                'umum' => false
            ];
            $this->notifikasiModel->insert($notifikasiData);

            // Tambahkan notifikasi ke dalam response
            $pesanan['notif'] = [
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif
            ];

            return $this->dataResponse($pesanan, 'Berhasil Checkout', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat checkout.' . $th, self::HTTP_SERVER_ERROR);
        }
    }

    public function suksesPayment(): Response
    {
        try {
            // Mengambil data dari request
            $trxId = $this->request->getPost('trx_id');
            $session = $this->request->getPost('sid');
            $subtotal = $this->request->getPost('sub_total');
            $total = $this->request->getPost('total');
            $status = $this->request->getPost('status');
            $via = $this->request->getPost('via');
            $channel = $this->request->getPost('channel');
            $idPesanan = $this->request->getPost('reference_id');

            // Ambil data pesanan berdasarkan idPesanan
            $pesanan = $this->pesananModel->find($idPesanan);

            if (!$pesanan) {
                return $this->messageResponse('Pesanan tidak ditemukan.', self::HTTP_NOT_FOUND);
            }

            // Ambil userId dari pesanan
            $userId = $pesanan['id_pengguna'];

            // Simpan data pembayaran
            $paymentData = [
                'id_pesanan' => $idPesanan,
                'trx' => $trxId,
                'session' => $session,
                'subtotal' => $subtotal,
                'total' => $total,
                'status' => $status,
                'via' => $via,
                'channel' => $channel,
            ];
            $this->pembayaranModel->insert($paymentData);

            // Update status pesanan menjadi dikemas
            $updateData = [
                'status' => 'dikemas',
                'metode_pembayaran' => $via,
                'total' => $total,
            ];
            $this->pesananModel->update($idPesanan, $updateData);

            $judulNotif = 'Pembayaran Berhasil';
            $deskNotif = 'Pembayaran Anda telah berhasil. Pesanan Anda sedang dikemas.';

            // Tambahkan notifikasi selamat datang
            $notifikasiData = [
                'id_pengguna' => $userId,
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif,
                'umum' => false
            ];
            $this->notifikasiModel->insert($notifikasiData);

            // Tambahkan notifikasi ke dalam response
            $notif = [
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif
            ];

            return $this->dataResponse(['notif' => $notif], 'Pembayaran sukses dan pesanan sedang dikemas.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat memproses pembayaran.' . $th, self::HTTP_SERVER_ERROR);
        }
    }

    // public function kirimPesanan(): Response
    // {
    //     return $this->ubahStatusPesanan('dikirim');
    // }

    public function selesaiPesanan(): Response
    {
        return $this->ubahStatusPesanan('selesai');
    }

    private function ubahStatusPesanan(string $status): Response
    {
        try {
            $idPesanan = $this->request->getPost('id_pesanan');

            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            $this->pesananModel->update($idPesanan, ['status' => $status]);

            $judulNotif = 'Status Pesanan Diperbarui';
            $deskNotif = "Status pesanan Anda telah diubah menjadi {$status}.";

            // Tambahkan notifikasi selamat datang
            $notifikasiData = [
                'id_pengguna' => $userId,
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif,
                'umum' => false
            ];
            $this->notifikasiModel->insert($notifikasiData);

            // Tambahkan notifikasi ke dalam response
            $notif = [
                'judul' => $judulNotif,
                'deskripsi' => $deskNotif,
            ];

            $message = "Status pesanan diubah menjadi {$status}.";
            return $this->dataResponse(['notif' => $notif], $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengubah status pesanan.', self::HTTP_SERVER_ERROR);
        }
    }

    public function daftarPesanan(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            // Ambil daftar pesanan pengguna
            $pesananList = $this->pesananModel->where('id_pengguna', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $response = [];

            foreach ($pesananList as $pesanan) {
                // Ambil data pengguna
                $user = $this->penggunaModel->find($pesanan['id_pengguna']);

                // Ambil data item pesanan
                $pesananItems = $this->pesananItemModel->where('id_pesanan', $pesanan['id'])->findAll();

                // Format items
                $items = [];
                foreach ($pesananItems as $item) {
                    $produk = $this->produkModel->find($item['id_produk']);
                    
                    if ($produk) {
                        $items[] = [
                            'nama_produk' => $produk['nama'],
                            'gambar' => $produk['gambar'],
                            'harga' => $produk['harga'],
                            'jumlah' => $item['jumlah'],
                            'total' => strval($produk['harga'] * $item['jumlah']),
                        ];
                    } else {
                        $items[] = [
                            'nama_produk' => 'Produk tidak ditemukan',
                            'gambar' => '',
                            'harga' => '',
                            'jumlah' => $item['jumlah'],
                            'total' => '0',
                        ];
                    }
                };

                // Format response
                $response[] = [
                    'nama_pengguna' => $user['nama'],
                    'id_pesanan' => $pesanan['id'],
                    'alamat' => $user['alamat'],
                    'status' => $pesanan['status'],
                    'tanggal' => $pesanan['created_at'],
                    'metode_pembayaran' => $pesanan['metode_pembayaran'],
                    'total' => $pesanan['total'],
                    'catatan' => $pesanan['catatan'],
                    'items' => $items,
                ];
            }

            return $this->dataResponse($response, 'Daftar pesanan berhasil diambil.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengambil daftar pesanan. ' . $th, self::HTTP_SERVER_ERROR);
        }
    }
}
