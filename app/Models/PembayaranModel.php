<?php
namespace App\Models;

use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id_pesanan', 'trx', 'session', 'subtotal', 'total', 
        'status', 'via', 'nomor_bayar', 'channel', 'created_at', 'updated_at', 'deleted_at'
    ];
    
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
