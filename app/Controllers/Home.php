<?php

namespace App\Controllers;
use CodeIgniter\API\ResponseTrait;

class Home extends BaseController
{
    use ResponseTrait;

    public function index(): \CodeIgniter\HTTP\Response
    {
        $message = "Selamat datang di API FasioNXT";
        return $this->messageResponse($message, 200);
    }
}
