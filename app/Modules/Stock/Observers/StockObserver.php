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
}
