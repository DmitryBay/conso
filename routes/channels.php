<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('company.{companyId}', function ($user, int $companyId) {
    return $user->is_active && $user->company_id === $companyId;
});
