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
use Illuminate\Database\Seeder;
use App\Models\OrganizationPaymentMethod;

class PenaltySeeder extends Seeder
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

        $damageAssessments = [
            'CCS'  => ['amount' => 1500.00, 'notes' => 'Cracked LCD panel on laptop identified during post-loan check-in inspection.'],
            'CED'  => ['amount' => 550.00,  'notes' => 'Damaged multimedia projector remote control and bent HDMI adapter connector.'],
            'COET' => ['amount' => 600.00,  'notes' => 'Damaged oscilloscope probe kit (snapped ground clip and frayed BNC lead).'],
            'CSM'  => ['amount' => 700.00,  'notes' => 'Cracked borosilicate graduated cylinder and damaged digital sensor probe tip.'],
            'CASS' => ['amount' => 450.00,  'notes' => 'Damaged DSLR camera lens hood (bayonet mount cracked during field shoot).'],
        ];

        $waiverReasons = [
            'CCS'  => 'Medical emergency certificate submitted and verified by college dean.',
            'CED'  => 'Authorized department lab waiver for official campus teaching demo.',
            'COET' => 'Equipment failure attributed to normal wear-and-tear per lab technician.',
            'CSM'  => 'University-sanctioned field research delay approved by department chair.',
            'CASS' => 'Inclement weather campus closure prevented scheduled return.',
        ];

        foreach ($collegeCodes as $collegeCode) {
            $college = Organization::where('code', $collegeCode)->first();

            if (! $college) {
                continue;
            }

            $admin    = User::where('email', sprintf('admin.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student1 = User::where('email', sprintf('student.%s@equilend.test', mb_strtolower($collegeCode)))->first();
            $student2 = User::where('email', sprintf('student2.%s@equilend.test', mb_strtolower($collegeCode)))->first();

            if (! $admin || ! $student1 || ! $student2) {
                continue;
            }

            $orgGcash = $gcash
                ? OrganizationPaymentMethod::where('organization_id', $college->id)->where('payment_method_id', $gcash->id)->first()
                : null;
            $orgMaya = $maya
                ? OrganizationPaymentMethod::where('organization_id', $college->id)->where('payment_method_id', $maya->id)->first()
                : null;

            $sampleUnit   = ItemUnit::where('organization_id', $college->id)->first();
            $returnedLoan = Loan::where('organization_id', $college->id)
                ->where('status', LoanStatus::Returned)
                ->first();

            // 1. Active Overdue Penalty (tied to Tier 3 Overdue Loan)
            $overdueLoan = Loan::where('organization_id', $college->id)
                ->where('status', LoanStatus::Overdue)
                ->first();

            if ($overdueLoan !== null) {
                $daysOverdue = max(1, (int) ceil($overdueLoan->due_at->diffInHours(now()) / 24));
                $overdueFee  = max(100.00, $daysOverdue * 50.00);

                Penalty::updateOrCreate(
                    [
                        'organization_id' => $college->id,
                        'loan_id'         => $overdueLoan->id,
                        'type'            => PenaltyType::Overdue,
                    ],
                    [
                        'user_id'           => $overdueLoan->user_id,
                        'item_unit_id'      => $overdueLoan->item_unit_id,
                        'amount'            => $overdueFee,
                        'paid_amount'       => 0.00,
                        'balance_remaining' => $overdueFee,
                        'status'            => PenaltyStatus::Unpaid,
                        'assessment_notes'  => "Automated overdue fine ({$daysOverdue} day(s) past deadline at ₱50.00/day).",
                        'assessed_by'       => $admin->id,
                        'waived_reason'     => null,
                    ]
                );
            }

            // 2. Equipment Damage Assessment (Unpaid, 1 per college)
            $damageData = $damageAssessments[$collegeCode];
            Penalty::updateOrCreate(
                [
                    'organization_id'  => $college->id,
                    'user_id'          => $student1->id,
                    'type'             => PenaltyType::Damage,
                    'assessment_notes' => $damageData['notes'],
                ],
                [
                    'loan_id'           => $returnedLoan?->id,
                    'item_unit_id'      => $sampleUnit?->id,
                    'amount'            => $damageData['amount'],
                    'paid_amount'       => 0.00,
                    'balance_remaining' => $damageData['amount'],
                    'status'            => PenaltyStatus::Unpaid,
                    'assessed_by'       => $admin->id,
                    'waived_reason'     => null,
                ]
            );

            // 3. Settled Penalties & Official Receipts (3 per college: Cash, GCash, Maya)
            $settledCash = Penalty::updateOrCreate(
                [
                    'organization_id'  => $college->id,
                    'user_id'          => $student2->id,
                    'type'             => PenaltyType::Overdue,
                    'assessment_notes' => "{$collegeCode} late return fee settled in cash at circulation desk.",
                ],
                [
                    'loan_id'           => $returnedLoan?->id,
                    'item_unit_id'      => $sampleUnit?->id,
                    'amount'            => 150.00,
                    'paid_amount'       => 150.00,
                    'balance_remaining' => 0.00,
                    'status'            => PenaltyStatus::Paid,
                    'assessed_by'       => $admin->id,
                    'waived_reason'     => null,
                ]
            );

            if (! Payment::where('penalty_id', $settledCash->id)->exists()) {
                Payment::create([
                    'organization_id'                => $college->id,
                    'penalty_id'                     => $settledCash->id,
                    'user_id'                        => $student2->id,
                    'payment_method_id'              => $cash->id,
                    'organization_payment_method_id' => null,
                    'amount_paid'                    => 150.00,
                    'reference_number'               => null,
                    'paid_at'                        => now()->subDays(3),
                    'status'                         => PaymentStatus::Verified,
                    'received_by'                    => $admin->id,
                    'notes'                          => 'Cash payment collected at circulation desk.',
                ]);
            }

            $settledGcash = Penalty::updateOrCreate(
                [
                    'organization_id'  => $college->id,
                    'user_id'          => $student2->id,
                    'type'             => PenaltyType::Damage,
                    'assessment_notes' => "{$collegeCode} minor accessory replacement fee settled via GCash.",
                ],
                [
                    'loan_id'           => $returnedLoan?->id,
                    'item_unit_id'      => $sampleUnit?->id,
                    'amount'            => 300.00,
                    'paid_amount'       => 300.00,
                    'balance_remaining' => 0.00,
                    'status'            => PenaltyStatus::Paid,
                    'assessed_by'       => $admin->id,
                    'waived_reason'     => null,
                ]
            );

            if (! Payment::where('penalty_id', $settledGcash->id)->exists()) {
                Payment::create([
                    'organization_id'                => $college->id,
                    'penalty_id'                     => $settledGcash->id,
                    'user_id'                        => $student2->id,
                    'payment_method_id'              => $gcash?->id,
                    'organization_payment_method_id' => $orgGcash?->id,
                    'amount_paid'                    => 300.00,
                    'reference_number'               => "GC-2026-{$collegeCode}-001",
                    'paid_at'                        => now()->subDays(2),
                    'status'                         => PaymentStatus::Verified,
                    'received_by'                    => $admin->id,
                    'notes'                          => "GCash transfer verified against {$collegeCode} official wallet.",
                ]);
            }

            $settledMaya = Penalty::updateOrCreate(
                [
                    'organization_id'  => $college->id,
                    'user_id'          => $student2->id,
                    'type'             => PenaltyType::Other,
                    'assessment_notes' => "{$collegeCode} unreturned carrying pouch replacement settled via Maya.",
                ],
                [
                    'loan_id'           => $returnedLoan?->id,
                    'item_unit_id'      => $sampleUnit?->id,
                    'amount'            => 200.00,
                    'paid_amount'       => 200.00,
                    'balance_remaining' => 0.00,
                    'status'            => PenaltyStatus::Paid,
                    'assessed_by'       => $admin->id,
                    'waived_reason'     => null,
                ]
            );

            if (! Payment::where('penalty_id', $settledMaya->id)->exists()) {
                Payment::create([
                    'organization_id'                => $college->id,
                    'penalty_id'                     => $settledMaya->id,
                    'user_id'                        => $student2->id,
                    'payment_method_id'              => $maya?->id,
                    'organization_payment_method_id' => $orgMaya?->id,
                    'amount_paid'                    => 200.00,
                    'reference_number'               => "MY-2026-{$collegeCode}-002",
                    'paid_at'                        => now()->subDay(),
                    'status'                         => PaymentStatus::Verified,
                    'received_by'                    => $admin->id,
                    'notes'                          => "Maya transfer verified against {$collegeCode} official account.",
                ]);
            }

            // 4. Waived Penalty (1 per college)
            Penalty::updateOrCreate(
                [
                    'organization_id'  => $college->id,
                    'user_id'          => $student2->id,
                    'type'             => PenaltyType::Overdue,
                    'assessment_notes' => "{$collegeCode} overdue fine reviewed and excused by college administration.",
                ],
                [
                    'loan_id'           => $returnedLoan?->id,
                    'item_unit_id'      => $sampleUnit?->id,
                    'amount'            => 250.00,
                    'paid_amount'       => 0.00,
                    'balance_remaining' => 0.00,
                    'status'            => PenaltyStatus::Waived,
                    'assessed_by'       => $admin->id,
                    'waived_reason'     => $waiverReasons[$collegeCode],
                ]
            );
        }
    }
}
