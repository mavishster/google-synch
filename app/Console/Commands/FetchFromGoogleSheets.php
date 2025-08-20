<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Sheets;

class FetchFromGoogleSheets extends Command
{
    protected $signature = 'google:fetch {count?}';
    protected $description = 'Fetch data from Google Sheets and show ID + Comments in console';

    public function handle()
    {
        $countLimit = $this->argument('count') ?: null;

        // Google Client setup
        $client = new Client();
        $client->setApplicationName('Laravel Google Fetch');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessType('offline');

        $tokenPath = storage_path('app/google/token.json');
        if (!file_exists($tokenPath)) {
            $this->error("Token not found! Run /authorize first.");
            return;
        }

        $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));
        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        $service = new Sheets($client);

        // --- Get Spreadsheet ID ---
        $sheetUrlPath = storage_path('app/config/sheet_url.txt');
        if (!file_exists($sheetUrlPath)) {
            $this->error("Google Sheet URL not set!");
            return;
        }

        $sheetUrl = file_get_contents($sheetUrlPath);
        preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheetUrl, $matches);
        if (!isset($matches[1])) {
            $this->error("Invalid Google Sheet URL!");
            return;
        }

        $spreadsheetId = $matches[1];
        $range = 'Лист1!A1:Z1000'; // include header

        // --- Read values from Google Sheet ---
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $allRows = $response->getValues() ?? [];

        if (empty($allRows)) {
            $this->info("No data found in Google Sheet.");
            return;
        }

        // Determine comment column index
        $header = $allRows[0];
        $commentIndex = array_search('comment', $header);
        if ($commentIndex === false) {
            $commentIndex = count($header); // assume last column
        }

        // Skip header
        $rows = array_slice($allRows, 1);

        if ($countLimit) {
            $rows = array_slice($rows, 0, (int)$countLimit);
        }

        // Output with progress bar
        $this->info("Fetching IDs and comments from Google Sheet...");
        $progress = $this->output->createProgressBar(count($rows));
        $progress->start();
        $this->line("\n");

        foreach ($rows as $row) {
            $id = $row[0] ?? 'N/A';
            $comment = $row[$commentIndex] ?? '';
            $this->line("ID: $id | Comment: $comment");
            $progress->advance();
        }

        $progress->finish();
        $this->info("\n✅ Fetched ".count($rows)." rows from Google Sheets.");
    }
}
