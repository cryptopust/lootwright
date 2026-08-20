<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function profile(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    public function security(Request $request): Response
    {
        return Inertia::render('Profile/Security', [
            'twoFactorEnabled' => $request->user()->two_factor_confirmed_at !== null,
            'twoFactorPending' => $request->user()->two_factor_secret !== null && $request->user()->two_factor_confirmed_at === null,
            'canDisableTwoFactor' => ! $request->user()->isAdmin(),
        ]);
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('Profile/Privacy', ['preferences' => DB::table('user_privacy_preferences')->where('user_id', $request->user()->id)->first()]);
    }
}
