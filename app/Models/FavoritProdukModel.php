<?php
namespace App\Models;

use CodeIgniter\Model;

class FavoritProdukModel extends Model
{
    protected $table = 'favorit_produk';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_produk', 'id_pengguna', 'created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
}
