<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Core\Models\Language;
use LunarCms\Models\Page;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Title and slug become `{language code: value}` maps, the same shape
     * Lunar uses for its translated attributes. Existing values are filed
     * under the default language. Block media moves into Lunar's image
     * collection so it gets the same conversions as product images.
     */
    public function up(): void
    {
        $code = Language::query()->where('default', true)->value('code') ?? config('app.locale');

        Schema::table('pages', function (Blueprint $table) {
            $table->json('title_translations')->nullable();
            $table->json('slug_translations')->nullable();
        });

        DB::table('pages')->get(['id', 'title', 'slug', 'blocks'])->each(function (object $page) use ($code): void {
            DB::table('pages')->where('id', $page->id)->update([
                'title_translations' => json_encode([$code => $page->title]),
                'slug_translations' => json_encode([$code => $page->slug]),
                'blocks' => json_encode($this->translateBlocks(json_decode($page->blocks ?? '[]', true), $code)),
            ]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['title', 'slug']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('title_translations', 'title');
            $table->renameColumn('slug_translations', 'slug');
        });

        DB::table('media')
            ->where('model_type', (new Page)->getMorphClass())
            ->where('collection_name', 'blocks')
            ->update(['collection_name' => config('lunar.media.collection')]);
    }

    /**
     * Wrap the single-language block fields in language maps. Rich text saved
     * as an editor JSON document can't be converted to HTML here and is dropped.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function translateBlocks(array $blocks, string $code): array
    {
        return array_map(function (array $block) use ($code): array {
            $data = $block['data'] ?? [];

            foreach (['alt', 'label'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = $data[$field] === '' ? [] : [$code => $data[$field]];
                }
            }

            if (($block['type'] ?? null) === 'rich_text') {
                $data['content'] = is_array($data['content'] ?? null) && array_is_list($data['content'] ?? []) === false && isset($data['content']['type'])
                    ? []
                    : ($data['content'] ?? []);
            }

            $block['data'] = $data;

            return $block;
        }, $blocks);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_single')->nullable();
            $table->string('slug_single')->nullable();
        });

        DB::table('pages')->get(['id', 'title', 'slug'])->each(function (object $page): void {
            DB::table('pages')->where('id', $page->id)->update([
                'title_single' => collect(json_decode($page->title, true))->first(),
                'slug_single' => collect(json_decode($page->slug, true))->first() ?? "page-{$page->id}",
            ]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['title', 'slug']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('title_single', 'title');
            $table->renameColumn('slug_single', 'slug');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->unique('slug');
        });

        DB::table('media')
            ->where('model_type', (new Page)->getMorphClass())
            ->where('collection_name', config('lunar.media.collection'))
            ->update(['collection_name' => 'blocks']);
    }
};
