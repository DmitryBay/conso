<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->company_id && $user->company, 403, 'Пользователь не привязан к компании.');
        abort_if($user->company->status === CompanyStatus::Suspended, 403, 'Аккаунт компании приостановлен.');

        app()->instance('currentCompany', $user->company);
        view()->share('currentCompany', $user->company);

        return $next($request);
    }
}
