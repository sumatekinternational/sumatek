<?php

namespace App\Http\Controllers;

use App\Models\VisaDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisaDocumentController extends Controller
{
    public function upload(Request $request, VisaDocument $document)
    {
        $this->authorizeAbility('visa.manage');

        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png']]);

        $document->update([
            'file_path' => $request->file('file')->store("visa-docs/{$document->visa_case_id}", 'local'),
            'status' => 'uploaded',
            'verified_at' => null,
        ]);

        return response()->json($document);
    }

    /** Mark a checklist document verified or rejected (auto-validation gate). */
    public function review(Request $request, VisaDocument $document)
    {
        $this->authorizeAbility('visa.manage');

        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'rejected'])]]);

        $document->update([
            'status' => $data['status'],
            'verified_at' => $data['status'] === 'verified' ? now() : null,
        ]);

        return response()->json($document);
    }
}
