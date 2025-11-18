<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the application's settings table.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            // Site Identity
            [
                'key' => 'site_name',
                'value' => 'RAPOLLO',
                'type' => 'string',
                'group' => 'site',
                'description' => 'The name of your website',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'site',
                'description' => 'Your website logo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'site_about',
                'value' => 'Welcome to our e-commerce store. We offer quality products at affordable prices.',
                'type' => 'text',
                'group' => 'site',
                'description' => 'About your website',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Contact Information
            [
                'key' => 'contact_email',
                'value' => 'info@rapollo.com',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Primary contact email',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+63 123 456 7890',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Primary contact phone',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_address',
                'value' => 'J.P Rizal St, North Road, Mandaue, 6014 Cebu',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Physical address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Store map links
            [
                'key' => 'iframe_link',
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d245.30648046083556!2d123.94873493250614!3d10.349585840747995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a999da5b97a80f%3A0xa12258794ff22b56!2sApollo%20Sports%20Bar!5e0!3m2!1sen!2sph!4v1761154289778!5m2!1sen!2sph',
                'type' => 'text',
                'group' => 'store',
                'description' => 'Google Maps iframe embed URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'gmap_link',
                'value' => 'https://www.google.com/maps/place/Apollo+Sports+Bar/@10.3495858,123.9487349,17z/data=!3m1!4b1!4m6!3m5!1s0x33a999da5b97a80f:0xa12258794ff22b56!8m2!3d10.3495858!4d123.9487349!16s%2Fg%2F11c0q8q8q8',
                'type' => 'text',
                'group' => 'store',
                'description' => 'Google Maps place URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_facebook',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Facebook page URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_instagram',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Instagram profile URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_twitter',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Twitter profile URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Team Information
            [
                'key' => 'team_members',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'team',
                'description' => 'Team members information',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Maintenance Mode
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Enable or disable maintenance mode',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'We are currently performing scheduled maintenance. Please check back soon!',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Message displayed during maintenance',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Structured Store Address (source of truth)
            [
                'key' => 'store_street',
                'value' => 'J.P Rizal St, North Road',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Store street address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'store_barangay',
                'value' => null,
                'type' => 'string',
                'group' => 'store',
                'description' => 'Store barangay (optional)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'store_city',
                'value' => 'Mandaue',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Store city/municipality',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'store_province',
                'value' => 'Cebu',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Store province',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'store_zipcode',
                'value' => '6014',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Store ZIP/postal code',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->upsert(
            $rows,
            ['key'],
            ['value', 'type', 'group', 'description', 'updated_at']
        );

        // Best-effort backfill from contact_address if structured fields are empty
        $storeCity = DB::table('settings')->where('key', 'store_city')->value('value');
        $storeProvince = DB::table('settings')->where('key', 'store_province')->value('value');
        $storeZip = DB::table('settings')->where('key', 'store_zipcode')->value('value');
        $contactAddress = DB::table('settings')->where('key', 'contact_address')->value('value');

        if ((!$storeCity || !$storeProvince) && $contactAddress) {
            // Try to parse: ", City, Province ZIP"
            if (preg_match('/,\s*([^,]+),\s*([^,\d]+)\s*(\d{4})?/i', (string) $contactAddress, $m)) {
                $city = trim($m[1] ?? '');
                $province = trim($m[2] ?? '');
                $zip = trim($m[3] ?? '');

                $updates = [];
                if (!$storeCity && $city) {
                    $updates[] = [
                        'key' => 'store_city',
                        'value' => $city,
                        'type' => 'string',
                        'group' => 'store',
                        'description' => 'Store city/municipality',
                        'updated_at' => $now,
                    ];
                }
                if (!$storeProvince && $province) {
                    $updates[] = [
                        'key' => 'store_province',
                        'value' => $province,
                        'type' => 'string',
                        'group' => 'store',
                        'description' => 'Store province',
                        'updated_at' => $now,
                    ];
                }
                if (!$storeZip && $zip) {
                    $updates[] = [
                        'key' => 'store_zipcode',
                        'value' => $zip,
                        'type' => 'string',
                        'group' => 'store',
                        'description' => 'Store ZIP/postal code',
                        'updated_at' => $now,
                    ];
                }

                if (!empty($updates)) {
                    DB::table('settings')->upsert($updates, ['key'], ['value', 'type', 'group', 'description', 'updated_at']);
                }
            }
        }
    }
}


