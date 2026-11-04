<?php

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeAttachment(string $name, int $kilobytes, string $mimeType): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, $mimeType);
}

test('a client can attach files to a new ticket and they are stored privately', function () {
    Storage::fake('local');

    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Screenshot attached',
        'category' => Ticket::CATEGORY_BUG_REPORT,
        'priority' => Ticket::PRIORITY_HIGH,
        'description' => 'See the attached screenshot.',
        'attachments' => [
            fakeAttachment('screen.png', 120, 'image/png'),
            fakeAttachment('invoice.pdf', 240, 'application/pdf'),
        ],
    ])->assertRedirect();

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->attachments()->count())->toBe(2);

    foreach ($ticket->attachments()->get() as $attachment) {
        expect($attachment->disk)->toBe('local')
            ->and($attachment->file_path)->toStartWith('ticket-attachments/');

        Storage::disk('local')->assertExists($attachment->file_path);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }
});

test('attachments can be added to conversation messages', function () {
    Storage::fake('local');

    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), [
            'body' => 'Here is the receipt.',
            'attachments' => [fakeAttachment('receipt.pdf', 80, 'application/pdf')],
        ])
        ->assertRedirect();

    $attachment = TicketAttachment::query()->firstOrFail();
    $message = $ticket->messages()->firstOrFail();

    expect($attachment->message_id)->toBe($message->getKey())
        ->and($attachment->ticket_id)->toBe($ticket->getKey())
        ->and($attachment->user_id)->toBe($client->getKey());

    Storage::disk('local')->assertExists($attachment->file_path);
});

test('attachment uploads reject unsupported file types', function () {
    Storage::fake('local');

    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Malware',
        'category' => Ticket::CATEGORY_OTHER,
        'priority' => Ticket::PRIORITY_LOW,
        'description' => 'Attached an executable.',
        'attachments' => [fakeAttachment('malware.exe', 10, 'application/x-msdownload')],
    ])->assertSessionHasErrors('attachments.0');

    expect(Ticket::query()->count())->toBe(0);
    expect(TicketAttachment::query()->count())->toBe(0);
});

test('attachment uploads reject files larger than 25 megabytes', function () {
    Storage::fake('local');

    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Huge file',
        'category' => Ticket::CATEGORY_OTHER,
        'priority' => Ticket::PRIORITY_LOW,
        'description' => 'Attached a file that is too large.',
        'attachments' => [fakeAttachment('archive.pdf', 26000, 'application/pdf')],
    ])->assertSessionHasErrors('attachments.0');

    expect(Ticket::query()->count())->toBe(0);
});

test('ticket attachments are only downloadable by users who can view the ticket', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_FAMILY]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $this->actingAs($owner)->post(route('tickets.store'), [
        'subject' => 'Private attachment',
        'category' => Ticket::CATEGORY_PAYMENT_ISSUE,
        'priority' => Ticket::PRIORITY_MEDIUM,
        'description' => 'Please see the attached document.',
        'attachments' => [fakeAttachment('proof.pdf', 60, 'application/pdf')],
    ])->assertRedirect();

    $ticket = Ticket::query()->firstOrFail();
    $attachment = TicketAttachment::query()->firstOrFail();
    $downloadUrl = route('tickets.attachments.download', [$ticket, $attachment]);

    $this->actingAs($owner)
        ->get($downloadUrl)
        ->assertOk()
        ->assertDownload($attachment->file_name);

    $this->actingAs($otherClient)
        ->get($downloadUrl)
        ->assertNotFound();

    $ticket->forceFill(['assignee_id' => $assignee->getKey(), 'status' => Ticket::STATUS_ASSIGNED])->save();

    $this->actingAs($assignee)
        ->get($downloadUrl)
        ->assertOk();
});

test('guests cannot download ticket attachments', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($owner)->post(route('tickets.store'), [
        'subject' => 'Protected attachment',
        'category' => Ticket::CATEGORY_OTHER,
        'priority' => Ticket::PRIORITY_LOW,
        'description' => 'Attachment for guests test.',
        'attachments' => [fakeAttachment('secret.pdf', 10, 'application/pdf')],
    ])->assertRedirect();

    $ticket = Ticket::query()->firstOrFail();
    $attachment = TicketAttachment::query()->firstOrFail();

    $this->app['auth']->forgetGuards();

    $this->get(route('tickets.attachments.download', [$ticket, $attachment]))
        ->assertRedirect(route('login'));
});
