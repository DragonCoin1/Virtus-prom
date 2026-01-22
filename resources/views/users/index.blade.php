@extends('layouts.app')

@section('title', 'Пользователи')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Пользователи</h1>
        @if($canManageUsers ?? false)
            <a href="{{ route('users.create') }}" class="btn btn-primary">Добавить</a>
        @endif
    </div>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Роль</th>
                <th>Город</th>
                <th>Филиал</th>
                <th>Активен</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->user_login }}</td>
                    <td>{{ $user->user_full_name }}</td>
                    <td>{{ $roles[$user->role_id] ?? ('#' . $user->role_id) }}</td>
                    <td>{{ $user->city?->city_name ?? '—' }}</td>
                    <td>{{ $user->branch?->branch_name ?? '—' }}</td>
                    <td>{{ (int) $user->user_is_active === 1 ? 'Да' : 'Нет' }}</td>
                    <td class="text-end">
                        @if($canManageUsers ?? false)
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', $user) }}">Изменить</a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить пользователя?')">Удалить</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Нет пользователей</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
