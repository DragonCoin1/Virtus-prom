<?php

namespace App\Http\Controllers;

use App\Models\AdTemplate;
use Illuminate\Http\Request;

class AdTemplatesController extends Controller
{
    public function index(Request $request)
    {
        $q = AdTemplate::query();

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $q->where('is_active', 1);
            }
            if ($request->input('status') === 'inactive') {
                $q->where('is_active', 0);
            }
        }

        if ($request->filled('type')) {
            $q->where('template_type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where('template_name', 'like', '%' . $s . '%');
        }

        $templates = $q->orderBy('template_type')
            ->orderBy('template_name')
            ->paginate(30)
            ->appends($request->query());

        return view('ad_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('ad_templates.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);

        AdTemplate::create([
            'template_name' => $data['template_name'],
            'template_type' => $data['template_type'],
            'is_active' => 1,
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Макет добавлен');
    }

    public function edit(AdTemplate $adTemplate)
    {
        return view('ad_templates.edit', compact('adTemplate'));
    }

    public function update(Request $request, AdTemplate $adTemplate)
    {
        $data = $this->validateTemplate($request);

        $adTemplate->update([
            'template_name' => $data['template_name'],
            'template_type' => $data['template_type'],
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Макет обновлён');
    }

    public function toggle(AdTemplate $adTemplate)
    {
        $adTemplate->update([
            'is_active' => $adTemplate->is_active ? 0 : 1,
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Статус изменён');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'template_type' => ['required', 'in:leaflet'],
        ]);
    }
}
