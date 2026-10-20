<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\OpportunityApplicationController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\CandidateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PublicCandidateController;
use App\Http\Controllers\PublicCompanyController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SeoFilesController;
use App\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';


Route::get('/robots.txt', [SeoFilesController::class, 'robots']);
Route::get('/llms.txt', [SeoFilesController::class, 'llms']);
Route::get('/indexnow-key.txt', [SeoFilesController::class, 'indexNowKey']);

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemaps/companies.xml', [SitemapController::class, 'companies']);
Route::get('/sitemaps/opportunities-{page}.xml', [SitemapController::class, 'opportunities'])->whereNumber('page');

Route::match(['get', 'post'], '/webhooks/billing/{gateway}', \App\Http\Controllers\BillingWebhookController::class)
    ->whereIn('gateway', ['konnect', 'flouci', 'lemonsqueezy'])->middleware('throttle:120,1')->name('billing.webhook');

Route::get('/', [PublicPageController::class, 'home'])->name('home');
// The student and professional pages were merged into the home page.
Route::permanentRedirect('/for-students', '/');
Route::permanentRedirect('/for-professionals', '/');
Route::permanentRedirect('/fr/for-students', '/fr');
Route::permanentRedirect('/fr/for-professionals', '/fr');
Route::prefix('fr')->name('fr.')->group(function () {
    Route::get('/', [PublicPageController::class, 'home'])->name('home');
    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities');
    Route::get('/opportunities/{category}', [OpportunityController::class, 'index'])->whereIn('category', ['internships', 'jobs'])->name('category');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'publicShow'])->where('opportunity', '[0-9]+(?:-[a-z0-9-]+)?')->name('opportunity');
    Route::get('/companies', [PublicCompanyController::class, 'index'])->name('companies');
    Route::get('/companies/{companySlug}', [PublicCompanyController::class, 'show'])->where('companySlug', '[0-9]+-[a-z0-9-]+')->name('company');
    Route::get('/candidates', [PublicCandidateController::class, 'index'])->name('candidates');
    Route::get('/candidates/{candidateSlug}', [PublicCandidateController::class, 'show'])->where('candidateSlug', \App\Support\Username::PROFILE_SLUG)->name('candidate');
    Route::inertia('/about', 'About')->name('about');
    Route::inertia('/contact', 'Contact')->name('contact');
    Route::inertia('/privacy', 'Privacy')->name('privacy');
    Route::inertia('/terms', 'Terms')->name('terms');
});
Route::inertia('/about', 'About')->name('about');
Route::inertia('/contact', 'Contact')->name('contact');
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:5,10,contact')->name('contact.store');
Route::inertia('/privacy', 'Privacy')->name('privacy');
Route::inertia('/terms', 'Terms')->name('terms');

foreach (['' => '', 'fr' => 'fr.'] as $prefix => $name) {
    Route::prefix($prefix)->name($name)->group(function () {
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
        Route::get('/blog/{slug}.md', [BlogController::class, 'markdown'])->where('slug', '[a-z0-9-]+')->name('blog.markdown');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('blog.show');
    });
}

// Signed-in pages moved under /app. Old links, emails and notifications still land in the right place.
Route::redirect('/saved', '/app/likes', 301);
Route::get('/{path}', fn (\Illuminate\Http\Request $request) => redirect('/app'.$request->getRequestUri(), 301))
    ->where('path', '(?:dashboard|profile|settings|applications|likes|saved-searches|messages|kits|resumes|billing|talent|fields|skills|admin|candidates/manage)(?:/.*)?|opportunities/(?:create|[0-9]+/(?:edit|apply|applications))|companies/(?:create|[0-9]+(?:/edit)?)|candidates/[0-9]+(?:/edit)?');

Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
Route::get('/opportunities/{category}', [OpportunityController::class, 'index'])
    ->whereIn('category', ['internships', 'jobs'])->name('opportunities.category');
// The Tunisia and relocation pages became filters on the main list.
foreach (['' => '', '/fr' => '/fr'] as $prefix) {
    foreach (['tunisia' => ['country' => 'TN'], 'remote-from-tunisia' => ['work_type' => 'remote'], 'relocation' => ['relocation' => 1]] as $old => $filter) {
        Route::get($prefix.'/opportunities/'.$old, fn (\Illuminate\Http\Request $request) => redirect($prefix.'/opportunities?'.\Illuminate\Support\Arr::query([...$request->except(array_keys($filter)), ...$filter]), 301));
    }
}
Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'publicShow'])
    ->where('opportunity', '[0-9]+(?:-[a-z0-9-]+)?')->name('opportunities.show');
Route::get('/companies', [PublicCompanyController::class, 'index'])->name('companies.index');
Route::get('/companies/{companySlug}', [PublicCompanyController::class, 'show'])
    ->where('companySlug', '[0-9]+-[a-z0-9-]+')->name('companies.public');
Route::get('/candidates', [PublicCandidateController::class, 'index'])->name('candidates.public.index');
Route::get('/candidates/{candidateSlug}', [PublicCandidateController::class, 'show'])
    ->where('candidateSlug', \App\Support\Username::PROFILE_SLUG)->name('candidates.public.show');
Route::get('/candidates/{candidateSlug}/resume', [PublicCandidateController::class, 'resume'])
    ->where('candidateSlug', \App\Support\Username::PROFILE_SLUG)->name('candidates.public.resume');
Route::get('/internships', function (\Illuminate\Http\Request $request) {
    $query = $request->only('search', 'fields', 'cities', 'companies', 'page', 'work_type', 'country');
    return redirect('/opportunities/internships'.($query ? '?'.http_build_query($query) : ''), 301);
})->name('internships.index');
Route::get('/internships/{opportunity}', [OpportunityController::class, 'show'])
    ->whereNumber('opportunity')->name('internships.show');
// Old workspace links from before the rename.
Route::get('/internships/{id}/{page}', fn (string $id, string $page) => redirect("/app/opportunities/{$id}/{$page}", 301))->whereNumber('id')->whereIn('page', ['edit', 'apply', 'applications']);
Route::get('/students', fn () => redirect('/app/candidates/manage', 301));
Route::get('/students/{path}', fn (string $path) => redirect('/app/candidates/'.$path, 301))->where('path', '[0-9]+(?:/edit)?');


// Only /app pages get the workspace sidebar and header.
Route::prefix('app')->middleware('auth')->group(function () {
    Route::redirect('/', '/app/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('app.opportunities.index');
    Route::get('/opportunities/{category}', [OpportunityController::class, 'index'])->whereIn('category', ['internships', 'jobs'])->name('app.opportunities.category');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'publicShow'])->where('opportunity', '[0-9]+(?:-[a-z0-9-]+)?')->name('app.opportunities.show');
    Route::get('/companies', [CompanyController::class, 'index'])->name('app.companies.index');
    Route::middleware('verified')->group(function () {
        Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/beta-credits', [\App\Http\Controllers\BillingController::class, 'claimBeta'])->middleware('throttle:10,1')->name('billing.beta');
        Route::post('/billing/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
        Route::get('/billing/return/{gateway}', [\App\Http\Controllers\BillingController::class, 'return'])->whereIn('gateway', ['konnect', 'flouci', 'lemonsqueezy'])->name('billing.return');
    });
	// Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::put('/profile/public', [PublicCandidateController::class, 'update'])->middleware('verified')->name('profile.public.update');
    Route::post('/candidates/{candidate}/public-profile', [PublicCandidateController::class, 'review'])->middleware('admin')->name('candidates.public.review');
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.change');
    // Resources
    Route::resource('opportunities', OpportunityController::class)->except(['index', 'show'])->middleware('verified');
    Route::resource('companies', CompanyController::class)->except('index');
    Route::get('/talent', [PublicCandidateController::class, 'talent'])->name('talent.index');
    Route::get('/talent/{candidateSlug}', [PublicCandidateController::class, 'show'])->where('candidateSlug', \App\Support\Username::PROFILE_SLUG)->name('talent.show');
    Route::get('/candidates/manage', [CandidateController::class, 'index'])->name('candidates.index');
    Route::resource('candidates', CandidateController::class)->except('index')->whereNumber('candidate');
    Route::resource('fields', FieldController::class);
    Route::resource('skills', SkillController::class);
    // Applications
    Route::post('/applications/{application}', [ApplicationController::class, 'reply'])->middleware('verified')->name('applications.reply');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/opportunities/{opportunity}/apply', [ApplicationController::class, 'create'])->middleware('verified')->name('applications.create');
    Route::post('/opportunities/{opportunity}/apply', [ApplicationController::class, 'store'])->middleware(['verified', 'throttle:10,1,apply'])->name('applications.store');
    Route::get('/applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::get('/opportunities/{opportunity}/applications', OpportunityApplicationController::class)->name('opportunities.applications.index');
	// Likes
    Route::get('/likes', [LikeController::class, 'index'])->name('likes.index');
    Route::post('/like/{opportunity}', [LikeController::class, 'store'])->name('likes.store');
	// Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->middleware(['verified', 'throttle:30,1,messages'])->name('messages.store');
	// Notifications
	Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::post('/notifications/{notification}', [NotificationController::class, 'store'])->name('notifications.store');
    Route::get('/admin/signups', \App\Http\Controllers\SignupSourceController::class)->middleware('admin')->name('signups.index');
});

require __DIR__.'/candidate_workflows.php';
require __DIR__.'/opportunity_tools.php';
