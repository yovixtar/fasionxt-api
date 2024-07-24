<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\KategoriModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\Response;

class Kategori extends BaseController
{
    use ResponseTrait;
    protected $kategoriModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->kategoriModel = new KategoriModel();
    }

    public function daftarKategori(): Response
    {
        try {
            $kategoriList = $this->kategoriModel->findAll();

            $message = "Daftar kategori berhasil diambil.";
            return $this->dataResponse($kategoriList, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            // Tangani kesalahan dan kirim respons error
            $message = 'Terjadi kesalahan dalam mengambil daftar kategori.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }
}