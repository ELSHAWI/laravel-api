<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\BlogCOntroller;
use App\Http\Controllers\BlogApprovalController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LegalChatController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SettingController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/create-checkout-session', [AuthController::class, 'createCheckoutSession']);
Route::get('/payment/success', [AuthController::class, 'handlePaymentSuccess']);
Route::get('/verify-payment', [AuthController::class, 'verifyPayment']);
Route::post('/settings/profile', [SettingController::class, 'updateProfile']);
Route::post('/settings/password', [SettingController::class, 'updatePassword']);
Route::post('/stripe/webhook', '\App\Http\Controllers\StripeWebhookController@handleWebhook');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
// Courses API
// routes/api.php or routes/web.php
Route::prefix('courses')->group(function () {
    Route::post('/', [CoursesController::class, 'store']); // Create course
    Route::get('/', [CoursesController::class, 'index']);  // Get all courses
    Route::get('/filter', [CoursesController::class, 'filter']); // Get courses by term, grade, college
    Route::get('/{id}', [CoursesController::class, 'show']); // Get single course
    Route::put('/{id}', [CoursesController::class, 'update']); // Edit course
    Route::delete('/{id}', [CoursesController::class, 'destroy']); // Delete course
});
// PDFS API 
// routes/api.php or web.php

Route::prefix('pdfs')->group(function () {
    Route::post('/', [PdfController::class, 'store']);          // Upload new PDF
    Route::get('/', [PdfController::class, 'index']);           // List all PDFs
    Route::get('/filter', [PdfController::class, 'filter']);    // Filter by grade, term, college
    Route::get('/{id}', [PdfController::class, 'show']);        // Get one PDF
    Route::put('/{id}', [PdfController::class, 'update']);      // Edit PDF info
    Route::delete('/{id}', [PdfController::class, 'destroy']);  // Delete PDF
});
// Chat API
Route::prefix('chats')->group(function () {
    Route::post('send', [ChatController::class, 'send']);
    Route::put('{id}/edit', [ChatController::class, 'edit']);
    Route::patch('{id}/edit', [ChatController::class, 'edit']); // Optional if using PATCH
    Route::delete('{id}/delete', [ChatController::class, 'delete']);
    Route::get('all', [ChatController::class, 'all']);
    Route::get('filtered', [ChatController::class, 'getFilteredMessages']);
});
// Blog API
Route::prefix('blogs')->group(function () {
    Route::post('/', [BlogController::class, 'store']);       // Create blog
    Route::get('/', [BlogController::class, 'index']);        // Get all blogs
    Route::get('/{id}', [BlogController::class, 'show']);     // Get single blog (with comments + user)
    Route::get('/{id}/comments', [BlogController::class, 'comments']);     // Get single blog (with comments + user)
    Route::put('/{id}', [BlogController::class, 'update']);   // Update blog
    Route::delete('/{id}', [BlogController::class, 'destroy']);// Delete blog
});
// Comments Api 

Route::prefix('comments')->group(function () {
    Route::post('/', [CommentController::class, 'store']);       // Create comment
    Route::get('/', [CommentController::class, 'index']);        // Get all comments with blog + user
    Route::get('/{id}', [CommentController::class, 'show']);     // Get single comment with blog + user
    Route::put('/{id}', [CommentController::class, 'update']);   // Update comment
    Route::delete('/{id}', [CommentController::class, 'destroy']);// Delete comment
});
// Blog Approval
Route::prefix('blog-approvals')->group(function () {
    Route::get('/', [BlogApprovalController::class, 'index']);
    Route::post('/', [BlogApprovalController::class, 'store']);
    Route::get('{id}', [BlogApprovalController::class, 'show']);
    Route::put('{id}', [BlogApprovalController::class, 'update']);
    Route::post('{id}/approve', [BlogApprovalController::class, 'approve']);
    Route::delete('{id}/reject', [BlogApprovalController::class, 'reject']);
});
// admin chat
Route::prefix('admin')->group(function () {
    Route::get('messages', [AdminChatController::class, 'getMessages']);
    Route::post('messages', [AdminChatController::class, 'sendMessage']);
    Route::delete('messages/{id}', [AdminChatController::class, 'deleteMessage']);
});
Route::apiResource('tests', TestController::class)->except(['show', 'update']);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/chat/send', [ChatController::class, 'send']);
//     Route::get('/chat/messages', [ChatController::class, 'all']);
// });
Route::get('/users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
Route::post('/legal-chat', [LegalChatController::class, 'handle']);
Route::post('/send-message', [MessagesController::class, 'send']);
Route::post('/fetch-messages', [MessagesController::class, 'fetch']);
Route::post('/admin/conversations', [AdminController::class, 'getConversations']);

Route::middleware(['auth:sanctum', 'role:admin|editor|contentManager'])->group(function () {
});

Route::get('/events', [EventController::class, 'index']);
Route::post('/events', [EventController::class, 'store']);
Route::put('/events/{event}', [EventController::class, 'update']);
Route::delete('/events/{event}', [EventController::class, 'destroy']);
Route::post('/events/{event}/publish', [EventController::class, 'publish']);
Route::get('/public/events', [EventController::class, 'publicIndex']);
Route::get('/student/events', [EventController::class, 'studentEvents']);
// Add this to your routes/api.php
Route::get('/user', [UserController::class, 'getUser']);
Route::put('/user/username', [UserController::class, 'updateUsername']);
Route::put('/user/profile', [UserController::class, 'updateProfile']);
Route::put('/user/password', [UserController::class, 'updatePassword']);
Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
Route::put('/admin/pricing', [AdminController::class, 'updatePricing']);

Route::post('/update-role', [SettingController::class, 'updateRole']);
Route::get('/admin/stats', [SettingController::class, 'getUserStats']);
    // Route::get('/users', [UserController::class, 'getUsers']);
    // Route::delete('/users/{id}', [UserController::class, 'destroy']);