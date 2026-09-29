<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        $documents = Document::query()
            ->select(['id', 'user_id', 'document_namespace_id', 'path', 'title', 'created_at'])
            ->where('visibility', DocumentVisibility::Public)
            ->with(['user:id,name', 'namespace:id,slug'])
            ->latest()
            ->get();

        return Inertia::render('dashboard', [
            'documents' => $documents,
        ]);
    }
}
