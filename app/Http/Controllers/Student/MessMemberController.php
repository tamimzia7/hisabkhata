<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MemberRequest;
use App\Models\Mess;
use App\Models\MessMember;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MessMemberController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * List the mess members and pending invitations.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $members = $mess->members()
            ->withSum('mealEntries as total_meals', 'total_meals')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'manager' THEN 1 WHEN 'accountant' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();

        return view('student.members.index', [
            'mess' => $mess,
            'members' => $members,
            'invitations' => $request->user()->can('manageMembers', $mess)
                ? $mess->invitations()->whereNull('accepted_at')->latest()->get()
                : collect(),
            'canManage' => $request->user()->can('manageMembers', $mess),
        ]);
    }

    /**
     * Add a member to the mess.
     */
    public function store(MemberRequest $request, Mess $mess): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');
        unset($data['photo']);

        $data['user_id'] = User::query()->where('email', $data['email'] ?? null)->value('id');
        $data['mess_id'] = $mess->getKey();
        $data['status'] = $data['status'] ?? MessMember::STATUS_ACTIVE;

        if ($photo !== null) {
            $data['photo_path'] = Storage::disk('public')->putFile('mess-members', $photo);
        }

        $member = MessMember::create($data);

        $this->audit->log($mess, $request->user(), 'member.created', $member, ['name' => $member->name], $request->ip());

        return back()->with('toast', __('student.members.created'));
    }

    /**
     * Update a member.
     */
    public function update(MemberRequest $request, Mess $mess, MessMember $member): RedirectResponse
    {
        $this->ensureBelongs($mess, $member);
        abort_if($member->role === MessMember::ROLE_OWNER && $request->user()->getKey() !== $mess->owner_id, 403);

        $data = $request->validated();
        abort_if($member->role === MessMember::ROLE_OWNER, 403, __('student.errors.owner_locked'));

        if ($request->hasFile('photo')) {
            if (filled($member->photo_path)) {
                Storage::disk('public')->delete($member->photo_path);
            }

            $data['photo_path'] = Storage::disk('public')->putFile('mess-members', $request->file('photo'));
        }

        unset($data['photo']);
        $data['user_id'] = User::query()->where('email', $data['email'] ?? null)->value('id') ?? $member->user_id;

        $member->update($data);

        $this->audit->log($mess, $request->user(), 'member.updated', $member, ['name' => $member->name], $request->ip());

        return back()->with('toast', __('student.members.updated'));
    }

    /**
     * Remove a member from the mess.
     */
    public function destroy(Request $request, Mess $mess, MessMember $member): RedirectResponse
    {
        $this->ensureBelongs($mess, $member);

        abort_if($member->role === MessMember::ROLE_OWNER, 403, __('student.errors.owner_locked'));

        $name = $member->name;

        if (filled($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $this->audit->log($mess, $request->user(), 'member.deleted', $member, ['name' => $name], $request->ip());
        $member->delete();

        return back()->with('toast', __('student.members.deleted'));
    }

    /**
     * Ensure the member belongs to the given mess.
     */
    private function ensureBelongs(Mess $mess, MessMember $member): void
    {
        abort_unless($member->mess_id === $mess->getKey(), 404);
    }
}
