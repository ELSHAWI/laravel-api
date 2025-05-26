<?php

namespace App\Http\Controllers;

use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    // Store a new PDF
   public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'term' => 'nullable|string|in:First,Second',
            'grade' => 'nullable|string|in:1,2,3,4',
            'college' => 'nullable|string|in:law,islamic,political',
            'plan_type' => 'required|string|in:free,basic,pro',
            'pdf' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        if ($request->hasFile('pdf')) {
            $data['pdf_path'] = $request->file('pdf')->store('pdfs', 'public');
        }

        $pdf = Pdf::create($data);

        return response()->json([
            'success' => true,
            'data' => $pdf,
        ], 201);
    }


    // Get all PDFs
    public function index()
    {
        $pdfs = Pdf::all();

        return response()->json([
            'success' => true,
            'data' => $pdfs,
        ]);
    }

    // Filter PDFs
    public function filter(Request $request)
    {
        $request->validate([
            'term' => 'nullable|string|in:First,Second',
            'grade' => 'nullable|string|in:1,2,3,4',
            'college' => 'nullable|string|in:law,islamic,political',
            'plan_type' => 'nullable|string|in:free,basic,pro'
        ]);

        $query = Pdf::query();

        if ($request->term) {
            $query->where('term', $request->term);
        }

        if ($request->grade) {
            $query->where('grade', $request->grade);
        }

        if ($request->college) {
            $query->where('college', $request->college);
        }
         if ($request->plan_type) {
            $query->where('plan_type', $request->plan_type);
        }

        $pdfs = $query->get();

        return response()->json([
            'success' => true,
            'data' => $pdfs,
        ]);
    }

    // Get single PDF
    public function show($id)
    {
        $pdf = Pdf::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pdf,
        ]);
    }

    // Update a PDF
    public function update(Request $request, $id)
    {
        $pdf = Pdf::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'term' => 'nullable|string|in:First,Second',
            'grade' => 'nullable|string|in:1,2,3,4',
            'college' => 'nullable|string|in:law,islamic,political',
            'plan_type' => 'sometimes|required|string|in:free,basic,pro',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
            'pdf_path' => 'nullable|string' // For existing files
        ]);

        // Handle file upload
        if ($request->hasFile('pdf')) {
            // Delete old file if exists
            if ($pdf->pdf_path) {
                Storage::disk('public')->delete($pdf->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf')->store('pdfs', 'public');
        } elseif ($request->has('pdf_path')) {
            // Keep existing file
            $data['pdf_path'] = $request->pdf_path;
        }

        $pdf->update($data);

        return response()->json([
            'success' => true,
            'data' => $pdf,
            'message' => 'PDF updated successfully'
        ]);
    }


    // Delete a PDF
    public function destroy($id)
    {
        $pdf = Pdf::findOrFail($id);

        // Delete associated file
        if ($pdf->pdf_path) {
            Storage::disk('public')->delete($pdf->pdf_path);
        }

        $pdf->delete();

        return response()->json([
            'success' => true,
            'message' => 'PDF deleted successfully',
        ]);
    }
}