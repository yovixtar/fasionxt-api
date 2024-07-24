<?php

namespace App\Controllers;

use App\Models\KategoriModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\Response;

class AdminKategori extends BaseController
{
    use ResponseTrait;

    protected $kategoriModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->kategoriModel = new KategoriModel();
    }

    public function create(): Response
    {
        try {
            $nama = $this->request->getPost('nama');

            if (!$nama) {
                return $this->messageResponse('Data tidak lengkap.', self::HTTP_BAD_REQUEST);
            }


            $data = [
                'nama' => $nama,
            ];

            $this->kategoriModel->insert($data);

            return $this->messageResponse('Kategori berhasil ditambahkan.', self::HTTP_SUCCESS_CREATE);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat menambah kategori. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function list(): Response
    {
        try {
            $kategoriList = $this->kategoriModel->findAll();

            return $this->dataResponse($kategoriList, 'Daftar kategori berhasil diambil.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengambil daftar kategori. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function update(): Response
    {
        try {
            if (!$this->request->getPost('id') || !$this->request->getPost('nama')) {
                return $this->messageResponse('Data tidak lengkap.', self::HTTP_BAD_REQUEST);
            }
            $data = [
                'nama' => $this->request->getPost('nama'),
            ];
            $id = $this->request->getPost('id');

            $this->kategoriModel->update($id, $data);

            return $this->messageResponse('Kategori berhasil diupdate.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengupdate kategori. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function delete($id): Response
    {
        try {
            $kategori = $this->kategoriModel->find($id);

            if (!$kategori) {
                return $this->messageResponse('kategori tidak ditemukan.', self::HTTP_BAD_REQUEST);
            }

            $this->kategoriModel->delete($id);

            return $this->messageResponse('kategori berhasil dihapus.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat menghapus kategori. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }
}
