<?php

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectFeedbackRequest;
use App\Models\ProjectFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectFeedbackController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProjectFeedback::find('ap-malls')?->publicData(),
        ]);
    }

    public function showForAdmin(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_active && $request->user()->email_verified_at, 403);

        return $this->show()->header('Cache-Control', 'no-store');
    }

    public function update(ProjectFeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();
        $now = now();

        // A fixed primary key makes concurrent submissions update one review, not create duplicates.
        ProjectFeedback::upsert([[
            'project_key' => 'ap-malls',
            'ratings' => json_encode(array_map('intval', $data['ratings']), JSON_THROW_ON_ERROR),
            'comment' => isset($data['comment']) ? (trim($data['comment']) ?: null) : null,
            'submitted_by' => $request->user()->id,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['project_key'], ['ratings', 'comment', 'submitted_by', 'published_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'message' => 'Owner feedback published successfully.',
            'data' => ProjectFeedback::findOrFail('ap-malls')->publicData(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_active && $request->user()->email_verified_at, 403);
        ProjectFeedback::whereKey('ap-malls')->delete();

        return response()->json(['success' => true, 'message' => 'Owner feedback withdrawn.', 'data' => null]);
    }
}
