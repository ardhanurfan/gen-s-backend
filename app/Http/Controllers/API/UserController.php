<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Notifications\ResetPasswordEmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => ['required', 'string', 'max:25', 'unique:users', 'min:6'],                
                'email' => ['required', 'email','string', 'max:255', 'unique:users'],               
                'password' => ['required', 'string', Password::defaults()],
                'confirmPassword' => ['required', 'string'],
            ]);

            if ($request->password != $request->confirmPassword) {
                return ResponseFormatter::error([
                    'message' => 'Something when wrong',
                    'error' => "Password not match",    
                ], 
                    'Register Failed', 
                    500,
                );
            }

            User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ])->sendEmailVerificationNotification();

            $user = User::where('email', $request->email)->first();

            return ResponseFormatter::success(
                [
                    'user' => $user
                ],
                'User Registered'
            );

        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Register Failed', 
                500,
            );
        }
    }

    public function verify($id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return view('email-failed-verification');
        }

        $user = User::find($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return view('email-verification');
    }

    public function login(Request $request) 
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required',
            ]);

            // Cek apakah ada email dan password yang sesuai
            $credentials = request(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                    'error' => 'Password incorrect'
                ],
                    'Authentication Failed',
                    500
                );
            }

            $user = User::where('email', $request->email)->first();

            // cek ulang apakah password sesuai (opsional)
            if(!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }

            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                    'error' => 'Account not verified. Check your email inbox or spam'
                ],
                    'Authentication Failed',
                    500
                );
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'acess_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');

        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Login Failed', 
                500,
            );
        }
    }

    public function fetch(Request $request)
    {
        $user = $request->user();
        return ResponseFormatter::success($user, 'Get user data success');
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->user()->currentAccessToken()->delete();
            return ResponseFormatter::success($token, 'Token Revoked');

        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Logout Failed', 
                500,
            );
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            // Validate and Check
            $request->validate([
                'email' => 'email|required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->email) {
                return ResponseFormatter::error([
                    'error' => 'Incorrect email address provided',    
                ], 
                    'No Record Found', 
                    404,
                );
            }

            // Generate Token
            $resetToken = str_pad(random_int(1,9999), 4, '0', STR_PAD_LEFT);

            if(!$userPassReset = PasswordResetToken::where('email', $request->email)->first()) {
                PasswordResetToken::create([
                    'email' => $user->email,
                    'token' => $resetToken,
                ]);
            } else {
                $userPassReset->update([
                    'email' => $user->email,
                    'token' => $resetToken,
                ]);
            }

            $user->notify(new ResetPasswordEmailVerification($resetToken));

            return ResponseFormatter::success(["token" => $resetToken], 'Forgot success, check your email otp');
        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Forgot Password Failed', 
                500,
            );
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // Validate and Check
            $request->validate([
                'email' => 'email|required',
                'password' => ['required', 'string', Password::defaults()->uncompromised()],
                'confirmPassword' => ['required', 'string'],
                'token' => ['required', 'integer'],
            ]);

            if ($request->password != $request->confirmPassword) {
                return ResponseFormatter::error([
                    'message' => 'Something when wrong',
                    'error' => "Password not match",    
                ], 
                    'Reset Password Failed', 
                    500,
                );
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->email) {
                return ResponseFormatter::error([
                    'error' => 'Incorrect email address provided',    
                ], 
                    'No Record Found', 
                    404,
                );
            }

            $resetRequest = PasswordResetToken::where('email', $user->email)->first();

            if (!$resetRequest || ($resetRequest->token != $request->token)) {
                return ResponseFormatter::error([
                    'error' => 'Token mismatch',    
                ], 
                    'Check token again', 
                    404,
                );
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // delete all previous token
            $user->tokens()->delete();

            return ResponseFormatter::success(null, 'Password Changed');
        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Reset Password Failed', 
                500,
            );
        }
    }

    public function delete(Request $request) {
        try {
            $userId = $request->user()->id;
            $audios = Audio::where('uploaderId', $userId)->get();
            
            // delete audio saved from storage
            foreach($audios as $audio) {
                unlink(public_path(str_replace(config('app.url'),'',$audio->url)));
            }
            
            // LogOut
            $request->user()->currentAccessToken()->delete();

            // Find in table by id and delete
            $user = User::find($userId);
            $user->forceDelete();

            return ResponseFormatter::success(null, 'Account Deleted');

        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Delete Account Failed', 
                500,
            );
        }
    }

    public function deleteInWeb(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $confirmPassword = $request->input('confirm_password');

        // Lakukan validasi email, password, dan konfirmasi password
        if ($email && $password && $confirmPassword && $password === $confirmPassword) {

            // Cek apakah ada email dan password yang sesuai
            $credentials = request(['email', 'password']);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return redirect()->back()->with('error', 'Email Not Found');
            }

            if (!Auth::attempt($credentials)) {
                return redirect()->back()->with('error', 'Password Incorrect');
            }

            // cek ulang apakah password sesuai (opsional)
            if(!Hash::check($request->password, $user->password, [])) {
                return redirect()->back()->with('error', 'Password Incorrect');
            }

            // Hapus Akun audios yang disimpan
            $audios = Audio::where('uploaderId', $user->id)->get();
            
            // delete audio saved from storage
            foreach($audios as $audio) {
                unlink(public_path(str_replace(config('app.url'),'',$audio->url)));
            }

            $user->forceDelete();

            // Jika akun berhasil dihapus, redirect ke halaman konfirmasi
            return view('account-deleted');
        } else {
            // Jika ada kesalahan validasi, kembalikan ke halaman delete account dengan pesan error
            return redirect()->back()->with('error', 'Invalid email or password');
        }
    }
}
