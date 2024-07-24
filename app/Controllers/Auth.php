<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\NotifikasiModel;
use App\Models\PenggunaModel;
use CodeIgniter\API\ResponseTrait;
use Config\Token;
use \Firebase\JWT\JWT;
use CodeIgniter\HTTP\Response;

class Auth extends BaseController
{
    use ResponseTrait;
    protected $penggunaModel, $notifikasiModel;

    const HTTP_SERVER_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SUCCESS = 200;
    const HTTP_SUCCESS_CREATE = 201;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
        $this->notifikasiModel = new NotifikasiModel();
    }

    public function register(): Response
    {
        try {
            // Ambil data POST dari request
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            // Verifikasi request
            if (empty($username) || empty($password)) {
                $message = "Username dan password harus diisi.";
                return $this->messageResponse($message, self::HTTP_BAD_REQUEST);
            }

            if (!is_string($password)) {
                $message = "Password harus berupa string.";
                return $this->messageResponse($message, self::HTTP_BAD_REQUEST);
            }

            // Hash password menggunakan SHA1
            $hashedPassword = sha1($password);

            // Cek apakah pengguna sudah ada berdasarkan username
            $existingUser = $this->penggunaModel->where('username', $username)->first();

            if ($existingUser) {
                $message = "Pengguna dengan username tersebut sudah ada.";
                return $this->messageResponse($message, self::HTTP_BAD_REQUEST);
            }

            // Tambahkan pengguna baru ke dalam tabel pengguna
            $data = [
                'username' => $username,
                'password' => $hashedPassword,
            ];

            $this->penggunaModel->insert($data);

            // Ambil ID pengguna yang baru saja ditambahkan
            $newUserId = $this->penggunaModel->getInsertID();

            // Tambahkan notifikasi selamat datang
            $notifikasiData = [
                'id_pengguna' => $newUserId,
                'judul' => "Selamat datang, $username",
                'deskripsi' => "Selamat bergabung di platform kami, $username. Kami senang Anda bergabung!",
                'umum' => false
            ];
            $this->notifikasiModel->insert($notifikasiData);

            // Kirim respons berhasil menambahkan pengguna
            $message = "Berhasil registrasi pengguna.";
            return $this->messageResponse($message, self::HTTP_SUCCESS_CREATE);
        } catch (\Throwable $th) {
            // Tangani kesalahan dan kirim respons error
            $message = 'Terjadi kesalahan dalam proses registrasi pengguna.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }


    public function login(): Response
    {
        try {
            // Mengambil request pengguna
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            // Validasi request
            if (empty($username) || empty($password)) {
                $message = "Username dan password harus diisi.";
                return $this->messageResponse($message, self::HTTP_BAD_REQUEST);
            }

            if (!is_string($password)) {
                $message = "Password harus berupa string.";
                return $this->messageResponse($message, self::HTTP_BAD_REQUEST);
            }

            // Pencocokan data pengguna
            $hashedPassword = sha1($password);

            $user = $this->penggunaModel->where('username', $username)->first();

            if (!$user || $user['password'] !== $hashedPassword) {
                $message = "Gagal Login. Username atau password salah.";
                return $this->messageResponse($message, self::HTTP_UNAUTHORIZED);
            }

            $key = Token::JWT_SECRET_KEY;
            $payload = [
                'id' => $user['id'],
                'username' => $user['username'],
                'timestamp' => time(),
            ];
            $token = JWT::encode($payload, $key, 'HS256');

            $this->penggunaModel->update($user['id'], ['token' => $token]);

            // Pengkondisian berhasil login
            $message = "Berhasil Login";
            $data = [
                'token' => $token,
            ];
            return $this->dataResponse($data, $message, self::HTTP_SUCCESS);
        } catch (\Throwable $th) {
            // Tangani kesalahan dan kirim respons error
            $message = 'Terjadi kesalahan dalam proses login.';
            return $this->messageResponse($message, self::HTTP_SERVER_ERROR);
        }
    }
}
