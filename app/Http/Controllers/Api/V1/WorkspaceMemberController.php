<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspace\UpdateMemberRequest;
use App\Http\Resources\Api\V1\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkspaceMemberController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        $members = $workspace->members()->get();

        return WorkspaceMemberResource::collection($members);
    }

    public function update(UpdateMemberRequest $request, Workspace $workspace, User $user): WorkspaceMemberResource
    {
        if ($workspace->owner_id === $user->id) {
            abort(403, 'Cannot change the role of the workspace owner.');
        }

        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        $user->load(['workspaces' => fn ($query) => $query->where('workspaces.id', $workspace->id)]);

        return new WorkspaceMemberResource($user);
    }

    public function destroy(Workspace $workspace, User $user): JsonResponse
    {
        if ($workspace->owner_id === $user->id) {
            abort(403, 'Cannot remove the workspace owner.');
        }

        $workspace->members()->detach($user->id);

        return response()->json(['message' => 'Member removed successfully.']);
    }

    public function leave(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->owner_id === $user->id) {
            abort(403, 'Workspace owner cannot leave. Transfer ownership first or delete the workspace.');
        }

        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            abort(404, 'You are not a member of this workspace.');
        }

        $workspace->members()->detach($user->id);

        return response()->json(['message' => 'You have left the workspace.']);
    }
}
