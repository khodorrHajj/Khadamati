<?php

namespace Tests\Feature;

use App\Broadcasting\RequestChatChannel;
use App\Events\MessageSent;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestMessage;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_send_message_on_own_request(): void
    {
        Event::fake([MessageSent::class]);

        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);

        $this->actingAs($citizen)
            ->postJson(route('citizen.requests.messages.store', $serviceRequest), [
                'body' => 'Hello municipality.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Hello municipality.');

        $this->assertDatabaseHas('request_messages', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $citizen->id,
            'body' => 'Hello municipality.',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_citizen_cannot_message_another_citizens_request(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($otherCitizen);

        $this->actingAs($citizen)
            ->postJson(route('citizen.requests.messages.store', $serviceRequest), [
                'body' => 'Not mine.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('request_messages', 0);
    }

    public function test_municipality_user_can_message_own_office_request(): void
    {
        Event::fake([MessageSent::class]);

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $office);

        $this->actingAs($municipalityUser)
            ->postJson(route('municipality.requests.messages.store', $serviceRequest), [
                'body' => 'Your request is being reviewed.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Your request is being reviewed.');

        $this->assertDatabaseHas('request_messages', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $municipalityUser->id,
            'body' => 'Your request is being reviewed.',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_municipality_user_cannot_message_another_office_request(): void
    {
        $ownOffice = $this->office();
        $otherOffice = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $ownOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $otherOffice);

        $this->actingAs($municipalityUser)
            ->postJson(route('municipality.requests.messages.store', $serviceRequest), [
                'body' => 'Wrong office.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('request_messages', 0);
    }

    public function test_private_channel_authorization_works(): void
    {
        $office = $this->office();
        $otherOffice = $this->office();
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $otherMunicipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $otherOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($citizen, $office);
        $channel = new RequestChatChannel();

        $this->assertTrue($channel->join($citizen, $serviceRequest));
        $this->assertTrue($channel->join($municipalityUser, $serviceRequest));
        $this->assertFalse($channel->join($otherCitizen, $serviceRequest));
        $this->assertFalse($channel->join($otherMunicipalityUser, $serviceRequest));
    }

    public function test_private_channel_registration_uses_request_chat_channel_class(): void
    {
        $channels = app('Illuminate\Contracts\Broadcasting\Factory')->connection()->getChannels();

        $this->assertSame(RequestChatChannel::class, $channels->get('request-chat.{serviceRequest}'));

        $this->artisan('channel:list')
            ->assertExitCode(0)
            ->expectsOutputToContain('request-chat.{serviceRequest}');
    }

    public function test_broadcast_auth_allows_request_owner_without_reflection_error(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);

        $this->actingAs($citizen)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-request-chat.' . $serviceRequest->id,
            ])
            ->assertOk();
    }

    public function test_broadcast_auth_denies_other_citizen_without_500(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);

        $response = $this->actingAs($otherCitizen)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-request-chat.' . $serviceRequest->id,
            ]);

        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function test_broadcast_auth_allows_assigned_municipality_without_reflection_error(): void
    {
        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $office);

        $this->actingAs($municipalityUser)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-request-chat.' . $serviceRequest->id,
            ])
            ->assertOk();
    }

    public function test_broadcast_auth_denies_other_municipality_without_500(): void
    {
        $ownOffice = $this->office();
        $otherOffice = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $ownOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $otherOffice);

        $response = $this->actingAs($municipalityUser)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-request-chat.' . $serviceRequest->id,
            ]);

        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function test_broadcast_auth_invalid_request_does_not_return_500(): void
    {
        $citizen = $this->userWithRole('citizen');

        $response = $this->actingAs($citizen)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-request-chat.999999',
            ]);

        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function test_message_sent_event_broadcasts(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);
        $message = $serviceRequest->requestMessages()->create([
            'sender_id' => $citizen->id,
            'body' => 'Broadcast me.',
        ]);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-request-chat.' . $serviceRequest->id, $channels[0]->name);
        $this->assertSame('message.sent', $event->broadcastAs());
        $this->assertSame('Broadcast me.', $event->broadcastWith()['message']['body']);
    }

    public function test_citizen_can_access_messages_page(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);
        $otherRequest = $this->serviceRequestFor($otherCitizen);
        $serviceRequest->requestMessages()->create([
            'sender_id' => $citizen->id,
            'body' => 'Citizen conversation.',
        ]);
        $otherRequest->requestMessages()->create([
            'sender_id' => $otherCitizen->id,
            'body' => 'Hidden conversation.',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.messages.index'))
            ->assertOk()
            ->assertSee('Citizen conversation.')
            ->assertSee($serviceRequest->tracking_code)
            ->assertSee($serviceRequest->status)
            ->assertDontSee('Hidden conversation.')
            ->assertSee(route('citizen.requests.show', $serviceRequest) . '#messages', false);
    }

    public function test_municipality_can_access_messages_page(): void
    {
        $office = $this->office();
        $otherOffice = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $office);
        $otherRequest = $this->serviceRequestFor($this->userWithRole('citizen'), $otherOffice);
        $serviceRequest->requestMessages()->create([
            'sender_id' => $serviceRequest->user_id,
            'body' => 'Municipality conversation.',
        ]);
        $otherRequest->requestMessages()->create([
            'sender_id' => $otherRequest->user_id,
            'body' => 'Other office conversation.',
        ]);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.messages.index'))
            ->assertOk()
            ->assertSee('Municipality conversation.')
            ->assertSee($serviceRequest->tracking_code)
            ->assertSee($office->name)
            ->assertDontSee('Other office conversation.')
            ->assertSee(route('municipality.requests.show', $serviceRequest) . '#messages', false);
    }

    public function test_sidebar_and_navbar_message_links_do_not_point_to_dashboard(): void
    {
        $citizen = $this->userWithRole('citizen');
        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee(route('citizen.messages.index'), false)
            ->assertDontSee(route('citizen.dashboard') . '#messages', false);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.dashboard'))
            ->assertOk()
            ->assertSee(route('municipality.messages.index'), false)
            ->assertDontSee(route('municipality.dashboard') . '#messages', false);
    }

    public function test_request_detail_chat_page_contains_request_id_and_echo_listener(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('data-request-chat', false)
            ->assertSee('data-request-id="' . $serviceRequest->id . '"', false)
            ->assertSee('Echo.private(`request-chat.${requestId}`)', false)
            ->assertSee("listen('.message.sent'", false);
    }

    public function test_unread_counts_work(): void
    {
        $office = $this->office();
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($citizen, $office);

        $serviceRequest->requestMessages()->create([
            'sender_id' => $municipalityUser->id,
            'body' => 'Unread for citizen.',
        ]);
        $serviceRequest->requestMessages()->create([
            'sender_id' => $citizen->id,
            'body' => 'Unread for municipality.',
        ]);

        $this->actingAs($citizen)
            ->getJson(route('citizen.messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($municipalityUser)
            ->getJson(route('municipality.messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk();

        $this->actingAs($citizen)
            ->getJson(route('citizen.messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_message_attachment_download_is_authorized(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $otherOffice = $this->office();
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $otherMunicipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $otherOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestFor($citizen, $office);
        $message = $serviceRequest->requestMessages()->create([
            'sender_id' => $citizen->id,
            'body' => null,
            'attachment_path' => 'request-messages/chat-file.pdf',
        ]);

        Storage::disk('public')->put($message->attachment_path, 'file contents');

        $this->actingAs($citizen)
            ->get(route('request-messages.attachments.download', $message))
            ->assertOk()
            ->assertDownload('chat-file.pdf');

        $this->actingAs($municipalityUser)
            ->get(route('request-messages.attachments.download', $message))
            ->assertOk()
            ->assertDownload('chat-file.pdf');

        $this->actingAs($otherCitizen)
            ->get(route('request-messages.attachments.download', $message))
            ->assertNotFound();

        $this->actingAs($otherMunicipalityUser)
            ->get(route('request-messages.attachments.download', $message))
            ->assertNotFound();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create(array_merge([
            'name' => ucfirst($role) . ' User',
            'email' => $role . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleModel->id,
            'is_active' => true,
        ], $attributes));
    }

    private function office(): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => 'Municipality ' . uniqid(),
        ]);

        return GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ]);
    }

    private function serviceRequestFor(User $citizen, ?GovernmentOffice $office = null): ServiceRequest
    {
        $office ??= $this->office();

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        $service = Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 10,
            'duration_days' => 2,
            'is_active' => true,
        ]);

        return ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
        ]);
    }
}
