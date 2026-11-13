<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Enums\ItemUnitCondition;
use App\Models\OrganizationPaymentMethod;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collegeCodes = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

        $cash = PaymentMethod::firstOrCreate(
            ['payment_code' => 'cash'],
            ['name' => 'Cash', 'description' => 'Over-the-counter Cash Payment at Circulation Desk', 'is_active' => true]
        );
        $gcash = PaymentMethod::where('payment_code', 'gcash')->first();
        $maya  = PaymentMethod::where('payment_code', 'maya')->first();

        $channels = array_values(array_filter([$cash, $gcash, $maya]));

        // Deterministic day offsets spanning 3 months (days 4..88 ago) for 30 loans per college
        $dayOffsets = [
            3, 5, 7, 9, 11, 13, 15, 18, 20, 22,
            25, 28, 31, 34, 37, 40, 43, 46, 49, 52,
            56, 60, 64, 68, 72, 76, 80, 83, 86, 88,
        ];

        // Realistic campus borrowing hours (peak between 9 AM and 4 PM)
        $borrowHours = [8, 9, 9, 10, 10, 11, 13, 13, 14, 15, 16, 10, 11, 14, 9];

        foreach ($collegeCodes as $collegeIndex => $collegeCode) {
            $college = Organization::where('code', $collegeCode)->first();

            if (! $college) {
                continue;
            }

            // Skip if historical analytics data already seeded for this college
            if (Loan::where('organization_id', $college->id)->where('notes', 'like', '[AnalyticsSeeder]%')->exists()) {
                continue;
            }

            $admin    = User::where('email', sprintf('admin.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student1 = User::where('email', sprintf('student.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student2 = User::where('email', sprintf('student2.%s@equilend.test', mb_strtolower($collegeCode)))->first();

            if (! $admin || ! $student1 || ! $student2) {
                continue;
            }

            $units = ItemUnit::with('item')
                ->where('organization_id', $college->id)
                ->get();

            if ($units->isEmpty()) {
                continue;
            }

            $students     = [$student1, $student2];
            $unitCount    = $units->count();
            $channelCount = count($channels);

            foreach ($dayOffsets as $index => $daysAgo) {
                // Bias unit selection toward the first few items to create clear "Most Borrowed Equipment" trends
                $unitIndex = ($index % 5 < 3)
                    ? ($index % min(3, $unitCount))
                    : ($index % $unitCount);

                $unit     = $units->get($unitIndex);
                $borrower = $students[$index % 2];
                $hour     = $borrowHours[$index % count($borrowHours)];

                $borrowedAt = Carbon::now()
                    ->subDays($daysAgo + ($collegeIndex % 3))
                    ->setTime($hour, ($index * 13) % 60);

                $durationHours = 4 + (($index % 4) * 2); // 4h to 10h
                $dueAt         = $borrowedAt->copy()->addHours($durationHours);

                // ~85% on-time (25 of 30), ~15% late (5 of 30)
                $isLate = ($index % 6 === 5);

                $returnedAt = $isLate
                    ? $dueAt->copy()->addHours(12 + (($index % 3) * 24))
                    : $dueAt->copy()->subMinutes(15 + (($index % 5) * 20));

                $loan = new Loan([
                    'organization_id'    => $college->id,
                    'reservation_id'     => null,
                    'user_id'            => $borrower->id,
                    'item_id'            => $unit->item_id,
                    'item_unit_id'       => $unit->id,
                    'issued_by'          => $admin->id,
                    'received_by'        => $admin->id,
                    'borrowed_at'        => $borrowedAt,
                    'due_at'             => $dueAt,
                    'returned_at'        => $returnedAt,
                    'checkout_condition' => ItemUnitCondition::Good,
                    'checkin_condition'  => ItemUnitCondition::Good,
                    'status'             => LoanStatus::Returned,
                    'notes'              => sprintf('[AnalyticsSeeder] Historical %s circulation #%d', $collegeCode, $index + 1),
                ]);
                $loan->created_at = $borrowedAt;
                $loan->updated_at = $returnedAt;
                $loan->save();

                if ($isLate && $channelCount > 0) {
                    $daysLate   = max(1, (int) ceil($dueAt->diffInHours($returnedAt) / 24));
                    $fineAmount = round($daysLate * 50.00, 2);

                    $penalty = new Penalty([
                        'organization_id'   => $college->id,
                        'loan_id'           => $loan->id,
                        'user_id'           => $borrower->id,
                        'item_unit_id'      => $unit->id,
                        'type'              => PenaltyType::Overdue,
                        'amount'            => $fineAmount,
                        'paid_amount'       => $fineAmount,
                        'balance_remaining' => 0.00,
                        'status'            => PenaltyStatus::Paid,
                        'assessment_notes'  => sprintf('[AnalyticsSeeder] Late return (%d day(s) overdue).', $daysLate),
                        'assessed_by'       => $admin->id,
                    ]);
                    $penalty->created_at = $returnedAt;
                    $penalty->updated_at = $returnedAt;
                    $penalty->save();

                    $method    = $channels[($index + $collegeIndex) % $channelCount];
                    $orgMethod = OrganizationPaymentMethod::where('organization_id', $college->id)
                        ->where('payment_method_id', $method->id)
                        ->first();

                    $paidAt  = $returnedAt->copy()->addHours(2);
                    $payment = new Payment([
                        'organization_id'                => $college->id,
                        'penalty_id'                     => $penalty->id,
                        'user_id'                        => $borrower->id,
                        'payment_method_id'              => $method->id,
                        'organization_payment_method_id' => $orgMethod?->id,
                        'amount_paid'                    => $fineAmount,
                        'reference_number'               => $method->payment_code === 'cash'
                            ? null
                            : sprintf('%s-%s-%04d', mb_strtoupper($method->payment_code), $collegeCode, $loan->id),
                        'receipt_number' => Payment::generateReceiptNumber(),
                        'paid_at'        => $paidAt,
                        'status'         => PaymentStatus::Verified,
                        'received_by'    => $admin->id,
                        'notes'          => sprintf('[AnalyticsSeeder] Settled via %s', $method->name),
                    ]);
                    $payment->created_at = $paidAt;
                    $payment->updated_at = $paidAt;
                    $payment->save();
                }
            }
        }
    }
}
