<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymeService {
    protected $url;
    protected $auth_header;

    public function __construct() {
        $this->url = config('services.payme.endpoint');
        $id = config('services.payme.id');
        $key = config('services.payme.key');
        $this->auth_header = "{$id}:{$key}";
    }

    public function request($method, $params) {
        $response = Http::withHeaders([
            'X-Auth' => $this->auth_header
        ])->post($this->url, [
            'id' => time(),
            'method' => $method,
            'params' => $params
        ]);
        Log::debug("Final Auth Header: " . $this->auth_header);

        if ($response->failed()) {
            return ['error' => 'Payme ulanishda xatolik', 'status' => $response->status()];
        }
        Log::info("Payme Response:", $response->json());

        return $response->json();
    }
}