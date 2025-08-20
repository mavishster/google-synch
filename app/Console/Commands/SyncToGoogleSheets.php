<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use App\Models\Record;

class SyncToGoogleSheets extends Command
{
    protected $signature = 'google:sync';
    protected $description = 'Sync local Allowed records with Google Sheets';

    public function handle()
    {
        // --- Google Client setup ---
        $client = new Client();
        $client->setApplicationName('Laravel Google Sync');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessType('offline');

        $tokenPath = storage_path('app/google/token.json');
        if (file_exists($tokenPath)) {
            $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));
            if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            }
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
        $sheetName = 'Лист1';

        // --- Read existing values from Google Sheet ---
        $existingValues = $service->spreadsheets_values->get($spreadsheetId, $sheetName)->getValues() ?? [];
        $rows = array_slice($existingValues, 1); // skip header

        // --- Update comments in DB for rows with valid IDs ---
        foreach ($rows as $row) {
            $recordId = $row[0] ?? null;
            $comment  = $row[4] ?? ''; // last column = comment
            if ($recordId && is_numeric($recordId)) {
                Record::where('id', $recordId)->update(['comment' => $comment]);
            }
        }

        // --- Get only Allowed records from DB ---
        $records = Record::allowed()->get();

        // --- Prepare new values to send to Google Sheet ---
        $header = ['id', 'title', 'description', 'status', 'comment'];
        $newValues = [$header];

        foreach ($records as $record) {
            $newValues[] = [
                $record->id,
                $record->title,
                $record->description,
                $record->status,
                $record->comment ?? '', // always last
            ];
        }

        // --- Update Google Sheet ---
        $body = new ValueRange(['values' => $newValues]);
        $params = ['valueInputOption' => 'RAW'];
        $service->spreadsheets_values->clear($spreadsheetId, $sheetName, new ClearValuesRequest());
        $service->spreadsheets_values->update($spreadsheetId, $sheetName, $body, $params);

        $this->info("✅ Allowed records successfully synced with Google Sheets!");

        // --- Display progress ---
        $progress = $this->output->createProgressBar(count($newValues) - 1);
        $progress->start();

        foreach (array_slice($newValues, 1) as $row) {
            $id = $row[0] ?? '';
            $comment = $row[4] ?? '';
            $this->line("ID: $id | Comment: $comment");
            $progress->advance();
        }

        $progress->finish();
        $this->info("\nDone!");
    }
}
