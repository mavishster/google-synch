<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;
use App\Models\Record;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;

class SyncToGoogleSheets extends Command
{
    protected $signature = 'google:sync';
    protected $description = 'Sync local Allowed records with Google Sheets';

    public function handle()
    {
        Log::info('Google sync started at ' . now());

        try {
            // --- Google Client setup ---
            $client = new Client();
            $client->setApplicationName('Laravel Google Sync');
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');

            // --- Load credentials ---
            $jsonEnv = env('GOOGLE_APPLICATION_CREDENTIALS_JSON');
            if ($jsonEnv) {
                $client->setAuthConfig(json_decode($jsonEnv, true));
            } else {
                $localPath = storage_path('app/google/credentials.json');
                if (!file_exists($localPath)) {
                    Log::error('No Google credentials found!');
                    $this->error('No Google credentials found!');
                    return;
                }
                $client->setAuthConfig($localPath);
            }

            $service = new Sheets($client);

            // --- Get Google Sheet URL from DB ---
            $sheetUrl = AppSetting::where('key', 'sheet_url')->value('value');
            if (!$sheetUrl) {
                $this->error("Google Sheet URL not set in DB!");
                Log::error("Google Sheet URL not set in DB!");
                return;
            }

            // --- Extract Sheet ID ---
            preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheetUrl, $matches);
            if (!isset($matches[1])) {
                $this->error("Invalid Google Sheet URL!");
                Log::error("Invalid Google Sheet URL: $sheetUrl");
                return;
            }

            $spreadsheetId = $matches[1];
            $sheetName = 'Лист1'; // adjust if your sheet name is different

            // --- Read existing values ---
            $existingValues = $service->spreadsheets_values
                ->get($spreadsheetId, $sheetName)
                ->getValues() ?? [];

            $rows = array_slice($existingValues, 1); // skip header row

            // --- Update comments in DB for existing rows ---
            foreach ($rows as $row) {
                $recordId = $row[0] ?? null;
                $comment  = $row[4] ?? '';
                if ($recordId && is_numeric($recordId)) {
                    Record::where('id', $recordId)->update(['comment' => $comment]);
                }
            }

            // --- Get Allowed records from DB ---
            $records = Record::allowed()->get();
            if ($records->isEmpty()) {
                Log::info('No Allowed records to sync.');
                return;
            }

            // --- Prepare values for Google Sheet ---
            $header = ['id', 'title', 'description', 'status', 'comment'];
            $newValues = [$header];

            foreach ($records as $record) {
                $newValues[] = [
                    $record->id,
                    $record->title,
                    $record->description,
                    $record->status,
                    $record->comment ?? '',
                ];
            }

            // --- Clear sheet before writing ---
            $service->spreadsheets_values->clear($spreadsheetId, $sheetName, new ClearValuesRequest());

            // --- Insert in batches ---
            $batchSize = 100; // insert 100 rows at a time
            for ($i = 1; $i < count($newValues); $i += $batchSize) {
                $chunk = array_slice($newValues, $i, $batchSize);
                $body = new ValueRange(['values' => array_merge([$header], $chunk)]);
                $params = ['valueInputOption' => 'RAW'];
                $service->spreadsheets_values->update($spreadsheetId, $sheetName, $body, $params);
            }

            Log::info('✅ Allowed records successfully synced with Google Sheets! Total rows: ' . (count($newValues) - 1));
            $this->info('✅ Google sync completed successfully!');

        } catch (\Throwable $e) {
            Log::error('Google sync failed: ' . $e->getMessage());
            $this->error('Google sync failed: ' . $e->getMessage());
        }
    }
}
