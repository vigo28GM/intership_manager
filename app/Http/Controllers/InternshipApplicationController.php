<?php

namespace App\Http\Controllers;

use App\Actions\CreateInternshipApplication;
use App\Exceptions\InternshipApplicationException;
use App\Models\Application;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InternshipApplicationController extends Controller
{
    public function __construct(
        private CreateInternshipApplication $createApplication,
        private ActivityLogService $activityLog
    ) {}

    public function validateApplication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'group_id' => 'required|integer',
            'internship_id' => 'required|integer',
            'motivation_letter' => 'nullable|string|max:1000',
        ]);

        try {
            $application = $this->createApplication->execute([
                'user_id' => $validated['user_id'],
                'group_id' => $validated['group_id'],
                'internship_id' => $validated['internship_id'],
                'motivation_letter' => $validated['motivation_letter'] ?? null,
            ]);

            // Log successful application
            $this->activityLog->logSuccess(
                'apply_internship',
                'Lietotājs pieteicās praksei',
                $application,
                [
                    'user_id' => $validated['user_id'],
                    'internship_id' => $validated['internship_id'],
                    'group_id' => $validated['group_id'],
                ],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Application created successfully',
                'data' => $application,
            ], 201);
        } catch (InternshipApplicationException $e) {
            // Log failed application
            $this->activityLog->logFailed(
                'apply_internship',
                'Lietotājs mēģināja pieteikties praksei, bet notika kļūda',
                null,
                [
                    'user_id' => $validated['user_id'] ?? null,
                    'internship_id' => $validated['internship_id'] ?? null,
                    'error_code' => $e->getErrorCode(),
                ],
                $e->getMessage(),
                $request
            );

            return response()->json([
                'success' => false,
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ], $e->getHttpCode());
        } catch (ValidationException $e) {
            // Log validation error
            $this->activityLog->logFailed(
                'apply_internship',
                'Lietotājs nosūtīja nederīgus datus',
                null,
                [
                    'user_id' => $validated['user_id'] ?? null,
                    'internship_id' => $validated['internship_id'] ?? null,
                    'validation_errors' => $e->errors(),
                ],
                'Validation error',
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Call the database procedure to create an internship application.
     */
    public function validateApplicationWithProcedure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'internship_id' => 'required|integer',
            'motivation_letter' => 'nullable|string|max:1000',
        ]);

        try {
            $result = DB::select('CALL create_internship_application(?, ?, ?, @p_result)', [
                $validated['user_id'],
                $validated['internship_id'],
                $validated['motivation_letter'] ?? null,
            ]);

            $resultData = DB::select('SELECT @p_result AS result');
            $response = json_decode($resultData[0]->result, true);

            if ($response['success']) {
                // Log successful application from procedure
                $application = Application::find($response['data']['id'] ?? null);
                $this->activityLog->logSuccess(
                    'apply_internship_procedure',
                    'Lietotājs pieteicās praksei (izmantojot DB procedūru)',
                    $application,
                    [
                        'user_id' => $validated['user_id'],
                        'internship_id' => $validated['internship_id'],
                        'method' => 'database_procedure',
                    ],
                    $request
                );

                return response()->json($response, 201);
            }

            // Log failed application from procedure
            $this->activityLog->logFailed(
                'apply_internship_procedure',
                'Lietotājs mēģināja pieteikties praksei (DB procedūra), bet notika kļūda',
                null,
                [
                    'user_id' => $validated['user_id'],
                    'internship_id' => $validated['internship_id'],
                    'error_code' => $response['error_code'] ?? 'UNKNOWN_ERROR',
                ],
                $response['message'] ?? 'Unknown error',
                $request
            );

            $httpCode = $this->getHttpCodeForErrorCode($response['error_code'] ?? 'UNKNOWN_ERROR');
            return response()->json($response, $httpCode);
        } catch (\Exception $e) {
            // Log exception
            $this->activityLog->logFailed(
                'apply_internship_procedure',
                'Lietotājs mēģināja pieteikties praksei (DB procedūra), bet notika izņēmums',
                null,
                [
                    'user_id' => $validated['user_id'] ?? null,
                    'internship_id' => $validated['internship_id'] ?? null,
                ],
                $e->getMessage(),
                $request
            );

            return response()->json([
                'success' => false,
                'error_code' => 'DATABASE_ERROR',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map error codes to HTTP status codes.
     */
    private function getHttpCodeForErrorCode(string $errorCode): int
    {
        return match ($errorCode) {
            'USER_NOT_FOUND', 'INTERNSHIP_NOT_FOUND' => 404,
            'INTERNSHIP_NOT_VALID', 'GROUP_MISMATCH' => 400,
            'USER_NOT_ALLOWED' => 403,
            'APPLICATION_ALREADY_EXISTS' => 409,
            default => 500,
        };
    }
}
