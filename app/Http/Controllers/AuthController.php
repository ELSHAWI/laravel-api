<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'idNumber' => 'required|string|max:50|unique:users,id_number',
            'college' => 'required|string|in:islamic,law,politics',
            'grade' => 'required|integer|between:1,4',
            'term' => 'required|string|in:first,second',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()
            ],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'agree' => 'required|accepted',
            'plan_type' => 'required|string|in:free,basic,pro',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'id_number' => $request->idNumber,
            'college' => $request->college,
            'grade' => $request->grade,
            'term' => $request->term,
            'password' => Hash::make($request->password),
            'profile_image' => $profileImagePath,
            'plan_type' => $request->plan_type,
            'plan_status' => $request->plan_type === 'free' ? 'active' : 'pending_payment',
        ]);

        if ($request->plan_type === 'free') {
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        }

        return response()->json([
            'user' => $user,
            'requires_payment' => true,
        ]);
    }

    /**
     * Create a Stripe checkout session
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'planId' => 'required|in:basic,pro',
            'userId' => 'required|exists:users,id',
            'customerEmail' => 'required|email',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $planPrices = [
            'basic' => 9900, // 99 EGP in cents
            'pro' => 19900,  // 199 EGP in cents
        ];

        $planNames = [
            'basic' => 'Basic Plan',
            'pro' => 'Pro Plan',
        ];

        $user = User::findOrFail($request->userId);

        if (!$user->stripe_id) {
            $customer = Customer::create([
                'email' => $request->customerEmail,
                'name' => $user->name,
                'phone' => $user->phone,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);
            $user->stripe_id = $customer->id;
            $user->save();
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer' => $user->stripe_id,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => $planNames[$request->planId],
                    ],
                    'unit_amount' => $planPrices[$request->planId],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            // 'success_url' => url('/api/payment/success?session_id={CHECKOUT_SESSION_ID}'),
            'success_url' => 'http://localhost:5173/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://localhost:5173/payment-failed?user_id=' . $user->id,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $request->planId,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    /**
     * Handle Stripe payment success
     */
public function handlePaymentSuccess(Request $request)
{
    Stripe::setApiKey(config('services.stripe.secret'));

    try {
        $session = Session::retrieve($request->session_id);

        if ($session->payment_status === 'paid') {
            $userId = $session->metadata->user_id ?? null;
            $planId = $session->metadata->plan_id ?? null;

            $user = User::find($userId);
            if ($user && $planId) {
                $user->update([
                    'plan_type' => $planId,
                    'plan_status' => 'active',
                    'plan_expires_at' => now()->addYear(),
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                // Redirect to frontend with token as query parameter
                return redirect(config('app.frontend_url') . '/payment/success?token=' . $token);
            }
        }

        return redirect(config('app.frontend_url') . '/payment-failed');
    } catch (\Exception $e) {
        return redirect(config('app.frontend_url') . '/payment-failed?error=' . urlencode($e->getMessage()));
    }
}

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Return the authenticated user
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout user by revoking token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function verifyPayment(Request $request)
{
    $request->validate([
        'session_id' => 'required|string',
    ]);

    Stripe::setApiKey(config('services.stripe.secret'));

    try {
        $session = Session::retrieve($request->session_id);

        if ($session->payment_status === 'paid') {
            $userId = $session->metadata->user_id ?? null;
            $planId = $session->metadata->plan_id ?? null;

            $user = User::find($userId);
            if ($user && $planId) {
                $user->update([
                    'plan_type' => $planId,
                    'plan_status' => 'active',
                    'plan_expires_at' => now()->addYear(),
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'user' => $user,
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Payment verification failed'], 400);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
}
