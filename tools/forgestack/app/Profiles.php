<?php
/**
 * Curated industry profiles.
 *
 * The generator never invents colours or lorem ipsum: each profile fixes the
 * palette (from fs_palettes), type pairing, section order, copy and seed data.
 * Intake values (business name, contact details, problem statement) are merged
 * on top at blueprint time.
 */
declare(strict_types=1);

function fs_profiles(): array
{
    static $profiles = null;
    if ($profiles !== null) {
        return $profiles;
    }

    $profiles = [
        'dental' => [
            'key' => 'dental',
            'label' => 'Dental & physiotherapy clinic',
            'industry' => 'Health & wellness',
            'description' => 'Appointment-led clinic with treatments, clinicians and a patient portal.',
            'default_name' => 'Northgate Dental Studio',
            'palette' => 'eucalyptus',
            'fonts' => ['display' => 'Sora', 'body' => 'Inter', 'google' => 'family=Inter:wght@400;500;600;700&family=Sora:wght@400;500;600;700'],
            'variant' => 'clinic',
            'labels' => [
                'txn_table' => 'appointments',
                'txn_one' => 'appointment',
                'txn_plural' => 'appointments',
                'txn_ref' => 'NDS',
                'customer' => 'patient',
                'customer_plural' => 'patients',
                'staff' => 'clinician',
                'staff_plural' => 'clinicians',
                'book' => 'Book an appointment',
                'track' => 'Track a request',
                'track_title' => 'Check an appointment request',
                'track_intro' => 'Enter the reference from your confirmation email to see the current status and your visit details.',
                'date' => 'Preferred date',
                'time' => 'Preferred time',
                'choice_a' => 'Treatment',
                'choice_b' => 'Clinician',
                'quantity' => 'Appointment length',
                'notes' => 'Anything we should know?',
                'notes_help' => 'Nervous patient, accessibility needs, or the reason for your visit.',
                'form_title' => 'Request an appointment',
                'form_intro' => 'Tell us what you need and when suits you. The practice confirms every request by email — usually within one working hour.',
                'form_aside_title' => 'What happens next',
                'form_aside' => ['We check the diary and confirm by email.', 'You get a written plan and costs before treatment.', 'Reschedule free of charge up to 24 hours ahead.'],
                'portal' => 'Patient portal',
                'admin' => 'Practice desk',
                'reference' => 'Appointment reference',
                'public_note' => 'For your privacy this page shows only the first name on the booking.',
            ],
            'status_meta' => [
                'pending' => ['label' => 'Requested', 'tone' => 'warn', 'next' => ['confirmed', 'cancelled']],
                'confirmed' => ['label' => 'Confirmed', 'tone' => 'ok', 'next' => ['completed', 'no_show', 'cancelled']],
                'completed' => ['label' => 'Completed', 'tone' => 'ok', 'next' => []],
                'cancelled' => ['label' => 'Cancelled', 'tone' => 'bad', 'next' => []],
                'no_show' => ['label' => 'Did not attend', 'tone' => 'bad', 'next' => []],
            ],
            'initial_status' => 'pending',
            'reminder_statuses' => ['confirmed'],
            'close_past_status' => 'no_show',
            'form' => [
                'choices_a' => [
                    ['value' => 'New patient consultation', 'price' => 18000],
                    ['value' => 'Hygiene appointment', 'price' => 25000],
                    ['value' => 'Composite bonding', 'price' => 45000],
                    ['value' => 'Clear aligner consultation', 'price' => 30000],
                    ['value' => 'Emergency visit', 'price' => 20000],
                ],
                'choices_b' => ['First available', 'Dr. Adaeze Okoye', 'Dr. Tunde Bakare', 'Ngozi Eze, hygienist'],
                'quantity_options' => [30, 45, 60],
                'quantity_min' => 1,
                'quantity_max' => 120,
                'times' => ['08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'],
            ],
            'copy' => [
                'tagline' => 'Calm, precise dentistry in Ikoyi.',
                'meta_description' => 'Northgate Dental Studio — preventive, cosmetic and emergency dentistry in Ikoyi, Lagos. Book online, get a real confirmation and keep every visit on record.',
                'hero' => [
                    'eyebrow' => 'Dentistry, minus the dread',
                    'headline' => 'A calmer kind of',
                    'accent' => 'dental visit.',
                    'lede' => 'Preventive care, cosmetic work and same-week emergency slots — booked online in under a minute, confirmed by a real person, and followed by a clear written plan.',
                    'primary' => 'Book an appointment',
                    'secondary' => 'Track a request',
                    'note' => 'Mon–Fri 8:30–18:00 · Sat 9:00–14:00 · Ikoyi, Lagos',
                    'panel_title' => 'Your first visit',
                    'panel' => [
                        ['15 minutes', 'Consultation, photos and a low-dose scan'],
                        ['Same day', 'A written treatment plan with fixed costs'],
                        ['No pressure', 'Options, payment plans and time to decide'],
                    ],
                    'panel_foot' => 'We confirm every request by email — never an automated “maybe”.',
                ],
                'sections' => [
                    [
                        'type' => 'services',
                        'eyebrow' => 'Treatments',
                        'title' => 'Everything a healthy mouth needs, in one calm room.',
                        'lede' => 'General dentistry for the whole family, with cosmetic and emergency care when you need it. Every price is confirmed before treatment starts.',
                        'items' => [
                            ['icon' => '◍', 'title' => 'Hygiene & prevention', 'text' => 'Scaling, polishing and a realistic home routine. The quiet work that prevents most emergency visits.', 'meta' => 'From ₦25,000 · 45 min'],
                            ['icon' => '◇', 'title' => 'Composite bonding', 'text' => 'Chipped, worn or gapped teeth rebuilt with hand-layered composite. No drilling where it is not needed.', 'meta' => 'From ₦45,000 · 60–90 min'],
                            ['icon' => '⌁', 'title' => 'Clear aligners', 'text' => 'A digital scan, a staged plan and aligners you can remove. You see the projected result before you commit.', 'meta' => 'Consultation ₦30,000'],
                            ['icon' => '✚', 'title' => 'Emergency dentistry', 'text' => 'Same-week slots for pain, swelling and broken teeth. Call us if you cannot find a slot online.', 'meta' => 'From ₦20,000'],
                        ],
                    ],
                    [
                        'type' => 'editorial',
                        'eyebrow' => 'Why patients switch',
                        'title' => 'The hardest part of dentistry is walking through the door.',
                        'body' => 'Most people who avoid the dentist are not afraid of pain — they are tired of being sold treatments they do not understand. We slow the appointment down: photographs on a screen, costs written down, and no treatment started until you have had time to think.',
                        'bullets' => ['Fixed prices quoted before treatment', 'Written plan after every consultation', 'Sedation and anxiety-friendly appointments'],
                        'image' => 'https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&fit=crop&w=1400&q=80',
                        'alt' => 'Dental clinician reviewing a scan with a patient',
                        'caption' => 'Every plan is reviewed with you on screen before anything begins.',
                    ],
                    [
                        'type' => 'process',
                        'eyebrow' => 'Booking',
                        'title' => 'Three quiet steps.',
                        'lede' => 'No phone tag, no “we will call you back”. The request is logged, the practice confirms and the reminder arrives before you forget.',
                        'steps' => [
                            ['title' => 'Choose a slot', 'text' => 'Pick a treatment, a clinician and a time that fits your week. Two minutes, from any device.'],
                            ['title' => 'We confirm', 'text' => 'The practice desk checks the diary and emails your reference. You can track it any time.'],
                            ['title' => 'Arrive to a plan', 'text' => 'We review, explain and quote before treatment. You leave knowing exactly what happens next.'],
                        ],
                    ],
                    [
                        'type' => 'stats',
                        'items' => [
                            ['value' => '4,800+', 'label' => 'Patients cared for'],
                            ['value' => '12 yrs', 'label' => 'In Ikoyi'],
                            ['value' => '98%', 'label' => 'Appointments on time'],
                            ['value' => '4.9/5', 'label' => 'Patient rating'],
                        ],
                    ],
                    [
                        'type' => 'proof',
                        'items' => [
                            ['quote' => 'I had put off a cracked molar for eleven months. One visit, a clear plan and a price I could budget for. I have been back twice since — on time, no drama.', 'name' => 'Yewande A.', 'detail' => 'Patient since 2023'],
                            ['quote' => 'They photographed everything and showed me why the filling needed replacing. Nobody has ever done that before.', 'name' => 'Peter O.', 'detail' => 'Hygiene + restorative'],
                        ],
                    ],
                    [
                        'type' => 'team',
                        'eyebrow' => 'The practice',
                        'title' => 'Small team. Long appointments.',
                        'members' => [
                            ['name' => 'Dr. Adaeze Okoye', 'role' => 'Clinical lead · restorative dentistry', 'bio' => 'Fifteen years in restorative and aesthetic dentistry, with a particular interest in anxious patients.'],
                            ['name' => 'Dr. Tunde Bakare', 'role' => 'Aligners & digital planning', 'bio' => 'Digital scans, staged aligner plans and the unglamorous follow-up that makes them work.'],
                            ['name' => 'Ngozi Eze', 'role' => 'Hygienist', 'bio' => 'Prevention-first hygiene, gentle scaling and a home routine you will actually keep.'],
                        ],
                    ],
                    [
                        'type' => 'faq',
                        'eyebrow' => 'Good to know',
                        'title' => 'Questions we hear most.',
                        'items' => [
                            ['q' => 'I am nervous. Can I still book?', 'a' => 'Yes. Tell us in the notes field. We book longer appointments, explain each step and can arrange sedation where appropriate.'],
                            ['q' => 'Do you take insurance?', 'a' => 'We issue itemised receipts you can claim from most HMOs. The practice desk will confirm cover before treatment.'],
                            ['q' => 'What if I need to cancel?', 'a' => 'Cancel or move the appointment free of charge up to 24 hours ahead using your reference.'],
                            ['q' => 'Is there parking?', 'a' => 'Yes — three patient bays behind the studio, plus street parking on Glover Road.'],
                        ],
                    ],
                    ['type' => 'cta', 'title' => 'The next available hygiene slot is this week.', 'body' => 'Book online, get a confirmed reference, and keep the whole visit on record.', 'primary' => 'Book an appointment', 'secondary' => 'Track a request'],
                ],
                'footer' => [
                    'blurb' => 'A calm, modern dental studio in Ikoyi. Preventive care, cosmetic dentistry and same-week emergency slots.',
                    'hours' => 'Mon–Fri 8:30–18:00 · Sat 9:00–14:00',
                    'address' => '12 Glover Road, Ikoyi, Lagos',
                ],
                'contact_intro' => 'Questions about a treatment, insurance or a nervous first visit? Send a note and the practice desk will reply within one working day.',
            ],
            'seed' => [
                'records' => [
                    ['ref' => 'NDS-SEED-01', 'name' => 'Amina Bello', 'email' => 'amina.bello@example.com', 'phone' => '+2348030000001', 'date' => '+2 days', 'time' => '10:00', 'a' => 'Hygiene appointment', 'b' => 'Dr. Adaeze Okoye', 'qty' => 45, 'notes' => 'Routine clean, sensitive lower left.', 'status' => 'confirmed', 'payment' => 'paid', 'amount' => 25000],
                    ['ref' => 'NDS-SEED-02', 'name' => 'Chidi Okafor', 'email' => 'chidi.okafor@example.com', 'phone' => '+2348030000002', 'date' => '+1 day', 'time' => '14:30', 'a' => 'New patient consultation', 'b' => 'First available', 'qty' => 30, 'notes' => 'Chipped front tooth, would like options.', 'status' => 'pending', 'payment' => 'unpaid', 'amount' => 18000],
                    ['ref' => 'NDS-SEED-03', 'name' => 'Funke Adeyemi', 'email' => 'funke.adeyemi@example.com', 'phone' => '+2348030000003', 'date' => '-6 days', 'time' => '11:00', 'a' => 'Composite bonding', 'b' => 'Dr. Tunde Bakare', 'qty' => 60, 'notes' => 'Two upper incisors.', 'status' => 'completed', 'payment' => 'paid', 'amount' => 45000],
                    ['ref' => 'NDS-SEED-04', 'name' => 'Musa Ibrahim', 'email' => 'musa.ibrahim@example.com', 'phone' => '+2348030000004', 'date' => '-12 days', 'time' => '09:00', 'a' => 'Hygiene appointment', 'b' => 'Ngozi Eze, hygienist', 'qty' => 45, 'notes' => '', 'status' => 'completed', 'payment' => 'paid', 'amount' => 25000],
                    ['ref' => 'NDS-SEED-05', 'name' => 'Grace Mensah', 'email' => 'grace.mensah@example.com', 'phone' => '+2348030000005', 'date' => '-3 days', 'time' => '16:00', 'a' => 'Clear aligner consultation', 'b' => 'Dr. Adaeze Okoye', 'qty' => 45, 'notes' => 'Travels monthly for work.', 'status' => 'cancelled', 'payment' => 'not_required', 'amount' => 30000],
                ],
                'products' => [],
                'messages' => [
                    ['name' => 'Bola Ade', 'email' => 'bola.ade@example.com', 'phone' => '+2348030000011', 'subject' => 'HMO cover for hygienist visits', 'body' => 'Does the studio accept Reliance HMO for hygiene appointments? I would like to book for two adults.', 'status' => 'new'],
                    ['name' => 'Ifeoma Nwosu', 'email' => 'ifeoma.n@example.com', 'phone' => '+2348030000012', 'subject' => 'Parking for a 7:30 appointment', 'body' => 'I have an early appointment next week. Are the patient bays accessible before 8am?', 'status' => 'handled'],
                ],
            ],
        ],

        'courier' => [
            'key' => 'courier',
            'label' => 'Logistics & courier operator',
            'industry' => 'Logistics',
            'description' => 'Shipment requests, public tracking and a dispatch-grade admin desk.',
            'default_name' => 'Kestrel Logistics',
            'palette' => 'indigo',
            'fonts' => ['display' => 'Space Grotesk', 'body' => 'Inter', 'google' => 'family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700'],
            'variant' => 'courier',
            'labels' => [
                'txn_table' => 'shipments',
                'txn_one' => 'shipment',
                'txn_plural' => 'shipments',
                'txn_ref' => 'KES',
                'customer' => 'shipper',
                'customer_plural' => 'shippers',
                'staff' => 'dispatcher',
                'staff_plural' => 'dispatch team',
                'book' => 'Book a pickup',
                'track' => 'Track a shipment',
                'track_title' => 'Track a shipment',
                'track_intro' => 'Enter the tracking code from your booking confirmation. The page updates on every rider scan.',
                'date' => 'Pickup date',
                'time' => 'Pickup window',
                'choice_a' => 'Service',
                'choice_b' => 'Destination zone',
                'quantity' => 'Weight',
                'notes' => 'Pickup notes',
                'notes_help' => 'Address landmarks, gate codes, or what is inside the parcel.',
                'form_title' => 'Book a pickup',
                'form_intro' => 'Tell us where to collect and where it is going. A dispatcher assigns a rider and you get a tracking code by email.',
                'form_aside_title' => 'How the pickup runs',
                'form_aside' => ['A dispatcher reviews the request within 10 minutes.', 'A vetted rider is assigned with a live job sheet.', 'Every scan updates the public tracking page.'],
                'portal' => 'Shipper portal',
                'admin' => 'Dispatch desk',
                'reference' => 'Tracking code',
                'public_note' => 'Tracking pages show the recipient name only — never contact details.',
            ],
            'status_meta' => [
                'requested' => ['label' => 'Requested', 'tone' => 'warn', 'next' => ['scheduled', 'cancelled']],
                'scheduled' => ['label' => 'Scheduled', 'tone' => 'info', 'next' => ['in_transit', 'cancelled']],
                'in_transit' => ['label' => 'In transit', 'tone' => 'info', 'next' => ['delivered', 'failed']],
                'delivered' => ['label' => 'Delivered', 'tone' => 'ok', 'next' => []],
                'failed' => ['label' => 'Failed', 'tone' => 'bad', 'next' => ['scheduled']],
                'cancelled' => ['label' => 'Cancelled', 'tone' => 'bad', 'next' => []],
            ],
            'initial_status' => 'requested',
            'reminder_statuses' => ['scheduled'],
            'close_past_status' => 'failed',
            'form' => [
                'choices_a' => [
                    ['value' => 'Same-day (by 18:00)', 'price' => 3500],
                    ['value' => 'Express 4-hour', 'price' => 6500],
                    ['value' => 'Scheduled route', 'price' => 2800],
                    ['value' => 'Fragile & medical', 'price' => 8000],
                ],
                'choices_b' => ['Lekki & Ajah', 'Victoria Island & Ikoyi', 'Yaba & Surulere', 'Ikeja & Maryland'],
                'quantity_min' => 1,
                'quantity_max' => 60,
                'times' => ['08:00–10:00', '10:00–12:00', '12:00–14:00', '14:00–16:00', '16:00–18:00'],
            ],
            'copy' => [
                'tagline' => 'Same-day courier, without the guesswork.',
                'meta_description' => 'Kestrel Logistics — same-day and express courier across Lagos with live tracking, vetted riders and a public proof-of-delivery trail.',
                'hero' => [
                    'eyebrow' => 'Same-day courier across Lagos',
                    'headline' => 'Every parcel,',
                    'accent' => 'accounted for.',
                    'lede' => 'Book a pickup in two minutes, hand it to a vetted rider, and give your customer a tracking page that updates on every scan. No WhatsApp group, no “the rider is coming” guesswork.',
                    'primary' => 'Book a pickup',
                    'secondary' => 'Track a shipment',
                    'note' => 'Same-day cutoff 14:00 · 148 riders on shift · Lagos mainland & island',
                    'panel_title' => 'Today’s network',
                    'panel' => [
                        ['148', 'vetted riders on shift'],
                        ['98.4%', 'delivered inside the promise'],
                        ['3.2 h', 'median same-day delivery'],
                    ],
                    'panel_foot' => 'Every shipment gets a tracking code and an immutable scan trail.',
                ],
                'sections' => [
                    [
                        'type' => 'services',
                        'eyebrow' => 'Services',
                        'title' => 'One network, four ways to move.',
                        'lede' => 'Priced by service, not by negotiation. Pickup windows are promised, riders are vetted and every handover is logged.',
                        'items' => [
                            ['icon' => '⟶', 'title' => 'Same-day dispatch', 'text' => 'Booked before 14:00, delivered before 18:00 across Lagos. Live rider assignment and customer updates.', 'meta' => 'From ₦3,500'],
                            ['icon' => '▣', 'title' => 'E-commerce fulfilment', 'text' => 'Scheduled daily collections from your store, with proof of delivery pushed back to your team.', 'meta' => 'From ₦2,800 / parcel'],
                            ['icon' => '⌁', 'title' => 'Scheduled routes', 'text' => 'Recurring runs for pharmacies, labs and professional services. Same window, same rider where possible.', 'meta' => 'Custom route plan'],
                            ['icon' => '◈', 'title' => 'Fragile & medical', 'text' => 'Cold-chain boxes, samples and fragile freight with handling notes attached to the job sheet.', 'meta' => 'From ₦8,000'],
                        ],
                    ],
                    [
                        'type' => 'editorial',
                        'eyebrow' => 'Why operators switch',
                        'title' => 'A WhatsApp group is not a dispatch system.',
                        'body' => 'Most small courier operators run on screenshots, voice notes and hope. When a customer asks where their parcel is, nobody can answer with confidence. Kestrel gives every shipment a code, every status change an author, and every customer the same live page your dispatchers use.',
                        'bullets' => ['One job sheet per rider, updated on every scan', 'Public tracking without exposing phone numbers', 'Daily digest of overdue pickups and failed attempts'],
                        'image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1400&q=80',
                        'alt' => 'Courier loading parcels into a delivery van',
                        'caption' => 'Dispatch assigns the rider; the rider updates the trail; the customer sees both.',
                    ],
                    [
                        'type' => 'stats',
                        'items' => [
                            ['value' => '98.4%', 'label' => 'On-time delivery'],
                            ['value' => '42,000', 'label' => 'Parcels moved'],
                            ['value' => '148', 'label' => 'Vetted riders'],
                            ['value' => '3.2 h', 'label' => 'Median same-day'],
                        ],
                    ],
                    [
                        'type' => 'process',
                        'eyebrow' => 'Dispatch',
                        'title' => 'From booking to proof, in four scans.',
                        'lede' => 'The customer sees the same status the dispatcher does. Nothing is updated by memory.',
                        'steps' => [
                            ['title' => 'Book', 'text' => 'Pickup zone, destination, weight and service. The system issues a tracking code immediately.'],
                            ['title' => 'Assign', 'text' => 'A dispatcher checks capacity and attaches a rider to the job sheet.'],
                            ['title' => 'Scan', 'text' => 'Pickup, transit and delivery each write an immutable event with a timestamp.'],
                            ['title' => 'Prove', 'text' => 'Delivery closes the loop and emails the shipper a receipt they can forward.'],
                        ],
                    ],
                    [
                        'type' => 'coverage',
                        'eyebrow' => 'Coverage',
                        'title' => 'Lagos, end to end.',
                        'body' => 'Same-day coverage across the island and mainland. Outside the zones below we run scheduled routes twice daily.',
                        'zones' => ['Lekki & Ajah', 'Victoria Island & Ikoyi', 'Yaba & Surulere', 'Ikeja & Maryland', 'Ikorodu (scheduled)', 'Epe (scheduled)'],
                    ],
                    [
                        'type' => 'proof',
                        'items' => [
                            ['quote' => 'We moved our pharmacy deliveries to Kestrel in March. The tracking page cut “where is my order” messages by roughly two thirds.', 'name' => 'Tola A.', 'detail' => 'Operations lead, community pharmacy'],
                            ['quote' => 'The dispatcher called to flag a failed gate code before the customer even noticed. That is the difference.', 'name' => 'Nneka O.', 'detail' => 'E-commerce founder'],
                        ],
                    ],
                    [
                        'type' => 'faq',
                        'eyebrow' => 'Good to know',
                        'title' => 'Before you book.',
                        'items' => [
                            ['q' => 'What cannot I send?', 'a' => 'Cash, live animals, weapons and unlicensed pharmaceuticals. Medical samples need prior approval and the fragile/medical service.'],
                            ['q' => 'Is my parcel insured?', 'a' => 'Every shipment carries a base cover of ₦50,000. Declare a higher value in the pickup notes and we will quote additional cover.'],
                            ['q' => 'How does proof of delivery work?', 'a' => 'The rider closes the job with a timestamped delivery event. The shipper receives an email with the tracking link and status.'],
                            ['q' => 'Do you deliver on Sundays?', 'a' => 'Only scheduled routes and medical collections. Same-day dispatch runs Monday to Saturday.'],
                        ],
                    ],
                    ['type' => 'cta', 'title' => 'Book before 14:00. Delivered before 18:00.', 'body' => 'Get a tracking code in seconds and a scan trail your customer can actually read.', 'primary' => 'Book a pickup', 'secondary' => 'Track a shipment'],
                ],
                'footer' => [
                    'blurb' => 'Same-day and express courier across Lagos, with live tracking and an auditable scan trail for every parcel.',
                    'hours' => 'Mon–Sat 07:30–19:00 · Sunday scheduled routes only',
                    'address' => '4 Admiralty Way, Lekki Phase 1, Lagos',
                ],
                'contact_intro' => 'New route, bulk collection or a shipment that needs eyes on it? Send the details and a dispatcher will reply within the hour.',
            ],
            'seed' => [
                'records' => [
                    ['ref' => 'KES-SEED-01', 'name' => 'Amara Foods', 'email' => 'ops@amarafoods.example.com', 'phone' => '+2348040000001', 'date' => '+0 days', 'time' => '12:00–14:00', 'a' => 'Same-day (by 18:00)', 'b' => 'Victoria Island & Ikoyi', 'qty' => 3, 'notes' => 'Two insulated boxes, one document envelope. Call on arrival.', 'status' => 'in_transit', 'payment' => 'paid', 'amount' => 3500],
                    ['ref' => 'KES-SEED-02', 'name' => 'Lagos Bookshop', 'email' => 'dispatch@lagosbookshop.example.com', 'phone' => '+2348040000002', 'date' => '+1 day', 'time' => '10:00–12:00', 'a' => 'Scheduled route', 'b' => 'Yaba & Surulere', 'qty' => 12, 'notes' => 'Monthly restock to three branches.', 'status' => 'scheduled', 'payment' => 'unpaid', 'amount' => 2800],
                    ['ref' => 'KES-SEED-03', 'name' => 'Dr. Kalu Diagnostics', 'email' => 'lab@kaludiagnostics.example.com', 'phone' => '+2348040000003', 'date' => '+1 day', 'time' => '08:00–10:00', 'a' => 'Fragile & medical', 'b' => 'Lekki & Ajah', 'qty' => 2, 'notes' => 'Cold-chain samples, keep upright.', 'status' => 'requested', 'payment' => 'unpaid', 'amount' => 8000],
                    ['ref' => 'KES-SEED-04', 'name' => 'Mira Beauty', 'email' => 'hello@mirabeauty.example.com', 'phone' => '+2348040000004', 'date' => '-1 day', 'time' => '14:00–16:00', 'a' => 'Express 4-hour', 'b' => 'Ikeja & Maryland', 'qty' => 1, 'notes' => 'Delivered to reception.', 'status' => 'delivered', 'payment' => 'paid', 'amount' => 6500],
                    ['ref' => 'KES-SEED-05', 'name' => 'Zuri Home', 'email' => 'orders@zurihome.example.com', 'phone' => '+2348040000005', 'date' => '-2 days', 'time' => '16:00–18:00', 'a' => 'Same-day (by 18:00)', 'b' => 'Lekki & Ajah', 'qty' => 8, 'notes' => 'Customer rescheduled twice.', 'status' => 'failed', 'payment' => 'unpaid', 'amount' => 3500],
                ],
                'products' => [
                    ['sku' => 'PKG-MAILER-M', 'name' => 'Padded mailer — medium', 'description' => 'Recycled padded envelope for documents and small electronics. Pack of 10.', 'price' => 6500, 'stock' => 40],
                    ['sku' => 'PKG-BOX-S', 'name' => 'Double-wall box — small', 'description' => 'Rigid 25×20×15cm box for fragile freight. Sold in fives.', 'price' => 9000, 'stock' => 24],
                    ['sku' => 'PKG-TAPE', 'name' => 'Water-activated tape', 'description' => 'Tamper-evident kraft tape, 50m roll. The quiet hero of every dispatch desk.', 'price' => 3500, 'stock' => 60],
                ],
                'messages' => [
                    ['name' => 'Kunle Adebayo', 'email' => 'kunle@fabricworks.example.com', 'phone' => '+2348040000011', 'subject' => 'Daily collections for a fabric studio', 'body' => 'We ship 10–15 parcels a day from Yaba. Can you quote a scheduled daily collection with proof of delivery?', 'status' => 'new'],
                    ['name' => 'Chinelo Obi', 'email' => 'chinelo@pharmacy.example.com', 'phone' => '+2348040000012', 'subject' => 'Cold-chain sample deliveries', 'body' => 'We need twice-weekly sample runs to two labs in Lekki. Do you provide temperature-stable boxes?', 'status' => 'new'],
                ],
            ],
        ],

        'restaurant' => [
            'key' => 'restaurant',
            'label' => 'Restaurant & hospitality venue',
            'industry' => 'Hospitality',
            'description' => 'Table reservations, a live menu and an optional takeaway order flow.',
            'default_name' => 'Ember & Thyme',
            'palette' => 'terracotta',
            'fonts' => ['display' => 'Fraunces', 'body' => 'Manrope', 'google' => 'family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700'],
            'variant' => 'hospitality',
            'labels' => [
                'txn_table' => 'reservations',
                'txn_one' => 'reservation',
                'txn_plural' => 'reservations',
                'txn_ref' => 'EMT',
                'customer' => 'guest',
                'customer_plural' => 'guests',
                'staff' => 'host',
                'staff_plural' => 'floor team',
                'book' => 'Reserve a table',
                'track' => 'Find a reservation',
                'track_title' => 'Find your reservation',
                'track_intro' => 'Enter the reference from your confirmation email to see your table details and current status.',
                'date' => 'Reservation date',
                'time' => 'Seating time',
                'choice_a' => 'Seating',
                'choice_b' => 'Occasion',
                'quantity' => 'Guests',
                'notes' => 'Dietary notes & requests',
                'notes_help' => 'Allergies, accessibility, high chairs or a quiet corner — tell us here.',
                'form_title' => 'Reserve a table',
                'form_intro' => 'We hold tables for 15 minutes past the booking time. Parties of seven or more are confirmed by the host directly.',
                'form_aside_title' => 'Before you arrive',
                'form_aside' => ['The host confirms every reservation by email.', 'Tables are held for 15 minutes past the booking time.', 'Tell us about allergies — the kitchen plans around them.'],
                'portal' => 'Guest portal',
                'admin' => 'Host desk',
                'reference' => 'Reservation reference',
                'public_note' => 'For privacy this page shows only the first name on the reservation.',
            ],
            'status_meta' => [
                'pending' => ['label' => 'Requested', 'tone' => 'warn', 'next' => ['confirmed', 'cancelled']],
                'confirmed' => ['label' => 'Confirmed', 'tone' => 'ok', 'next' => ['seated', 'no_show', 'cancelled']],
                'seated' => ['label' => 'Seated', 'tone' => 'info', 'next' => ['completed']],
                'completed' => ['label' => 'Completed', 'tone' => 'ok', 'next' => []],
                'cancelled' => ['label' => 'Cancelled', 'tone' => 'bad', 'next' => []],
                'no_show' => ['label' => 'No-show', 'tone' => 'bad', 'next' => []],
            ],
            'initial_status' => 'pending',
            'reminder_statuses' => ['confirmed'],
            'close_past_status' => 'no_show',
            'form' => [
                'choices_a' => [
                    ['value' => 'Dining room', 'price' => 0],
                    ['value' => 'Chef’s counter', 'price' => 0],
                    ['value' => 'Terrace', 'price' => 0],
                    ['value' => 'Private room', 'price' => 0],
                ],
                'choices_b' => ['No occasion', 'Birthday', 'Anniversary', 'Business dinner', 'Celebration'],
                'quantity_min' => 1,
                'quantity_max' => 12,
                'times' => ['12:00', '12:30', '13:00', '13:30', '14:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'],
            ],
            'copy' => [
                'tagline' => 'Wood fire, natural wine and slow Sundays in Ikoyi.',
                'meta_description' => 'Ember & Thyme — a wood-fired neighbourhood restaurant in Ikoyi. Seasonal plates, a short natural-wine list and tables you can book online.',
                'hero' => [
                    'eyebrow' => 'Wood fire · natural wine · slow Sundays',
                    'headline' => 'A table worth',
                    'accent' => 'staying for.',
                    'lede' => 'Seasonal plates cooked over open flame, a short natural-wine list, and a room that never rushes you. Reserve in under a minute — the host confirms by email.',
                    'primary' => 'Reserve a table',
                    'secondary' => 'Find a reservation',
                    'note' => 'Tue–Sun from 17:30 · Sunday lunch 12:00–16:00 · Ikoyi, Lagos',
                    'panel_title' => 'On the fire tonight',
                    'panel' => [
                        ['Fire-roasted dorade', 'charred lemon, fennel, olive'],
                        ['Aged ribeye', 'bone marrow, watercress, burnt onion'],
                        ['Burnt honey tart', 'crème fraîche, orange zest'],
                    ],
                    'panel_foot' => 'The menu changes with what the market gives us. Ask about the off-menu fire plate.',
                ],
                'sections' => [
                    [
                        'type' => 'editorial',
                        'eyebrow' => 'The room',
                        'title' => 'Low light, open fire, no hurry.',
                        'body' => 'Ember & Thyme began as a Sunday supper club in a courtyard behind the market. The room is still small — twenty-eight seats, a counter facing the fire, and a terrace that catches the evening breeze. We cook what the market gives us, over wood, and we would rather you stayed for a third glass than turned the table.',
                        'bullets' => ['Twenty-eight seats, one seating per evening', 'Wood-fired kitchen, seasonal West African produce', 'A short list of natural wine, low-intervention spirits'],
                        'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1400&q=80',
                        'alt' => 'Warmly lit restaurant dining room with an open kitchen',
                        'caption' => 'The counter seats face the fire — the best seats in the house.',
                    ],
                    [
                        'type' => 'menu',
                        'eyebrow' => 'The menu',
                        'title' => 'A short menu, cooked over flame.',
                        'lede' => 'Three sections, changing weekly. Prices are final; service is included.',
                        'groups' => [
                            ['name' => 'To begin', 'items' => [
                                ['name' => 'Smoked catfish croquettes', 'desc' => 'scotch bonnet aioli, lime', 'price' => '₦7,500'],
                                ['name' => 'Charred greens', 'desc' => 'tiger nut cream, crisp shallot', 'price' => '₦6,000'],
                                ['name' => 'Wood-fired focaccia', 'desc' => 'cultured butter, smoked salt', 'price' => '₦4,500'],
                            ]],
                            ['name' => 'From the fire', 'items' => [
                                ['name' => 'Fire-roasted dorade', 'desc' => 'charred lemon, fennel, olive', 'price' => '₦24,000'],
                                ['name' => 'Aged ribeye', 'desc' => 'bone marrow, watercress, burnt onion', 'price' => '₦38,000'],
                                ['name' => 'Ember yam', 'desc' => 'palm-oil béchamel, wild herbs', 'price' => '₦16,000'],
                            ]],
                            ['name' => 'To finish', 'items' => [
                                ['name' => 'Burnt honey tart', 'desc' => 'crème fraîche, orange zest', 'price' => '₦8,500'],
                                ['name' => 'Dark chocolate, smoked salt', 'desc' => 'cocoa nib, olive oil', 'price' => '₦7,000'],
                            ]],
                        ],
                    ],
                    [
                        'type' => 'process',
                        'eyebrow' => 'Reservations',
                        'title' => 'How a table works here.',
                        'lede' => 'We keep the booking simple: a real confirmation, a held table, and a host who knows why you are coming.',
                        'steps' => [
                            ['title' => 'Choose a table', 'text' => 'Dining room, chef’s counter, terrace or private room — with the time that suits you.'],
                            ['title' => 'The host confirms', 'text' => 'You get an email reference immediately and a host confirmation shortly after.'],
                            ['title' => 'We hold it', 'text' => 'Tables are held for 15 minutes. Running late? Reply to the confirmation and we will wait.'],
                        ],
                    ],
                    [
                        'type' => 'stats',
                        'items' => [
                            ['value' => '28', 'label' => 'Seats per evening'],
                            ['value' => '1', 'label' => 'Seating, not two'],
                            ['value' => '14', 'label' => 'Producers we buy from'],
                            ['value' => '4.8/5', 'label' => 'Guest rating'],
                        ],
                    ],
                    [
                        'type' => 'proof',
                        'items' => [
                            ['quote' => 'The kind of place where the host remembers your name on the second visit. The dorade alone is worth crossing the bridge for.', 'name' => 'Adaeze N.', 'detail' => 'Dinner on the terrace'],
                            ['quote' => 'We booked the private room for eight and never felt rushed. They planned the menu around two allergies without making it a thing.', 'name' => 'Tunde & Kemi', 'detail' => 'Anniversary dinner'],
                        ],
                    ],
                    [
                        'type' => 'team',
                        'eyebrow' => 'The people',
                        'title' => 'A small kitchen, on purpose.',
                        'members' => [
                            ['name' => 'Chef Lola Adewale', 'role' => 'Head chef & co-founder', 'bio' => 'Trained in Cape Town and Lagos, cooking over flame with West African produce and a light hand.'],
                            ['name' => 'Marta Silva', 'role' => 'Wine & front of house', 'bio' => 'Builds the natural-wine list and makes sure nobody is hurried out of their seat.'],
                            ['name' => 'Yusuf Bello', 'role' => 'Host', 'bio' => 'Knows the room, the regulars and which table gets the best breeze.'],
                        ],
                    ],
                    [
                        'type' => 'faq',
                        'eyebrow' => 'Good to know',
                        'title' => 'Before you book.',
                        'items' => [
                            ['q' => 'Do you cater for allergies?', 'a' => 'Yes. Tell us in the booking notes. The kitchen writes a separate plan for each allergy and the host confirms it before service.'],
                            ['q' => 'What is the dress code?', 'a' => 'None. Come as you are — though the terrace is cooler than the dining room after dark.'],
                            ['q' => 'Can we book for a large group?', 'a' => 'Parties of seven to twelve are confirmed by the host directly. The private room seats twelve.'],
                            ['q' => 'Is there a corkage fee?', 'a' => '₦8,000 per bottle, limited to two bottles per table. The list is short on purpose; the host will help you choose.'],
                        ],
                    ],
                    ['type' => 'cta', 'title' => 'The fire is lit at 17:30.', 'body' => 'Book a table, tell us what you are celebrating, and we will hold it.', 'primary' => 'Reserve a table', 'secondary' => 'Find a reservation'],
                ],
                'footer' => [
                    'blurb' => 'A wood-fired neighbourhood restaurant in Ikoyi. Seasonal plates, natural wine and a room built for staying.',
                    'hours' => 'Tue–Sun from 17:30 · Sunday lunch 12:00–16:00',
                    'address' => '8 Rumens Road, Ikoyi, Lagos',
                ],
                'contact_intro' => 'Private dining, press, or a question about the menu? Send a note and the host desk will reply within a day.',
            ],
            'seed' => [
                'records' => [
                    ['ref' => 'EMT-SEED-01', 'name' => 'Kemi Alabi', 'email' => 'kemi.alabi@example.com', 'phone' => '+2348050000001', 'date' => '+0 days', 'time' => '19:30', 'a' => 'Dining room', 'b' => 'Anniversary', 'qty' => 2, 'notes' => 'One shellfish allergy. Quiet corner if possible.', 'status' => 'confirmed', 'payment' => 'not_required', 'amount' => 0],
                    ['ref' => 'EMT-SEED-02', 'name' => 'Ibrahim Sule', 'email' => 'ibrahim.sule@example.com', 'phone' => '+2348050000002', 'date' => '+1 day', 'time' => '20:00', 'a' => 'Chef’s counter', 'b' => 'Business dinner', 'qty' => 4, 'notes' => 'Two guests vegetarian.', 'status' => 'confirmed', 'payment' => 'not_required', 'amount' => 0],
                    ['ref' => 'EMT-SEED-03', 'name' => 'Sarah Okon', 'email' => 'sarah.okon@example.com', 'phone' => '+2348050000003', 'date' => '+2 days', 'time' => '13:00', 'a' => 'Terrace', 'b' => 'Birthday', 'qty' => 6, 'notes' => 'Bringing a cake. One high chair.', 'status' => 'pending', 'payment' => 'not_required', 'amount' => 0],
                    ['ref' => 'EMT-SEED-04', 'name' => 'Chuka Eze', 'email' => 'chuka.eze@example.com', 'phone' => '+2348050000004', 'date' => '-2 days', 'time' => '18:30', 'a' => 'Dining room', 'b' => 'No occasion', 'qty' => 2, 'notes' => '', 'status' => 'completed', 'payment' => 'not_required', 'amount' => 0],
                    ['ref' => 'EMT-SEED-05', 'name' => 'Halima Musa', 'email' => 'halima.musa@example.com', 'phone' => '+2348050000005', 'date' => '-4 days', 'time' => '19:00', 'a' => 'Private room', 'b' => 'Celebration', 'qty' => 10, 'notes' => 'Moved to next month.', 'status' => 'cancelled', 'payment' => 'not_required', 'amount' => 0],
                ],
                'products' => [
                    ['sku' => 'MENU-FOCACCIA', 'name' => 'Wood-fired focaccia', 'description' => 'Cultured butter and smoked salt. Baked to order, collected from the host desk.', 'price' => 4500, 'stock' => 18],
                    ['sku' => 'MENU-TART', 'name' => 'Burnt honey tart', 'description' => 'Whole tart for four, crème fraîche on the side. 24 hours notice.', 'price' => 12000, 'stock' => 8],
                    ['sku' => 'WINE-SKIN', 'name' => 'Skin-contact bottle', 'description' => 'A rotating natural orange wine from the list. Collect in person, no delivery.', 'price' => 28000, 'stock' => 14],
                    ['sku' => 'LUNCH-BOX', 'name' => 'Sunday lunch box', 'description' => 'Fire-roasted chicken, charred greens, focaccia and a tart. Serves two.', 'price' => 22000, 'stock' => 12],
                ],
                'messages' => [
                    ['name' => 'Ayo Bankole', 'email' => 'ayo.bankole@example.com', 'phone' => '+2348050000011', 'subject' => 'Private room for twelve', 'body' => 'We are planning a leaving dinner for twelve in December. Is the private room available on a Friday, and do you offer a set menu?', 'status' => 'new'],
                    ['name' => 'Reni Ojo', 'email' => 'reni.ojo@example.com', 'phone' => '+2348050000012', 'subject' => 'Gluten-free options', 'body' => 'My partner is coeliac. Is the kitchen able to prepare a gluten-free menu for a Saturday dinner?', 'status' => 'handled'],
                ],
            ],
        ],

        'generic' => [
            'key' => 'generic',
            'label' => 'Professional services',
            'industry' => 'Professional services',
            'description' => 'A clean, booking-led site for studios, consultants and service firms.',
            'default_name' => 'Meridian Studio',
            'palette' => 'slate',
            'fonts' => ['display' => 'Sora', 'body' => 'Inter', 'google' => 'family=Inter:wght@400;500;600;700&family=Sora:wght@400;500;600;700'],
            'variant' => 'clinic',
            'labels' => [
                'txn_table' => 'consultations',
                'txn_one' => 'consultation',
                'txn_plural' => 'consultations',
                'txn_ref' => 'MDS',
                'customer' => 'client',
                'customer_plural' => 'clients',
                'staff' => 'consultant',
                'staff_plural' => 'consultants',
                'book' => 'Book a consultation',
                'track' => 'Track a request',
                'track_title' => 'Check a consultation request',
                'track_intro' => 'Enter the reference from your confirmation email to see the status of your request.',
                'date' => 'Preferred date',
                'time' => 'Preferred time',
                'choice_a' => 'Service',
                'choice_b' => 'Consultant',
                'quantity' => 'Session length',
                'notes' => 'What would you like to cover?',
                'notes_help' => 'A sentence or two is enough — we will prepare before the call.',
                'form_title' => 'Request a consultation',
                'form_intro' => 'Tell us what you need and when suits you. We confirm every request by email.',
                'form_aside_title' => 'What happens next',
                'form_aside' => ['We review your request and confirm a time.', 'You receive a short agenda before the call.', 'No obligation after the first conversation.'],
                'portal' => 'Client portal',
                'admin' => 'Studio desk',
                'reference' => 'Request reference',
                'public_note' => 'For privacy this page shows only the first name on the booking.',
            ],
            'status_meta' => [
                'pending' => ['label' => 'Requested', 'tone' => 'warn', 'next' => ['confirmed', 'cancelled']],
                'confirmed' => ['label' => 'Confirmed', 'tone' => 'ok', 'next' => ['completed', 'no_show', 'cancelled']],
                'completed' => ['label' => 'Completed', 'tone' => 'ok', 'next' => []],
                'cancelled' => ['label' => 'Cancelled', 'tone' => 'bad', 'next' => []],
                'no_show' => ['label' => 'Did not attend', 'tone' => 'bad', 'next' => []],
            ],
            'initial_status' => 'pending',
            'reminder_statuses' => ['confirmed'],
            'close_past_status' => 'no_show',
            'form' => [
                'choices_a' => [
                    ['value' => 'Introductory consultation', 'price' => 25000],
                    ['value' => 'Strategy session', 'price' => 60000],
                    ['value' => 'Audit & review', 'price' => 90000],
                ],
                'choices_b' => ['First available', 'Lead consultant', 'Associate consultant'],
                'quantity_options' => [30, 45, 60],
                'quantity_min' => 1,
                'quantity_max' => 120,
                'times' => ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00'],
            ],
            'copy' => [
                'tagline' => 'Clear thinking for growing businesses.',
                'meta_description' => 'Meridian Studio — practical consulting for growing businesses. Book a consultation online and keep every decision on record.',
                'hero' => [
                    'eyebrow' => 'Independent consulting studio',
                    'headline' => 'Clear thinking,',
                    'accent' => 'written down.',
                    'lede' => 'A small studio for strategy, operations and the unglamorous follow-through. Book a conversation, get a written agenda, and leave with a decision you can act on.',
                    'primary' => 'Book a consultation',
                    'secondary' => 'Track a request',
                    'note' => 'Mon–Fri 9:00–17:00 · Remote-first · Lagos',
                    'panel_title' => 'How we work',
                    'panel' => [
                        ['Listen', 'A short call to understand the actual problem'],
                        ['Frame', 'A written brief with options and trade-offs'],
                        ['Deliver', 'The smallest useful thing, then iterate'],
                    ],
                    'panel_foot' => 'Every engagement starts with a written brief — not a pitch deck.',
                ],
                'sections' => [
                    [
                        'type' => 'services',
                        'eyebrow' => 'Services',
                        'title' => 'Three ways we can help.',
                        'lede' => 'Most work starts with a conversation and a written brief. Scope and fees are agreed before anything begins.',
                        'items' => [
                            ['icon' => '◇', 'title' => 'Strategy & positioning', 'text' => 'Work out what the business actually is, who it serves and what to stop doing. Delivered as a short, usable brief.', 'meta' => 'From ₦250,000'],
                            ['icon' => '⌁', 'title' => 'Operations & systems', 'text' => 'Map the work, remove the hand-offs that leak time, and put a simple system in place that people will use.', 'meta' => 'Project-based'],
                            ['icon' => '◍', 'title' => 'Ongoing advisory', 'text' => 'A standing monthly conversation for founders who need a thinking partner, not another report.', 'meta' => 'Monthly retainer'],
                        ],
                    ],
                    [
                        'type' => 'editorial',
                        'eyebrow' => 'The approach',
                        'title' => 'Good advice is specific, written and short.',
                        'body' => 'Consulting has a reputation for decks that restate the problem. We work the other way: a short brief, a decision, and the smallest useful implementation. If a question can be answered in a paragraph, it gets a paragraph — not a workshop.',
                        'bullets' => ['Written briefs instead of slide decks', 'Fixed scope agreed before work starts', 'Honest “this is not worth doing” conversations'],
                        'image' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1400&q=80',
                        'alt' => 'Small team working together at a table',
                        'caption' => 'Small engagements, senior attention, no hand-off to a junior team.',
                    ],
                    [
                        'type' => 'process',
                        'eyebrow' => 'Engagement',
                        'title' => 'A simple way in.',
                        'steps' => [
                            ['title' => 'Request a time', 'text' => 'Tell us the problem and pick a slot. We confirm with a short agenda.'],
                            ['title' => 'First conversation', 'text' => 'Thirty minutes to test whether we are the right fit. No charge, no pitch.'],
                            ['title' => 'Written brief', 'text' => 'If it makes sense to continue, you get scope, fee and a decision date in writing.'],
                        ],
                    ],
                    [
                        'type' => 'proof',
                        'items' => [
                            ['quote' => 'They spent the first meeting telling us which part of the project not to do. That saved us three months.', 'name' => 'Damilola A.', 'detail' => 'Founder, services business'],
                            ['quote' => 'The brief was four pages. We made a decision in one meeting and implemented it the same week.', 'name' => 'Ruth K.', 'detail' => 'COO, logistics company'],
                        ],
                    ],
                    [
                        'type' => 'faq',
                        'eyebrow' => 'Good to know',
                        'title' => 'Questions before a first call.',
                        'items' => [
                            ['q' => 'Do you work remotely?', 'a' => 'Yes. Most engagements run remotely with a written record of every decision.'],
                            ['q' => 'What does a first call cost?', 'a' => 'Nothing. The introductory call is thirty minutes and there is no obligation afterwards.'],
                            ['q' => 'How quickly can you start?', 'a' => 'Usually within two weeks. Urgent audits can sometimes start sooner — say so in the notes.'],
                        ],
                    ],
                    ['type' => 'cta', 'title' => 'Start with a conversation.', 'body' => 'Request a time, get a written agenda, and decide afterwards whether it is useful.', 'primary' => 'Book a consultation', 'secondary' => 'Track a request'],
                ],
                'footer' => [
                    'blurb' => 'An independent consulting studio for strategy, operations and honest advice.',
                    'hours' => 'Mon–Fri 9:00–17:00',
                    'address' => 'Remote-first · Lagos, Nigeria',
                ],
                'contact_intro' => 'A question about scope, availability or fees? Send a note and we will reply within a working day.',
            ],
            'seed' => [
                'records' => [
                    ['ref' => 'MDS-SEED-01', 'name' => 'Tola Fashola', 'email' => 'tola@example.com', 'phone' => '+2348060000001', 'date' => '+2 days', 'time' => '10:00', 'a' => 'Introductory consultation', 'b' => 'Lead consultant', 'qty' => 30, 'notes' => 'Pricing model for a second product line.', 'status' => 'confirmed', 'payment' => 'paid', 'amount' => 25000],
                    ['ref' => 'MDS-SEED-02', 'name' => 'Ngozi Umeh', 'email' => 'ngozi@example.com', 'phone' => '+2348060000002', 'date' => '+3 days', 'time' => '14:00', 'a' => 'Strategy session', 'b' => 'First available', 'qty' => 60, 'notes' => 'Team structure after a merger.', 'status' => 'pending', 'payment' => 'unpaid', 'amount' => 60000],
                    ['ref' => 'MDS-SEED-03', 'name' => 'Bode Ajayi', 'email' => 'bode@example.com', 'phone' => '+2348060000003', 'date' => '-5 days', 'time' => '11:00', 'a' => 'Audit & review', 'b' => 'Lead consultant', 'qty' => 60, 'notes' => 'Operations audit, distribution business.', 'status' => 'completed', 'payment' => 'paid', 'amount' => 90000],
                ],
                'products' => [],
                'messages' => [],
            ],
        ],
    ];

    return $profiles;
}

function fs_profile(string $key): array
{
    $profiles = fs_profiles();
    return $profiles[$key] ?? $profiles['generic'];
}

/** Pick the closest industry profile from the intake's free-text industry. */
function fs_profile_key(?string $industry, ?string $idea = ''): string
{
    $text = strtolower(trim((string)$industry . ' ' . (string)$idea));
    if ($text === '') {
        return 'generic';
    }
    if (preg_match('/dental|dentist|physio|clinic|medical|health|doctor|therapy|ortho/', $text)) {
        return 'dental';
    }
    if (preg_match('/courier|logistic|delivery|dispatch|shipping|freight|parcel|transport/', $text)) {
        return 'courier';
    }
    if (preg_match('/restaurant|hospitality|hotel|cafe|bar|food|dining|kitchen|catering/', $text)) {
        return 'restaurant';
    }
    return 'generic';
}
