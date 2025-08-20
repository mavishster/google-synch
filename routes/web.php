<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecordController;
use Google\Client;
use Google\Service\Sheets;

Route::get('/', [RecordController::class, 'index'])->name('records.index');
Route::post('/records', [RecordController::class, 'store'])->name('records.store');
Route::delete('/records/{id}', [RecordController::class, 'destroy'])->name('records.destroy');

Route::post('/generate', [RecordController::class, 'generate'])->name('records.generate');
Route::post('/truncate', [RecordController::class, 'truncate'])->name('records.truncate');
Route::post('/set-url', [RecordController::class, 'setUrl'])->name('records.setUrl');

Route::get('/authorize', function () {
    $client = new Client();
    $client->setApplicationName('Laravel Google Sync');
    $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
    $client->setAuthConfig(storage_path('app/google/credentials.json'));
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $authUrl = $client->createAuthUrl();
    return redirect($authUrl);
});
Route::get('/oauth2callback', function () {
    $client = new Client();
    $client->setApplicationName('Laravel Google Sync');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig(storage_path('app/google/credentials.json'));
    $client->setAccessType('offline');

    if (request()->has('code')) {
        $accessToken = $client->fetchAccessTokenWithAuthCode(request('code'));
        file_put_contents(storage_path('app/google/token.json'), json_encode($accessToken));
        return "✅ Authorization successful! Token saved.";
    }

    return "Authorization failed.";
});

Route::get('/fetch/{count?}', function ($count = null) {
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    Artisan::call('google:fetch', ['count' => $count], $output);
    $result = $output->fetch();
    return nl2br(e($result));
});
