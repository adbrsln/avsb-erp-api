<?php

namespace App;

use App\Seeds\AccountingSeeder;
use App\Seeds\AssetSeeder;
use App\Seeds\AttendanceSeeder;
use App\Seeds\BulkAccountingSeeder;
use App\Seeds\BulkActivitySeeder;
use App\Seeds\BulkAssetSeeder;
use App\Seeds\BulkClientSeeder;
use App\Seeds\BulkDocSeeder;
use App\Seeds\BulkFinanceSeeder;
use App\Seeds\BulkHrSeeder;
use App\Seeds\BulkInventorySeeder;
use App\Seeds\BulkNotificationSeeder;
use App\Seeds\BulkPayrollSeeder;
use App\Seeds\BulkProjectSeeder;
use App\Seeds\BulkPurchasingSeeder;
use App\Seeds\BulkStaffSeeder;
use App\Seeds\BulkSubconSeeder;
use App\Seeds\BulkVendorSeeder;
use App\Seeds\ChecklistSeeder;
use App\Seeds\ClientSeeder;
use App\Seeds\CompanySettingSeeder;
use App\Seeds\ExpenseClaimSeeder;
use App\Seeds\ExtraProjectSeeder;
use App\Seeds\FinanceSeeder;
use App\Seeds\InventoryItemSeeder;
use App\Seeds\InvoicePaymentSeeder;
use App\Seeds\LeaveBalanceSeeder;
use App\Seeds\LeaveGroupSeeder;
use App\Seeds\MaterialUsageSeeder;
use App\Seeds\MillPaveSeeder;
use App\Seeds\NotificationPrefSeeder;
use App\Seeds\NotificationQueueSeeder;
use App\Seeds\NotificationTemplateSeeder;
use App\Seeds\NumberingSequenceSeeder;
use App\Seeds\PayrollDataSeeder;
use App\Seeds\PhaseTemplateSeeder;
use App\Seeds\PivotSeeder;
use App\Seeds\ProjectClaimSeeder;
use App\Seeds\ProjectDocSeeder;
use App\Seeds\ProjectSeeder;
use App\Seeds\ProjectTypeSeeder;
use App\Seeds\PurchasingSeeder;
use App\Seeds\RoadMarkingSeeder;
use App\Seeds\SelfBilledSeeder;
use App\Seeds\ServiceCatalogSeeder;
use App\Seeds\ServiceTypeSeeder;
use App\Seeds\Socso24hTierSeeder;
use App\Seeds\SocsoEisSeeder;
use App\Seeds\StaffLeaveSeeder;
use App\Seeds\StaffSeeder;
use App\Seeds\SubconDataSeeder;
use App\Seeds\SubcontractorSeeder;
use App\Seeds\TaxCodeSeeder;
use App\Seeds\TimecardSeeder;
use App\Seeds\TransactionSeeder;

class DatabaseSeeder
{
    protected array $initialSeeders = [];

    protected array $dummySeeders = [];

    protected array $bulkSeeders = [];

    protected array $skipWhenBulk = [];

    public function __construct()
    {
        $this->initialSeeders = [
            100 => CompanySettingSeeder::class,
            110 => ServiceTypeSeeder::class,
            120 => NumberingSequenceSeeder::class,
            130 => PhaseTemplateSeeder::class,
            140 => ProjectTypeSeeder::class,
            150 => NotificationTemplateSeeder::class,
            160 => TaxCodeSeeder::class,
            170 => StaffSeeder::class,
            180 => LeaveGroupSeeder::class,
            190 => LeaveBalanceSeeder::class,
            200 => NotificationPrefSeeder::class,
            235 => Socso24hTierSeeder::class,
            240 => InventoryItemSeeder::class,
            245 => ServiceCatalogSeeder::class,
            250 => SocsoEisSeeder::class,
            270 => ClientSeeder::class,
            280 => MillPaveSeeder::class,
            290 => RoadMarkingSeeder::class,
        ];

        $this->dummySeeders = [
            300 => ProjectSeeder::class,
            330 => ExtraProjectSeeder::class,
            340 => PivotSeeder::class,
            350 => ProjectDocSeeder::class,
            360 => ProjectClaimSeeder::class,
            370 => ChecklistSeeder::class,
            380 => FinanceSeeder::class,
            390 => InvoicePaymentSeeder::class,
            400 => PurchasingSeeder::class,
            410 => MaterialUsageSeeder::class,
            420 => AssetSeeder::class,
            430 => AttendanceSeeder::class,
            440 => PayrollDataSeeder::class,
            450 => StaffLeaveSeeder::class,
            460 => ExpenseClaimSeeder::class,
            470 => TimecardSeeder::class,
            480 => TransactionSeeder::class,
            490 => AccountingSeeder::class,
            500 => SubconDataSeeder::class,
            510 => SelfBilledSeeder::class,
            520 => NotificationQueueSeeder::class,
        ];

        $this->bulkSeeders = [
            300 => BulkStaffSeeder::class,
            310 => BulkClientSeeder::class,
            320 => BulkVendorSeeder::class,
            330 => BulkInventorySeeder::class,
            340 => BulkProjectSeeder::class,
            350 => BulkFinanceSeeder::class,
            360 => BulkPurchasingSeeder::class,
            370 => BulkAssetSeeder::class,
            380 => BulkHrSeeder::class,
            390 => BulkPayrollSeeder::class,
            400 => BulkAccountingSeeder::class,
            410 => BulkSubconSeeder::class,
            420 => BulkDocSeeder::class,
            430 => BulkActivitySeeder::class,
            440 => BulkNotificationSeeder::class,
        ];

        $this->skipWhenBulk = [
            StaffSeeder::class,
            Socso24hTierSeeder::class,
            InventoryItemSeeder::class,
            ServiceCatalogSeeder::class,
            LeaveBalanceSeeder::class,
            NotificationPrefSeeder::class,
            SubcontractorSeeder::class,
            MillPaveSeeder::class,
            RoadMarkingSeeder::class,
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
