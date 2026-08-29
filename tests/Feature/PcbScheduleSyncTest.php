<?php

use App\Models\PcbRelief;
use App\Models\PcbTaxBracket;
use App\Services\Payroll\PcbScheduleSyncService;

use function Pest\Laravel\artisan;

it('syncs the configured schedule for a year', function () {
    (new PcbScheduleSyncService)->sync(2026);

    expect(PcbTaxBracket::where('year', 2026)->count())->toBe(14);
    expect(PcbRelief::where('year', 2026)->count())->toBe(11);
});

it('restores config values on re-sync (updateOrCreate)', function () {
    (new PcbScheduleSyncService)->sync(2026);
    PcbTaxBracket::where('year', 2026)->where('worker_category', 'pemastautin')->where('min_chargeable', 0)->update(['rate' => 5]);

    (new PcbScheduleSyncService)->sync(2026);

    expect(PcbTaxBracket::where('year', 2026)->where('worker_category', 'pemastautin')->where('min_chargeable', 0)->value('rate'))->toBe(0.0);
});

it('throws for an unconfigured year', function () {
    (new PcbScheduleSyncService)->sync(2030);
})->throws(RuntimeException::class);

it('dry-run writes nothing', function () {
    $before = PcbTaxBracket::count();

    (new PcbScheduleSyncService)->sync(2026, true);

    expect(PcbTaxBracket::count())->toBe($before);
});

it('pcb:sync-schedule command syncs the requested year', function () {
    PcbTaxBracket::where('year', 2026)->delete();

    artisan('pcb:sync-schedule', ['--year' => '2026'])->assertSuccessful();

    expect(PcbTaxBracket::where('year', 2026)->count())->toBe(14);
});
