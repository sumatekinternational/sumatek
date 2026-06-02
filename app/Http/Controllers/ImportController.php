<?php

namespace App\Http\Controllers;

use App\Models\DataImport;
use App\Services\Import\ImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Legacy migration tool (§6.6): upload -> preview (dry-run) -> commit ->
 * (optional) rollback.
 */
class ImportController extends Controller
{
    public function index()
    {
        $this->authorizeAbility('import.manage');

        return response()->json(DataImport::latest()->paginate(20));
    }

    /** Downloadable column template for the chosen record type. */
    public function template(Request $request)
    {
        $this->authorizeAbility('import.manage');

        $type = $request->validate(['type' => ['required', Rule::in(DataImport::TYPES)]])['type'];

        return response()->json([
            'type' => $type,
            'columns' => ImportService::templateColumns($type),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('import.manage');

        $data = $request->validate([
            'type' => ['required', Rule::in(DataImport::TYPES)],
            'file' => ['required', 'file', 'max:20480', 'mimes:csv,txt'],
            'dedupe_strategy' => ['nullable', Rule::in(['skip', 'update'])],
            'mapping' => ['nullable', 'array'], // target_field => source_column
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        $import = DataImport::create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'source' => 'csv',
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'pending',
            'dedupe_strategy' => $data['dedupe_strategy'] ?? 'skip',
            'mapping' => $data['mapping'] ?? null,
        ]);

        return response()->json($import, 201);
    }

    public function show(DataImport $import)
    {
        $this->authorizeAbility('import.manage');

        return response()->json($import);
    }

    public function preview(DataImport $import, ImportService $service)
    {
        $this->authorizeAbility('import.manage');

        return response()->json($service->preview($import));
    }

    public function commit(DataImport $import, ImportService $service)
    {
        $this->authorizeAbility('import.manage');

        abort_if(in_array($import->status, ['completed', 'rolled_back'], true), 422, 'Import already processed.');

        return response()->json($service->run($import));
    }

    public function rollback(DataImport $import, ImportService $service)
    {
        $this->authorizeAbility('import.manage');

        return response()->json($service->rollback($import));
    }
}
