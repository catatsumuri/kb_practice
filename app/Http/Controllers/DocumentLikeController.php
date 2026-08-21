<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentLikeController extends Controller
{
    /**
     * Like the specified document.
     */
    public function store(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('like', $document);

        $document->likes()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return back();
    }

    /**
     * Remove the authenticated user's like from the specified document.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('like', $document);

        $document->likes()
            ->where('user_id', $request->user()->id)
            ->delete();

        return back();
    }
}
