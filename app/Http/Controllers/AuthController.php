<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{
    //Register a new user
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // profile fields optional
            'course' => 'sometimes|nullable|string|max:255',
            'school' => 'sometimes|nullable|string|max:255',
            'department' => 'sometimes|nullable|string|max:255',
            'bio' => 'sometimes|nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'course' => $validated['course'] ?? 'Not specified',
            'school' => $validated['school'] ?? 'Not specified',
            'department' => $validated['department'] ?? 'Not specified',
            'bio' => $validated['bio'] ?? null,
        ]);

        // assign a random default profile picture from public/default/profiles
        $profileDir = public_path('default/profiles');
        if (File::exists($profileDir)) {
            $files = File::files($profileDir);
            $images = array_filter($files, function ($f) {
                $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
                return in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
            });
            if (!empty($images)) {
                $random = $images[array_rand($images)];
                $user->profile_picture = 'default/profiles/' . $random->getFilename();
                $user->save();
            }
        }

        // ensure we return the latest user attributes (including profile_picture)
        $user->refresh();

        // return an access token so client can authenticate immediately
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }
    
    //Login user and create token
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token, 
            'token_type' => 'Bearer', 
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'course' => $user->course,
                'school' => $user->school,
                'department' => $user->department,
                'bio' => $user->bio,
                'profile_picture_url' => asset($user->profile_picture),
            ]
        ], 200);
    }

    //Logout user (Revoke the token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out'], 200);
    }
}
