<?php

namespace App\Controllers;

use App\Models\ProdukModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\Response;

class AdminProduk extends BaseController
{
    use ResponseTrait;

    protected $produkModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->produkModel = new ProdukModel();
    }

    public function create(): Response
    {
        try {
            $nama = $this->request->getPost('nama');
            $harga = $this->request->getPost('harga');
            $deskripsi = $this->request->getPost('deskripsi');
            $id_kategori = $this->request->getPost('id_kategori');
            $stok = $this->request->getPost('stok');
            $ukuran = $this->request->getPost('ukuran');
            $file = $this->request->getFile('gambar');

            if (!$nama || !$harga || !$ukuran || !$file) {
                return $this->messageResponse('Data tidak lengkap.', self::HTTP_BAD_REQUEST);
            }

            $ukuranArray = explode(',', $ukuran);
            $ukuranJson = json_encode($ukuranArray);

            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move(ROOTPATH . 'public/images', $newName);
                $gambar = $newName;
            } else {
                return $this->messageResponse('Data gambar gagal diupload.' . $file->getErrorString(). ' - '. !$file->hasMoved(), self::HTTP_SERVER_ERROR);
            }

            $data = [
                'nama' => $nama,
                'harga' => $harga,
                'ukuran' => $ukuranJson,
                'deskripsi' => $deskripsi,
                'id_kategori' => $id_kategori,
                'stok' => $stok,
                'gambar' => base_url('images/' . $gambar),
            ];

            $this->produkModel->insert($data);

            return $this->messageResponse('Produk berhasil ditambahkan.', self::HTTP_SUCCESS_CREATE);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat menambah produk. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function list(): Response
    {
        try {
            $produkList = $this->produkModel->findAll();

            $response = array_map(function ($produk) {
                $ukuranArray = json_decode($produk['ukuran']);
                $ukuranString = implode(',', $ukuranArray);
                $produk['ukuran'] = $ukuranString;
                return $produk;
            }, $produkList);

            return $this->dataResponse($response, 'Daftar produk berhasil diambil.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengambil daftar produk. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function update(): Response
    {
        try {
            $id = $this->request->getPost('id');
            $nama = $this->request->getPost('nama');
            $harga = $this->request->getPost('harga');
            $ukuran = $this->request->getPost('ukuran');
            $deskripsi = $this->request->getPost('deskripsi');
            $id_kategori = $this->request->getPost('id_kategori');
            $stok = $this->request->getPost('stok');
            if (!$id || !$nama || !$harga || !$ukuran || !$deskripsi || !$id_kategori || !$stok) {
                return $this->messageResponse('Data tidak lengkap.', self::HTTP_BAD_REQUEST);
            }
            $data = [
                'nama' => $nama,
                'harga' => $harga,
                'deskripsi' => $deskripsi,
                'id_kategori' => $id_kategori,
                'stok' => $stok,
            ];
            $gambar = $this->request->getFile('gambar');


            $ukuranArray = explode(',', $ukuran);
            $ukuranJson = json_encode($ukuranArray);
            $data['ukuran'] = $ukuranJson;

            $produk = $this->produkModel->find($id);
            if ($this->request->getFile('gambar') && $this->request->getFile('gambar')->isValid()) {
                // Delete old image
                if ($produk['gambar'] && file_exists(ROOTPATH . 'public/images/' . basename($produk['gambar']))) {
                    unlink(ROOTPATH . 'public/images/' . basename($produk['gambar']));
                }

                // Upload new image
                $gambar = $this->request->getFile('gambar');
                $gambarName = $gambar->getRandomName();
                $gambar->move(ROOTPATH . 'public/images/', $gambarName);
                $data['gambar'] = base_url('images/' . $gambarName);
            }

            $this->produkModel->update($id, $data);

            return $this->messageResponse('Produk berhasil diupdate.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat mengupdate produk. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }

    public function delete($id): Response
    {
        try {
            $produk = $this->produkModel->find($id);

            if (!$produk) {
                return $this->messageResponse('Produk tidak ditemukan.', self::HTTP_BAD_REQUEST);
            }

            if ($produk['gambar'] && file_exists(ROOTPATH . 'public/images/' . basename($produk['gambar']))) {
                unlink(ROOTPATH . 'public/images/' . basename($produk['gambar']));
            }

            $this->produkModel->delete($id);

            return $this->messageResponse('Produk berhasil dihapus.', self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            return $this->messageResponse('Terjadi kesalahan saat menghapus produk. ' . $th->getMessage(), self::HTTP_SERVER_ERROR);
        }
    }
}
