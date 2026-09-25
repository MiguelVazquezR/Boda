<?php

namespace Tests\Feature;

use App\Http\Controllers\InvitationController;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function guest(string $fullName): Guest
    {
        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        return Guest::create([
            'first_name' => $firstName,
            'last_name' => trim($lastName),
        ]);
    }

    /**
     * Invitación de prueba con sus invitados ya vinculados.
     *
     * @param  array<int, Guest>  $guests
     */
    private function invitation(array $guests, string $displayName): Invitation
    {
        $invitation = Invitation::create(['display_name' => $displayName]);

        foreach ($guests as $guest) {
            $guest->update(['invitation_id' => $invitation->id]);
        }

        return $invitation;
    }

    // ── Puerta de apertura (/i/{token}) ──────────────────────────

    public function test_la_puerta_de_apertura_muestra_el_nombre_de_los_invitados(): void
    {
        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana & Luis');

        $this->get(route('invitation.show', $invitation->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invitacion')
                ->where('invitation.display_name', 'Ana & Luis')
                ->where('invitation.token', $invitation->token)
                ->has('invitation.members', 1)
                ->where('invitation.members.0.full_name', 'Ana Pérez'));
    }

    public function test_un_token_inexistente_devuelve_404(): void
    {
        $this->get('/i/tokenquenoexiste')->assertNotFound();
    }

    public function test_abrir_la_invitacion_guarda_la_cookie_y_redirige_al_sitio_de_canva(): void
    {
        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        $response = $this->get(route('invitation.open', $invitation->token));

        $response->assertRedirect(config('wedding.canva_url'));
        $response->assertCookie(InvitationController::COOKIE);
    }

    // ── Confirmación de asistencia ───────────────────────────────

    public function test_la_confirmacion_precarga_la_invitacion_por_link_directo(): void
    {
        $ana = $this->guest('Ana Pérez');
        $luis = $this->guest('Luis Pérez');
        $invitation = $this->invitation([$ana, $luis], 'Ana & Luis');

        $this->get(route('invitation.rsvp', $invitation->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('focusRsvp', true)
                ->where('invitation.display_name', 'Ana & Luis')
                ->has('invitation.members', 2));
    }

    public function test_la_confirmacion_reconoce_la_invitacion_por_cookie(): void
    {
        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        // La cookie viaja encriptada (EncryptCookies + CookieValuePrefix):
        // el propio harness de pruebas la encripta al enviarla.
        $this->withCookie(InvitationController::COOKIE, $invitation->token)
            ->get('/rsvp')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('focusRsvp', true)
                ->where('invitation.display_name', 'Ana Pérez'));
    }

    public function test_sin_invitacion_la_confirmacion_muestra_el_buscador(): void
    {
        $this->get('/rsvp')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('invitation', null));
    }

    public function test_olvidar_la_invitacion_limpia_la_cookie(): void
    {
        $this->get(route('rsvp.forget'))->assertRedirect(route('rsvp.landing'));
    }

    // ── RSVP con token de la invitación ──────────────────────────

    public function test_el_rsvp_con_el_token_correcto_registra_la_respuesta(): void
    {
        $ana = $this->guest('Ana Pérez');
        $invitation = $this->invitation([$ana], 'Ana Pérez');

        $this->post(route('rsvp.confirm', $ana->id), [
            'attending' => true,
            'rsvp_message' => '¡Ahí estaremos!',
            'invitation_token' => $invitation->token,
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $ana->id,
            'rsvp_status' => 'confirmed',
            'rsvp_message' => '¡Ahí estaremos!',
        ]);
    }

    public function test_el_rsvp_rechaza_un_token_de_otra_invitacion(): void
    {
        $ana = $this->guest('Ana Pérez');
        $this->invitation([$ana], 'Ana Pérez');
        $otra = $this->invitation([$this->guest('Pedro Ramírez')], 'Pedro Ramírez');

        $this->post(route('rsvp.confirm', $ana->id), [
            'attending' => true,
            'invitation_token' => $otra->token,
        ])->assertSessionHasErrors('invitation_token');

        $this->assertDatabaseHas('guests', ['id' => $ana->id, 'rsvp_status' => 'pending']);
    }

    public function test_el_rsvp_sin_invitacion_sigue_funcionando_con_el_buscador(): void
    {
        $ana = $this->guest('Ana Pérez');

        $this->post(route('rsvp.confirm', $ana->id), ['attending' => false])->assertRedirect();

        $this->assertDatabaseHas('guests', ['id' => $ana->id, 'rsvp_status' => 'declined']);
    }

    // ── Panel de invitados: parejas / links ──────────────────────

    public function test_admin_crea_una_invitacion_para_una_pareja(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana Pérez');
        $luis = $this->guest('Luis Pérez');

        $this->post(route('admin.invitations.store'), ['members' => [$ana->id, $luis->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invitation = Invitation::firstOrFail();

        $this->assertSame('Ana Pérez & Luis Pérez', $invitation->display_name);
        $this->assertSame(10, strlen($invitation->token));
        $this->assertSame($invitation->id, $ana->fresh()->invitation_id);
        $this->assertSame($invitation->id, $luis->fresh()->invitation_id);
    }

    public function test_el_texto_para_whatsapp_incluye_nombres_lugares_y_links(): void
    {
        $invitation = $this->invitation(
            [$this->guest('Ana Pérez'), $this->guest('Luis Pérez')],
            'Ana & Luis',
        );

        $message = $invitation->shareMessage();

        $this->assertStringContainsString('¡Hola, Ana Pérez y Luis Pérez!', $message);
        $this->assertStringContainsString('hemos reservado 2 lugares especialmente', $message);
        $this->assertStringContainsString($invitation->publicUrl(), $message);
        $this->assertStringContainsString(route('home'), $message);
        $this->assertStringContainsString("Con mucho cariño,\n\nJosé y Elizabeth", $message);
    }

    public function test_el_texto_para_whatsapp_usa_singular_cuando_hay_un_solo_lugar(): void
    {
        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        $message = $invitation->shareMessage();

        $this->assertStringContainsString('¡Hola, Ana Pérez!', $message);
        $this->assertStringContainsString('hemos reservado 1 lugar especialmente', $message);
        $this->assertStringNotContainsString('1 lugares', $message);
    }

    public function test_el_listado_de_invitados_incluye_el_texto_para_whatsapp(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        $this->get(route('admin.guests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Guests/Index')
                ->where('guests.0.invitation.share_message', $invitation->shareMessage()));
    }

    public function test_no_permite_mas_de_dos_invitados_por_invitacion(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ids = collect(['Ana Pérez', 'Luis Pérez', 'Pedro Ramírez'])
            ->map(fn (string $name) => $this->guest($name)->id)
            ->all();

        $this->post(route('admin.invitations.store'), ['members' => $ids])
            ->assertSessionHasErrors('members');

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_un_invitado_no_puede_pertenecer_a_dos_invitaciones(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana Pérez');
        $this->invitation([$ana], 'Ana Pérez');
        $luis = $this->guest('Luis Pérez');

        $this->post(route('admin.invitations.store'), ['members' => [$ana->id, $luis->id]])
            ->assertSessionHasErrors('members');

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_editar_una_invitacion_permite_unir_una_pareja(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana Pérez');
        $invitation = $this->invitation([$ana], 'Ana Pérez');
        $luis = $this->guest('Luis Pérez');

        $this->put(route('admin.invitations.update', $invitation->id), [
            'members' => [$ana->id, $luis->id],
        ])->assertRedirect();

        $invitation->refresh();

        $this->assertSame(2, $invitation->members()->count());
        $this->assertSame($invitation->id, $luis->fresh()->invitation_id);
    }

    public function test_regenerar_el_link_invalida_el_anterior(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');
        $oldToken = $invitation->token;

        $this->post(route('admin.invitations.token', $invitation->id))->assertRedirect();

        $newToken = $invitation->fresh()->token;

        $this->assertNotSame($oldToken, $newToken);
        $this->get("/i/{$oldToken}")->assertNotFound();
        $this->get("/i/{$newToken}")->assertOk();
    }

    public function test_marca_y_desmarca_la_invitacion_como_enviada(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        $this->assertNull($invitation->sent_at);

        $this->put(route('admin.invitations.sent', $invitation->id), ['sent' => true])
            ->assertRedirect();

        $this->assertNotNull($invitation->fresh()->sent_at);

        $this->put(route('admin.invitations.sent', $invitation->id), ['sent' => false])
            ->assertRedirect();

        $this->assertNull($invitation->fresh()->sent_at);
    }

    public function test_un_usuario_normal_no_puede_marcar_la_invitacion_como_enviada(): void
    {
        $regular = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $invitation = $this->invitation([$this->guest('Ana Pérez')], 'Ana Pérez');

        $this->actingAs($regular, 'sanctum');

        $this->put(route('admin.invitations.sent', $invitation->id), ['sent' => true])
            ->assertForbidden();

        $this->assertNull($invitation->fresh()->sent_at);
    }

    public function test_eliminar_la_invitacion_conserva_a_los_invitados(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana Pérez');
        $invitation = $this->invitation([$ana], 'Ana Pérez');

        $this->delete(route('admin.invitations.destroy', $invitation->id))->assertRedirect();

        $this->assertDatabaseCount('invitations', 0);
        $this->assertNull($ana->fresh()->invitation_id);
        $this->assertDatabaseHas('guests', ['id' => $ana->id]);
    }

    public function test_crea_una_invitacion_individual_para_los_invitados_sin_invitacion(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->guest('Ana Pérez');
        $this->guest('Luis Pérez');
        $this->invitation([$this->guest('Pedro Ramírez')], 'Pedro');

        $this->post(route('admin.invitations.single'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('invitations', 3);
        $this->assertSame(0, Guest::whereNull('invitation_id')->count());
    }

    public function test_un_usuario_normal_no_puede_gestionar_invitaciones(): void
    {
        $regular = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($regular, 'sanctum');

        $this->post(route('admin.invitations.store'), ['members' => [$this->guest('Ana Pérez')->id]])
            ->assertForbidden();
    }
}

