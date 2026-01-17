<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Virtus Prom — Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 420px; margin-top: 80px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3">Вход</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Логин</label>
                    <input name="user_login" class="form-control" value="{{ old('user_login') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button class="btn btn-primary w-100" type="submit">Войти</button>
            </form>

            <div class="text-muted small mt-3">
                Тестовые аккаунты: owner / owner12345, manager / manager12345
            </div>
        </div>
    </div>
</div>
</body>
</html>
