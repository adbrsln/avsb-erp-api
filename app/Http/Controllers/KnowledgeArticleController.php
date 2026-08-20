<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KnowledgeArticleController extends Controller
{
    use PaginatedResponse;

    private function isSuperAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = KnowledgeArticle::query();

        // Public browse: only published articles unless superadmin asks for all
        if (! empty($params['include_unpublished']) && $params['include_unpublished'] === 'true' && $this->isSuperAdmin($request)) {
            // superadmin management view — no is_published filter applied
        } else {
            $query->where('is_published', true);
        }

        if (! empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (! empty($params['module'])) {
            $query->where('module', $params['module']);
        }
        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('summary', 'like', "%{$s}%")
                    ->orWhere('body', 'like', "%{$s}%");
            });
        }

        if (! empty($params['all']) && $params['all'] === 'true') {
            return response()->json(['data' => $query->get()]);
        }

        return $this->paginate($query, $params, [
            'sortable' => ['sort_order', 'title', 'category', 'created_at', 'updated_at'],
            'default_sort' => 'sort_order',
        ]);
    }

    public function meta(Request $request): JsonResponse
    {
        return response()->json([
            'categories' => KnowledgeArticle::CATEGORIES,
            'modules' => KnowledgeArticle::query()
                ->whereNotNull('module')
                ->where('module', '!=', '')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $article = KnowledgeArticle::findOrFail($id);
        if (! $article->is_published && ! $this->isSuperAdmin($request)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($article);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $article = KnowledgeArticle::where('slug', $slug)->firstOrFail();
        if (! $article->is_published && ! $this->isSuperAdmin($request)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($article);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->all();

        if (empty($data['title'])) {
            return response()->json(['error' => 'Title is required'], 422);
        }
        if (empty($data['body'])) {
            return response()->json(['error' => 'Body is required'], 422);
        }
        if (! in_array($data['category'] ?? null, KnowledgeArticle::CATEGORIES)) {
            return response()->json(['error' => 'Invalid category'], 422);
        }

        $article = KnowledgeArticle::create(fillableData(new KnowledgeArticle, $data) + [
            'slug' => ! empty($data['slug']) ? $data['slug'] : KnowledgeArticle::generateSlug($data['title']),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($article, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $article = KnowledgeArticle::findOrFail($id);
        $data = $request->all();

        if (isset($data['title']) && empty($data['title'])) {
            return response()->json(['error' => 'Title is required'], 422);
        }
        if (isset($data['category']) && ! in_array($data['category'], KnowledgeArticle::CATEGORIES)) {
            return response()->json(['error' => 'Invalid category'], 422);
        }

        $update = fillableData($article, $data);
        if (isset($data['title']) && empty($data['slug'])) {
            $update['slug'] = KnowledgeArticle::generateSlug($data['title']);
        }
        $article->update($update);

        return response()->json($article);
    }

    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        KnowledgeArticle::findOrFail($id)->delete();

        return response()->noContent();
    }
}
