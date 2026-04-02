<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * Add a comment to an Application or Evaluation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|in:App\Models\Application,App\Models\Evaluation',
            'commentable_id' => 'required|integer',
            'user_id' => 'nullable|integer|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);

        // Verify the model exists
        $model = $validated['commentable_type']::find($validated['commentable_id']);
        
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Model not found',
            ], 404);
        }

        try {
            $comment = Comment::create([
                'commentable_type' => $validated['commentable_type'],
                'commentable_id' => $validated['commentable_id'],
                'user_id' => $validated['user_id'] ?? null,
                'content' => $validated['content'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all comments for a specific model.
     */
    public function index(Request $request, string $type, int $id): JsonResponse
    {
        $modelClass = match ($type) {
            'applications' => 'App\Models\Application',
            'evaluations' => 'App\Models\Evaluation',
            default => throw ValidationException::withMessages([
                'type' => 'Invalid model type. Must be "applications" or "evaluations".',
            ]),
        };

        $comments = Comment::where('commentable_type', $modelClass)
            ->where('commentable_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }
}
