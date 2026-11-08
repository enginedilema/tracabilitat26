<?php

namespace App\Livewire\Administracio;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UsuarisIndex extends Component
{
    public string $nom = '';

    public string $email = '';

    public string $contrasenya = '';

    public string $rolNou = 'tecnic';

    /** @var array<int, string> */
    public array $rols = [];

    /** @var array<int, bool> */
    public array $actius = [];

    public string $missatge = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
        $this->recarregarControls();
    }

    private function recarregarControls(): void
    {
        foreach (User::query()->get(['id', 'rol', 'actiu']) as $usuari) {
            $this->rols[$usuari->id] = (string) $usuari->rol;
            $this->actius[$usuari->id] = (bool) $usuari->actiu;
        }
    }

    public function crear(): void
    {
        Gate::authorize('create', User::class);

        $dades = $this->validate([
            'nom' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contrasenya' => ['required', 'string', 'min:12', 'max:255'],
            'rolNou' => ['required', Rule::in(['admin', 'tecnic', 'patoleg'])],
        ]);

        DB::transaction(function () use ($dades): void {
            $usuari = User::query()->create([
                'name' => $dades['nom'],
                'email' => $dades['email'],
                'password' => Hash::make($dades['contrasenya']),
            ]);
            $usuari->forceFill(['rol' => $dades['rolNou'], 'actiu' => true])->save();
            $this->auditar($usuari, 'creat', null, ['rol' => $usuari->rol, 'actiu' => true]);
        });

        $this->reset(['nom', 'email', 'contrasenya']);
        $this->rolNou = 'tecnic';
        $this->recarregarControls();
        $this->missatge = 'Usuari creat. Dona-li la contrasenya per un canal segur.';
    }

    public function actualitzar(int $id): void
    {
        $dades = validator([
            'rol' => $this->rols[$id] ?? null,
            'actiu' => $this->actius[$id] ?? null,
        ], [
            'rol' => ['required', Rule::in(['pendent', 'admin', 'tecnic', 'patoleg'])],
            'actiu' => ['required', 'boolean'],
        ])->validate();

        $nouRol = $dades['rol'];
        $nouActiu = (bool) $dades['actiu'];
        $actorId = (int) auth()->id();

        DB::transaction(function () use ($id, $nouRol, $nouActiu, $actorId): void {
            $usuari = User::query()->lockForUpdate()->findOrFail($id);
            Gate::authorize('update', $usuari);

            $anterior = ['rol' => $usuari->rol, 'actiu' => (bool) $usuari->actiu];
            $nou = ['rol' => $nouRol, 'actiu' => $nouActiu];

            if ($usuari->id === $actorId && $nou !== $anterior) {
                throw ValidationException::withMessages(['rols' => 'No pots canviar el teu propi rol ni desactivar el teu compte.']);
            }

            if ($anterior['rol'] === 'admin' && $anterior['actiu'] && ($nouRol !== 'admin' || ! $nouActiu)) {
                $altres = User::query()->where('rol', 'admin')->where('actiu', true)->where('id', '!=', $usuari->id)->count();
                if ($altres === 0) {
                    throw ValidationException::withMessages(['rols' => 'Cal conservar com a mínim un administrador actiu.']);
                }
            }

            if ($nou !== $anterior) {
                $usuari->forceFill($nou)->save();
                $this->auditar($usuari, 'permisos_actualitzats', $anterior, $nou);
            }
        });

        $this->recarregarControls();
        $this->missatge = 'Permisos actualitzats correctament.';
    }

    /**
     * @param  array<string, mixed>|null  $abans
     * @param  array<string, mixed>  $despres
     */
    private function auditar(User $usuari, string $accio, ?array $abans, array $despres): void
    {
        DB::table('usuari_auditoria')->insert([
            'actor_id' => auth()->id(),
            'actor_email' => auth()->user()?->email,
            'usuari_id' => $usuari->id,
            'usuari_email' => $usuari->email,
            'accio' => $accio,
            'abans' => $abans === null ? null : json_encode($abans, JSON_THROW_ON_ERROR),
            'despres' => json_encode($despres, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('livewire.administracio.usuaris-index', [
            'usuaris' => User::query()->orderBy('name')->get(),
            'auditoria' => DB::table('usuari_auditoria')->orderByDesc('id')->limit(10)->get(),
        ]);
    }
}
