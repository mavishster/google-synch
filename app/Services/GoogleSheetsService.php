<?php
namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Storage;
class GoogleSheetsService
{
    protected $client;
    protected $service;
    public function __construct()
    {

        $this->client = new Client();
        $this->client->setApplicationName('Laravel Google Sync');
        $this->client->setScopes([Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $tokenPath = storage_path('app/google/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
            } else {
                throw new \Exception('Authorization required. Go to /authorize');
            }
        }

        $this->service = new Sheets($this->client);
    }
    public function getService(): Sheets
    {
        return $this->service;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
