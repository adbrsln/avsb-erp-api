<?php

namespace App;

class DatabaseSeeder
{
    protected array $initialSeeders = [];
    protected array $dummySeeders = [];
    protected array $bulkSeeders = [];
    protected array $skipWhenBulk = [];

    public function __construct()
    {
        $this->initialSeeders = [
            100 => \App\Seeds\CompanySettingSeeder::class,
            110 => \App\Seeds\ServiceTypeSeeder::class,
            120 => \App\Seeds\NumberingSequenceSeeder::class,
            130 => \App\Seeds\PhaseTemplateSeeder::class,
            140 => \App\Seeds\ProjectTypeSeeder::class,
            150 => \App\Seeds\NotificationTemplateSeeder::class,
            160 => \App\Seeds\TaxCodeSeeder::class,
            170 => \App\Seeds\StaffSeeder::class,
            180 => \App\Seeds\LeaveGroupSeeder::class,
            190 => \App\Seeds\LeaveBalanceSeeder::class,
             200 => \App\Seeds\NotificationPrefSeeder::class,
             235 => \App\Seeds\Socso24hTierSeeder::class,
              240 => \App\Seeds\InventoryItemSeeder::class,
             245 => \App\Seeds\ServiceCatalogSeeder::class,
             250 => \App\Seeds\SocsoEisSeeder::class,
            270 => \App\Seeds\ClientSeeder::class,
            280 => \App\Seeds\MillPaveSeeder::class,
            290 => \App\Seeds\RoadMarkingSeeder::class,
        ];

        $this->dummySeeders = [
            300 => \App\Seeds\ProjectSeeder::class,
            330 => \App\Seeds\ExtraProjectSeeder::class,
            340 => \App\Seeds\PivotSeeder::class,
            350 => \App\Seeds\ProjectDocSeeder::class,
            360 => \App\Seeds\ProjectClaimSeeder::class,
            370 => \App\Seeds\ChecklistSeeder::class,
            380 => \App\Seeds\FinanceSeeder::class,
            390 => \App\Seeds\InvoicePaymentSeeder::class,
            400 => \App\Seeds\PurchasingSeeder::class,
            410 => \App\Seeds\MaterialUsageSeeder::class,
            420 => \App\Seeds\AssetSeeder::class,
            430 => \App\Seeds\AttendanceSeeder::class,
            440 => \App\Seeds\PayrollDataSeeder::class,
            450 => \App\Seeds\StaffLeaveSeeder::class,
            460 => \App\Seeds\ExpenseClaimSeeder::class,
            470 => \App\Seeds\TimecardSeeder::class,
            480 => \App\Seeds\TransactionSeeder::class,
            490 => \App\Seeds\AccountingSeeder::class,
            500 => \App\Seeds\SubconDataSeeder::class,
            510 => \App\Seeds\SelfBilledSeeder::class,
            520 => \App\Seeds\NotificationQueueSeeder::class,
        ];

        $this->bulkSeeders = [
            300 => \App\Seeds\BulkStaffSeeder::class,
            310 => \App\Seeds\BulkClientSeeder::class,
            320 => \App\Seeds\BulkVendorSeeder::class,
            330 => \App\Seeds\BulkInventorySeeder::class,
            340 => \App\Seeds\BulkProjectSeeder::class,
            350 => \App\Seeds\BulkFinanceSeeder::class,
            360 => \App\Seeds\BulkPurchasingSeeder::class,
            370 => \App\Seeds\BulkAssetSeeder::class,
            380 => \App\Seeds\BulkHrSeeder::class,
            390 => \App\Seeds\BulkPayrollSeeder::class,
            400 => \App\Seeds\BulkAccountingSeeder::class,
            410 => \App\Seeds\BulkSubconSeeder::class,
            420 => \App\Seeds\BulkDocSeeder::class,
            430 => \App\Seeds\BulkActivitySeeder::class,
            440 => \App\Seeds\BulkNotificationSeeder::class,
        ];

        $this->skipWhenBulk = [
            \App\Seeds\StaffSeeder::class,
            \App\Seeds\Socso24hTierSeeder::class,
            \App\Seeds\InventoryItemSeeder::class,
            \App\Seeds\ServiceCatalogSeeder::class,
            \App\Seeds\LeaveBalanceSeeder::class,
            \App\Seeds\NotificationPrefSeeder::class,
            \App\Seeds\SubcontractorSeeder::class,
            \App\Seeds\MillPaveSeeder::class,
            \App\Seeds\RoadMarkingSeeder::class,
        ];
    }

    public function run(bool $dummy = false, bool $bulk = false): void
    {
        foreach ($this->initialSeeders as $priority => $seederClass) {
            if ($bulk && in_array($seederClass, $this->skipWhenBulk)) {
                continue;
            }
            $this->execute($seederClass);
        }

        if ($bulk) {
            echo "  --- Bulk seeders (~150 records each) ---\n";
            ksort($this->bulkSeeders);
            foreach ($this->bulkSeeders as $priority => $seederClass) {
                $this->execute($seederClass);
            }
        } elseif ($dummy) {
            ksort($this->dummySeeders);
            foreach ($this->dummySeeders as $priority => $seederClass) {
                $this->execute($seederClass);
            }
        }
    }

    protected function execute(string $seederClass): void
    {
        $parts = explode('\\', $seederClass);
        $name = end($parts);
        echo "  {$name}...\n";
        (new $seederClass)->run();
    }
}
