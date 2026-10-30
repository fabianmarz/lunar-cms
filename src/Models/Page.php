<?php

namespace LunarCms\Models;

use Illuminate\Database\Eloquent\Model;
use LunarCms\PageStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'status',
        'published_at',
        'blocks',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'blocks' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Media rows/files don't cascade on their own.
        static::deleting(fn (Page $page) => $page->clearMediaCollection('blocks'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('blocks');
    }

    public function publish(): void
    {
        $this->status = PageStatus::Published;
        $this->published_at ??= now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = PageStatus::Draft;
        $this->save();
    }
}
