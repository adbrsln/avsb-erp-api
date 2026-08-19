<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeArticle extends Model
{
    use Auditable, HasFactory;

    public const CATEGORIES = ['how-to', 'faq', 'statutory', 'reference'];

    protected $fillable = [
        'title', 'slug', 'category', 'module', 'tags', 'summary',
        'body', 'is_published', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
