<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    /**
     * Show the student profile form.
     */
    public function edit(Request $request): View
    {
        return view('student.profile.edit', [
            'user' => $request->user(),
            'subscription' => $request->user()->activeSubscription(),
        ]);
    }

    /**
     * Update the student profile.
     */
    public function update(StudentProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if (filled($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $validated['avatar_path'] = Storage::disk('public')->putFile('avatars', $request->file('avatar'));
        }

        unset($validated['avatar']);
        $user->update($validated);

        return back()->with('toast', __('student.profile.updated'));
    }
}
