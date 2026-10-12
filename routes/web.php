<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicationController;
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


Route::get('/robots.txt', function () {
    return response("User-agent: *
Allow: /
Sitemap: " . url('/sitemap.xml') . "
", 200)->header('Content-Type', 'text/plain');
});

Route::get('/sitemap.xml', function () {
    $blogPosts = collect(config('internly_blog'))->map(fn ($post) => config('app.url') . '/blog/' . $post['slug']);

    $urls = collect([
        config('app.url'),
        config('app.url') . '/internships',
        config('app.url') . '/blog',
        config('app.url') . '/about',
        config('app.url') . '/contact',
    ])->merge($blogPosts)
        ->merge(\App\Models\Internship::open()->latest()->take(500)->get()->map(fn ($internship) => config('app.url') . '/internships/' . $internship->id));

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "
";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
    foreach ($urls as $url) {
        $xml .= '  <url><loc>' . e($url) . '</loc></url>' . "
";
    }
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'posts' => collect(config('internly_blog'))
            ->take(3)
            ->map(fn ($post) => collect($post)->only('slug', 'category', 'title', 'excerpt', 'read_time'))
            ->values(),
    ]);
})->name('home');
Route::inertia('/about', 'About')->name('about');
Route::inertia('/contact', 'Contact')->name('contact');

Route::get('/blog', function () {
    return Inertia::render('Blog/Index', [
        'posts' => config('internly_blog'),
    ]);
})->name('blog.index');

Route::get('/blog/{slug}', function (string $slug) {
    $post = collect(config('internly_blog'))->firstWhere('slug', $slug);

    abort_unless($post, 404);

    return Inertia::render('Blog/Show', [
        'post' => $post,
    ]);
})->name('blog.show');

// Public internship discovery pages for SEO and unauthenticated students.
Route::get('/internships', [InternshipController::class, 'index'])->name('internships.index');
Route::get('/internships/{internship}', [InternshipController::class, 'show'])
    ->whereNumber('internship')
    ->name('internships.show');

Route::group(['middleware' => 'auth'], function() {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
	// Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
    Route::post('/internship/{internship}/apply', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::get('/internships/{internship}/applications', InternshipApplicationController::class)->name('internships.applications.index');
	// Likes
    Route::get('/likes', [LikeController::class, 'index'])->name('likes.index');
    Route::post('/like/{internship}', [LikeController::class, 'store'])->name('likes.store');
	// Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
	// Notifications
	Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
	Route::get('/notifications/clear', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::post('/notifications/{notification}', [NotificationController::class, 'store'])->name('notifications.store');
});
