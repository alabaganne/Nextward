<?php

namespace Tests\Feature;

use App\Billing\Credits;
use App\Models\CreditTransaction;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        Http::fake();
        $this->user = User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_uploads_must_be_pdfs_and_the_first_one_becomes_the_main_resume(): void
    {
        $this->actingAs($this->user)->post('/resumes', ['resume' => UploadedFile::fake()->create('cv.docx', 40)])->assertSessionHasErrors('resume');
        $this->actingAs($this->user)->post('/resumes', ['resume' => UploadedFile::fake()->create('First CV.pdf', 40, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post('/resumes', ['resume' => UploadedFile::fake()->create('second.pdf', 40, 'application/pdf')])->assertSessionHasNoErrors();

        [$first, $second] = Resume::orderBy('id')->get();
        $this->assertSame('First CV', $first->name);
        $this->assertSame('First CV.pdf', $first->file_name);
        $this->assertSame(['uploaded', 'ready', true], [$first->source, $first->status, $first->is_main]);
        $this->assertFalse($second->is_main);
        Storage::disk('local')->assertExists($first->path);

        $this->actingAs($this->user)->get('/profile/resume')->assertOk()->assertDownload('First CV.pdf');
        $this->actingAs($this->user)->get(route('resumes.download', $second))->assertOk()->assertDownload('second.pdf');
    }

    public function test_a_candidate_keeps_at_most_ten_resumes(): void
    {
        foreach (range(1, Resume::LIMIT) as $i) {
            $this->resume("Resume $i", $i === 1);
        }

        $this->actingAs($this->user)->post('/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')])->assertSessionHasErrors('resume');
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en'])->assertSessionHasErrors('resume');
        $this->assertSame(Resume::LIMIT, Resume::count());

        // Replacing the main resume does not need a free slot.
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en', 'replace' => true])->assertSessionHasNoErrors();
        $this->assertSame(Resume::LIMIT, Resume::count());
    }

    public function test_making_a_resume_the_main_one(): void
    {
        $first = $this->resume('First', true);
        $second = $this->resume('Second', false);
        $draft = $this->resume('Draft', false, 'draft');

        $this->actingAs($this->user)->post(route('resumes.main', $second))->assertRedirect();
        $this->assertFalse($first->fresh()->is_main);
        $this->assertTrue($second->fresh()->is_main);
        $this->assertSame(1, Resume::where('is_main', true)->count());

        $this->actingAs($this->user)->post(route('resumes.main', $draft))->assertStatus(409);
        $other = User::where('email', 'professional@example.com')->firstOrFail();
        $this->actingAs($other)->post(route('resumes.main', $first))->assertNotFound();
        $this->actingAs($other)->get(route('resumes.download', $first))->assertNotFound();
        $this->actingAs($other)->delete(route('resumes.destroy', $first))->assertNotFound();
    }

    public function test_deleting_the_main_resume_promotes_the_newest_ready_one(): void
    {
        $main = $this->resume('Main', true);
        $older = $this->resume('Older', false);
        $newer = $this->resume('Newer', false);
        $draft = $this->resume('Draft', false, 'draft');
        $older->forceFill(['updated_at' => now()->subDays(3)])->save();
        $newer->forceFill(['updated_at' => now()->subDay()])->save();

        $this->actingAs($this->user)->delete(route('resumes.destroy', $main))->assertRedirect(route('profile.show').'#resumes');

        Storage::disk('local')->assertMissing($main->path);
        $this->assertModelMissing($main);
        $this->assertTrue($newer->fresh()->is_main);
        $this->assertFalse($older->fresh()->is_main);
        $this->assertFalse($draft->fresh()->is_main);
    }

    public function test_the_public_profile_shares_the_main_resume(): void
    {
        $this->user->userable->forceFill(['public_status' => 'approved', 'public_contact' => true])->save();
        $first = $this->resume('First', true);
        $second = $this->resume('Second', false);
        $url = $this->user->userable->fresh()->publicUrl();

        $this->assertSame('%PDF-1.4 First', $this->get("$url/resume")->assertOk()->streamedContent());

        $this->actingAs($this->user)->post(route('resumes.main', $second));
        auth()->logout();
        $this->assertSame('%PDF-1.4 Second', $this->get("$url/resume")->assertOk()->streamedContent());

        $second->remove();
        $first->remove();
        $this->get("$url/resume")->assertNotFound();
    }

    public function test_a_resume_from_the_profile_is_free_and_makes_no_ai_call(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1]);
        $user = $this->verifiedCandidate();
        $user->userable->update(['headline' => 'Laravel developer', 'about' => 'I build web apps.']);
        $before = app(Credits::class)->balance($user);

        $this->actingAs($user)->post('/resumes/from-profile', ['locale' => 'fr', 'name' => 'Mon CV', 'style' => ['template' => 'atlas']])->assertRedirect();

        $resume = Resume::sole();
        $this->assertSame(['Mon CV', 'profile', 'ready', 'fr', true], [$resume->name, $resume->source, $resume->status, $resume->locale, $resume->is_main]);
        $this->assertSame('atlas', $resume->style['template']);
        $this->assertSame('navy', $resume->style['accent']);
        $this->assertSame('Buyer Name', $resume->content['name']);
        $this->assertSame('Laravel developer', $resume->content['headline']);
        $this->assertSame('Buyer_Name_CV.pdf', $resume->file_name);
        Storage::disk('local')->assertExists($resume->path);
        Http::assertNothingSent();
        $this->assertSame($before, app(Credits::class)->balance($user));
        $this->assertSame(0, CreditTransaction::where('user_id', $user->id)->where('amount', '<', 0)->count());
    }

    public function test_a_new_profile_resume_can_replace_the_main_one_or_sit_next_to_it(): void
    {
        $old = $this->resume('Old', true);

        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en'])->assertSessionHasNoErrors();
        $kept = Resume::where('source', 'profile')->sole();
        $this->assertTrue($kept->is_main);
        $this->assertFalse($old->fresh()->is_main);
        $this->assertSame(2, Resume::count());

        $this->actingAs($this->user->fresh())->post('/resumes/from-profile', ['locale' => 'en', 'replace' => true])->assertSessionHasNoErrors();
        $this->assertModelMissing($kept);
        Storage::disk('local')->assertMissing($kept->path);
        $this->assertSame(2, Resume::count());
        $this->assertTrue(Resume::latest('id')->first()->is_main);
        $this->assertNotNull($old->fresh());
    }

    public function test_themes_must_be_known_and_are_merged_on_save(): void
    {
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en', 'style' => ['template' => 'nope']])->assertSessionHasErrors('style.template');
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'ar'])->assertSessionHasErrors('locale');
        $this->assertSame(0, Resume::count());

        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en', 'style' => ['template' => 'soren', 'accent' => 'plum']]);
        $resume = Resume::sole();
        $path = $resume->path;

        $this->actingAs($this->user)->putJson(route('resumes.update', $resume), ['style' => ['paper' => 'huge']])->assertStatus(422)->assertJsonValidationErrors('style.paper');
        $this->actingAs($this->user)->putJson(route('resumes.update', $resume), ['style' => ['paper' => 'letter']])->assertOk()
            ->assertJsonPath('resume.style', ['template' => 'soren', 'accent' => 'plum', 'font' => 'sans', 'paper' => 'letter']);

        // A new style prints a new PDF in place of the old one.
        $this->assertNotSame($path, $resume->fresh()->path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_an_uploaded_pdf_keeps_its_file_when_renamed(): void
    {
        $resume = $this->resume('Upload', true);

        $this->actingAs($this->user)->putJson(route('resumes.update', $resume), ['name' => 'Renamed', 'style' => ['template' => 'atlas'], 'content' => ['name' => 'X']])->assertOk()
            ->assertJsonPath('resume.name', 'Renamed')
            ->assertJsonPath('resume.content', null);
        $this->assertSame($resume->path, $resume->fresh()->path);
        $this->assertNull($resume->fresh()->style);
    }

    public function test_refresh_lays_a_profile_resume_out_again_from_the_profile(): void
    {
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en']);
        $resume = Resume::sole();
        $this->user->userable->update(['headline' => 'Senior Laravel developer']);

        $this->actingAs($this->user->fresh())->post(route('resumes.refresh', $resume))->assertRedirect();
        $this->assertSame('Senior Laravel developer', $resume->fresh()->content['headline']);

        $this->actingAs($this->user)->post(route('resumes.refresh', $this->resume('Upload', false)))->assertNotFound();
    }

    public function test_the_editor_pages_open_for_the_owner(): void
    {
        $this->actingAs($this->user)->get('/resumes/new')->assertOk();
        $this->actingAs($this->user)->post('/resumes/from-profile', ['locale' => 'en']);
        $resume = Resume::sole();

        $this->actingAs($this->user)->get(route('resumes.show', $resume))->assertOk();
        $this->actingAs($this->user)->getJson(route('resumes.show', $resume))->assertOk()
            ->assertJsonPath('resume.id', $resume->id)
            ->assertJsonPath('resume.labels.experience', 'Experience')
            ->assertJsonPath('resume.content.name', 'Ala Baganne');
        $this->actingAs(User::where('email', 'professional@example.com')->firstOrFail())->getJson(route('resumes.show', $resume))->assertNotFound();
    }

    private function resume(string $name, bool $main, string $status = 'ready'): Resume
    {
        $path = 'resumes/'.str($name)->slug().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 '.$name);

        return Resume::create([
            'candidate_id' => $this->user->userable_id, 'name' => $name, 'source' => 'uploaded', 'status' => $status,
            'path' => $path, 'file_name' => str($name)->slug().'.pdf', 'is_main' => $main,
        ]);
    }

    private function verifiedCandidate(): User
    {
        $user = \App\Models\Candidate::create()->user()->create(['name' => 'Buyer Name', 'email' => 'buyer@example.com', 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
