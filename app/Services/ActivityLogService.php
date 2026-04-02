<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log a user action.
     *
     * @param string $actionType Type of action (e.g., 'create', 'update', 'delete', 'apply_internship')
     * @param string $description Human-readable description
     * @param Model|null $model Related model instance
     * @param array $properties Additional properties to store
     * @param string $status Action status (success, failed, error)
     * @param string|null $errorMessage Error message if action failed
     * @param Request|null $request Current HTTP request
     * @return ActivityLog
     */
    public function log(
        string $actionType,
        string $description,
        ?Model $model = null,
        array $properties = [],
        string $status = 'success',
        ?string $errorMessage = null,
        ?Request $request = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action_type' => $actionType,
            'description' => $description,
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->id,
            'properties' => $properties,
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }

    /**
     * Log a successful action.
     */
    public function logSuccess(
        string $actionType,
        string $description,
        ?Model $model = null,
        array $properties = [],
        ?Request $request = null
    ): ActivityLog {
        return $this->log($actionType, $description, $model, $properties, 'success', null, $request);
    }

    /**
     * Log a failed action.
     */
    public function logFailed(
        string $actionType,
        string $description,
        ?Model $model = null,
        array $properties = [],
        ?string $errorMessage = null,
        ?Request $request = null
    ): ActivityLog {
        return $this->log($actionType, $description, $model, $properties, 'failed', $errorMessage, $request);
    }

    /**
     * Log a system action (no user).
     */
    public function logSystem(
        string $actionType,
        string $description,
        ?Model $model = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => null,
            'action_type' => $actionType,
            'description' => $description,
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->id,
            'properties' => $properties,
            'status' => 'success',
        ]);
    }

    /**
     * Get activity logs for a user.
     *
     * @param int $userId User ID
     * @param int $days Number of days to look back
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserActivity(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::forUser($userId)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity logs for a model.
     *
     * @param Model $model Model instance
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModelActivity(Model $model): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('model_type', $model->getMorphClass())
            ->where('model_id', $model->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
