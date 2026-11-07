<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Support\DadesDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Index extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('livewire.dashboard.index', [
            'resum' => app(DadesDashboard::class)->obtenir(),
        ]);
    }
}
