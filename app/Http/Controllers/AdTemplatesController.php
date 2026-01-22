<?php

namespace App\Http\Controllers;

use App\Models\AdTemplate;
use App\Services\AccessService;
use Illuminate\Http\Request;

class AdTemplatesController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
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

    public function create(AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
        return view('ad_templates.create');
    }

    public function store(Request $request, AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
        $data = $this->validateTemplate($request);

        AdTemplate::create([
            'template_name' => $data['template_name'],
            'template_type' => $data['template_type'],
            'is_active' => 1,
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Макет добавлен');
    }

    public function edit(AdTemplate $adTemplate, AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
        return view('ad_templates.edit', compact('adTemplate'));
    }

    public function update(Request $request, AdTemplate $adTemplate, AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
        $data = $this->validateTemplate($request);

        $adTemplate->update([
            'template_name' => $data['template_name'],
            'template_type' => $data['template_type'],
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Макет обновлён');
    }

    public function toggle(AdTemplate $adTemplate, AccessService $accessService)
    {
        $this->assertAdTemplateAccess($accessService);
        $adTemplate->update([
            'is_active' => $adTemplate->is_active ? 0 : 1,
        ]);

        return redirect()->route('ad_templates.index')->with('ok', 'Статус изменён');
    }

    private function assertAdTemplateAccess(AccessService $accessService): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        if (!$accessService->canManageAdTemplates($user)) {
            abort(403, 'Нет доступа к управлению рекламой');
        }
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'template_type' => ['required', 'in:leaflet'],
        ]);
    }
}
