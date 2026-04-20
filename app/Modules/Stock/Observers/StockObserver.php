<?php

namespace App\Modules\Stock\Observers;

use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Services\ClinicService;
use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;

class StockObserver
{
    protected $stockRepository;
    protected $clinicService;

    public function __construct(
        StockRepositoryInterface $stockRepository,
        ClinicService $clinicService
    ) {
        $this->stockRepository = $stockRepository;
        $this->clinicService = $clinicService;
    }

    /**
     * Handle the Stock "creating" event.
     */
    public function creating(Stock $stock): void
    {
        if (!$stock->code) {
            $stock->code = $this->generateStockCode($stock->clinic_id);
        }
    }

    /**
     * Generate unique stock code based on clinic.
     */
    private function generateStockCode(int $clinicId): string
    {
        $clinic = $this->clinicService->getClinicById($clinicId);
        $prefix = $clinic ? $clinic->code : 'STK';
        
        $sequence = $this->stockRepository->getNextSequenceNumber($clinicId);

        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
