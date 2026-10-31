<?php

namespace LunarCms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Lunar\Core\Contracts\Actions\Media\AddsMedia;
use LunarCms\Http\Requests\PageMediaStoreRequest;
use LunarCms\Models\Page;

class PageMediaController
{
    /**
     * Attach an uploaded image to the page through Lunar's media action, so it
     * lands in the same collection, with the same conversions, as product
     * images. The block editor stores the returned id in the block's data.
     */
    public function store(PageMediaStoreRequest $request, Page $page, AddsMedia $addsMedia): JsonResponse
    {
        $media = $addsMedia->execute($page, $request->file('file'));

        return response()->json([
            'id' => $media->id,
            'url' => $media->getAvailableUrl(['small']),
            'original_url' => $media->getUrl(),
            'name' => $media->file_name,
        ], 201);
    }
}
