<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('public');
    }

    private function owner(): User
    {
        return User::where('email', 'company@example.com')->firstOrFail();
    }

    private function save(User $user, array $data = [])
    {
        $company = $user->userable;

        return $this->actingAs($user)->post(route('companies.update', $company), [
            '_method' => 'put',
            'name' => $user->name,
            'email' => $user->email,
            'website' => $company->website,
            'about' => $company->about,
            ...$data,
        ]);
    }

    public function test_company_can_upload_and_replace_its_logo(): void
    {
        $user = $this->owner();

        $this->save($user, ['logo' => UploadedFile::fake()->image('first.png', 80, 80)])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.show', $user->userable));
        $first = $user->fresh()->image;
        $this->assertStringStartsWith('company-logos/', $first);
        Storage::disk('public')->assertExists($first);

        $this->save($user, ['logo' => UploadedFile::fake()->image('second.png', 80, 80)])->assertSessionHasNoErrors();
        $second = $user->fresh()->image;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->save($user)->assertSessionHasNoErrors();
        $this->assertSame($second, $user->fresh()->image);
    }

    public function test_logo_must_be_a_small_image(): void
    {
        $user = $this->owner();

        $this->save($user, ['logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf')])->assertSessionHasErrors('logo');
        $this->save($user, ['logo' => UploadedFile::fake()->image('big.png')->size(3000)])->assertSessionHasErrors('logo');

        $this->assertNull($user->fresh()->image);
    }

    public function test_company_pages_get_a_full_logo_url(): void
    {
        $user = $this->owner();
        $user->update(['image' => 'company-logos/realinflo.png']);
        $url = url(Storage::disk('public')->url('company-logos/realinflo.png'));
        $this->assertMatchesRegularExpression('#^https?://#', $url);

        $this->actingAs($user)->get(route('companies.show', $user->userable))
            ->assertInertia(fn (Assert $page) => $page->where('company.image', $url));
        $this->get(route('companies.edit', $user->userable))
            ->assertInertia(fn (Assert $page) => $page->where('company.image', $url)->where('company.user_id', $user->id));
        $this->get(route('companies.index'))
            ->assertInertia(fn (Assert $page) => $page->where('companies.data', fn ($companies) => collect($companies)->contains(fn ($company) => $company['id'] === $user->userable_id && $company['image'] === $url)));

        $imported = Company::doesntHave('user')->firstOrFail();
        $imported->update(['logo_url' => 'https://cdn.example.com/logo.png']);
        $this->assertNull($imported->fresh()->logo);
    }

    public function test_registration_logo_is_shown_on_the_company_page(): void
    {
        $this->post('/register', [
            'account_type' => 'company',
            'name' => 'Logo Works',
            'email' => 'logo-works@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'website' => 'https://logo-works.example.com',
            'about' => 'We make tools that help small teams plan their week.',
            'country_code' => 'TN',
            'company_logo' => UploadedFile::fake()->image('logo.png', 60, 60),
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'logo-works@example.com')->firstOrFail();
        $this->assertSame(url(Storage::disk('public')->url($user->image)), $user->userable->logo);
    }
}
