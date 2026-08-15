<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::where('company_id', $request->user()->company_id)
            ->withCount('guestStays')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('number')
            ->get();

        return view('workspace.rooms.index', compact('rooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $data = $this->validated($request, $company->id);

        Room::create([
            ...$data,
            'company_id' => $company->id,
            'pin_hash' => Hash::make(Str::random(40)),
            'is_active' => true,
        ]);
        $this->syncRoomCount($company->id);

        return back()->with('success', __('workspace.room_created'));
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->company_id === $request->user()->company_id, 404);
        $data = $this->validated($request, $room->company_id, $room->id);
        $data['is_active'] = $request->boolean('is_active');
        $room->update($data);
        $this->syncRoomCount($room->company_id);

        return back()->with('success', __('workspace.room_updated'));
    }

    private function validated(Request $request, int $companyId, ?int $roomId = null): array
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:30', Rule::unique('rooms')->where('company_id', $companyId)->ignore($roomId)],
            'name' => ['nullable', 'string', 'max:160', Rule::unique('rooms')->where('company_id', $companyId)->ignore($roomId)],
            'floor' => ['nullable', 'string', 'max:30'],
        ]);

        $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $data);
        $data['name'] = ($data['name'] ?? '') !== '' ? $data['name'] : null;
        $data['floor'] = ($data['floor'] ?? '') !== '' ? $data['floor'] : null;

        $identifiers = array_filter([$data['number'], $data['name']]);
        $conflict = Room::where('company_id', $companyId)
            ->when($roomId, fn ($query) => $query->whereKeyNot($roomId))
            ->where(function ($query) use ($identifiers) {
                $query->whereIn('number', $identifiers)->orWhereIn('name', $identifiers);
            })
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['number' => __('workspace.room_identifier_taken')]);
        }

        return $data;
    }

    private function syncRoomCount(int $companyId): void
    {
        $count = Room::where('company_id', $companyId)->where('is_active', true)->count();
        Company::whereKey($companyId)->update(['rooms_count' => $count]);
    }
}
