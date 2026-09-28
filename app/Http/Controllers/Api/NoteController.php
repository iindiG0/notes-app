<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * JSON API for notes:
 *   GET    /api/notes        list your notes
 *   POST   /api/notes        create
 *   GET    /api/notes/{id}   show one
 *   PUT    /api/notes/{id}   update
 *   DELETE /api/notes/{id}   delete
 */
class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->notes()->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $note = $request->user()->notes()->create($this->validated($request));

        return response()->json($note, 201);
    }

    public function show(Request $request, int $note): JsonResponse
    {
        return response()->json($request->user()->notes()->findOrFail($note));
    }

    public function update(Request $request, int $note): JsonResponse
    {
        $note = $request->user()->notes()->findOrFail($note);
        $note->update($this->validated($request));

        return response()->json($note);
    }

    public function destroy(Request $request, int $note): Response
    {
        $request->user()->notes()->findOrFail($note)->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
