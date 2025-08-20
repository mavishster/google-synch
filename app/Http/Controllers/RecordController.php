<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;

class RecordController extends Controller
{

    public function index()
    {
        $records = Record::all();
        $sheetUrl = config('app.sheet_url', '');
        return view('records.index', compact('records', 'sheetUrl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string'
        ]);

        Record::create($validated);

        return redirect()->route('records.index')->with('success', 'Record created successfully');
    }

    public function destroy($id)
    {
        $record = Record::findOrFail($id);
        $record->delete();

        return redirect()->route('records.index')->with('success', 'Record deleted successfully');
    }

    public function generate()
    {
        $statuses = ['Allowed', 'Prohibited'];
        $records = Record::factory()->count(1000)->create();
        return redirect()->back()->with('success', 'Records generated successfully');
    }

    public function truncate()
    {
        Record::truncate();
        return redirect()->back()->with('success', 'Records truncated successfully');
    }

    public function setUrl(Request $request)
    {
        $request->validate([
            'sheet_url' => 'required|url'
        ]);

        file_put_contents(storage_path('app/config/sheet_url.txt'), $request->sheet_url);

        return redirect()->back()->with('success', 'Sheet URL updated successfully');
    }

}

