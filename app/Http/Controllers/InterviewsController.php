<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Illuminate\Http\Request;

class InterviewsController extends Controller
{
    public function index(Request $request)
    {
        $q = Interview::query()->with(['createdBy']);

        if ($request->filled('date_from')) {
            $q->whereDate('interview_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('interview_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $q->where(function ($qq) use ($s) {
                $qq->where('candidate_name', 'like', '%' . $s . '%')
                   ->orWhere('candidate_phone', 'like', '%' . $s . '%')
                   ->orWhere('source', 'like', '%' . $s . '%');
            });
        }

        $interviews = $q->orderByDesc('interview_date')
            ->orderByDesc('interview_id')
            ->paginate(30)
            ->appends($request->query());

        return view('interviews.index', compact('interviews'));
    }

    public function create()
    {
        return view('interviews.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateInterview($request);

        Interview::create([
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('interviews.index')->with('ok', 'Собеседование добавлено');
    }

    public function edit(Interview $interview)
    {
        return view('interviews.edit', compact('interview'));
    }

    public function update(Request $request, Interview $interview)
    {
        $data = $this->validateInterview($request);

        $interview->update([
            'interview_date' => $data['interview_date'],
            'candidate_name' => $data['candidate_name'],
            'candidate_phone' => $data['candidate_phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'planned',
            'comment' => $data['comment'] ?? null,
        ]);

        return redirect()->route('interviews.index')->with('ok', 'Собеседование обновлено');
    }

    public function destroy(Interview $interview)
    {
        $interview->delete();
        return redirect()->route('interviews.index')->with('ok', 'Собеседование удалено');
    }

    private function validateInterview(Request $request): array
    {
        return $request->validate([
            'interview_date' => ['required', 'date'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'candidate_phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:planned,came,no_show,hired,rejected'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
