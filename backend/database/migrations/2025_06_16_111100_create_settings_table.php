<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string, text, boolean, json, image
            $table->string('group')->default('general'); // general, contact, team, maintenance
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            // Site Identity
            [
                'key' => 'site_name',
                'value' => 'Rapollo E-Commerce',
                'type' => 'string',
                'group' => 'site',
                'description' => 'The name of your website',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'site',
                'description' => 'Your website logo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_about',
                'value' => 'Welcome to our e-commerce store. We offer quality products at affordable prices.',
                'type' => 'text',
                'group' => 'site',
                'description' => 'About your website',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Contact Information
            [
                'key' => 'contact_email',
                'value' => 'info@rapollo.com',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Primary contact email',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_phone',
                'value' => '+63 123 456 7890',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Primary contact phone',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_address',
                'value' => '123 Main Street, Manila, Philippines',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Physical address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_facebook',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Facebook page URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_instagram',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Instagram profile URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_twitter',
                'value' => null,
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Twitter profile URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Team Information
            [
                'key' => 'team_members',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'team',
                'description' => 'Team members information',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Maintenance Mode
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Enable or disable maintenance mode',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'We are currently performing scheduled maintenance. Please check back soon!',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Message displayed during maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

