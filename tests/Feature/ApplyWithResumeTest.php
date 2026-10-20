<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationKit;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplyWithResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Opportunity $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        $this->user = User::where('email', 'student@example.com')->firstOrFail();
        $this->role = Opportunity::where('application_mode', 'internal')
            ->whereDoesntHave('applications', fn ($query) => $query->where('candidate_id', $this->user->userable_id))->firstOrFail();
    }

    public function test_the_apply_page_offers_ready_resumes_and_this_listings_tailored_cvs(): void
    {
        $main = $this->resume('Main CV', true);
        $this->resume('Draft', false, 'draft');
        $kit = $this->kit();
        $this->kit(Opportunity::whereKeyNot($this->role->id)->firstOrFail());

        $this->actingAs($this->user)->get(route('applications.create', $this->role))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Applications/Edit')
            ->has('resumes', 1)
            ->where('resumes.0.id', $main->id)
            ->where('resumes.0.is_main', true)
            ->has('kits', 1)
            ->where('kits.0.id', $kit->id));
    }

    public function test_the_chosen_resume_is_copied_and_sent_first(): void
    {
        $resume = $this->resume('Main CV', true);

        $this->apply(['resume_id' => $resume->id, 'attachment_files' => [UploadedFile::fake()->create('sample.pdf', 20, 'application/pdf')]])->assertRedirect();

        $files = Application::where('candidate_id', $this->user->userable_id)->where('opportunity_id', $this->role->id)->sole()->attachments;
        $this->assertCount(2, $files);
        $this->assertTrue($files[0]['resume']);
        $this->assertSame('main-cv.pdf', $files[0]['name']);
        $this->assertStringStartsWith('applications/', $files[0]['path']);
        $this->assertNotSame($resume->path, $files[0]['path']);
        $this->assertSame('sample.pdf', $files[1]['name']);

        // The company keeps the file after the candidate deletes their resume.
        $resume->remove();
        Storage::disk('local')->assertExists($files[0]['path']);
    }

    public function test_a_tailored_cv_for_this_listing_is_printed_and_sent(): void
    {
        $kit = $this->kit();

        $this->apply(['kit_id' => $kit->id])->assertRedirect();

        $file = Application::where('opportunity_id', $this->role->id)->where('candidate_id', $this->user->userable_id)->sole()->attachments[0];
        $this->assertTrue($file['resume']);
        $this->assertStringEndsWith('.pdf', $file['name']);
        $this->assertStringContainsString('Laravel developer', Storage::disk('local')->get($file['path']));
    }

    public function test_only_own_ready_resumes_and_this_listings_cvs_can_be_sent(): void
    {
        $other = User::where('userable_type', 'candidate')->whereKeyNot($this->user->id)->firstOrFail();
        $theirs = Resume::create(['candidate_id' => $other->userable_id, 'name' => 'Theirs', 'source' => 'uploaded', 'status' => 'ready', 'path' => 'resumes/theirs.pdf', 'is_main' => true]);
        $draft = $this->resume('Draft', false, 'draft');
        $elsewhere = $this->kit(Opportunity::whereKeyNot($this->role->id)->firstOrFail());

        $this->apply(['resume_id' => $theirs->id])->assertSessionHasErrors('resume_id');
        $this->apply(['resume_id' => $draft->id])->assertSessionHasErrors('resume_id');
        $this->apply(['kit_id' => $elsewhere->id])->assertSessionHasErrors('kit_id');
        $this->apply(['resume_id' => $this->resume('Mine', true)->id, 'kit_id' => $this->kit()->id])->assertSessionHasErrors('resume_id');
        $this->assertSame(0, Application::where('opportunity_id', $this->role->id)->where('candidate_id', $this->user->userable_id)->count());
    }

    public function test_the_resume_counts_toward_the_ten_files(): void
    {
        $files = collect(range(1, 10))->map(fn ($i) => UploadedFile::fake()->create("file-$i.pdf", 5, 'application/pdf'))->all();

        $this->apply(['resume_id' => $this->resume('Main CV', true)->id, 'attachment_files' => $files])->assertSessionHasErrors('attachment_files');
        $this->apply(['attachment_files' => $files])->assertSessionHasNoErrors();
    }

    public function test_editing_swaps_or_removes_the_resume_and_withdrawing_deletes_the_files(): void
    {
        $resume = $this->resume('Main CV', true);
        $this->apply(['resume_id' => $resume->id, 'attachment_files' => [UploadedFile::fake()->create('sample.pdf', 20, 'application/pdf')]]);
        $application = Application::where('opportunity_id', $this->role->id)->where('candidate_id', $this->user->userable_id)->sole();
        $first = $application->attachments[0]['path'];

        $this->update($application, ['kit_id' => $this->kit()->id])->assertRedirect(route('applications.show', $application));
        $files = $application->fresh()->attachments;
        Storage::disk('local')->assertMissing($first);
        $this->assertCount(2, $files);
        $this->assertTrue($files[0]['resume']);
        $this->assertSame('sample.pdf', $files[1]['name']);

        // Saving without a choice keeps it.
        $this->update($application, [])->assertSessionHasNoErrors();
        $this->assertSame($files, $application->fresh()->attachments);

        $this->update($application, ['remove_resume' => true]);
        $files = $application->fresh()->attachments;
        $this->assertCount(1, $files);
        $this->assertSame('sample.pdf', $files[0]['name']);

        $this->actingAs($this->user)->delete(route('applications.destroy', $application))->assertRedirect();
        Storage::disk('local')->assertMissing($files[0]['path']);
    }

    public function test_the_application_page_marks_the_resume(): void
    {
        $this->apply(['resume_id' => $this->resume('Main CV', true)->id]);
        $application = Application::where('opportunity_id', $this->role->id)->where('candidate_id', $this->user->userable_id)->sole();

        $this->actingAs($this->user)->get(route('applications.show', $application))->assertInertia(fn (Assert $page) => $page
            ->where('application.attachments.0.resume', true)
            ->where('application.attachments.0.name', 'main-cv.pdf'));
        $this->actingAs($this->role->company->user)->get(route('applications.attachments.download', [$application, 0]))->assertOk()->assertDownload('main-cv.pdf');
    }

    private function apply(array $data)
    {
        return $this->actingAs($this->user)->post(route('applications.store', $this->role), [
            'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
            ...$data,
        ]);
    }

    private function update(Application $application, array $data)
    {
        return $this->actingAs($this->user)->put(route('applications.update', $application), [
            'cover_letter' => str_repeat('Here is my updated experience for the role. ', 10),
            ...$data,
        ]);
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

    private function kit(?Opportunity $role = null): ApplicationKit
    {
        return ApplicationKit::create([
            'candidate_id' => $this->user->userable_id, 'opportunity_id' => ($role ?? $this->role)->id, 'locale' => 'en', 'status' => 'ready',
            'cv' => ['name' => 'Sami Ben Ali', 'headline' => 'Laravel developer', 'contact' => ['sami@example.com']], 'cover_letter' => 'Dear team.',
        ]);
    }
}
