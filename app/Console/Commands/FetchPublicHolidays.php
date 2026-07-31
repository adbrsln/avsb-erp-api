<?php

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchPublicHolidays extends Command
{
    protected $signature = 'holidays:fetch
        {--year= : Year to fetch (default: current year)}
        {--state=kuala-lumpur : State/territory slug used by publicholidays.com.my}';

    protected $description = 'Scrape Malaysian public holidays from publicholidays.com.my for the given year';

    private const BASE_URL = 'https://publicholidays.com.my';

    public function handle(): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : (int) now()->year;
        $state = strtolower($this->option('state'));

        $this->info("Fetching {$state} public holidays for {$year}...");

        $response = Http::withHeaders([
            'User-Agent' => 'AVSB-ERP/1.0 (public holiday import; contact: admin@azamventures.com)',
            'Accept' => 'text/html',
        ])->timeout(30)->get(self::BASE_URL.'/'.$state.'/');

        if (! $response->ok()) {
            $this->error('Failed to fetch page: HTTP '.$response->status());

            return Command::FAILURE;
        }

        $rows = $this->parseTable($response->body(), $year);

        if ($rows === null) {
            $this->warn("No public holiday table found for {$year} on the page. The source may not have published this year yet.");

            return Command::SUCCESS;
        }

        if (count($rows) === 0) {
            $this->warn("Table for {$year} exists but contains no rows.");

            return Command::SUCCESS;
        }

        // Merge holidays sharing the same date (e.g. Thaipusam + Federal Territory Day on 1 Feb)
        $merged = [];
        foreach ($rows as $row) {
            if (isset($merged[$row['date']])) {
                $merged[$row['date']]['name'] .= ' & '.$row['name'];
            } else {
                $merged[$row['date']] = $row;
            }
        }

        $inserted = 0;
        $updated = 0;

        foreach ($merged as $row) {
            $existing = PublicHoliday::whereDate('date', $row['date'])
                ->where('year', $year)
                ->where('is_recurring', false)
                ->first();

            if ($existing) {
                $existing->update(['name' => $row['name']]);
                $updated++;
            } else {
                PublicHoliday::create([
                    'name' => $row['name'],
                    'date' => $row['date'],
                    'year' => $year,
                    'is_recurring' => false,
                ]);
                $inserted++;
            }
        }

        $this->info("Done: {$inserted} inserted, {$updated} updated.");

        return Command::SUCCESS;
    }

    /**
     * Extracts holiday rows for the target year from the page HTML.
     *
     * @return array<int, array{date: string, name: string}>|null
     */
    private function parseTable(string $html, int $year): ?array
    {
        $anchor = 'id="'.$year.'-public-holidays"';
        $anchorPos = strpos($html, $anchor);
        if ($anchorPos === false) {
            return null;
        }

        $tableStart = strpos($html, '<table', $anchorPos);
        if ($tableStart === false) {
            return null;
        }

        $tableEnd = strpos($html, '</table>', $tableStart);
        if ($tableEnd === false) {
            return null;
        }

        $table = substr($html, $tableStart, $tableEnd - $tableStart);

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $table, $rowMatches);

        $rows = [];
        foreach ($rowMatches[1] as $rowHtml) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $rowHtml, $cellMatches);
            $cells = array_map(fn ($c) => trim(strip_tags($c)), $cellMatches[1]);

            if (count($cells) < 3) {
                continue;
            }

            $dateText = $cells[0];
            $name = $cells[2];
            if ($dateText === '' || $name === '' || strtolower($dateText) === 'date') {
                continue;
            }

            $parsed = $this->parseDate($dateText, $year);
            if (! $parsed) {
                $this->warn("Skipping unparsable date '{$dateText}' for holiday '{$name}'.");

                continue;
            }

            $rows[] = ['date' => $parsed, 'name' => $name];
        }

        return $rows;
    }

    private function parseDate(string $dateText, int $year): ?string
    {
        $parts = explode(' ', trim($dateText));
        if (count($parts) < 2) {
            return null;
        }

        $day = (int) $parts[0];
        $month = $this->monthNumber($parts[1]);

        if ($month === null || $day < 1 || $day > 31) {
            return null;
        }

        try {
            return Carbon::create($year, $month, $day)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function monthNumber(string $month): ?int
    {
        $months = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
            'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
            'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        return $months[strtolower(substr(trim($month), 0, 3))] ?? null;
    }
}
