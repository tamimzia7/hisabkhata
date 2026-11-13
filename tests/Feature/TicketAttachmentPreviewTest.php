<?php

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function uploadTicketWithAttachments(TestCase $test, User $client, array $attachments): Ticket
{
    $test->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Attachment preview test',
        'category' => Ticket::CATEGORY_BUG_REPORT,
        'priority' => Ticket::PRIORITY_MEDIUM,
        'description' => 'See the attached files.',
        'attachments' => $attachments,
    ])->assertRedirect();

    return Ticket::query()->firstOrFail();
}

test('image attachments are previewed inline for authorized participants', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = uploadTicketWithAttachments($this, $owner, [
        UploadedFile::fake()->create('screen.png', 30, 'image/png'),
    ]);

    $attachment = TicketAttachment::query()->firstOrFail();
    $previewUrl = route('tickets.attachments.preview', [$ticket, $attachment]);

    $response = $this->actingAs($owner)->get($previewUrl);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('image/png')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline');

    $this->actingAs($otherClient)->get($previewUrl)->assertNotFound();

    $ticket->forceFill([
        'assignee_id' => $assignee->getKey(),
        'status' => Ticket::STATUS_ASSIGNED,
    ])->save();

    $this->actingAs($assignee)->get($previewUrl)->assertOk();
});

test('guests cannot preview private attachments', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $ticket = uploadTicketWithAttachments($this, $owner, [
        UploadedFile::fake()->create('screen.jpg', 20, 'image/jpeg'),
    ]);

    $attachment = TicketAttachment::query()->firstOrFail();

    $this->app['auth']->forgetGuards();

    $this->get(route('tickets.attachments.preview', [$ticket, $attachment]))
        ->assertRedirect(route('login'));
});

test('document attachments cannot be previewed inline', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $ticket = uploadTicketWithAttachments($this, $owner, [
        UploadedFile::fake()->create('invoice.pdf', 30, 'application/pdf'),
    ]);

    $attachment = TicketAttachment::query()->firstOrFail();

    $this->actingAs($owner)
        ->get(route('tickets.attachments.preview', [$ticket, $attachment]))
        ->assertNotFound();
});

test('an attachment cannot be previewed through another ticket', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $ticket = uploadTicketWithAttachments($this, $owner, [
        UploadedFile::fake()->create('screen.webp', 20, 'image/webp'),
    ]);

    $attachment = TicketAttachment::query()->firstOrFail();

    $otherTicket = Ticket::factory()->create(['user_id' => $owner->getKey()]);

    $this->actingAs($owner)
        ->get(route('tickets.attachments.preview', [$otherTicket, $attachment]))
        ->assertNotFound();
});

test('the conversation renders image previews and document file cards', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $owner->getKey()]);

    $this->actingAs($owner)->post(route('tickets.messages.store', $ticket), [
        'body' => 'Screenshots and the invoice.',
        'attachments' => [
            UploadedFile::fake()->create('screenshot.png', 30, 'image/png'),
            UploadedFile::fake()->create('invoice.pdf', 40, 'application/pdf'),
            UploadedFile::fake()->create('report.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ],
    ])->assertRedirect();

    $attachments = TicketAttachment::query()->orderBy('id')->get();
    $image = $attachments->firstWhere('file_name', 'screenshot.png');
    $pdf = $attachments->firstWhere('file_name', 'invoice.pdf');
    $word = $attachments->firstWhere('file_name', 'report.docx');

    expect($image)->not->toBeNull()
        ->and($image->isImage())->toBeTrue()
        ->and($pdf->isPdf())->toBeTrue()
        ->and($word->isWordDocument())->toBeTrue();

    $response = $this->actingAs($owner)->get(route('tickets.show', $ticket));

    $response->assertOk()
        ->assertSee(route('tickets.attachments.preview', [$ticket, $image]), false)
        ->assertSee(route('tickets.attachments.download', [$ticket, $pdf]), false)
        ->assertSee(route('tickets.attachments.download', [$ticket, $word]), false)
        ->assertSee('PDF')
        ->assertSee('DOCX')
        ->assertSee('screenshot.png')
        ->assertSee(__('tickets.attachments.view'));
});

test('attachment-only messages are rendered', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $ticket = Ticket::factory()->create(['user_id' => $owner->getKey()]);

    $this->actingAs($owner)->post(route('tickets.messages.store', $ticket), [
        'attachments' => [
            UploadedFile::fake()->create('photo.png', 25, 'image/png'),
        ],
    ])->assertRedirect();

    $message = $ticket->messages()->firstOrFail();
    $attachment = TicketAttachment::query()->firstOrFail();

    expect($message->body)->toBeNull()
        ->and($attachment->message_id)->toBe($message->getKey());

    $this->actingAs($owner)->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee(route('tickets.attachments.preview', [$ticket, $attachment]), false);
});
