<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Penalty;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Models\Reservation;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Enums\ReservationStatus;
use App\Livewire\Circulation\Desk;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use App\Livewire\Circulation\BookingRequests;
use App\Notifications\LoanOverdueNotification;
use App\Notifications\PenaltyImposedNotification;
use App\Notifications\ReservationStatusNotification;
use App\Notifications\AdvanceReturnReminderNotification;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('dispatches AdvanceReturnReminderNotification once for loans due within one hour', function (): void {
    Notification::fake();

    $org     = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $staff   = User::factory()->create();
    $student = User::factory()->create();

    $item        = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Canon EOS R50']);
    $unitDueSoon = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CCS-CAM-001',
    ]);
    $unitDueLater = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CCS-CAM-002',
    ]);

    $loanDueSoon = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unitDueSoon->id,
        'issued_by'       => $staff->id,
        'borrowed_at'     => now()->subHours(3),
        'due_at'          => now()->addMinutes(30),
        'status'          => LoanStatus::Active,
    ]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unitDueLater->id,
        'issued_by'       => $staff->id,
        'borrowed_at'     => now()->subHour(),
        'due_at'          => now()->addHours(4),
        'status'          => LoanStatus::Active,
    ]);

    $this->artisan('equilend:check-reminders')
        ->assertSuccessful();

    Notification::assertSentToTimes($student, AdvanceReturnReminderNotification::class, 1);
    Notification::assertSentTo(
        $student,
        AdvanceReturnReminderNotification::class,
        function (AdvanceReturnReminderNotification $notification, array $channels) use ($loanDueSoon, $student): bool {
            $payload = $notification->toArray($student);

            return $channels === ['database', 'mail']
                && $notification->loan->id === $loanDueSoon->id
                && $payload['item_name'] === 'Canon EOS R50'
                && $payload['asset_tag'] === 'CCS-CAM-001';
        }
    );

    expect($loanDueSoon->fresh()->reminder_sent_at)->not->toBeNull();

    // Running again should not send duplicate reminders
    $this->artisan('equilend:check-reminders')
        ->assertSuccessful();

    Notification::assertSentToTimes($student, AdvanceReturnReminderNotification::class, 1);
});

it('marks past-due loans as overdue, creates penalty, locks borrowing, and writes database notification', function (): void {
    $org     = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);
    $staff   = User::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Fluke Multimeter']);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'COET-MM-009',
    ]);

    $overdueLoan = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $staff->id,
        'borrowed_at'     => now()->subDays(3),
        'due_at'          => now()->subHours(5),
        'status'          => LoanStatus::Active,
    ]);

    expect($student->canBorrow())->toBeFalse();

    $this->artisan('equilend:check-overdue')
        ->assertSuccessful();

    $overdueLoan->refresh();

    expect($overdueLoan->status)->toBe(LoanStatus::Overdue);
    expect($student->canBorrow())->toBeFalse();
    expect($student->getOutstandingFinesTotal())->toBe(0.00);

    $penalty = Penalty::where('loan_id', $overdueLoan->id)->first();
    expect($penalty)->not->toBeNull()
        ->and($penalty->type)->toBe(PenaltyType::Overdue)
        ->and($penalty->status)->toBe(PenaltyStatus::PendingApproval)
        ->and((float) $penalty->amount)->toBe(50.00);

    expect($student->unreadNotifications()->count())->toBe(1);

    $dbNotification = $student->unreadNotifications()->first();
    expect($dbNotification->type)->toBe(LoanOverdueNotification::class)
        ->and($dbNotification->data['type'])->toBe('loan_overdue')
        ->and($dbNotification->data['loan_id'])->toBe($overdueLoan->id)
        ->and($dbNotification->data['asset_tag'])->toBe('COET-MM-009');
});

it('creates unread database notification when a penalty is imposed via Circulation Desk check-in', function (): void {
    $org   = Organization::factory()->create(['code' => 'CCS']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id]);
    $unit = ItemUnit::factory()->forItem($item)->available()->create(['organization_id' => $org->id]);

    $loan = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $admin->id,
        'status'          => LoanStatus::Active,
        'returned_at'     => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(Desk::class)
        ->set('inspectingLoanId', $loan->id)
        ->set('assessPenalty', true)
        ->set('penaltyType', PenaltyType::Damage->value)
        ->set('penaltyAmount', '250.00')
        ->set('penaltyNotes', 'Cracked lens filter ring upon inspection.')
        ->call('processCheckin')
        ->assertHasNoErrors();

    expect($student->unreadNotifications()->count())->toBe(1);

    $dbNotification = $student->unreadNotifications()->first();
    expect($dbNotification->type)->toBe(PenaltyImposedNotification::class)
        ->and($dbNotification->data['type'])->toBe('penalty_imposed')
        ->and((float) $dbNotification->data['amount'])->toBe(250.00);
});

it('dispatches unread database notification when a reservation is approved or rejected', function (): void {
    $org   = Organization::factory()->create(['code' => 'CSM']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Microscope Pro']);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CSM-MIC-001',
    ]);

    $approvedRes = Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $rejectedRes = Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $this->actingAs($admin);

    Livewire::test(BookingRequests::class)
        ->call('openApproveModal', $approvedRes->id)
        ->set('assignedUnitId', $unit->id)
        ->call('confirmApproval')
        ->assertHasNoErrors();

    Livewire::test(BookingRequests::class)
        ->call('openRejectModal', $rejectedRes->id)
        ->set('rejectionRemarks', 'Equipment reserved for scheduled laboratory exam.')
        ->call('confirmRejection')
        ->assertHasNoErrors();

    $notifications = $student->unreadNotifications()->orderBy('created_at')->get();
    expect($notifications)->toHaveCount(2);

    expect($notifications[0]->type)->toBe(ReservationStatusNotification::class)
        ->and($notifications[0]->data['decision'])->toBe('approved');

    expect($notifications[1]->type)->toBe(ReservationStatusNotification::class)
        ->and($notifications[1]->data['decision'])->toBe('rejected')
        ->and($notifications[1]->data['rejection_remarks'])->toBe('Equipment reserved for scheduled laboratory exam.');
});
