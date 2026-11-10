<?php
/**
 * Generated site configuration. Edit through ForgeStack when possible;
 * business copy lives here, secrets live in .env.
 */
declare(strict_types=1);

return json_decode(<<<'JSON'
{
    "app_name": "Kite Courier",
    "description": "Kite Courier — same-day and express courier across Lagos with live tracking, vetted riders and a public proof-of-delivery trail.",
    "tagline": "Same-day courier, without the guesswork.",
    "profile": "courier",
    "variant": "courier",
    "palette": {
        "name": "Midnight indigo & signal amber",
        "description": "Operations-grade indigo with an amber signal colour.",
        "ink": "#0d1526",
        "brand": "#3b4fe0",
        "brand-deep": "#101a3d",
        "accent": "#f0a93c",
        "paper": "#f3f5fa",
        "surface": "#ffffff",
        "soft": "#e7eaf6",
        "line": "#dfe3ee",
        "muted": "#5d6a83",
        "on-brand": "#ffffff"
    },
    "fonts": {
        "display": "Space Grotesk",
        "body": "Inter",
        "google": "family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700"
    },
    "pages": [
        {
            "path": "/",
            "label": "Home",
            "title": "Home",
            "nav": true
        },
        {
            "path": "/book",
            "label": "Book a pickup",
            "title": "Book a pickup",
            "nav": false
        },
        {
            "path": "/track",
            "label": "Track a shipment",
            "title": "Track a shipment",
            "nav": true
        },
        {
            "path": "/about",
            "label": "About",
            "title": "About",
            "nav": true
        },
        {
            "path": "/services",
            "label": "Services",
            "title": "Services & pricing",
            "nav": true
        },
        {
            "path": "/faq",
            "label": "FAQ",
            "title": "FAQ",
            "nav": false
        },
        {
            "path": "/contact",
            "label": "Contact",
            "title": "Contact",
            "nav": true
        },
        {
            "path": "/login",
            "label": "Sign in",
            "title": "Sign in",
            "nav": false
        },
        {
            "path": "/register",
            "label": "Create account",
            "title": "Create account",
            "nav": false
        },
        {
            "path": "/portal",
            "label": "Shipper portal",
            "title": "Shipper portal",
            "nav": false
        },
        {
            "path": "/admin",
            "label": "Dispatch desk",
            "title": "Dispatch desk",
            "nav": false
        },
        {
            "path": "/admin/records",
            "label": "All shipments",
            "title": "All shipments",
            "nav": false
        },
        {
            "path": "/admin/users",
            "label": "People",
            "title": "People",
            "nav": false
        },
        {
            "path": "/admin/outbox",
            "label": "Email outbox",
            "title": "Email outbox",
            "nav": false
        },
        {
            "path": "/admin/messages",
            "label": "Enquiries",
            "title": "Enquiries",
            "nav": false
        },
        {
            "path": "/admin/reports",
            "label": "Reports",
            "title": "Reports",
            "nav": false
        }
    ],
    "contact": {
        "name": "Operations desk",
        "email": "hello@kitecourier.ng",
        "phone": "+234 801 555 0100",
        "address": "14 Allen Avenue, Ikeja, Lagos"
    },
    "copy": {
        "tagline": "Same-day courier, without the guesswork.",
        "meta_description": "Kite Courier — same-day and express courier across Lagos with live tracking, vetted riders and a public proof-of-delivery trail.",
        "hero": {
            "eyebrow": "Same-day courier across Lagos",
            "headline": "Every parcel,",
            "accent": "accounted for.",
            "lede": "Book a pickup in two minutes, hand it to a vetted rider, and give your customer a tracking page that updates on every scan. No WhatsApp group, no “the rider is coming” guesswork.",
            "primary": "Book a pickup",
            "secondary": "Track a shipment",
            "note": "Same-day cutoff 14:00 · 148 riders on shift · Lagos mainland & island",
            "panel_title": "Today’s network",
            "panel": [
                [
                    "148",
                    "vetted riders on shift"
                ],
                [
                    "98.4%",
                    "delivered inside the promise"
                ],
                [
                    "3.2 h",
                    "median same-day delivery"
                ]
            ],
            "panel_foot": "Every shipment gets a tracking code and an immutable scan trail."
        },
        "sections": [
            {
                "type": "services",
                "eyebrow": "Services",
                "title": "One network, four ways to move.",
                "lede": "Priced by service, not by negotiation. Pickup windows are promised, riders are vetted and every handover is logged.",
                "items": [
                    {
                        "icon": "⟶",
                        "title": "Same-day dispatch",
                        "text": "Booked before 14:00, delivered before 18:00 across Lagos. Live rider assignment and customer updates.",
                        "meta": "From ₦3,500"
                    },
                    {
                        "icon": "▣",
                        "title": "E-commerce fulfilment",
                        "text": "Scheduled daily collections from your store, with proof of delivery pushed back to your team.",
                        "meta": "From ₦2,800 / parcel"
                    },
                    {
                        "icon": "⌁",
                        "title": "Scheduled routes",
                        "text": "Recurring runs for pharmacies, labs and professional services. Same window, same rider where possible.",
                        "meta": "Custom route plan"
                    },
                    {
                        "icon": "◈",
                        "title": "Fragile & medical",
                        "text": "Cold-chain boxes, samples and fragile freight with handling notes attached to the job sheet.",
                        "meta": "From ₦8,000"
                    }
                ]
            },
            {
                "type": "editorial",
                "eyebrow": "Why operators switch",
                "title": "A WhatsApp group is not a dispatch system.",
                "body": "Most small courier operators run on screenshots, voice notes and hope. When a customer asks where their parcel is, nobody can answer with confidence. Kestrel gives every shipment a code, every status change an author, and every customer the same live page your dispatchers use.",
                "bullets": [
                    "One job sheet per rider, updated on every scan",
                    "Public tracking without exposing phone numbers",
                    "Daily digest of overdue pickups and failed attempts"
                ],
                "image": "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1400&q=80",
                "alt": "Courier loading parcels into a delivery van",
                "caption": "Dispatch assigns the rider; the rider updates the trail; the customer sees both."
            },
            {
                "type": "stats",
                "items": [
                    {
                        "value": "98.4%",
                        "label": "On-time delivery"
                    },
                    {
                        "value": "42,000",
                        "label": "Parcels moved"
                    },
                    {
                        "value": "148",
                        "label": "Vetted riders"
                    },
                    {
                        "value": "3.2 h",
                        "label": "Median same-day"
                    }
                ]
            },
            {
                "type": "process",
                "eyebrow": "Dispatch",
                "title": "From booking to proof, in four scans.",
                "lede": "The customer sees the same status the dispatcher does. Nothing is updated by memory.",
                "steps": [
                    {
                        "title": "Book",
                        "text": "Pickup zone, destination, weight and service. The system issues a tracking code immediately."
                    },
                    {
                        "title": "Assign",
                        "text": "A dispatcher checks capacity and attaches a rider to the job sheet."
                    },
                    {
                        "title": "Scan",
                        "text": "Pickup, transit and delivery each write an immutable event with a timestamp."
                    },
                    {
                        "title": "Prove",
                        "text": "Delivery closes the loop and emails the shipper a receipt they can forward."
                    }
                ]
            },
            {
                "type": "coverage",
                "eyebrow": "Coverage",
                "title": "Lagos, end to end.",
                "body": "Same-day coverage across the island and mainland. Outside the zones below we run scheduled routes twice daily.",
                "zones": [
                    "Lekki & Ajah",
                    "Victoria Island & Ikoyi",
                    "Yaba & Surulere",
                    "Ikeja & Maryland",
                    "Ikorodu (scheduled)",
                    "Epe (scheduled)"
                ]
            },
            {
                "type": "proof",
                "items": [
                    {
                        "quote": "We moved our pharmacy deliveries to Kestrel in March. The tracking page cut “where is my order” messages by roughly two thirds.",
                        "name": "Tola A.",
                        "detail": "Operations lead, community pharmacy"
                    },
                    {
                        "quote": "The dispatcher called to flag a failed gate code before the customer even noticed. That is the difference.",
                        "name": "Nneka O.",
                        "detail": "E-commerce founder"
                    }
                ]
            },
            {
                "type": "faq",
                "eyebrow": "Good to know",
                "title": "Before you book.",
                "items": [
                    {
                        "q": "What cannot I send?",
                        "a": "Cash, live animals, weapons and unlicensed pharmaceuticals. Medical samples need prior approval and the fragile/medical service."
                    },
                    {
                        "q": "Is my parcel insured?",
                        "a": "Every shipment carries a base cover of ₦50,000. Declare a higher value in the pickup notes and we will quote additional cover."
                    },
                    {
                        "q": "How does proof of delivery work?",
                        "a": "The rider closes the job with a timestamped delivery event. The shipper receives an email with the tracking link and status."
                    },
                    {
                        "q": "Do you deliver on Sundays?",
                        "a": "Only scheduled routes and medical collections. Same-day dispatch runs Monday to Saturday."
                    }
                ]
            },
            {
                "type": "cta",
                "title": "Book before 14:00. Delivered before 18:00.",
                "body": "Get a tracking code in seconds and a scan trail your customer can actually read.",
                "primary": "Book a pickup",
                "secondary": "Track a shipment"
            }
        ],
        "footer": {
            "blurb": "Same-day and express courier across Lagos, with live tracking and an auditable scan trail for every parcel.",
            "hours": "Mon–Sat 07:30–19:00 · Sunday scheduled routes only",
            "address": "4 Admiralty Way, Lekki Phase 1, Lagos"
        },
        "contact_intro": "New route, bulk collection or a shipment that needs eyes on it? Send the details and a dispatcher will reply within the hour."
    },
    "labels": {
        "txn_table": "shipments",
        "txn_one": "shipment",
        "txn_plural": "shipments",
        "txn_ref": "KES",
        "customer": "shipper",
        "customer_plural": "shippers",
        "staff": "dispatcher",
        "staff_plural": "dispatch team",
        "book": "Book a pickup",
        "track": "Track a shipment",
        "track_title": "Track a shipment",
        "track_intro": "Enter the tracking code from your booking confirmation. The page updates on every rider scan.",
        "date": "Pickup date",
        "time": "Pickup window",
        "choice_a": "Service",
        "choice_b": "Destination zone",
        "quantity": "Weight",
        "notes": "Pickup notes",
        "notes_help": "Address landmarks, gate codes, or what is inside the parcel.",
        "form_title": "Book a pickup",
        "form_intro": "Tell us where to collect and where it is going. A dispatcher assigns a rider and you get a tracking code by email.",
        "form_aside_title": "How the pickup runs",
        "form_aside": [
            "A dispatcher reviews the request within 10 minutes.",
            "A vetted rider is assigned with a live job sheet.",
            "Every scan updates the public tracking page."
        ],
        "portal": "Shipper portal",
        "admin": "Dispatch desk",
        "reference": "Tracking code",
        "public_note": "Tracking pages show the recipient name only — never contact details.",
        "shop_title": "Packaging shop",
        "quantity_unit": "parcels"
    },
    "status_meta": {
        "requested": {
            "label": "Requested",
            "tone": "warn",
            "next": [
                "scheduled",
                "cancelled"
            ]
        },
        "scheduled": {
            "label": "Scheduled",
            "tone": "info",
            "next": [
                "in_transit",
                "cancelled"
            ]
        },
        "in_transit": {
            "label": "In transit",
            "tone": "info",
            "next": [
                "delivered",
                "failed"
            ]
        },
        "delivered": {
            "label": "Delivered",
            "tone": "ok",
            "next": []
        },
        "failed": {
            "label": "Failed",
            "tone": "bad",
            "next": [
                "scheduled"
            ]
        },
        "cancelled": {
            "label": "Cancelled",
            "tone": "bad",
            "next": []
        }
    },
    "initial_status": "requested",
    "reminder_statuses": [
        "scheduled"
    ],
    "close_past_status": "failed",
    "form": {
        "choices_a": [
            {
                "value": "Same-day (by 18:00)",
                "price": 3500
            },
            {
                "value": "Express 4-hour",
                "price": 6500
            },
            {
                "value": "Scheduled route",
                "price": 2800
            },
            {
                "value": "Fragile & medical",
                "price": 8000
            }
        ],
        "choices_b": [
            "Lekki & Ajah",
            "Victoria Island & Ikoyi",
            "Yaba & Surulere",
            "Ikeja & Maryland"
        ],
        "quantity_min": 1,
        "quantity_max": 60,
        "times": [
            "08:00–10:00",
            "10:00–12:00",
            "12:00–14:00",
            "14:00–16:00",
            "16:00–18:00"
        ]
    },
    "modules": {
        "auth": true,
        "admin": true,
        "bookings": true,
        "orders": false,
        "payments": false,
        "messaging": true,
        "reports": true
    },
    "features": [
        "auth",
        "admin",
        "bookings",
        "messaging",
        "reports"
    ]
}
JSON, true);
