<?php

namespace App\Models;

use CodeIgniter\Model;

class PesananItemModel extends Model
{
    protected $table = 'pesanan_item';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_pesanan', 'id_produk', 'jumlah', 'ukuran', 'created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $useSoftDeletes = true;
}
