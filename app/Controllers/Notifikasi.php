<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\NotifikasiModel;
use App\Models\PenggunaModel;
use CodeIgniter\API\ResponseTrait;
use Config\Token;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;

class Notifikasi extends BaseController
{
    use ResponseTrait;
    protected $notifikasiModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->notifikasiModel = new NotifikasiModel();
    }

    public function notifikasiPengguna(): Response
    {
        try {
            $decoded = JwtHelper::decodeTokenFromRequest($this->request);

            if (!$decoded) {
                return $this->messageResponse('Token tidak valid', self::HTTP_UNAUTHORIZED);
            }

            $userId = $decoded->id;

            $notifikasiList = $this->notifikasiModel
                ->where('id_pengguna', $userId)
                ->orWhere('umum', true)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $message = "Daftar notifikasi berhasil diambil.";
            return $this->dataResponse($notifikasiList, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            $message = 'Terjadi kesalahan dalam mengambil daftar notifikasi.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }
}
