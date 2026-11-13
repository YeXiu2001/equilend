<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use App\Models\ItemUnit;
use Illuminate\Support\Str;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\InventoryLogType;
use Illuminate\Database\Seeder;
use App\Enums\ItemUnitCondition;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryMap = Category::pluck('id', 'name')->all();

        $catalog = [
            'CCS' => [
                'admin_email' => 'admin.ccs@equilend.test',
                'items'       => [
                    [
                        'name'          => 'Apple MacBook Air M2',
                        'brand'         => 'Apple',
                        'model_number'  => 'MLXY3PP/A (Space Gray)',
                        'category_name' => 'Computing Devices & Laptops',
                        'description'   => '13.6-inch Liquid Retina display, Apple M2 chip with 8-core CPU and 10-core GPU, 16GB unified memory, 512GB SSD storage for software engineering and mobile development.',
                        'specs'         => [
                            'processor' => 'Apple M2 (8-core CPU / 10-core GPU)',
                            'ram'       => '16GB Unified Memory',
                            'storage'   => '512GB SSD',
                            'display'   => '13.6-inch Liquid Retina (2560 x 1664)',
                            'os'        => 'macOS Sequoia',
                        ],
                        'prefix'    => 'CCS-LPT',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Keyboard keycap replacement and battery calibration in progress.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Cracked trackpad and casing dent after laboratory transit incident.'],
                        ],
                    ],
                    [
                        'name'          => 'Arduino Engineering Starter Kits (Rev 2)',
                        'brand'         => 'Arduino',
                        'model_number'  => 'AKX00022',
                        'category_name' => 'Electronics, Microcontrollers & Robotics',
                        'description'   => 'Comprehensive mechatronics kit including Arduino Nano 33 IoT, motors, optical sensors, li-ion battery holders, and MATLAB/Simulink curriculum access.',
                        'specs'         => [
                            'board'      => 'Arduino Nano 33 IoT',
                            'sensors'    => 'Ultrasonic distance, optical line tracker, IMU accelerometer',
                            'actuators'  => 'DC gear motors with encoders, servo motor',
                            'curriculum' => 'MATLAB and Simulink 1-year individual license',
                        ],
                        'prefix'    => 'CCS-ARD',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Breadboard jumper wires and loose DC motor wiring repair.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Burnt Nano 33 IoT microcontroller IC from reverse voltage supply.'],
                        ],
                    ],
                    [
                        'name'          => 'Cisco Catalyst 24-Port Gigabit Switches',
                        'brand'         => 'Cisco',
                        'model_number'  => 'WS-C2960X-24TS-L',
                        'category_name' => 'Computing Devices & Laptops',
                        'description'   => 'Enterprise managed Layer 2 Gigabit rackmount switch for network administration laboratory training, CCNA routing, and VLAN configurations.',
                        'specs'         => [
                            'ports'         => '24 x 10/100/1000 Gigabit Ethernet Ports',
                            'uplink'        => '4 x 1G SFP Uplinks',
                            'forwarding'    => '71.4 Mpps',
                            'switching_cap' => '216 Gbps',
                        ],
                        'prefix'    => 'CCS-SW',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Cooling fan bearing replacement and Cisco IOS firmware upgrade.'],
                        ],
                    ],
                    [
                        'name'          => 'Epson PowerLite 1080p Projectors',
                        'brand'         => 'Epson',
                        'model_number'  => 'PowerLite 1781W',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Ultra-slim wireless 3LCD multimedia projector with 3,200 lumens of color brightness and automatic vertical/horizontal keystone correction.',
                        'specs'         => [
                            'brightness' => '3,200 Lumens (Color and White Brightness)',
                            'resolution' => 'WXGA / 1080p Full HD support',
                            'inputs'     => 'HDMI (MHL), VGA, USB Type-A/B, Wireless 802.11b/g/n',
                        ],
                        'prefix'    => 'CCS-PRJ',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Optical prism cleaning and lamp module replacement.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'HDMI port pins crushed by borrower force; video signal broken.'],
                        ],
                    ],
                    [
                        'name'          => 'Meta Quest 2 VR Development Headsets',
                        'brand'         => 'Meta',
                        'model_number'  => 'Quest 2 (256GB All-in-One)',
                        'category_name' => 'Computing Devices & Laptops',
                        'description'   => 'Standalone wireless virtual reality headset with 6DOF inside-out spatial tracking and Touch controllers for VR simulation and Game Dev capstones.',
                        'specs'         => [
                            'storage'      => '256GB High-Speed Flash',
                            'refresh_rate' => '90Hz / 120Hz Fast-Switch LCD',
                            'resolution'   => '1832 x 1920 pixels per eye',
                            'processor'    => 'Qualcomm Snapdragon XR2 Platform',
                        ],
                        'prefix'    => 'CCS-VR',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Left Touch controller analog stick drift recalibration.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Scratched Fresnel lenses and damaged head strap mount.'],
                        ],
                    ],
                ],
            ],

            'CED' => [
                'admin_email' => 'admin.ced@equilend.test',
                'items'       => [
                    [
                        'name'          => 'AVer Document Cameras & Visualizers',
                        'brand'         => 'AVer',
                        'model_number'  => 'AVerVision U50 USB Visualizer',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Portable flexible gooseneck document camera designed for interactive classroom demonstrations, STEM lesson recording, and specimen projection.',
                        'specs'         => [
                            'sensor'     => '5 Megapixel CMOS Sensor',
                            'frame_rate' => 'Full HD 1080p at 30fps',
                            'lighting'   => 'Built-in LED head illumination',
                            'interface'  => 'USB 2.0 plug-and-play',
                        ],
                        'prefix'    => 'CED-DOC',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Gooseneck tension joint tightening and lens inspection.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Snapped USB connection cord and camera housing fracture.'],
                        ],
                    ],
                    [
                        'name'          => 'Logitech Spotlight Wireless Presenters',
                        'brand'         => 'Logitech',
                        'model_number'  => 'Spotlight Slate 910-004654',
                        'category_name' => 'Computing Devices & Laptops',
                        'description'   => 'Advanced presentation remote with virtual cursor highlighting, magnification, gesture volume controls, and 30-meter wireless operational range.',
                        'specs'         => [
                            'range'   => '30 meters (100 feet)',
                            'battery' => 'USB-C fast rechargeable (Up to 3 months runtime)',
                            'sensors' => '3D accelerometer and gyroscope',
                        ],
                        'prefix'    => 'CED-PRE',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'USB receiver pairing firmware update.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Defective internal Li-Po battery not taking recharge.'],
                        ],
                    ],
                    [
                        'name'          => 'Fender Portable PA Sound Systems',
                        'brand'         => 'Fender',
                        'model_number'  => 'Passport Conference Series 2',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'All-in-one portable suitcase sound system with 175-watt Class-D power amplifier, 5-channel mixer, dual full-range speakers, and Bluetooth streaming.',
                        'specs'         => [
                            'power'    => '175 Watts Class-D Power',
                            'speakers' => 'Two 5.25-inch woofers, two 2.75-inch tweeters',
                            'inputs'   => '3 x XLR/TRS mic lines, 1 x 3.5mm Aux / Bluetooth',
                        ],
                        'prefix'    => 'CED-PAS',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Speaker latch fastener repair and channel 2 pot cleaning.'],
                        ],
                    ],
                    [
                        'name'          => 'Heavy-Duty Telescopic Tripods',
                        'brand'         => 'Manfrotto',
                        'model_number'  => 'Compact Action Aluminum (MKCOMPACTACN-BK)',
                        'category_name' => 'Surveying & Field Hardware',
                        'description'   => 'Ergonomic aluminum 5-section telescopic tripod with joystick head and circular quick-release plate for classroom recording cameras.',
                        'specs'         => [
                            'max_height' => '165 cm',
                            'payload'    => '1.5 kg - 3.0 kg maximum payload',
                            'weight'     => '1.16 kg ultra-lightweight aluminum',
                        ],
                        'prefix'    => 'CED-TRI',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Leg clamp collar rubber grip replacement.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Bent third-stage aluminum telescopic leg; cannot collapse.'],
                        ],
                    ],
                ],
            ],

            'COET' => [
                'admin_email' => 'admin.coet@equilend.test',
                'items'       => [
                    [
                        'name'          => 'Rigol Digital Storage Oscilloscopes (100MHz)',
                        'brand'         => 'Rigol',
                        'model_number'  => 'DS1102Z-E',
                        'category_name' => 'Electronics, Microcontrollers & Robotics',
                        'description'   => 'Two-channel 100MHz bench oscilloscope with 1 GSa/s real-time sample rate, 24 Mpts memory depth, and UltraVision hardware waveform recording.',
                        'specs'         => [
                            'bandwidth'    => '100 MHz (2 analog channels)',
                            'sample_rate'  => '1 GSa/s maximum real-time',
                            'memory_depth' => '24 Mpts standard',
                            'display'      => '7-inch WVGA (800x480) TFT LCD',
                        ],
                        'prefix'    => 'COET-OSC',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Channel 1 BNC connector calibration and probe zeroing.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Shattered LCD front panel glass following accidental drop.'],
                        ],
                    ],
                    [
                        'name'          => 'Fluke 87V True-RMS Digital Multimeters',
                        'brand'         => 'Fluke',
                        'model_number'  => 'Fluke 87V Industrial',
                        'category_name' => 'Electronics, Microcontrollers & Robotics',
                        'description'   => 'Heavy-duty industrial multimeters with True-RMS AC voltage/current measurement, built-in thermometer, and low-pass filter for VFD motor drives.',
                        'specs'         => [
                            'voltage_dc' => 'Up to 1000V (0.05% basic accuracy)',
                            'current'    => 'Up to 10A (20A for 30 seconds maximum)',
                            'safety'     => 'CAT III 1000V, CAT IV 600V safety certified',
                        ],
                        'prefix'    => 'COET-DMM',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Blown internal 11A fast-acting ceramic fuse replacement.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Melted test lead probes and burned range rotary selector.'],
                        ],
                    ],
                    [
                        'name'          => 'Korad Programmable DC Bench Power Supplies',
                        'brand'         => 'Korad',
                        'model_number'  => 'KD3005D Precision Digital',
                        'category_name' => 'Electronics, Microcontrollers & Robotics',
                        'description'   => 'Regulated DC laboratory power supply with 0-30V / 0-5A output, digital 4-digit LED displays, low ripple noise, and over-current protection.',
                        'specs'         => [
                            'voltage'    => '0 to 30 Volts DC variable',
                            'current'    => '0 to 5 Amperes DC variable',
                            'resolution' => '10mV / 1mA precision LED readouts',
                        ],
                        'prefix'    => 'COET-PWR',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Rear thermal exhaust fan cleaning and binding post tightening.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Transformer short-circuit caused by external surge.'],
                        ],
                    ],
                    [
                        'name'          => 'Topcon Digital Total Station Survey Equipment',
                        'brand'         => 'Topcon',
                        'model_number'  => 'GM-50 Series (GM-52)',
                        'category_name' => 'Surveying & Field Hardware',
                        'description'   => 'High-accuracy dual-axis geodetic total station with fast EDM, 500m reflectorless range, 4000m prism range, and Bluetooth field data transmission.',
                        'specs'         => [
                            'accuracy'  => '2 arc-seconds angle measurement',
                            'edm_range' => '500m reflectorless / 4000m with single prism',
                            'ingress'   => 'IP66 dust and heavy rain protection',
                        ],
                        'prefix'    => 'COET-TOT',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Bi-annual optical collimation and electronic tilt sensor recalibration.'],
                        ],
                    ],
                ],
            ],

            'CSM' => [
                'admin_email' => 'admin.csm@equilend.test',
                'items'       => [
                    [
                        'name'          => 'Olympus Binocular Compound Microscopes',
                        'brand'         => 'Olympus',
                        'model_number'  => 'CX23LEDRFS1',
                        'category_name' => 'Laboratory & Scientific Instruments',
                        'description'   => 'Biological laboratory microscope with Plan Achromat objectives (4x, 10x, 40x, 100x Oil), Abbe condenser, and low-heat uniform LED illumination.',
                        'specs'         => [
                            'optics'       => 'Plan Achromat 4X, 10X, 40X, 100X Oil immersion',
                            'eyepieces'    => '10X Widefield (FN 20) with diopter adjustment',
                            'illumination' => 'Built-in 0.5W LED with aperture iris diaphragm',
                        ],
                        'prefix'    => 'CSM-MIC',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Coaxial coarse/fine focus tension lubrication and 100x oil cleanup.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Cracked Abbe condenser lens and stripped mechanical stage rack gear.'],
                        ],
                    ],
                    [
                        'name'          => 'Ohaus Analytical Precision Balances (0.1mg)',
                        'brand'         => 'Ohaus',
                        'model_number'  => 'Pioneer PX124/E',
                        'category_name' => 'Laboratory & Scientific Instruments',
                        'description'   => 'Electronic analytical balance with 120g capacity, 0.1mg readability, glass draftshield with 3 sliding doors, and RS232 / USB laboratory data output.',
                        'specs'         => [
                            'capacity'    => '120 grams',
                            'readability' => '0.0001 grams (0.1 mg)',
                            'pan_size'    => '90 mm diameter stainless steel pan',
                            'features'    => 'Static removal bar and auto-internal calibration',
                        ],
                        'prefix'    => 'CSM-BAL',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Draftshield sliding door hinge realignment and standard weight recalibration.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Internal electromagnetic load cell overload error due to dropped heavy mass.'],
                        ],
                    ],
                    [
                        'name'          => 'Thermo Scientific Digital Benchtop pH Meters',
                        'brand'         => 'Thermo Scientific',
                        'model_number'  => 'Orion Star A211',
                        'category_name' => 'Laboratory & Scientific Instruments',
                        'description'   => 'Benchtop pH / mV / temperature meter featuring 5-point calibration, automatic buffer recognition, and ROSS Ultra glass-body combination pH electrode.',
                        'specs'         => [
                            'range'     => '-2.000 to 20.000 pH',
                            'accuracy'  => '±0.002 pH relative accuracy',
                            'electrode' => 'ROSS Ultra glass refillable pH probe',
                        ],
                        'prefix'    => 'CSM-PHM',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Electrode soaking solution replenishment and slope calibration.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Broken glass bulb on combination pH electrode probe.'],
                        ],
                    ],
                    [
                        'name'          => 'Benchmark Scientific Benchtop Centrifuges',
                        'brand'         => 'Benchmark Scientific',
                        'model_number'  => 'LC-8 Plus Digital Benchtop',
                        'category_name' => 'Laboratory & Scientific Instruments',
                        'description'   => 'Compact digital centrifuge with 8-place angled rotor accommodating 15ml conical and round tubes, with variable digital speed up to 5,000 rpm.',
                        'specs'         => [
                            'speed'    => '300 to 5,000 RPM (up to 3,070 x g)',
                            'capacity' => '8 x 15ml blood or conical centrifuge tubes',
                            'safety'   => 'Lid safety lock and imbalance detection system',
                        ],
                        'prefix'    => 'CSM-CEN',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Motor brush inspection and rotor balance verification.'],
                        ],
                    ],
                ],
            ],

            'CASS' => [
                'admin_email' => 'admin.cass@equilend.test',
                'items'       => [
                    [
                        'name'          => 'Sony Alpha A7 III Full-Frame Cameras',
                        'brand'         => 'Sony',
                        'model_number'  => 'ILCE-7M3 Body',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Professional 24.2MP full-frame mirrorless camera with 4K HDR video, 15-stop dynamic range, 693 phase-detection AF points, and 5-axis image stabilization for media production.',
                        'specs'         => [
                            'sensor'    => '24.2MP Back-Illuminated Exmor R CMOS Sensor',
                            'video'     => '4K HDR (3840 x 2160) recording with HLG / S-Log3',
                            'autofocus' => 'Real-time Eye AF (Human and Animal)',
                            'mount'     => 'Sony E-mount',
                        ],
                        'prefix'    => 'CASS-CAM',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Full-frame sensor wet-cleaning to remove dust spots.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Broken mechanical shutter curtain showing Error 99 message.'],
                        ],
                    ],
                    [
                        'name'          => 'Rode Wireless GO II Dual Lavalier Mic Systems',
                        'brand'         => 'Rode',
                        'model_number'  => 'WIGOII Dual Channel',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Dual channel wireless microphone system with built-in omnidirectional microphones, 200m encrypted range, on-board recording backup, and TRS/USB-C outputs.',
                        'specs'         => [
                            'transmission' => '2.4GHz Series IV Digital Transmission with 128-bit encryption',
                            'range'        => 'Up to 200m line-of-sight',
                            'internal_rec' => 'Over 40 hours of onboard memory recording',
                        ],
                        'prefix'    => 'CASS-MIC',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::New],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Transmitter B firmware synchronization and clip latch repair.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Water damage to Transmitter A internal microphone capsule.'],
                        ],
                    ],
                    [
                        'name'          => 'Godox SL60W LED Video Lighting Kits',
                        'brand'         => 'Godox',
                        'model_number'  => 'SL-60W Daylight Balanced',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Continuous daylight-balanced 5600K 60W LED video light with Bowens mount, wireless remote control, reflector dish, and whisper-quiet cooling fan for documentary and interview lighting.',
                        'specs'         => [
                            'power'      => '60W Continuous Daylight LED',
                            'color_temp' => '5600K ± 200K',
                            'accuracy'   => 'CRI 95+, TLCI 90+',
                            'accessory'  => 'Standard Bowens S-mount reflector',
                        ],
                        'prefix'    => 'CASS-LGT',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Cooling fan lubrication and Bowens mount locking lever replacement.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Burnt COB LED diode cluster emitting flickering light.'],
                        ],
                    ],
                    [
                        'name'          => 'Zoom H6 6-Track Portable Field Recorders',
                        'brand'         => 'Zoom',
                        'model_number'  => 'Zoom H6 All Black Edition',
                        'category_name' => 'Audio & Visual Equipment',
                        'description'   => 'Handheld modular field recorder with 4 XLR/TRS combo inputs, interchangeable XY stereo mic capsule, and high-fidelity 24-bit/96kHz audio for broadcast journalism and podcasting.',
                        'specs'         => [
                            'tracks'  => '6 simultaneous recording tracks',
                            'inputs'  => '4 x XLR/TRS mic/line preamps with dedicated gain knobs',
                            'display' => '2.0-inch full-color backlit LCD',
                        ],
                        'prefix'    => 'CASS-REC',
                        'unit_plan' => [
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::Available, 'condition' => ItemUnitCondition::Good],
                            ['status' => ItemUnitStatus::UnderMaintenance, 'condition' => ItemUnitCondition::Fair, 'notes' => 'Input 3 XLR locking tab spring tension adjustment.'],
                            ['status' => ItemUnitStatus::Damaged, 'condition' => ItemUnitCondition::Poor, 'notes' => 'Broken battery terminal contacts and LCD display bleeding.'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalog as $collegeCode => $data) {
            $college = Organization::where('code', $collegeCode)->first();

            if (! $college) {
                continue;
            }

            $adminUser = User::where('email', $data['admin_email'])->first()
                ?? $college->members()->first()
                ?? User::first();

            $itemCounter = 1;

            foreach ($data['items'] as $itemData) {
                $categoryId = $categoryMap[$itemData['category_name']] ?? null;

                if (! $categoryId) {
                    $category = Category::firstOrCreate(
                        ['slug' => Str::slug($itemData['category_name'])],
                        ['name' => $itemData['category_name']]
                    );
                    $categoryId                              = $category->id;
                    $categoryMap[$itemData['category_name']] = $categoryId;
                }

                $item = Item::updateOrCreate(
                    [
                        'organization_id' => $college->id,
                        'name'            => $itemData['name'],
                    ],
                    [
                        'category_id'    => $categoryId,
                        'brand'          => $itemData['brand'],
                        'model_number'   => $itemData['model_number'],
                        'description'    => $itemData['description'],
                        'specifications' => $itemData['specs'],
                        'is_active'      => true,
                    ]
                );

                $unitNumber   = 1;
                $unitsCreated = 0;

                foreach ($itemData['unit_plan'] as $unitPlan) {
                    $assetTag     = sprintf('%s-%03d', $itemData['prefix'], $unitNumber);
                    $serialNumber = sprintf('SN-%s-%04d', $collegeCode, ($itemCounter * 100) + $unitNumber);

                    $unit = ItemUnit::updateOrCreate(
                        ['asset_tag' => $assetTag],
                        [
                            'item_id'         => $item->id,
                            'organization_id' => $college->id,
                            'serial_number'   => $serialNumber,
                            'condition'       => $unitPlan['condition'],
                            'status'          => $unitPlan['status'],
                            'notes'           => $unitPlan['notes'] ?? null,
                        ]
                    );

                    $unitsCreated++;
                    $unitNumber++;
                }

                // Log batch procurement restock entry
                $batchRef = sprintf('PO-2026-%s-%03d', $collegeCode, $itemCounter);

                InventoryLog::firstOrCreate(
                    [
                        'item_id'          => $item->id,
                        'reference_number' => $batchRef,
                        'type'             => InventoryLogType::Restock,
                    ],
                    [
                        'item_unit_id'    => null,
                        'organization_id' => $college->id,
                        'user_id'         => $adminUser?->id,
                        'quantity_change' => $unitsCreated,
                        'notes'           => 'Initial batch procurement intake and university inventory cataloging.',
                    ]
                );

                $itemCounter++;
            }
        }
    }
}
