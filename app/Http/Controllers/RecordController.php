<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Record;
use Illuminate\Http\Request;
use Faker\Factory;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{

    public function index()
    {
        $records = Record::all();
        $sheetUrl = AppSetting::where('key', 'sheet_url')->value('value');

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
        Record::factory(1000)->create();
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

        AppSetting::updateOrCreate(
            ['key' => 'sheet_url'],
            ['value' => $request->sheet_url]
        );

        return redirect()->back()->with('success', 'Sheet URL updated successfully');
    }


}

