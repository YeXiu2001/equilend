<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Notifications\LoanOverdueNotification;
use App\Notifications\PenaltyImposedNotification;
use App\Notifications\ReservationStatusNotification;
use App\Notifications\AdvanceReturnReminderNotification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collegeCodes = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

        foreach ($collegeCodes as $collegeCode) {
            $college = Organization::where('code', $collegeCode)->first();

            if (! $college) {
                continue;
            }

            $admin    = User::where('email', sprintf('admin.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student1 = User::where('email', sprintf('student.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student2 = User::where('email', sprintf('student2.%s@equilend.test', mb_strtolower($collegeCode)))->first();

            if ($admin) {
                $this->seedNotification($admin, "admin-pending-res-{$collegeCode}", [
                    'type'            => 'reservation_request',
                    'title'           => 'New Reservation Request',
                    'message'         => "Student borrower submitted a new equipment reservation in {$college->code}.",
                    'organization_id' => $college->id,
                    'action_url'      => route('circulation.reservations', [], false),
                ], unread: true, minutesAgo: 18);

                $this->seedNotification($admin, "admin-damage-alert-{$collegeCode}", [
                    'type'            => 'penalty_imposed',
                    'title'           => 'Damage Penalty Recorded',
                    'message'         => "Post-loan inspection damage assessment logged for {$college->code} equipment.",
                    'organization_id' => $college->id,
                    'action_url'      => route('finance.penalties', [], false),
                ], unread: true, minutesAgo: 95);

                $this->seedNotification($admin, "admin-overdue-summary-{$collegeCode}", [
                    'type'            => 'loan_overdue',
                    'title'           => 'Overdue Loan Alert',
                    'message'         => "An active {$college->code} loan has passed its scheduled return deadline.",
                    'organization_id' => $college->id,
                    'action_url'      => route('circulation.desk', [], false),
                ], unread: false, minutesAgo: 420);
            }

            if ($student1) {
                $this->seedNotification($student1, "student1-reminder-{$collegeCode}", [
                    'type'            => 'return_reminder',
                    'title'           => 'Equipment Return Reminder',
                    'message'         => "Reminder: Your {$college->code} equipment loan is due in 1 hour. Please return it to the circulation counter.",
                    'organization_id' => $college->id,
                    'action_url'      => route('portal.loans', [], false),
                ], unread: true, minutesAgo: 12, notificationClass: AdvanceReturnReminderNotification::class);

                $this->seedNotification($student1, "student1-penalty-{$collegeCode}", [
                    'type'            => 'penalty_imposed',
                    'title'           => 'Penalty Assessed',
                    'message'         => "A penalty has been assessed on your {$college->code} account. Settle via Cash, GCash, or Maya.",
                    'organization_id' => $college->id,
                    'action_url'      => route('portal.penalties', [], false),
                ], unread: true, minutesAgo: 140, notificationClass: PenaltyImposedNotification::class);

                $this->seedNotification($student1, "student1-res-approved-{$collegeCode}", [
                    'type'            => 'reservation_status',
                    'title'           => 'Reservation Approved',
                    'message'         => "Your {$college->code} equipment reservation has been approved for pickup.",
                    'organization_id' => $college->id,
                    'decision'        => 'approved',
                    'action_url'      => route('portal.reservations', [], false),
                ], unread: false, minutesAgo: 1440, notificationClass: ReservationStatusNotification::class);
            }

            if ($student2) {
                $this->seedNotification($student2, "student2-overdue-{$collegeCode}", [
                    'type'            => 'loan_overdue',
                    'title'           => 'Equipment Loan Overdue',
                    'message'         => "Your {$college->code} loan is overdue. Borrowing privileges are suspended until returned and settled.",
                    'organization_id' => $college->id,
                    'action_url'      => route('portal.loans', [], false),
                ], unread: true, minutesAgo: 45, notificationClass: LoanOverdueNotification::class);
            }
        }
    }

    /**
     * Insert a notification record idempotently using a seed tag.
     *
     * @param  array<string, mixed>  $data
     */
    private function seedNotification(
        User $user,
        string $seedTag,
        array $data,
        bool $unread,
        int $minutesAgo,
        string $notificationClass = ReservationStatusNotification::class,
    ): void {
        $exists = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('data->seed_tag', $seedTag)
            ->exists();

        if ($exists) {
            return;
        }

        $timestamp = now()->subMinutes($minutesAgo);

        DB::table('notifications')->insert([
            'id'              => (string) Str::uuid(),
            'type'            => $notificationClass,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => json_encode(array_merge($data, ['seed_tag' => $seedTag]), JSON_THROW_ON_ERROR),
            'read_at'         => $unread ? null : $timestamp->copy()->addMinutes(15),
            'created_at'      => $timestamp,
            'updated_at'      => $timestamp,
        ]);
    }
}
