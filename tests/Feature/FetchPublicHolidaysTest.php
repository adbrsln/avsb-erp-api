<?php

use App\Models\PublicHoliday;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

function klHolidayPage(int $year): string
{
    $anchor = '<h2 id="'.$year.'-public-holidays">'.$year.' Public Holidays</h2>';
    $rows = [
        ['1 Jan', 'Thu', 'New Year\'s Day'],
        ['1 Feb', 'Sun', 'Thaipusam'],
        ['2 Feb', 'Mon', 'Thaipusam Holiday'],
        ['17 Feb', 'Tue', 'Chinese New Year'],
    ];

    $trs = '';
    foreach ($rows as $i => [$date, $day, $name]) {
        $class = $i % 2 === 0 ? 'even' : 'odd';
        $trs .= "<tr class=\"{$class}  row-linked\" data-rowlink=\"https://publicholidays.com.my/test/\"><td>{$date}</td><td>{$day}</td><td><a href=\"https://publicholidays.com.my/test/\" class=\"summary url\">{$name}</a> </td></tr>";
    }

    return '<html><body>'
        .$anchor
        .'<table class="publicholidays phgtable "><thead><tr><th>Date</th><th>Day</th><th>Holiday</th></tr></thead><tbody>'
        .$trs
        .'</tbody></table>'
        .'</body></html>';
}

describe('holidays:fetch', function () {

    it('scrapes and inserts public holidays for the given year', function () {
        Http::fake([
            'publicholidays.com.my/*' => Http::response(klHolidayPage(2026), 200),
        ]);

        artisan('holidays:fetch', ['--year' => 2026])
            ->assertSuccessful();

        $holidays = PublicHoliday::where('year', 2026)->where('is_recurring', false)->get();
        expect($holidays)->toHaveCount(4)
            ->and(PublicHoliday::whereDate('date', '2026-01-01')->first()->name)->toBe('New Year\'s Day')
            ->and(PublicHoliday::whereDate('date', '2026-02-02')->first()->name)->toBe('Thaipusam Holiday');
    });

    it('defaults to the current year when no --year is given', function () {
        Http::fake([
            'publicholidays.com.my/*' => Http::response(klHolidayPage((int) date('Y')), 200),
        ]);

        artisan('holidays:fetch')
            ->assertSuccessful();

        expect(PublicHoliday::where('year', (int) date('Y'))->where('is_recurring', false)->count())->toBe(4);
    });

    it('is idempotent on repeated runs', function () {
        Http::fake([
            'publicholidays.com.my/*' => Http::response(klHolidayPage(2026), 200),
        ]);

        artisan('holidays:fetch', ['--year' => 2026])->assertSuccessful();
        artisan('holidays:fetch', ['--year' => 2026])->assertSuccessful();

        expect(PublicHoliday::where('year', 2026)->where('is_recurring', false)->count())->toBe(4);
    });

    it('updates names of existing holidays instead of duplicating', function () {
        PublicHoliday::create([
            'name' => 'Old Name',
            'date' => '2026-01-01',
            'year' => 2026,
            'is_recurring' => false,
        ]);

        Http::fake([
            'publicholidays.com.my/*' => Http::response(klHolidayPage(2026), 200),
        ]);

        artisan('holidays:fetch', ['--year' => 2026])->assertSuccessful();

        expect(PublicHoliday::where('year', 2026)->where('is_recurring', false)->count())->toBe(4)
            ->and(PublicHoliday::whereDate('date', '2026-01-01')->first()->name)->toBe('New Year\'s Day');
    });

    it('merges holidays sharing the same date', function () {
        $html = '<html><body><h2 id="2026-public-holidays">2026 Public Holidays</h2>'
            .'<table class="publicholidays phgtable "><thead><tr><th>Date</th><th>Day</th><th>Holiday</th></tr></thead><tbody>'
            .'<tr class="odd  row-linked" data-rowlink="https://publicholidays.com.my/thaipusam/"><td>1 Feb</td><td>Sun</td><td><a href="https://publicholidays.com.my/thaipusam/" class="summary url">Thaipusam</a> </td></tr>'
            .'<tr class="even  row-linked" data-rowlink="https://publicholidays.com.my/federal-territory-day/"><td>1 Feb</td><td>Sun</td><td><a href="https://publicholidays.com.my/federal-territory-day/" class="summary url">Federal Territory Day</a> </td></tr>'
            .'</tbody></table></body></html>';

        Http::fake([
            'publicholidays.com.my/*' => Http::response($html, 200),
        ]);

        artisan('holidays:fetch', ['--year' => 2026])->assertSuccessful();

        expect(PublicHoliday::where('year', 2026)->where('is_recurring', false)->count())->toBe(1)
            ->and(PublicHoliday::whereDate('date', '2026-02-01')->first()->name)->toBe('Thaipusam & Federal Territory Day');
    });

    it('returns success gracefully when the year table is missing', function () {
        Http::fake([
            'publicholidays.com.my/*' => Http::response(klHolidayPage(2026), 200),
        ]);

        artisan('holidays:fetch', ['--year' => 2029])
            ->assertSuccessful();

        expect(PublicHoliday::where('year', 2029)->count())->toBe(0);
    });

    it('returns failure when the page cannot be fetched', function () {
        Http::fake([
            'publicholidays.com.my/*' => Http::response('Forbidden', 403),
        ]);

        artisan('holidays:fetch', ['--year' => 2026])
            ->assertFailed();
    });

});
