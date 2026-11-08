<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\InviteMemberRequest;
use App\Models\Mess;
use App\Models\MessInvitation;
use App\Models\MessMember;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MessInvitationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Invite a user to the mess by email.
     */
    public function store(InviteMemberRequest $request, Mess $mess): RedirectResponse
    {
        $invitation = $mess->invitations()->create([
            'invited_by' => $request->user()->getKey(),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'token' => Str::random(48),
            'expires_at' => now()->addDays(7),
        ]);

        $this->audit->log($mess, $request->user(), 'invitation.created', $invitation, ['email' => $invitation->email], $request->ip());

        return back()->with('toast', __('student.invitations.created'));
    }

    /**
     * Cancel a pending invitation.
     */
    public function destroy(Request $request, Mess $mess, MessInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->mess_id === $mess->getKey(), 404);
        abort_if($invitation->accepted_at !== null, 403);

        $this->audit->log($mess, $request->user(), 'invitation.cancelled', $invitation, ['email' => $invitation->email], $request->ip());
        $invitation->delete();

        return back()->with('toast', __('student.invitations.cancelled'));
    }

    /**
     * Show an invitation acceptance screen.
     */
    public function show(Request $request, string $token): View
    {
        $invitation = MessInvitation::query()->where('token', $token)->with('mess')->firstOrFail();

        return view('student.invitations.show', [
            'invitation' => $invitation,
            'canAccept' => $invitation->isPending()
                && strcasecmp((string) $request->user()->email, $invitation->email) === 0,
        ]);
    }

    /**
     * Accept an invitation and join the mess.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = MessInvitation::query()->where('token', $token)->with('mess')->firstOrFail();

        abort_unless($invitation->isPending(), 410, __('student.invitations.expired'));
        abort_unless(strcasecmp((string) $request->user()->email, $invitation->email) === 0, 403);

        $member = $invitation->mess->members()->where('user_id', $request->user()->getKey())->first();

        if ($member === null) {
            $invitation->mess->members()->create([
                'user_id' => $request->user()->getKey(),
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'joining_date' => now()->toDateString(),
                'status' => MessMember::STATUS_ACTIVE,
                'role' => $invitation->role,
            ]);
        }

        $invitation->update(['accepted_at' => now()]);
        session(['student_mess_id' => $invitation->mess_id]);

        $this->audit->log($invitation->mess, $request->user(), 'invitation.accepted', $invitation, ['email' => $invitation->email], $request->ip());

        return redirect()
            ->route('student.dashboard')
            ->with('toast', __('student.invitations.accepted'));
    }
}
