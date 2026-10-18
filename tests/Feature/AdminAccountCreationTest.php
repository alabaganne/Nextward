<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Field;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_accounts_receive_private_random_passwords_and_linked_profiles(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $city = City::create(['name' => 'Tunis']);
        $field = Field::create(['name' => 'Engineering']);
        $passwords = [];

        foreach (['student', 'company'] as $role) {
            $resource = $role === 'company' ? 'companies' : 'students';
            $this->actingAs($admin)->post(route("{$resource}.store"), [
                'name' => "New {$role}",
                'email' => "{$role}@example.com",
                'city_id' => $city->id,
                'field_id' => $field->id,
                'website' => 'https://example.com',
                'about' => 'A profile created by the administrator.',
            ])->assertRedirect(route("{$resource}.index"));

            $user = User::where('email', "{$role}@example.com")->firstOrFail();
            $this->assertSame($role, $user->userable_type);
            $this->assertSame($city->id, $user->userable->city_id);
            $this->assertFalse($user->isAdmin());
            $this->assertFalse(Hash::check('password', $user->password));
            $this->assertFalse(Hash::needsRehash($user->password));
            $this->assertArrayNotHasKey('password', $user->toArray());
            $passwords[] = $user->password;
        }

        $this->assertNotSame($passwords[0], $passwords[1]);
        Notification::assertSentTimes(\App\Notifications\AccountCreated::class, 2);
    }

    public function test_admin_student_creation_validates_supplied_profile_references(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'New student',
            'email' => 'student@example.com',
            'field_id' => 9999,
            'city_id' => 9999,
        ])->assertSessionHasErrors(['field_id', 'city_id']);

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_create_worldwide_candidates_without_local_profile_references(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'New candidate',
            'email' => 'candidate@example.com',
            'country_code' => 'FR',
            'location' => 'Paris',
            'field_id' => null,
            'city_id' => null,
        ])->assertRedirect(route('students.index'))->assertSessionHasNoErrors();

        $profile = User::where('email', 'candidate@example.com')->firstOrFail()->userable;
        $this->assertSame('FR', $profile->country_code);
        $this->assertSame('Paris', $profile->location);
        $this->assertNull($profile->field_id);
        $this->assertNull($profile->city_id);
    }

    public function test_members_cannot_create_other_accounts_through_admin_routes(): void
    {
        $city = City::create(['name' => 'Tunis']);
        $field = Field::create(['name' => 'Engineering']);
        $student = Student::create(['city_id' => $city->id, 'field_id' => $field->id]);
        $user = $student->user()->create([
            'name' => 'Existing student',
            'email' => 'existing@example.com',
            'password' => Hash::make('existing-password'),
        ]);

        foreach (['students.store', 'companies.store'] as $route) {
            $this->actingAs($user)->post(route($route), [
                'name' => 'New account',
                'email' => 'new@example.com',
                'city_id' => $city->id,
                'field_id' => $field->id,
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('users', 1);
    }
}
