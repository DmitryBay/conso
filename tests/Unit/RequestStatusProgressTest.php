<?php

namespace Tests\Unit;

use App\Enums\RequestStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequestStatusProgressTest extends TestCase
{
    #[DataProvider('progressProvider')]
    public function test_each_status_has_a_guest_progress_value(RequestStatus $status, int $percent, int $step): void
    {
        $this->assertSame($percent, $status->guestProgressPercent());
        $this->assertSame($step, $status->guestProgressStep());
    }

    public static function progressProvider(): array
    {
        return [
            'new' => [RequestStatus::New, 10, 0],
            'accepted' => [RequestStatus::Accepted, 30, 1],
            'in progress' => [RequestStatus::InProgress, 60, 2],
            'waiting for guest' => [RequestStatus::WaitingGuest, 90, 3],
            'ready' => [RequestStatus::Ready, 90, 3],
            'completed' => [RequestStatus::Completed, 100, 4],
            'cancelled' => [RequestStatus::Cancelled, 0, 0],
        ];
    }
}
