<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Requests\UpdateInvitationRequest;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Invitaciones digitales desde el panel de invitados.
 *
 * Una invitación agrupa a 1 o 2 personas (pareja, o persona sola si no tiene
 * pareja) y genera el link público /i/{token} que se comparte por WhatsApp.
 * Se administra desde la sección «Invitados» (botón «Parejas / Links»).
 */
class InvitationController extends Controller
{
    /**
     * Crea una invitación con los invitados seleccionados.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $members = $this->resolveMembers($validated['members']);

        $this->assertMembersAreAvailable($members);

        DB::transaction(function () use ($validated, $members) {
            $invitation = Invitation::create([
                'display_name' => ($validated['display_name'] ?? null) ?: Invitation::suggestDisplayName($members),
            ]);

            $this->syncMembers($invitation, $members);
        });

        return back()->with('success', 'Invitación creada. Ya puedes copiar y compartir su link.');
    }

    /**
     * Actualiza el nombre visible y/o los invitados de una invitación.
     */
    public function update(UpdateInvitationRequest $request, Invitation $invitation): RedirectResponse
    {
        $validated = $request->validated();
        $members = $this->resolveMembers($validated['members']);

        $this->assertMembersAreAvailable($members, $invitation);

        DB::transaction(function () use ($invitation, $validated, $members) {
            $invitation->update([
                'display_name' => ($validated['display_name'] ?? null) ?: Invitation::suggestDisplayName($members),
            ]);

            $this->syncMembers($invitation, $members);
        });

        return back()->with('success', 'Invitación actualizada correctamente.');
    }

    /**
     * Regenera el link de la invitación (el anterior deja de funcionar).
     * Útil si un link se compartió por error.
     */
    public function regenerateToken(Invitation $invitation): RedirectResponse
    {
        $invitation->update(['token' => Invitation::generateToken()]);

        return back()->with('success', 'Se generó un link nuevo. El anterior ya no funciona.');
    }

    /**
     * Marca (o desmarca) la invitación como ya enviada a los invitados.
     * El panel la muestra en verde cuando está marcada como enviada.
     */
    public function markSent(Request $request, Invitation $invitation): RedirectResponse
    {
        $validated = $request->validate([
            'sent' => ['required', 'boolean'],
        ]);

        $invitation->update([
            'sent_at' => $validated['sent'] ? ($invitation->sent_at ?? now()) : null,
        ]);

        return back();
    }

    /**
     * Elimina la invitación. Los invitados se conservan, sólo quedan sin invitación.
     */
    public function destroy(Invitation $invitation): RedirectResponse
    {
        DB::transaction(function () use ($invitation) {
            Guest::where('invitation_id', $invitation->id)->update(['invitation_id' => null]);
            $invitation->delete();
        });

        return back()->with('success', 'Invitación eliminada. Los invitados se conservaron.');
    }

    /**
     * Atajo: crea una invitación individual para cada invitado que aún no tenga
     * una. Después se pueden unir en parejas editando cada invitación.
     */
    public function storeSingle(): RedirectResponse
    {
        $guests = Guest::whereNull('invitation_id')
            ->orderBy('full_name')
            ->get(['id', 'first_name', 'full_name']);

        if ($guests->isEmpty()) {
            return back()->with('error', 'Todos los invitados ya tienen una invitación asignada.');
        }

        DB::transaction(function () use ($guests) {
            foreach ($guests as $guest) {
                $invitation = Invitation::create([
                    'display_name' => $guest->first_name ?: $guest->full_name,
                ]);

                $guest->update(['invitation_id' => $invitation->id]);
            }
        });

        return back()->with(
            'success',
            "Se crearon {$guests->count()} invitaciones individuales. Ahora puedes unir parejas editándolas.",
        );
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Recupera los invitados indicados (ids) con los campos necesarios.
     *
     * @param  array<int, int>  $ids
     */
    private function resolveMembers(array $ids): Collection
    {
        return Guest::whereIn('id', $ids)->get(['id', 'first_name', 'full_name', 'invitation_id']);
    }

    /**
     * Vincula a los invitados indicados y libera a los que ya no forman parte
     * de la invitación.
     */
    private function syncMembers(Invitation $invitation, Collection $members): void
    {
        $ids = $members->pluck('id')->all();

        Guest::where('invitation_id', $invitation->id)
            ->whereNotIn('id', $ids)
            ->update(['invitation_id' => null]);

        Guest::whereIn('id', $ids)->update(['invitation_id' => $invitation->id]);
    }

    /**
     * Un invitado sólo puede pertenecer a una invitación a la vez.
     */
    private function assertMembersAreAvailable(Collection $members, ?Invitation $current = null): void
    {
        $conflicts = $members->filter(
            fn (Guest $guest) => $guest->invitation_id !== null && $guest->invitation_id !== $current?->id,
        );

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'members' => $conflicts->pluck('full_name')->implode(', ')
                    .' ya pertenece a otra invitación. Libéralo editando esa invitación primero.',
            ]);
        }
    }
}
