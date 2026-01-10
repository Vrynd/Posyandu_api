<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Registrasi Pengguna Baru
     * 
     * Mendaftarkan akun kader baru ke dalam sistem.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'kader',
            'nik' => $request->nik,
            'nik_hash' => User::hashNik($request->nik),
            'phone_number' => $request->phone_number,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Login Pengguna
     * 
     * Masuk ke sistem menggunakan email atau NIK dan mendapatkan token akses.
     * Dilengkapi dengan proteksi lockout akun setelah 5 kali percobaan gagal.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::findByEmailOrNik($request->identifier);

        // Check if user exists
        if (!$user) {
            Log::channel('security')->warning('Login failed: User not found', [
                'identifier' => $request->identifier,
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials',
            ], 401);
        }

        // Check if account is locked
        if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            $remainingMinutes = Carbon::now()->diffInMinutes(Carbon::parse($user->locked_until));
            return response()->json([
                'success' => false,
                'message' => "Akun terkunci. Silakan coba lagi dalam {$remainingMinutes} menit.",
            ], 423);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            // Increment failed attempts
            $user->failed_login_attempts++;

            // Lock account if reached 5 attempts
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = Carbon::now()->addMinutes(15);
                $user->save();
                return response()->json([
                    'success' => false,
                    'message' => 'Akun terkunci selama 15 menit karena terlalu banyak percobaan gagal.',
                ], 423);
            }

            $user->save();
            $attemptsLeft = 5 - $user->failed_login_attempts;
            Log::channel('security')->warning('Login failed: Invalid password', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'attempts_left' => $attemptsLeft,
            ]);
            return response()->json([
                'success' => false,
                'message' => "Password salah. Sisa percobaan: {$attemptsLeft}.",
            ], 401);
        }

        // Reset lockout on successful login
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::channel('security')->info('Login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout Pengguna
     * 
     * Menghapus token akses yang sedang digunakan (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Profil Pengguna
     * 
     * Mengambil data profil lengkap pengguna yang sedang login.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    }

    /**
     * Lupa Password (Reset Satu Langkah)
     * 
     * Mengatur ulang password dengan memverifikasi NIK dan nomor telepon.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
            'nik' => 'required|string|size:16',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Verify identity using NIK hash and phone number
        if ($user->nik_hash !== User::hashNik($request->nik) || $user->phone_number !== $request->phone_number) {
            return response()->json([
                'success' => false,
                'message' => 'Verification data does not match our records.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Update Profil
     * 
     * Memperbarui data dasar profil pengguna (nama, telepon, avatar).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'avatar_url' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only('name', 'phone_number', 'avatar_url'));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh(),
            ],
        ]);
    }

    /**
     * Ganti Password
     * 
     * Mengubah password pengguna dengan memverifikasi password saat ini.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Hapus Akun
     * 
     * Menghapus akun pengguna secara permanen. Memerlukan konfirmasi teks "HAPUS AKUN".
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|string|in:HAPUS AKUN',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please type "HAPUS AKUN" to confirm deletion.',
            ], 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }
}
