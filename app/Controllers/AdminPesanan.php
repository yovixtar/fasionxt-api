<?php

namespace App\Controllers;

use App\Models\PembayaranModel;
use App\Models\PesananItemModel;
use App\Models\PesananModel;
use App\Models\ProdukModel;
use App\Models\PenggunaModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\Response;

class AdminPesanan extends BaseController
{
    use ResponseTrait;
    protected $pembayaranModel, $pesananModel, $pesananItemModel, $produkModel, $penggunaModel;

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
    }

    public function kirimPesanan(): Response
    {
        return $this->ubahStatusPesanan('dikirim');
    }

    public function selesaiPesanan(): Response
    {
        return $this->ubahStatusPesanan('selesai');
    }

    private function ubahStatusPesanan(string $status): Response
    {
        try {
            $idPesanan = $this->request->getPost('id_pesanan');

            $this->pesananModel->update($idPesanan, ['status' => $status]);

            $message = "Status pesanan diubah menjadi {$status}.";
            return $this->messageResponse($message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengubah status pesanan.', self::HTTP_SERVER_ERROR);
        }
    }

    public function daftarPesanan(): Response
{
    try {
        // Ambil daftar pesanan pengguna dengan status dikemas, dikirim, atau selesai
        $pesananList = $this->pesananModel
            ->whereIn('status', ['dikemas', 'dikirim', 'selesai'])
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
        return $this->messageResponse('Terjadi kesalahan saat mengambil daftar pesanan. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
    }
}

}
