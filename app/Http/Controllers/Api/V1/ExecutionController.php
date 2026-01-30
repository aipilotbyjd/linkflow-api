<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExecutionMode;
use App\Enums\ExecutionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExecutionLogResource;
use App\Http\Resources\Api\V1\ExecutionNodeResource;
use App\Http\Resources\Api\V1\ExecutionResource;
use App\Models\Execution;
use App\Models\Workflow;
use App\Models\Workspace;
use App\Services\WorkspacePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExecutionController extends Controller
{
    public function __construct(
        private WorkspacePermissionService $permissionService
    ) {}

    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');

        $query = $workspace->executions()
            ->with(['workflow', 'triggeredBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('workflow_id')) {
            $query->where('workflow_id', $request->input('workflow_id'));
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->input('mode'));
        }

        $executions = $query->latest()->paginate($request->input('per_page', 20));

        return ExecutionResource::collection($executions);
    }

    public function show(Request $request, Workspace $workspace, Execution $execution): JsonResponse
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        $execution->load(['workflow', 'triggeredBy', 'nodes']);

        return response()->json([
            'execution' => new ExecutionResource($execution),
        ]);
    }

    public function destroy(Request $request, Workspace $workspace, Execution $execution): JsonResponse
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.delete');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        $execution->delete();

        return response()->json([
            'message' => 'Execution deleted successfully.',
        ]);
    }

    public function nodes(Request $request, Workspace $workspace, Execution $execution): AnonymousResourceCollection
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        return ExecutionNodeResource::collection($execution->nodes);
    }

    public function logs(Request $request, Workspace $workspace, Execution $execution): AnonymousResourceCollection
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        $query = $execution->logs();

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('execution_node_id')) {
            $query->where('execution_node_id', $request->input('execution_node_id'));
        }

        return ExecutionLogResource::collection($query->get());
    }

    public function retry(Request $request, Workspace $workspace, Execution $execution): JsonResponse
    {
        $this->permissionService->authorize($request->user(), $workspace, 'workflow.execute');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        if (! $execution->canRetry()) {
            return response()->json([
                'message' => 'This execution cannot be retried.',
            ], 422);
        }

        $newExecution = Execution::create([
            'workflow_id' => $execution->workflow_id,
            'workspace_id' => $execution->workspace_id,
            'status' => ExecutionStatus::Pending,
            'mode' => ExecutionMode::Retry,
            'triggered_by' => $request->user()->id,
            'trigger_data' => $execution->trigger_data,
            'attempt' => $execution->attempt + 1,
            'max_attempts' => $execution->max_attempts,
            'parent_execution_id' => $execution->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $newExecution->load(['workflow', 'triggeredBy']);

        return response()->json([
            'message' => 'Execution retry started.',
            'execution' => new ExecutionResource($newExecution),
        ], 201);
    }

    public function cancel(Request $request, Workspace $workspace, Execution $execution): JsonResponse
    {
        $this->permissionService->authorize($request->user(), $workspace, 'workflow.execute');
        $this->ensureExecutionBelongsToWorkspace($execution, $workspace);

        if (! $execution->canCancel()) {
            return response()->json([
                'message' => 'This execution cannot be cancelled.',
            ], 422);
        }

        $execution->cancel();
        $execution->load(['workflow', 'triggeredBy']);

        return response()->json([
            'message' => 'Execution cancelled.',
            'execution' => new ExecutionResource($execution),
        ]);
    }

    public function workflowExecutions(Request $request, Workspace $workspace, Workflow $workflow): AnonymousResourceCollection
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');

        if ($workflow->workspace_id !== $workspace->id) {
            abort(404, 'Workflow not found.');
        }

        $query = $workflow->executions()->with(['triggeredBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $executions = $query->latest()->paginate($request->input('per_page', 20));

        return ExecutionResource::collection($executions);
    }

    public function stats(Request $request, Workspace $workspace): JsonResponse
    {
        $this->permissionService->authorize($request->user(), $workspace, 'execution.view');

        $baseQuery = $workspace->executions();

        if ($request->filled('workflow_id')) {
            $baseQuery->where('workflow_id', $request->input('workflow_id'));
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', ExecutionStatus::Completed)->count(),
            'failed' => (clone $baseQuery)->where('status', ExecutionStatus::Failed)->count(),
            'running' => (clone $baseQuery)->where('status', ExecutionStatus::Running)->count(),
            'pending' => (clone $baseQuery)->where('status', ExecutionStatus::Pending)->count(),
            'cancelled' => (clone $baseQuery)->where('status', ExecutionStatus::Cancelled)->count(),
            'avg_duration_ms' => (clone $baseQuery)->whereNotNull('duration_ms')->avg('duration_ms'),
        ];

        $stats['success_rate'] = $stats['total'] > 0
            ? round(($stats['completed'] / $stats['total']) * 100, 2)
            : 0;

        return response()->json(['stats' => $stats]);
    }

    private function ensureExecutionBelongsToWorkspace(Execution $execution, Workspace $workspace): void
    {
        if ($execution->workspace_id !== $workspace->id) {
            abort(404, 'Execution not found.');
        }
    }
}
