<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait Tenantable
{
    /**
     * Boot the tenantable trait.
     */
    protected static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (Auth::check() && !isset($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
            // ⚠️ NOT: Artisan komutlarında, Job'larda ve Seeder'larda Auth::check() false döner.
            // Bu bağlamlarda company_id null kalabilir veya DB hatası verebilir.
            // Çözüm: İlgili Job/Command içinde model'e kaydetmeden önce
            //   $model->company_id = $companyId;
            // satırını açıkça ekleyin.
        });
    }

    /**
     * Scope a query to only include specific company data.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Global tenant scope'u bypass et.
     * Kullanım: Super Admin tüm şirket verilerini görmesi gerektiğinde.
     *
     * Örnek:
     *   $allData = Model::ignoreTenant()->get();
     *   $companyData = Model::ignoreTenant()->where('company_id', $id)->get();
     *
     * ⚠️ Bu scope'u sadece Super Admin yetkisi kontrolü yapıldıktan sonra kullanın!
     */
    public function scopeIgnoreTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
