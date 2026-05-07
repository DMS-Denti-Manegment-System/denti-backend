<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class CompanyOwned implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected string $table,
        protected string $column = 'id'
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 🛡️ CLI veya Schedule ortamında auth()->user() null olabilir.
        if (! auth()->check()) {
            return;
        }

        $companyId = auth()->user()->company_id;

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where('company_id', $companyId)
            ->exists();

        if (! $exists) {
            $fail("Seçilen {$attribute} geçersiz veya yetkiniz yok.");
        }
    }
}
