<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use App\Services\AccessService;
use Illuminate\Http\Request;

class InstructionsController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        $query = Instruction::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active' ? 1 : 0);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('title', 'like', '%' . $search . '%');
        }

        $instructions = $query->orderByDesc('instruction_id')
            ->paginate(20)
            ->appends($request->query());

        return view('instructions.index', compact('instructions'));
    }

    public function create(AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        return view('instructions.create');
    }

    public function store(Request $request, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        $data = $this->validateInstruction($request);

        Instruction::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'created_by' => auth()->id(),
            'is_active' => 1,
        ]);

        return redirect()->route('instructions.index')->with('ok', 'Инструкция добавлена');
    }

    public function edit(Instruction $instruction, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        return view('instructions.edit', compact('instruction'));
    }

    public function update(Request $request, Instruction $instruction, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        $data = $this->validateInstruction($request);

        $instruction->update([
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        return redirect()->route('instructions.index')->with('ok', 'Инструкция обновлена');
    }

    public function toggle(Instruction $instruction, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        $instruction->update([
            'is_active' => $instruction->is_active ? 0 : 1,
        ]);

        return redirect()->route('instructions.index')->with('ok', 'Статус инструкции изменён');
    }

    public function destroy(Instruction $instruction, AccessService $accessService)
    {
        $this->assertInstructionAccess($accessService);

        $instruction->delete();

        return redirect()->route('instructions.index')->with('ok', 'Инструкция удалена');
    }

    private function validateInstruction(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);
    }

    private function assertInstructionAccess(AccessService $accessService): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        if (!$accessService->canManageInstructions($user)) {
            abort(403, 'Нет доступа к инструкциям');
        }
    }
}
