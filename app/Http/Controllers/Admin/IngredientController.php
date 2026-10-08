<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::latest();
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('code', 'like', "%{$request->q}%");
            });
        }
        if ($request->filled('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'reorder_level');
        }
        $ingredients = $query->paginate(20)->withQueryString();
        return view('admin.ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        return view('admin.ingredients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:ingredients',
            'unit' => 'required|string|max:20',
            'stock_quantity' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string',
        ]);
        Ingredient::create($data);
        return redirect()->route('ingredients.index')->with('success', 'Ingredient created.');
    }

    public function edit(Ingredient $ingredient)
    {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:ingredients,code,' . $ingredient->id,
            'unit' => 'required|string|max:20',
            'stock_quantity' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $ingredient->update($data);
        return redirect()->route('ingredients.index')->with('success', 'Ingredient updated.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return redirect()->route('ingredients.index')->with('success', 'Ingredient deleted.');
    }

    public function importTemplate()
    {
        $headers = [
            'name', 'code', 'unit', 'stock_quantity', 'reorder_level',
            'cost_per_unit', 'expiry_date', 'storage_location', 'description', 'is_active',
        ];
        $sample = [
            'Basmati Rice', 'ING-RICE', 'kg', '50', '10', '280', '', 'Dry store', 'Premium rice', '1',
        ];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'ingredients-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request, CsvImportService $csv)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        [, $rows] = $csv->parse($request->file('csv_file'));

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = $row['_line'] ?? '?';
            $name = trim((string) ($row['name'] ?? ''));
            $code = trim((string) ($row['code'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));

            if ($name === '' || $unit === '') {
                $errors[] = "Line {$line}: name and unit are required.";
                continue;
            }

            if ($code === '') {
                $base = 'ING-' . Str::upper(Str::slug($name, '-'));
                $code = $base;
                $i = 1;
                while (Ingredient::where('code', $code)->exists()) {
                    $code = $base . '-' . $i++;
                }
            }

            $payload = [
                'name' => $name,
                'code' => $code,
                'unit' => $unit,
                'stock_quantity' => $csv->float($row['stock_quantity'] ?? 0),
                'reorder_level' => $csv->float($row['reorder_level'] ?? 0),
                'cost_per_unit' => $csv->float($row['cost_per_unit'] ?? 0),
                'expiry_date' => ! empty($row['expiry_date']) ? $row['expiry_date'] : null,
                'storage_location' => $row['storage_location'] ?? null,
                'description' => $row['description'] ?? null,
                'is_active' => $csv->bool($row['is_active'] ?? '1', true),
            ];

            $existing = Ingredient::where('code', $code)->first();
            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                Ingredient::create($payload);
                $created++;
            }
        }

        $message = "Ingredients import done: {$created} created, {$updated} updated.";
        if ($errors) {
            $message .= ' ' . count($errors) . ' row(s) skipped.';
            return redirect()->route('ingredients.index')
                ->with('success', $message)
                ->with('import_errors', array_slice($errors, 0, 20));
        }

        return redirect()->route('ingredients.index')->with('success', $message);
    }
}
