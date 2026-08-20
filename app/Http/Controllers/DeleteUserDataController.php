<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\UseCases\DeleteUserData;

final class DeleteUserDataController extends Controller
{
    public function __invoke(
        Request $request,
        DeleteUserData $useCase,
        PrivacyPrincipalResolver $principals,
        PrivacySessionRepository $sessions,
    ): JsonResponse {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response()->json(['status' => 'unauthorized'], 401, ['Cache-Control' => 'no-store']);
        }

        $user = $request->user();
        if ($user instanceof User && $user->isSuperAdmin() && User::query()->where('role', UserRole::SuperAdmin)->where('status', UserStatus::Active)->whereKeyNot($user->id)->doesntExist()) {
            return response()->json(['message' => 'Son aktif super-admin hesabı silinemez.'], 422, ['Cache-Control' => 'no-store']);
        }

        try {
            $result = $useCase->handle($ownerId);
        } catch (TransientWorkflowFailure) {
            return response()->json(['status' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        $credential = $request->header('X-Lootwright-Privacy-Session');
        if (is_string($credential)) {
            $sessions->markDeleted($credential);
        }

        if ($user instanceof User) {
            DB::table('analysis_drafts')->where('user_id', $user->id)->delete();
            DB::table('user_privacy_preferences')->where('user_id', $user->id)->delete();
            $user->forceFill(['status' => UserStatus::PendingDeletion, 'deletion_requested_at' => now()])->save();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'status' => 'deleted',
            'artifacts_deleted' => $result->artifactsDeleted,
            'analyses_deleted' => $result->analysesDeleted,
        ], headers: ['Cache-Control' => 'no-store']);
    }
}
