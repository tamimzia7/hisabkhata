<?php

namespace App\Http\Middleware;

use App\Models\Mess;
use App\Services\MessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStudentMess
{
    public function __construct(private readonly MessService $messes) {}

    /**
     * Resolve the active mess for the navigation and remember the last workspace.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeMess = $request->route('mess');

        if ($routeMess instanceof Mess) {
            if ($user !== null && $routeMess->hasMember($user)) {
                session(['student_mess_id' => $routeMess->getKey()]);
            }

            view()->share('navMess', $routeMess);

            return $next($request);
        }

        view()->share('navMess', $user ? $this->messes->activeMessFor($user, session('student_mess_id')) : null);

        return $next($request);
    }
}
