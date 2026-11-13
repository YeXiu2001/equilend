<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use Illuminate\Database\Seeder;
use App\Enums\ItemUnitCondition;
use App\Enums\ReservationStatus;
use App\Models\EquipmentSchedule;
use App\Enums\EquipmentScheduleType;

class CirculationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collegeCodes = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

        $purposesByCollege = [
            'CCS' => [
                'Capstone Project Defense - IoT Smart Agriculture System',
                'Mobile App Development Testing with High-Performance Hardware',
                'Network Security Vulnerability Scanning in Isolated Lab',
                'Inter-Collegiate Programming Contest & Hackathon Showcase',
            ],
            'CED' => [
                'Curriculum Material Digital Recording & Teaching Demo',
                'Classroom Micro-Teaching Audio-Visual Presentation',
                'Educational Technology Workshop Equipment Demonstration',
                'Regional Teachers Congress Interactive Multi-Media Display',
            ],
            'COET' => [
                'Autonomous Robotics Navigation Field Trials',
                'Mechanical Vibration & Strain Gauge Data Logging',
                'Microcontroller Sensor Calibration for Thesis Prototype',
                'Engineering Accreditation Facility Demonstration & Inspection',
            ],
            'CSM' => [
                'Botanical Specimen High-Resolution Digital Microscopy',
                'Environmental Chemistry Spectrophotometric Analysis',
                'Physics Department Mechanics & Dynamics Laboratory Experiments',
                'Inter-Campus Science Olympiad Experimental Testing Station',
            ],
            'CASS' => [
                'Documentary Film Production & On-Site Interviews',
                'Broadcast Journalism Audio Recording Session',
                'Cultural Heritage Digital Archiving & Studio Photography',
                'University Theater Arts Production Stage Equipment Setup',
            ],
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

            $items = Item::where('organization_id', $college->id)->get();

            if ($items->isEmpty()) {
                continue;
            }

            // Retrieve available units for this college
            $availableUnits = ItemUnit::where('organization_id', $college->id)
                ->where('status', ItemUnitStatus::Available)
                ->get();

            if ($availableUnits->count() < 7) {
                continue;
            }

            $activeUnit1    = $availableUnits->get(0);
            $activeUnit2    = $availableUnits->get(1);
            $overdueUnit    = $availableUnits->get(2);
            $reservedUnit   = $availableUnits->get(3);
            $completedUnit1 = $availableUnits->get(4);
            $completedUnit2 = $availableUnits->get(5);
            $completedUnit3 = $availableUnits->get(6);

            // 1. Active Loans (2 per college)
            // Loan 1: Due tomorrow
            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $activeUnit1->id,
                    'status'          => LoanStatus::Active,
                ],
                [
                    'user_id'            => $student1->id,
                    'item_id'            => $activeUnit1->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => null,
                    'borrowed_at'        => now()->subHours(5),
                    'due_at'             => now()->addDay()->setTime(17, 0),
                    'returned_at'        => null,
                    'checkout_condition' => $activeUnit1->condition,
                    'checkin_condition'  => null,
                    'notes'              => 'Issued for academic laboratory work and project prototype testing.',
                ]
            );
            $activeUnit1->update(['status' => ItemUnitStatus::Borrowed]);

            // Loan 2: Due today
            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $activeUnit2->id,
                    'status'          => LoanStatus::Active,
                ],
                [
                    'user_id'            => $student2->id,
                    'item_id'            => $activeUnit2->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => null,
                    'borrowed_at'        => now()->subHours(8),
                    'due_at'             => now()->setTime(18, 0),
                    'returned_at'        => null,
                    'checkout_condition' => $activeUnit2->condition,
                    'checkin_condition'  => null,
                    'notes'              => 'Desk walk-in loan for scheduled collegiate lecture.',
                ]
            );
            $activeUnit2->update(['status' => ItemUnitStatus::Borrowed]);

            // 2. Overdue Loans (1 per college)
            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $overdueUnit->id,
                    'status'          => LoanStatus::Overdue,
                ],
                [
                    'user_id'            => $student1->id,
                    'item_id'            => $overdueUnit->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => null,
                    'borrowed_at'        => now()->subDays(4)->setTime(9, 30),
                    'due_at'             => now()->subDays(1)->setTime(17, 0),
                    'returned_at'        => null,
                    'checkout_condition' => ItemUnitCondition::Good,
                    'checkin_condition'  => null,
                    'notes'              => 'Automated notification sent to borrower; loan past return deadline.',
                ]
            );
            $overdueUnit->update(['status' => ItemUnitStatus::Borrowed]);

            // 3. Approved Upcoming Booking (1 per college)
            Reservation::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'user_id'         => $student2->id,
                    'item_id'         => $reservedUnit->item_id,
                    'status'          => ReservationStatus::Approved,
                ],
                [
                    'item_unit_id' => $reservedUnit->id,
                    'start_time'   => now()->addDays(2)->setTime(8, 0),
                    'end_time'     => now()->addDays(4)->setTime(17, 0),
                    'purpose'      => $purposesByCollege[$collegeCode][3],
                    'approved_by'  => $admin->id,
                    'approved_at'  => now()->subDay(),
                ]
            );
            $reservedUnit->update(['status' => ItemUnitStatus::Reserved]);

            // 4. Pending Reservation Requests (3 per college)
            $pendingItem1 = $items->first();
            $pendingItem2 = $items->skip(1)->first() ?? $pendingItem1;
            $pendingItem3 = $items->skip(2)->first() ?? $pendingItem1;

            Reservation::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'user_id'         => $student1->id,
                    'item_id'         => $pendingItem1->id,
                    'status'          => ReservationStatus::Pending,
                ],
                [
                    'item_unit_id'      => null,
                    'start_time'        => now()->addDays(2)->setTime(9, 0),
                    'end_time'          => now()->addDays(4)->setTime(17, 0),
                    'purpose'           => $purposesByCollege[$collegeCode][0],
                    'rejection_remarks' => null,
                    'approved_by'       => null,
                    'approved_at'       => null,
                ]
            );

            Reservation::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'user_id'         => $student2->id,
                    'item_id'         => $pendingItem2->id,
                    'status'          => ReservationStatus::Pending,
                ],
                [
                    'item_unit_id'      => null,
                    'start_time'        => now()->addDays(3)->setTime(13, 0),
                    'end_time'          => now()->addDays(5)->setTime(17, 0),
                    'purpose'           => $purposesByCollege[$collegeCode][1],
                    'rejection_remarks' => null,
                    'approved_by'       => null,
                    'approved_at'       => null,
                ]
            );

            Reservation::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'user_id'         => $student1->id,
                    'item_id'         => $pendingItem3->id,
                    'purpose'         => $purposesByCollege[$collegeCode][2],
                ],
                [
                    'item_unit_id'      => null,
                    'start_time'        => now()->addDays(5)->setTime(8, 0),
                    'end_time'          => now()->addDays(7)->setTime(17, 0),
                    'status'            => ReservationStatus::Pending,
                    'rejection_remarks' => null,
                    'approved_by'       => null,
                    'approved_at'       => null,
                ]
            );

            // 5. Historical Completed Loans (3 per college)
            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $completedUnit1->id,
                    'borrowed_at'     => now()->subDays(14)->setTime(10, 0),
                ],
                [
                    'user_id'            => $student1->id,
                    'item_id'            => $completedUnit1->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => $admin->id,
                    'due_at'             => now()->subDays(12)->setTime(17, 0),
                    'returned_at'        => now()->subDays(12)->setTime(15, 30),
                    'checkout_condition' => ItemUnitCondition::Good,
                    'checkin_condition'  => ItemUnitCondition::Good,
                    'status'             => LoanStatus::Returned,
                    'notes'              => 'Returned on time and in full working order.',
                ]
            );

            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $completedUnit2->id,
                    'borrowed_at'     => now()->subDays(10)->setTime(13, 0),
                ],
                [
                    'user_id'            => $student2->id,
                    'item_id'            => $completedUnit2->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => $admin->id,
                    'due_at'             => now()->subDays(8)->setTime(17, 0),
                    'returned_at'        => now()->subDays(8)->setTime(16, 45),
                    'checkout_condition' => ItemUnitCondition::Good,
                    'checkin_condition'  => ItemUnitCondition::Good,
                    'status'             => LoanStatus::Returned,
                    'notes'              => 'Routine return inspected by circulation desk officer.',
                ]
            );

            Loan::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'item_unit_id'    => $completedUnit3->id,
                    'borrowed_at'     => now()->subDays(7)->setTime(9, 0),
                ],
                [
                    'user_id'            => $student1->id,
                    'item_id'            => $completedUnit3->item_id,
                    'issued_by'          => $admin->id,
                    'received_by'        => $admin->id,
                    'due_at'             => now()->subDays(5)->setTime(17, 0),
                    'returned_at'        => now()->subDays(5)->setTime(16, 0),
                    'checkout_condition' => ItemUnitCondition::Good,
                    'checkin_condition'  => ItemUnitCondition::Good,
                    'status'             => LoanStatus::Returned,
                    'notes'              => 'Returned with all accessories and cables intact.',
                ]
            );

            // 6. Maintenance & Calendar Blackouts (1 Maintenance + 1 Blackout per college)
            $maintenanceUnit = ItemUnit::where('organization_id', $college->id)
                ->where('status', ItemUnitStatus::UnderMaintenance)
                ->first();

            EquipmentSchedule::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'title'           => sprintf('%s Preventive Maintenance Window', $collegeCode),
                    'type'            => EquipmentScheduleType::Maintenance,
                ],
                [
                    'item_id'      => $maintenanceUnit?->item_id ?? $items->first()->id,
                    'item_unit_id' => $maintenanceUnit?->id,
                    'reason'       => 'Routine sensor recalibration, firmware diagnostics, and preventive inspection.',
                    'start_time'   => now()->addDays(1)->setTime(9, 0),
                    'end_time'     => now()->addDays(3)->setTime(17, 0),
                ]
            );

            EquipmentSchedule::updateOrCreate(
                [
                    'organization_id' => $college->id,
                    'title'           => sprintf('%s Midterm Examination Blackout', $collegeCode),
                    'type'            => EquipmentScheduleType::Blackout,
                ],
                [
                    'item_id'      => null,
                    'item_unit_id' => null,
                    'reason'       => 'Circulation desk lending and equipment reservations suspended during collegiate midterm exam week.',
                    'start_time'   => now()->addDays(8)->setTime(8, 0),
                    'end_time'     => now()->addDays(14)->setTime(18, 0),
                ]
            );
        }
    }
}
