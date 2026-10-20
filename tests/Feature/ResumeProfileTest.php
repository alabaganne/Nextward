<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    public function test_the_resume_is_read_into_suggestions_without_saving_anything(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $before = $user->userable->fresh()->toArray();
        $this->actingAs($user)->post('/app/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([
            'headline' => 'Laravel developer', 'about' => 'I build web apps.', 'experience_years' => 2,
            'location' => 'Tunis, Tunisia', 'country_code' => 'tn', 'phone_number' => '', 'website' => 'not a link',
            'linkedin_profile_url' => 'https://linkedin.com/in/demo', 'languages' => ['French', 'English', 'french'],
            'skills' => ['Laravel', 'Vue'],
            'experience' => [['title' => 'Developer', 'company' => 'Acme', 'location' => '', 'start' => '2023-01', 'end' => '', 'current' => true, 'summary' => 'Built APIs.'], ['title' => '', 'company' => 'Empty', 'location' => '', 'start' => '', 'end' => '', 'current' => false, 'summary' => '']],
            'education' => [['school' => 'ISSAT Sousse', 'degree' => 'Engineering', 'field' => 'Software', 'start' => '2018', 'end' => '2023']],
            'projects' => [['name' => 'Nextward', 'summary' => 'Job board', 'url' => 'https://example.com']],
        ]))]);

        $this->actingAs($user)->postJson('/app/profile/resume/read')->assertOk()
            ->assertJsonPath('suggestions.headline', 'Laravel developer')
            ->assertJsonPath('suggestions.country_code', 'TN')
            ->assertJsonPath('suggestions.website', '')
            ->assertJsonPath('suggestions.languages', ['French', 'English'])
            ->assertJsonCount(1, 'suggestions.experience')
            ->assertJsonPath('suggestions.experience.0.current', true)
            ->assertJsonPath('suggestions.education.0.school', 'ISSAT Sousse');

        $after = $user->userable->fresh()->toArray();
        $this->assertSame($before['headline'], $after['headline']);
        $this->assertSame($before['about'], $after['about']);
        $this->assertEmpty($after['experience']);
        Http::assertSent(fn ($request) => $request['model'] === 'gpt-5.4-mini'
            && collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : [])->contains('type', 'input_file'));
    }

    public function test_reading_needs_a_resume_and_ai(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $this->actingAs($user)->postJson('/app/profile/resume/read')->assertNotFound();

        $this->actingAs($user)->post('/app/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        config(['ai.providers.openai.key' => null]);
        $this->actingAs($user->fresh())->postJson('/app/profile/resume/read')->assertStatus(503);
    }

    public function test_a_phone_number_used_by_another_account_is_not_suggested(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();
        User::where('email', 'admin@example.com')->update(['phone_number' => '+216 50 101 959']);
        $this->actingAs($user)->post('/app/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([
            'headline' => '', 'about' => '', 'experience_years' => 0, 'location' => '', 'country_code' => '', 'phone_number' => '+216 50 101 959',
            'website' => '', 'linkedin_profile_url' => '', 'languages' => [], 'skills' => [], 'experience' => [], 'education' => [], 'projects' => [],
        ]))]);

        $this->actingAs($user)->postJson('/app/profile/resume/read')->assertOk()->assertJsonPath('suggestions.phone_number', '');
        $this->actingAs($user)->put('/app/profile', ['name' => $user->name, 'phone_number' => '+216 50 101 959'])
            ->assertSessionHasErrors(['phone_number' => 'Another account already uses this phone number.']);
    }

    public function test_reading_costs_a_credit_needs_a_verified_email_and_refunds_on_failure(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1]);
        $user = \App\Models\Candidate::create()->user()->create(['name' => 'Buyer Name', 'email' => 'buyer@example.com', 'password' => 'password']);
        $this->actingAs($user)->post('/app/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        $credits = app(\App\Billing\Credits::class);

        $this->actingAs($user->fresh())->postJson('/app/profile/resume/read')->assertForbidden();

        $user->forceFill(['email_verified_at' => now()])->save();
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()->push(['error' => ['message' => 'Server error']], 500)->push($this->reply([
            'language' => 'fr', 'headline' => 'Développeur', 'about' => '', 'experience_years' => 0, 'location' => '', 'country_code' => '', 'phone_number' => '',
            'website' => '', 'linkedin_profile_url' => '', 'languages' => [], 'skills' => [], 'experience' => [], 'education' => [], 'projects' => [],
        ]))]);

        $this->actingAs($user->fresh())->postJson('/app/profile/resume/read')->assertStatus(502);
        $this->assertSame(1, $credits->balance($user));

        $this->actingAs($user->fresh())->postJson('/app/profile/resume/read')->assertOk()->assertJsonPath('suggestions.headline', 'Développeur');
        $this->assertSame(0, $credits->balance($user));
        // The language read from the resume is kept on it.
        $this->assertSame('fr', $user->userable->mainResume->fresh()->locale);

        $this->actingAs($user->fresh())->postJson('/app/profile/resume/read')->assertStatus(402);
    }

    public function test_settings_saves_experience_education_and_projects(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($user)->put('/app/profile', [
            'name' => $user->name, 'email' => $user->email,
            'experience' => [['title' => 'Developer', 'company' => 'Acme', 'start' => '2023-01', 'current' => true, 'junk' => 'x']],
            'education' => [['school' => 'ISSAT Sousse', 'degree' => 'Engineering']],
            'projects' => [['name' => 'Nextward', 'url' => 'https://example.com']],
        ])->assertSessionHasNoErrors();

        $candidate = $user->userable->fresh();
        $this->assertSame('Developer', $candidate->experience[0]['title']);
        $this->assertTrue($candidate->experience[0]['current']);
        $this->assertArrayNotHasKey('junk', $candidate->experience[0]);
        $this->assertSame('ISSAT Sousse', $candidate->education[0]['school']);
        $this->assertSame('https://example.com', $candidate->projects[0]['url']);

        $this->actingAs($user)->put('/app/profile', ['name' => $user->name, 'email' => $user->email, 'experience' => [['company' => 'No title']]])
            ->assertSessionHasErrors('experience.0.title');
    }

    private function reply(array $data): array
    {
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}
