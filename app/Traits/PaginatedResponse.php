<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

trait PaginatedResponse
{
    protected function paginate(Builder $query, array $params = [], array $extra = []): JsonResponse
    {
        $page = max(1, intval($params['page'] ?? 1));
        $perPage = min(100, max(1, intval($params['per_page'] ?? 15)));

        $sortable = $extra['sortable'] ?? ['created_at'];
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, $sortable)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $result = [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / max(1, $perPage)),
            ],
        ];

        foreach ($extra as $key => $val) {
            $result[$key] = $val;
        }

        return response()->json($result);
    }
}
