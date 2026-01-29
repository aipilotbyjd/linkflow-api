<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Subscription\StoreSubscriptionRequest;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function show(Workspace $workspace): SubscriptionResource|JsonResponse
    {
        $subscription = $workspace->subscription()->with('plan')->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 404);
        }

        return new SubscriptionResource($subscription);
    }

    public function store(StoreSubscriptionRequest $request, Workspace $workspace): SubscriptionResource
    {
        $plan = Plan::query()->findOrFail($request->validated('plan_id'));

        $subscription = $workspace->subscription()->first();

        if ($subscription) {
            $subscription->update([
                'plan_id' => $plan->id,
            ]);
        } else {
            $subscription = $workspace->subscription()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        $subscription->load('plan');

        return new SubscriptionResource($subscription);
    }

    public function destroy(Workspace $workspace): JsonResponse
    {
        $subscription = $workspace->subscription()->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 404);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription canceled successfully.',
        ]);
    }
}
