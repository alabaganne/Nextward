<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\InternshipApplicationController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/for-students', [PublicPageController::class, 'audience'])->defaults('audience', 'students')->name('audience.students');
Route::get('/for-professionals', [PublicPageController::class, 'audience'])->defaults('audience', 'professionals')->name('audience.professionals');
Route::prefix('fr')->name('fr.')->group(function () {
    Route::get('/', [PublicPageController::class, 'home'])->name('home');
    Route::get('/for-students', [PublicPageController::class, 'audience'])->defaults('audience', 'students')->name('students');
    Route::get('/for-professionals', [PublicPageController::class, 'audience'])->defaults('audience', 'professionals')->name('professionals');
    Route::get('/opportunities', [InternshipController::class, 'index'])->name('opportunities');
    Route::get('/opportunities/{category}', [InternshipController::class, 'index'])->whereIn('category', ['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'])->name('category');
    Route::inertia('/about', 'About')->name('about');
    Route::inertia('/contact', 'Contact')->name('contact');
});
Route::inertia('/about', 'About')->name('about');
Route::inertia('/contact', 'Contact')->name('contact');

foreach (['' => '', 'fr' => 'fr.'] as $prefix => $name) {
    Route::prefix($prefix)->name($name)->group(function () {
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
        Route::get('/blog/{slug}.md', [BlogController::class, 'markdown'])->where('slug', '[a-z0-9-]+')->name('blog.markdown');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('blog.show');
    });
}

Route::get('/opportunities', [InternshipController::class, 'index'])->name('opportunities.index');
Route::get('/opportunities/{category}', [InternshipController::class, 'index'])
    ->whereIn('category', ['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'])->name('opportunities.category');
Route::get('/opportunities/{opportunity}', [InternshipController::class, 'publicShow'])
    ->where('opportunity', '[0-9]+(?:-[a-z0-9-]+)?')->name('opportunities.show');
Route::get('/companies/{companySlug}', [PublicCompanyController::class, 'show'])
    ->where('companySlug', '[0-9]+-[a-z0-9-]+')->name('companies.public');
Route::get('/internships', function (\Illuminate\Http\Request $request) {
    $query = $request->only('search', 'fields', 'cities', 'companies', 'page', 'work_type', 'country');
    return redirect('/opportunities/internships'.($query ? '?'.http_build_query($query) : ''), 301);
})->name('internships.index');
Route::get('/internships/{internship}', [InternshipController::class, 'show'])
    ->whereNumber('internship')->name('internships.show');

Route::group(['middleware' => 'auth'], function() {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
	// Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.change');
    // Resources
    Route::resource('internships', InternshipController::class)->except(['index', 'show']);
    Route::resource('companies', CompanyController::class);
    Route::resource('students', StudentController::class);
    Route::resource('fields', FieldController::class);
    Route::resource('skills', SkillController::class);
    // Applications
    Route::post('/applications/{application}', [ApplicationController::class, 'reply'])->name('applications.reply');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/internships/{internship}/apply', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/internship/{internship}/apply', [ApplicationController::class, 'store'])->middleware('throttle:10,1,apply')->name('applications.store');
    Route::get('/applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::get('/internships/{internship}/applications', InternshipApplicationController::class)->name('internships.applications.index');
	// Likes
    Route::get('/likes', [LikeController::class, 'index'])->name('likes.index');
    Route::redirect('/saved', '/likes');
    Route::post('/like/{internship}', [LikeController::class, 'store'])->name('likes.store');
	// Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:30,1,messages')->name('messages.store');
	// Notifications
	Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::post('/notifications/{notification}', [NotificationController::class, 'store'])->name('notifications.store');
});

require __DIR__.'/candidate_workflows.php';
require __DIR__.'/opportunity_tools.php';
