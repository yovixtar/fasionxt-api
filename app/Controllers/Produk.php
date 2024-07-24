<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\FavoritProdukModel;
use App\Models\PenggunaModel;
use App\Models\ProdukModel;
use CodeIgniter\API\ResponseTrait;
use Config\Token;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;

class Produk extends BaseController
{
    use ResponseTrait;
    protected $produkModel, $favoritProdukModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->produkModel = new ProdukModel();
        $this->favoritProdukModel = new FavoritProdukModel();
    }

    public function daftarProduk(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            $produkList = $this->produkModel->findAll();

            $responseData = [];

            foreach ($produkList as $produk) {
                // Cek apakah produk ini adalah favorit pengguna
                $isFavorit = $this->favoritProdukModel
                    ->where('id_produk', $produk['id'])
                    ->where('id_pengguna', $userId)
                    ->first() !== null;

                $responseData[] = [
                    'produk' => $produk,
                    'isFavorit' => $isFavorit,
                ];
            }

            $message = "Daftar produk berhasil diambil.";
            return $this->dataResponse($responseData, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            // Tangani kesalahan dan kirim respons error
            $message = 'Terjadi kesalahan dalam mengambil daftar produk.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }

    public function produkFavorit(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            // Ambil semua produk favorit berdasarkan pengguna
            $favoritList = $this->favoritProdukModel->where('id_pengguna', $userId)->findAll();

            $responseData = [];

            foreach ($favoritList as $favorit) {
                $produk = $this->produkModel->find($favorit['id_produk']);
                $responseData[] = [
                    'produk' => $produk,
                    'isFavorit' => true,
                ];
            }

            $message = "Daftar produk favorit berhasil diambil.";
            return $this->dataResponse($responseData, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            $message = 'Terjadi kesalahan dalam mengambil daftar produk favorit.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }

    public function switchFavorit(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;
            $produkId = $this->request->getPost('produk_id');

            $favorit = $this->favoritProdukModel
                ->where('id_pengguna', $userId)
                ->where('id_produk', $produkId)
                ->first();

            if ($favorit) {
                // Hapus dari favorit
                $this->favoritProdukModel->delete($favorit['id']);
                $message = 'Produk dihapus dari favorit.';
            } else {
                // Tambahkan ke favorit
                $this->favoritProdukModel->insert([
                    'id_produk' => $produkId,
                    'id_pengguna' => $userId,
                ]);
                $message = 'Produk ditambahkan ke favorit.';
            }

            return $this->messageResponse($message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            $message = 'Terjadi kesalahan dalam mengubah status favorit.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }
}
