<?php

use App\Models\ActivityLog;
use App\Models\KnowledgeArticle;
use App\Models\User;
use Database\Seeders\KnowledgeArticleSeeder;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function makeKnowledgeUser(string $role = 'staff'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);

    return [
        'user' => $user,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

function makeKnowledgeArticle(array $overrides = []): KnowledgeArticle
{
    return KnowledgeArticle::create(array_merge([
        'title' => 'Test article',
        'slug' => 'test-article-'.rand(1000, 9999),
        'category' => 'how-to',
        'module' => 'punch',
        'summary' => 'A test article.',
        'body' => '<p>Body content</p>',
        'is_published' => true,
        'sort_order' => 0,
    ], $overrides));
}

beforeEach(function () {
    $this->super = User::where('email', 'superadmin@azamventures.com')->first();
    $this->superHeaders = ['Authorization' => 'Bearer '.$this->super->createToken('test')->plainTextToken];
});

describe('Knowledge Base reads', function () {

    it('lists published articles for any authenticated role', function () {
        makeKnowledgeArticle();

        foreach (['staff', 'pm', 'hr', 'finance', 'admin', 'super_admin'] as $role) {
            $ctx = makeKnowledgeUser($role);
            getJson('/api/v1/knowledge', $ctx['headers'])
                ->assertStatus(200)
                ->assertJsonStructure(['data', 'meta']);
        }
    });

    it('hides draft articles from the public list', function () {
        $published = makeKnowledgeArticle(['title' => 'Published one']);
        makeKnowledgeArticle(['title' => 'Draft one', 'is_published' => false]);

        getJson('/api/v1/knowledge', $this->superHeaders)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published one');
    });

    it('includes drafts when include_unpublished is set', function () {
        makeKnowledgeArticle(['is_published' => false]);

        getJson('/api/v1/knowledge?include_unpublished=true', $this->superHeaders)
            ->assertJsonCount(1, 'data');
    });

    it('filters by category and module and search', function () {
        makeKnowledgeArticle(['title' => 'Punch guide', 'category' => 'how-to', 'module' => 'punch']);
        makeKnowledgeArticle(['title' => 'SST explainer', 'category' => 'statutory', 'module' => 'invoices']);

        getJson('/api/v1/knowledge?category=statutory', $this->superHeaders)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'SST explainer');

        getJson('/api/v1/knowledge?module=punch', $this->superHeaders)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Punch guide');

        getJson('/api/v1/knowledge?search=Punch', $this->superHeaders)
            ->assertJsonCount(1, 'data');
    });

    it('returns a single article and by slug', function () {
        $article = makeKnowledgeArticle(['slug' => 'unique-slug-abc']);

        getJson('/api/v1/knowledge/'.$article->id, $this->superHeaders)
            ->assertStatus(200)
            ->assertJsonPath('id', $article->id);

        getJson('/api/v1/knowledge/slug/unique-slug-abc', $this->superHeaders)
            ->assertStatus(200)
            ->assertJsonPath('id', $article->id);
    });

    it('returns categories and modules from meta', function () {
        makeKnowledgeArticle(['module' => 'punch']);
        makeKnowledgeArticle(['module' => 'invoices']);

        getJson('/api/v1/knowledge/meta', $this->superHeaders)
            ->assertStatus(200)
            ->assertJsonPath('categories.0', 'how-to')
            ->assertJsonPath('modules.0', 'invoices');
    });

});

describe('Knowledge Base writes (superadmin only)', function () {

    it('blocks staff, pm, hr, finance, and admin from creating articles', function () {
        foreach (['staff', 'pm', 'hr', 'finance', 'admin'] as $role) {
            $ctx = makeKnowledgeUser($role);
            postJson('/api/v1/knowledge', [
                'title' => 'Blocked',
                'category' => 'how-to',
                'body' => '<p>x</p>',
            ], $ctx['headers'])->assertStatus(403);
        }
    });

    it('allows superadmin to create an article with auto slug', function () {
        postJson('/api/v1/knowledge', [
            'title' => 'My New Guide',
            'category' => 'how-to',
            'module' => 'punch',
            'body' => '<p>Content</p>',
            'is_published' => true,
        ], $this->superHeaders)
            ->assertStatus(201)
            ->assertJsonPath('slug', 'my-new-guide')
            ->assertJsonPath('created_by', $this->super->id);
    });

    it('validates required fields and category', function () {
        postJson('/api/v1/knowledge', ['category' => 'how-to'], $this->superHeaders)->assertStatus(422);
        postJson('/api/v1/knowledge', ['title' => 'No body', 'category' => 'how-to'], $this->superHeaders)->assertStatus(422);
        postJson('/api/v1/knowledge', ['title' => 'Bad cat', 'category' => 'bogus', 'body' => '<p>x</p>'], $this->superHeaders)->assertStatus(422);
    });

    it('allows superadmin to update and delete articles', function () {
        $article = makeKnowledgeArticle();

        putJson('/api/v1/knowledge/'.$article->id, ['title' => 'Renamed', 'is_published' => false], $this->superHeaders)
            ->assertStatus(200)
            ->assertJsonPath('title', 'Renamed')
            ->assertJsonPath('is_published', false);

        deleteJson('/api/v1/knowledge/'.$article->id, [], $this->superHeaders)
            ->assertStatus(204);

        expect(KnowledgeArticle::find($article->id))->toBeNull();
    });

    it('blocks non-superadmin from updating and deleting', function () {
        $article = makeKnowledgeArticle();
        $admin = makeKnowledgeUser('admin');

        putJson('/api/v1/knowledge/'.$article->id, ['title' => 'Nope'], $admin['headers'])->assertStatus(403);
        deleteJson('/api/v1/knowledge/'.$article->id, [], $admin['headers'])->assertStatus(403);
    });

    it('audits article writes', function () {
        $article = makeKnowledgeArticle();

        putJson('/api/v1/knowledge/'.$article->id, ['title' => 'Audited'], $this->superHeaders)->assertStatus(200);

        $log = ActivityLog::where('subject_type', KnowledgeArticle::class)
            ->where('subject_id', (string) $article->id)
            ->orderByDesc('id')
            ->first();

        expect($log)->not->toBeNull();
    });

});

describe('Knowledge Article Seeder', function () {

    it('seeds only valid, published articles', function () {
        (new KnowledgeArticleSeeder)->run();

        $articles = KnowledgeArticle::all();

        expect($articles->count())->toBeGreaterThan(10);

        foreach ($articles as $a) {
            expect($a->title)->not->toBeEmpty()
                ->and($a->body)->not->toBeEmpty()
                ->and($a->category)->toBeIn(KnowledgeArticle::CATEGORIES)
                ->and($a->slug)->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
        }
    });

    it('is idempotent', function () {
        (new KnowledgeArticleSeeder)->run();
        $first = KnowledgeArticle::count();
        (new KnowledgeArticleSeeder)->run();
        expect(KnowledgeArticle::count())->toBe($first);
    });

});
