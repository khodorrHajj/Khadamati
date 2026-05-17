<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\GovernmentOffice;
use App\Models\GovernmentOfficeWorkingHour;
use App\Models\IdentityVerification;
use App\Models\Municipality;
use App\Models\RequestMessage;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ──────────────────────────────────────────────────────────────
        $adminRole        = Role::firstOrCreate(['role' => 'admin']);
        $municipalityRole = Role::firstOrCreate(['role' => 'municipality']);
        $citizenRole      = Role::firstOrCreate(['role' => 'citizen']);

        // ── Admin ──────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@eservices.lb'],
            [
                'name'              => 'System Admin',
                'password'          => Hash::make('password'),
                'role_id'           => $adminRole->id,
                'status'            => 'active',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // ── Municipalities ─────────────────────────────────────────────────────
        $municipalities = [
            [
                'name'      => 'Beirut Municipality',
                'city'      => 'Beirut',
                'address'   => 'Riad El Solh Square, Beirut',
                'phone'     => '+961 1 981 000',
                'email'     => 'info@beirut.gov.lb',
                'latitude'  => 33.8938,
                'longitude' => 35.5018,
                'status'    => 'active',
            ],
            [
                'name'      => 'Tripoli Municipality',
                'city'      => 'Tripoli',
                'address'   => 'Al-Mina Road, Tripoli',
                'phone'     => '+961 6 430 000',
                'email'     => 'info@tripoli.gov.lb',
                'latitude'  => 34.4367,
                'longitude' => 35.8497,
                'status'    => 'active',
            ],
            [
                'name'      => 'Jounieh Municipality',
                'city'      => 'Jounieh',
                'address'   => 'Jounieh Old Town, Jounieh',
                'phone'     => '+961 9 910 000',
                'email'     => 'info@jounieh.gov.lb',
                'latitude'  => 33.9803,
                'longitude' => 35.6178,
                'status'    => 'active',
            ],
        ];

        $createdMunicipalities = [];
        foreach ($municipalities as $m) {
            $createdMunicipalities[] = Municipality::firstOrCreate(['name' => $m['name']], $m);
        }

        // ── Government Offices ─────────────────────────────────────────────────
        $officesData = [
            // Beirut offices
            [
                'municipality' => 'Beirut Municipality',
                'name'         => 'Beirut Civil Registry Office',
                'service_type' => 'Civil Registry',
                'phone'        => '+961 1 981 100',
                'email'        => 'civil@beirut.gov.lb',
                'city'         => 'Beirut',
                'address'      => 'Riad El Solh Square, Beirut Central District',
                'latitude'     => 33.8938,
                'longitude'    => 35.5018,
                'google_maps_url' => 'https://maps.google.com/?q=33.8938,35.5018',
                'status'       => 'active',
            ],
            [
                'municipality' => 'Beirut Municipality',
                'name'         => 'Beirut Urban Planning Office',
                'service_type' => 'Urban Planning & Permits',
                'phone'        => '+961 1 981 200',
                'email'        => 'planning@beirut.gov.lb',
                'city'         => 'Beirut',
                'address'      => 'Hamra Street, Beirut',
                'latitude'     => 33.8960,
                'longitude'    => 35.4784,
                'google_maps_url' => 'https://maps.google.com/?q=33.8960,35.4784',
                'status'       => 'active',
            ],
            // Tripoli offices
            [
                'municipality' => 'Tripoli Municipality',
                'name'         => 'Tripoli Civil Registry Office',
                'service_type' => 'Civil Registry',
                'phone'        => '+961 6 430 100',
                'email'        => 'civil@tripoli.gov.lb',
                'city'         => 'Tripoli',
                'address'      => 'Al-Tell Square, Tripoli',
                'latitude'     => 34.4367,
                'longitude'    => 35.8497,
                'google_maps_url' => 'https://maps.google.com/?q=34.4367,35.8497',
                'status'       => 'active',
            ],
            [
                'municipality' => 'Tripoli Municipality',
                'name'         => 'Tripoli Social Affairs Office',
                'service_type' => 'Social Services',
                'phone'        => '+961 6 430 200',
                'email'        => 'social@tripoli.gov.lb',
                'city'         => 'Tripoli',
                'address'      => 'Mina Road, Tripoli',
                'latitude'     => 34.4530,
                'longitude'    => 35.8220,
                'google_maps_url' => 'https://maps.google.com/?q=34.4530,35.8220',
                'status'       => 'active',
            ],
            // Jounieh offices
            [
                'municipality' => 'Jounieh Municipality',
                'name'         => 'Jounieh General Services Office',
                'service_type' => 'General Services',
                'phone'        => '+961 9 910 100',
                'email'        => 'general@jounieh.gov.lb',
                'city'         => 'Jounieh',
                'address'      => 'Jounieh Bay Area',
                'latitude'     => 33.9803,
                'longitude'    => 35.6178,
                'google_maps_url' => 'https://maps.google.com/?q=33.9803,35.6178',
                'status'       => 'active',
            ],
        ];

        $offices = [];
        foreach ($officesData as $od) {
            $municipality = collect($createdMunicipalities)->firstWhere('name', $od['municipality']);
            unset($od['municipality']);
            $od['municipality_id'] = $municipality->id;
            $office = GovernmentOffice::firstOrCreate(['name' => $od['name']], $od);
            $offices[$office->name] = $office;

            // Working hours Mon-Fri 8am-4pm
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($days as $day) {
                GovernmentOfficeWorkingHour::firstOrCreate(
                    ['government_office_id' => $office->id, 'day_of_week' => $day],
                    ['is_open' => true, 'start_time' => '08:00', 'end_time' => '16:00']
                );
            }
            foreach (['Saturday', 'Sunday'] as $day) {
                GovernmentOfficeWorkingHour::firstOrCreate(
                    ['government_office_id' => $office->id, 'day_of_week' => $day],
                    ['is_open' => false, 'start_time' => null, 'end_time' => null]
                );
            }
        }

        // ── Municipality Users (one per office) ────────────────────────────────
        $municipalityUsers = [];
        $officeIndex = 1;
        foreach ($offices as $officeName => $office) {
            $slug = Str::slug($officeName, '.');
            $user = User::firstOrCreate(
                ['email' => "staff.{$officeIndex}@eservices.lb"],
                [
                    'name'                => "Staff - {$officeName}",
                    'password'            => Hash::make('password'),
                    'role_id'             => $municipalityRole->id,
                    'government_office_id'=> $office->id,
                    'job_title'           => 'Office Manager',
                    'status'              => 'active',
                    'is_active'           => true,
                    'email_verified_at'   => now(),
                ]
            );
            $municipalityUsers[$officeName] = $user;
            $officeIndex++;
        }

        // ── Service Categories & Services ──────────────────────────────────────
        $catalogData = [
            'Beirut Civil Registry Office' => [
                'categories' => [
                    [
                        'name' => 'Birth Certificates',
                        'services' => [
                            ['name' => 'Birth Certificate (Original)',      'price' => 0,   'duration' => 3,  'docs' => 'Hospital birth report, National ID of parents'],
                            ['name' => 'Birth Certificate (Certified Copy)','price' => 0,   'duration' => 2,  'docs' => 'Original birth certificate, National ID'],
                        ],
                    ],
                    [
                        'name' => 'Marriage & Family Records',
                        'services' => [
                            ['name' => 'Marriage Certificate',   'price' => 0,   'duration' => 5, 'docs' => 'Religious marriage deed, National IDs of both spouses'],
                            ['name' => 'Family Registry Extract','price' => 0,   'duration' => 2, 'docs' => 'National ID'],
                            ['name' => 'Name Change Request',    'price' => 50,  'duration' => 15,'docs' => 'Notarized name change request, National ID, passport'],
                        ],
                    ],
                ],
            ],
            'Beirut Urban Planning Office' => [
                'categories' => [
                    [
                        'name' => 'Construction Permits',
                        'services' => [
                            ['name' => 'New Construction Permit',   'price' => 200, 'duration' => 30, 'docs' => 'Architectural plans, Land ownership deed, Engineer certification'],
                            ['name' => 'Renovation Permit',         'price' => 75,  'duration' => 14, 'docs' => 'Renovation plans, Property ownership proof'],
                            ['name' => 'Demolition Permit',         'price' => 100, 'duration' => 10, 'docs' => 'Property deed, Engineer safety report'],
                        ],
                    ],
                    [
                        'name' => 'Land & Zoning',
                        'services' => [
                            ['name' => 'Zoning Certificate',     'price' => 30,  'duration' => 7,  'docs' => 'Property deed, Land plot map'],
                            ['name' => 'Land Use Confirmation',  'price' => 50,  'duration' => 10, 'docs' => 'Property registration, Survey map'],
                        ],
                    ],
                ],
            ],
            'Tripoli Civil Registry Office' => [
                'categories' => [
                    [
                        'name' => 'Identity Documents',
                        'services' => [
                            ['name' => 'National ID Card Renewal',  'price' => 0,  'duration' => 7,  'docs' => 'Old National ID, Passport photo, Proof of address'],
                            ['name' => 'Residence Certificate',     'price' => 0,  'duration' => 2,  'docs' => 'National ID, Utility bill'],
                            ['name' => 'Good Conduct Certificate',  'price' => 0,  'duration' => 5,  'docs' => 'National ID, Application form'],
                        ],
                    ],
                ],
            ],
            'Tripoli Social Affairs Office' => [
                'categories' => [
                    [
                        'name' => 'Social Support',
                        'services' => [
                            ['name' => 'Social Aid Application',       'price' => 0,  'duration' => 21, 'docs' => 'National ID, Proof of income, Family registry'],
                            ['name' => 'Disability Certificate',       'price' => 0,  'duration' => 14, 'docs' => 'Medical reports, National ID, Doctor certification'],
                            ['name' => 'Elderly Care Enrollment',      'price' => 0,  'duration' => 10, 'docs' => 'National ID, Medical clearance, Family contact info'],
                        ],
                    ],
                ],
            ],
            'Jounieh General Services Office' => [
                'categories' => [
                    [
                        'name' => 'Municipal Services',
                        'services' => [
                            ['name' => 'Business License',           'price' => 150, 'duration' => 14, 'docs' => 'Commercial registration, National ID, Lease agreement'],
                            ['name' => 'Street Excavation Permit',   'price' => 80,  'duration' => 7,  'docs' => 'Engineering plan, Property proof'],
                            ['name' => 'Event Permit',               'price' => 50,  'duration' => 5,  'docs' => 'Event description, Venue ownership proof, Security plan'],
                        ],
                    ],
                ],
            ],
        ];

        $allServices = [];
        foreach ($catalogData as $officeName => $data) {
            $office = $offices[$officeName];
            foreach ($data['categories'] as $catData) {
                $category = ServiceCategory::firstOrCreate(
                    ['name' => $catData['name'], 'government_office_id' => $office->id],
                    ['description' => "Services under {$catData['name']}"]
                );
                foreach ($catData['services'] as $svc) {
                    $service = Service::firstOrCreate(
                        ['name' => $svc['name'], 'government_office_id' => $office->id],
                        [
                            'service_category_id' => $category->id,
                            'description'         => "Official {$svc['name']} service provided by {$officeName}.",
                            'price'               => $svc['price'],
                            'duration_days'       => $svc['duration'],
                            'required_documents'  => $svc['docs'],
                            'is_active'           => true,
                        ]
                    );
                    $allServices[] = $service;
                }
            }
        }

        // ── Citizens ───────────────────────────────────────────────────────────
        $citizensData = [
            ['name' => 'Ahmad Khalil',     'email' => 'ahmad.khalil@gmail.com'],
            ['name' => 'Sara Haddad',      'email' => 'sara.haddad@gmail.com'],
            ['name' => 'Georges Nassar',   'email' => 'georges.nassar@outlook.com'],
            ['name' => 'Lara Khoury',      'email' => 'lara.khoury@gmail.com'],
            ['name' => 'Omar Farouk',      'email' => 'omar.farouk@hotmail.com'],
            ['name' => 'Maya Saleh',       'email' => 'maya.saleh@gmail.com'],
            ['name' => 'Elie Gemayel',     'email' => 'elie.gemayel@gmail.com'],
        ];

        $citizens = [];
        foreach ($citizensData as $cd) {
            $user = User::firstOrCreate(
                ['email' => $cd['email']],
                [
                    'name'              => $cd['name'],
                    'password'          => Hash::make('password'),
                    'role_id'           => $citizenRole->id,
                    'status'            => 'active',
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );
            // Approved ID verification
            IdentityVerification::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'status'                 => IdentityVerification::STATUS_APPROVED,
                    'extracted_first_name'   => explode(' ', $cd['name'])[0],
                    'extracted_family_name'  => explode(' ', $cd['name'])[1] ?? '',
                    'extracted_full_name'    => $cd['name'],
                    'extracted_id_number'    => rand(100000000, 999999999),
                    'ocr_confidence'         => 0.95,
                    'reviewed_by'            => $admin->id,
                    'reviewed_at'            => now()->subDays(rand(1, 30)),
                ]
            );
            $citizens[] = $user;
        }

        // ── Time Slots ─────────────────────────────────────────────────────────
        $createdSlots = [];
        foreach ($offices as $officeName => $office) {
            $muniStaff = $municipalityUsers[$officeName];
            // 6 upcoming slots + 4 past slots per office
            for ($i = 1; $i <= 6; $i++) {
                $start = now()->addDays($i * 2)->setTime(9 + $i, 0);
                $slot = TimeSlot::firstOrCreate(
                    ['government_office_id' => $office->id, 'starts_at' => $start],
                    [
                        'ends_at'      => (clone $start)->addHour(),
                        'created_by'   => $muniStaff->id,
                        'is_available' => true,
                    ]
                );
                $createdSlots[$office->id][] = $slot;
            }
            for ($i = 1; $i <= 4; $i++) {
                $start = now()->subDays($i * 3)->setTime(10, 0);
                $slot = TimeSlot::firstOrCreate(
                    ['government_office_id' => $office->id, 'starts_at' => $start],
                    [
                        'ends_at'      => (clone $start)->addHour(),
                        'created_by'   => $muniStaff->id,
                        'is_available' => false,
                    ]
                );
                $createdSlots[$office->id][] = $slot;
            }
        }

        // ── Service Requests & Appointments ───────────────────────────────────
        $statuses = [
            ServiceRequest::STATUS_PENDING,
            ServiceRequest::STATUS_IN_REVIEW,
            ServiceRequest::STATUS_MISSING_DOCUMENTS,
            ServiceRequest::STATUS_APPROVED,
            ServiceRequest::STATUS_COMPLETED,
            ServiceRequest::STATUS_REJECTED,
        ];

        $requestMessages = [
            'Hello, I would like to follow up on my request. When can I expect a response?',
            'Could you please let me know if any additional documents are needed?',
            'I have uploaded the required documents. Please review at your earliest convenience.',
            'Thank you for processing my request.',
        ];

        $municipalityReplies = [
            'Your request is currently under review. We will notify you within 3 business days.',
            'Please upload a clear copy of your national ID and proof of address.',
            'We have received your documents and are processing them now.',
            'Your request has been approved. Please visit the office to collect your documents.',
        ];

        $requestCount = 0;
        foreach ($citizens as $citizen) {
            // Each citizen gets 3 service requests across different offices
            $randomServices = collect($allServices)->random(min(3, count($allServices)));
            foreach ($randomServices as $service) {
                $status = $statuses[$requestCount % count($statuses)];
                $officeId = $service->government_office_id;

                // Skip if request already exists for this citizen+service
                $existing = ServiceRequest::where('user_id', $citizen->id)
                    ->where('service_id', $service->id)
                    ->first();
                if ($existing) continue;

                $serviceRequest = ServiceRequest::create([
                    'user_id'        => $citizen->id,
                    'service_id'     => $service->id,
                    'tracking_code'  => 'REQ-' . strtoupper(Str::random(10)),
                    'status'         => $status,
                    'message'        => 'I am requesting this service as required for official purposes.',
                    'workflow_state' => in_array($status, [ServiceRequest::STATUS_APPROVED, ServiceRequest::STATUS_COMPLETED])
                        ? 'awaiting_admin' : 'awaiting_municipality',
                    'created_at'     => now()->subDays(rand(1, 60)),
                ]);

                // Add a message thread for most requests
                if ($requestCount % 3 !== 0) {
                    $citizenMsg = RequestMessage::create([
                        'service_request_id' => $serviceRequest->id,
                        'sender_id'          => $citizen->id,
                        'body'               => $requestMessages[$requestCount % count($requestMessages)],
                        'created_at'         => $serviceRequest->created_at->addHours(2),
                    ]);
                    $muniStaff = $municipalityUsers[GovernmentOffice::find($officeId)->name] ?? null;
                    if ($muniStaff) {
                        RequestMessage::create([
                            'service_request_id' => $serviceRequest->id,
                            'sender_id'          => $muniStaff->id,
                            'body'               => $municipalityReplies[$requestCount % count($municipalityReplies)],
                            'read_at'            => now(),
                            'created_at'         => $serviceRequest->created_at->addHours(26),
                        ]);
                    }
                }

                // Appointment for approved/completed requests
                if (in_array($status, [ServiceRequest::STATUS_APPROVED, ServiceRequest::STATUS_COMPLETED])) {
                    $availableSlots = $createdSlots[$officeId] ?? [];
                    $pastSlots = collect($availableSlots)->filter(fn($s) => $s->starts_at < now() && !$s->is_available);
                    $slot = $pastSlots->first();
                    if ($slot) {
                        Appointment::firstOrCreate(
                            ['service_request_id' => $serviceRequest->id],
                            [
                                'user_id'              => $citizen->id,
                                'government_office_id' => $officeId,
                                'time_slot_id'         => $slot->id,
                                'status'               => $status === ServiceRequest::STATUS_COMPLETED ? 'Approved' : 'Requested',
                                'notes'                => 'Please bring original documents.',
                                'municipality_notes'   => 'Your appointment is confirmed. Please arrive 10 minutes early.',
                                'approved_by'          => $municipalityUsers[GovernmentOffice::find($officeId)->name]?->id,
                                'approved_at'          => now()->subDays(rand(1, 5)),
                            ]
                        );
                    }
                }

                // Feedback for completed requests
                if ($status === ServiceRequest::STATUS_COMPLETED) {
                    Feedback::firstOrCreate(
                        ['service_request_id' => $serviceRequest->id],
                        [
                            'user_id'   => $citizen->id,
                            'rating'    => rand(3, 5),
                            'comment'   => collect([
                                'Great service, very professional.',
                                'The process was smooth and fast.',
                                'Staff were helpful and responsive.',
                                'Everything went well, thank you.',
                            ])->random(),
                        ]
                    );
                }

                $requestCount++;
            }
        }

        $this->command->info('✓ Seeded: roles, municipalities, offices, working hours, services, citizens, requests, appointments, messages, feedback.');
    }
}
