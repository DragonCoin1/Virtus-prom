<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // 1) Убираем стандартные поля Laravel, которые нам не нужны
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('users', 'email')) {
                // если на email есть уникальный индекс — пробуем убрать
                // (в зависимости от версии Laravel он может называться по-разному)
                try {
                    $table->dropUnique('users_email_unique');
                } catch (\Throwable $e) {
                    // если индекса нет/другое имя — просто игнорируем
                }
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }

            // 2) Добавляем наши поля
            $table->unsignedBigInteger('role_id')->after('id');

            $table->string('user_login')->unique()->after('role_id');
            $table->string('user_password_hash')->after('user_login');
            $table->string('user_full_name')->after('user_password_hash');

            $table->char('user_phone', 10)->nullable()->after('user_full_name');
            $table->integer('user_age')->nullable()->after('user_phone');

            $table->enum('user_work_status', ['active', 'trainee', 'fired', 'paused'])
                ->nullable()
                ->after('user_age');

            $table->date('user_hired_at')->nullable()->after('user_work_status');
            $table->date('user_fired_at')->nullable()->after('user_hired_at');

            $table->string('user_notes')->nullable()->after('user_fired_at');

            $table->boolean('user_is_active')->default(true)->after('user_notes');
            $table->dateTime('user_last_login_at')->nullable()->after('user_is_active');
            $table->dateTime('user_password_changed_at')->nullable()->after('user_last_login_at');
        });

        // 3) Добавляем внешний ключ ОТДЕЛЬНО (так надёжнее на MySQL)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('role_id')->on('roles');
        });
    }

    public function down(): void
    {
        // Откат — возвращаем обратно как минимум email/name (упрощённо)
        // В реальных проектах down иногда не делают идеальным, но здесь сделаем аккуратно.

        Schema::table('users', function (Blueprint $table) {
            // Сначала удалить FK
            try {
                $table->dropForeign(['role_id']);
            } catch (\Throwable $e) {
            }

            // Удаляем наши поля
            $columns = [
                'role_id',
                'user_login',
                'user_password_hash',
                'user_full_name',
                'user_phone',
                'user_age',
                'user_work_status',
                'user_hired_at',
                'user_fired_at',
                'user_notes',
                'user_is_active',
                'user_last_login_at',
                'user_password_changed_at',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }

            // Возвращаем стандартные (минимально)
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });
    }
};
