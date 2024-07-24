<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\PenggunaModel;
use CodeIgniter\API\ResponseTrait;
use Config\Token;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;

class Profile extends BaseController
{
    use ResponseTrait;
    protected $penggunaModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
    }

    public function currentUser(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            $user = $this->penggunaModel->find($userId);

            $message = 'Current user berhasil dimuat.';
            return $this->dataResponse($user, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            $message = 'Terjadi kesalahan dalam mengambil data akun.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }

    public function pengaturanAkun(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            $nama = $this->request->getPost('nama');
            $username = $this->request->getPost('username');
            $passwordLama = $this->request->getPost('password_lama');
            $passwordBaru = $this->request->getPost('password_baru');
            $alamat = $this->request->getPost('alamat');

            $data = [];

            if ($nama) {
                $data['nama'] = $nama;
            }

            if ($username) {
                // Cek apakah username sudah tersedia
                $existingUser = $this->penggunaModel->where('username', $username)->first();
                if ($existingUser && $existingUser['id'] != $userId) {
                    return $this->messageResponse('Username sudah tersedia.', self::HTTP_BAD_REQUEST);
                }
                $data['username'] = $username;
            }

            if ($passwordLama && $passwordBaru) {
                $user = $this->penggunaModel->find($userId);

                if (!$user || $user['password'] !== sha1($passwordLama)) {
                    return $this->messageResponse('Password lama tidak valid.', self::HTTP_UNAUTHORIZED);
                }

                $data['password'] = sha1($passwordBaru);
            }

            if ($alamat) {
                $data['alamat'] = $alamat;
            }

            if (empty($data)) {
                return $this->messageResponse('Tidak ada data yang diubah.', self::HTTP_BAD_REQUEST);
            }

            $this->penggunaModel->update($userId, $data);

            $message = 'Pengaturan akun berhasil diubah.';
            return $this->messageResponse($message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            $message = 'Terjadi kesalahan dalam mengubah pengaturan akun.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }
}
