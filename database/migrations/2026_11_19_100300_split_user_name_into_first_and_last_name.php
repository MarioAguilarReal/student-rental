<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D-48: first name(s) and last name(s) are stored separately; User::$name is derived.
     * Existing names are split assuming two surnames when there are three or more words.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->default('')->after('id');
            $table->string('last_name')->default('')->after('first_name');
        });

        DB::table('users')->select(['id', 'name'])->orderBy('id')->each(function (object $user) {
            [$first, $last] = self::split((string) $user->name);
            DB::table('users')->where('id', $user->id)->update(['first_name' => $first, 'last_name' => $last]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
        });

        DB::statement("UPDATE users SET name = trim(first_name || ' ' || last_name)");

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_name', 'first_name']);
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function split(string $name): array
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];

        return match (true) {
            count($words) <= 1 => [$words[0] ?? '', ''],
            count($words) === 2 => [$words[0], $words[1]],
            default => [implode(' ', array_slice($words, 0, -2)), implode(' ', array_slice($words, -2))],
        };
    }
};
