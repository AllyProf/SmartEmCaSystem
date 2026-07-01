<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\VisitConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function visitor_confirmation_auto_registers_customer()
    {
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@emca.tech',
            'phone' => '0712345678',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Mock staff verified email in session
        $session = ['verified_staff_email' => 'staff@emca.tech'];

        $response = $this->withSession($session)->post(route('visits.store'), [
            'type' => 'single',
            'visit_date' => now()->format('Y-m-d'),
            'customer_name' => 'John Doe',
            'phone' => '255712345678',
            'location' => 'Dar es Salaam',
            'representative_name' => 'Jane Staff',
            'purpose' => 'System Support',
        ]);

        $response->assertRedirect();

        // Verify Customer was automatically created in the database
        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'phone_number' => '255712345678',
            'location' => 'Dar es Salaam',
            'visiting_purpose' => 'System Support',
            'created_by' => $staff->id,
        ]);
    }

    /** @test */
    public function group_visit_attendees_are_registered_as_customers()
    {
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@emca.tech',
            'phone' => '0712345678',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $signature = 'data:image/png;base64,' . base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        $response = $this->withSession(['verified_staff_email' => 'staff@emca.tech'])->post(route('visits.store'), [
            'type' => 'group',
            'subject' => 'ICT Training',
            'attendees' => [
                [
                    'name' => 'Alice Group',
                    'institution' => 'Kilimanjaro School',
                    'position' => 'Teacher',
                    'phone' => '0711223344',
                    'email' => 'alice@example.com',
                    'signature' => $signature,
                ],
                [
                    'name' => 'Bob Group',
                    'institution' => 'Moshi College',
                    'position' => 'Principal',
                    'phone' => '0755667788',
                    'signature' => $signature,
                ],
            ],
        ]);

        $response->assertRedirect(route('visits.success'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Alice Group',
            'phone_number' => '255711223344',
            'location' => 'Kilimanjaro School',
            'visiting_purpose' => 'ICT Training',
            'created_by' => $staff->id,
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Bob Group',
            'phone_number' => '255755667788',
            'location' => 'Moshi College',
            'visiting_purpose' => 'ICT Training',
            'created_by' => $staff->id,
        ]);
    }

    /** @test */
    public function sms_scheduling_stores_scheduled_log()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@emcatech.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone_number' => '255712345678',
            'created_by' => $admin->id
        ]);

        $scheduleTime = now()->addHours(2)->format('Y-m-d\TH:i');

        $response = $this->actingAs($admin)->post(route('sms.store'), [
            'send_to' => 'single',
            'customer_id' => $customer->id,
            'message' => 'Hello {name}, this is scheduled.',
            'sms_type' => 'custom',
            'is_scheduled' => '1',
            'scheduled_at' => $scheduleTime,
        ]);

        $response->assertRedirect();
        
        // Verify SmsLog exists in database as scheduled
        $this->assertDatabaseHas('sms_logs', [
            'customer_id' => $customer->id,
            'phone_number' => '255712345678',
            'message' => 'Hello Test Customer, this is scheduled.',
            'status' => 'scheduled',
        ]);
    }

    /** @test */
    public function reports_page_loads_successfully_for_authenticated_users()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@emcatech.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Reports & Analytics');
        $response->assertSee('Total Customers');
    }
}
