<?php

namespace Tests\Feature;

use App\Importing\CompanyLogos;
use App\Importing\SourceHttp;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImportedCompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    private function png(int $size = 32): string
    {
        $image = imagecreatetruecolor($size, $size);
        ob_start();
        imagepng($image);
        return ob_get_clean();
    }

    private function company(string $logo = 'https://cdn.example.com/acme.png'): Company
    {
        return Company::forceCreate(['name' => 'Acme Labs', 'logo_url' => $logo]);
    }

    public function test_outside_logo_is_never_sent_to_the_browser(): void
    {
        $company = $this->company();

        $this->assertNull($company->fresh()->logo);
        $this->get($company->publicUrl())->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('company.logo', null));
    }

    public function test_command_downloads_the_logo_once_and_serves_the_copy(): void
    {
        Http::fake(['cdn.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
        $company = $this->company();

        $this->artisan('companies:logos')->assertSuccessful();
        $company->refresh();

        Storage::disk('public')->assertExists($company->logo_path);
        $this->assertStringStartsWith('company-logos/imported/'.$company->id.'-', $company->logo_path);
        $url = url(Storage::disk('public')->url($company->logo_path));
        $this->assertSame($url, $company->logo);
        $this->get($company->publicUrl())->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('company.logo', $url)
            ->where('seo.image', $url)
            ->where('seo.structured_data.0.logo', $url));

        $this->artisan('companies:logos')->assertSuccessful();
        $this->assertCount(1, Http::recorded(fn ($request) => $request->url() === 'https://cdn.example.com/acme.png'));
    }

    public function test_bad_downloads_keep_the_initials_badge(): void
    {
        Http::fake([
            'cdn.example.com/page.html' => Http::response('<html><script>alert(1)</script></html>', 200, ['Content-Type' => 'image/png']),
            'cdn.example.com/logo.svg' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"/>', 200, ['Content-Type' => 'image/svg+xml']),
            'cdn.example.com/big.png' => Http::response(str_repeat('a', CompanyLogos::MAX_BYTES + 1)),
            'cdn.example.com/pixel.png' => Http::response($this->png(1)),
            'cdn.example.com/gone.png' => Http::response('', 404),
        ]);
        foreach (['page.html', 'logo.svg', 'big.png', 'pixel.png', 'gone.png'] as $file) {
            $company = $this->company('https://cdn.example.com/'.$file);
            $this->assertFalse((new CompanyLogos())->fetch($company), $file);
            $this->assertNull($company->fresh()->logo_path);
            $this->assertNull($company->fresh()->logo);
            $this->assertNotNull($company->fresh()->logo_fetched_at);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_local_and_private_hosts_are_not_fetched(): void
    {
        Http::fake();
        SourceHttp::$resolveUsing = fn () => ['10.0.0.5'];
        foreach (['https://intranet.example.com/logo.png', 'http://127.0.0.1/logo.png', 'http://localhost/logo.png', 'file:///etc/passwd', 'https://cdn.example.com:8443/logo.png'] as $url) {
            $this->assertFalse((new CompanyLogos())->fetch($this->company($url)), $url);
        }
        Http::assertNothingSent();
    }

    public function test_redirects_are_checked_again(): void
    {
        Http::fake(['cdn.example.com/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest'])]);

        $this->assertFalse((new CompanyLogos())->fetch($this->company()));
        Http::assertSentCount(1);
    }

    public function test_new_logo_url_replaces_the_old_copy(): void
    {
        Http::fake([
            'cdn.example.com/old.png' => Http::response($this->png(32)),
            'cdn.example.com/new.png' => Http::response($this->png(48)),
        ]);
        $company = $this->company('https://cdn.example.com/old.png');
        $this->artisan('companies:logos')->assertSuccessful();
        $old = $company->fresh()->logo_path;

        $company->fresh()->update(['logo_url' => 'https://cdn.example.com/new.png']);
        $this->assertNull($company->fresh()->logo_fetched_at);
        $this->artisan('companies:logos')->assertSuccessful();

        $new = $company->fresh()->logo_path;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);

        $company->fresh()->delete();
        Storage::disk('public')->assertMissing($new);
    }

    public function test_uploaded_logo_wins_and_outside_account_images_are_downloaded(): void
    {
        Http::fake(['cdn.example.com/*' => Http::response($this->png())]);
        $company = Company::forceCreate(['name' => 'Acme Labs']);
        $user = User::factory()->create(['userable_type' => 'company', 'userable_id' => $company->id, 'image' => 'https://cdn.example.com/avatar.png']);
        $this->assertNull($company->fresh()->logo);

        $this->artisan('companies:logos')->assertSuccessful();
        $company->refresh();
        $this->assertNull($user->fresh()->image);
        $this->assertSame('https://cdn.example.com/avatar.png', $company->logo_url);
        $this->assertSame(url(Storage::disk('public')->url($company->logo_path)), $company->logo);

        $user->update(['image' => 'company-logos/uploaded.png']);
        $this->assertSame(url(Storage::disk('public')->url('company-logos/uploaded.png')), $company->fresh()->logo);
    }
}
