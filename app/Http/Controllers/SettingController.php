<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class SettingController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'college' => 'nullable|string|in:law,islamic,political',
            'grade' => 'nullable|integer|in:1,2,3,4',
            'term' => 'nullable|string|in:First,Second',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh()
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->newPassword),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
    public function updateRole(Request $request)
    {
        // تحقق من صحة الريكوست
        $request->validate([
            'changer_id' => 'required|exists:users,id',
            'target_id' => 'required|exists:users,id',
            'role' => 'required|string|in:admin,contentManager,editor,contributor' // عدّل حسب أدوارك المتاحة
        ]);

        // جلب المستخدم الذي طلب التغيير
        $changer = \App\Models\User::find($request->changer_id);

        // تحقق إذا هو أدمن
        if ($changer->role !== 'admin') {
            return response()->json(['message' => 'صلاحيات غير كافية لتنفيذ العملية'], 403);
        }

        // جلب المستخدم الذي سيتم تغيير دوره
        $targetUser = \App\Models\User::find($request->target_id);

        // تحديث الدور
        $targetUser->role = $request->role;
        $targetUser->save();

        return response()->json([
            'message' => 'تم تحديث دور المستخدم بنجاح',
            'user' => $targetUser
        ]);
    }
    public function getUserStats()
    {
        $totalUsers = User::count();
        $basicUsers = User::where('plan_type', 'basic')->count();
        $proUsers = User::where('plan_type', 'pro')->count();
        
        $basicRevenue = $basicUsers * 9.99; // Basic plan price
        $proRevenue = $proUsers * 19.99;    // Pro plan price
        $totalRevenue = $basicRevenue + $proRevenue;

        return response()->json([
            'total_users' => $totalUsers,
            'basic_users' => $basicUsers,
            'pro_users' => $proUsers,
            'monthly_revenue' => $totalRevenue
        ]);
    }
}
