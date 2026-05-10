<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    if ($this->isEncrypted($user->two_factor_secret)) {
                        continue;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'two_factor_secret' => Crypt::encryptString($user->two_factor_secret),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    if (! $this->isEncrypted($user->two_factor_secret)) {
                        continue;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'two_factor_secret' => Crypt::decryptString($user->two_factor_secret),
                    ]);
                }
            });
    }

    private function isEncrypted(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
