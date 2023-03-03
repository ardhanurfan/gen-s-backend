<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
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
                'username' => ['required', 'string', 'max:25', 'unique:users'],                
                'email' => ['required', 'email','string', 'max:255', 'unique:users'],               
                'password' => ['required', 'string', Password::defaults()->uncompromised()],
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
            return ResponseFormatter::error([
                'message' => 'Unauthorized',
                'error' => 'Account not verified'
            ],
                'Authentication Failed',
                500
            );
        }

        $user = User::find($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return view('welcome');
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
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                    'error' => 'Account not verified'
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
}
