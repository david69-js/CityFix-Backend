<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function requestReset(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Si el correo existe, se enviará un enlace de recuperación.'
            ]);
        }

        PasswordReset::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $plainToken = Str::random(64);

        $reset = PasswordReset::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(30),
        ]);

        // Aquí puedes enviar por correo un link real del frontend
        // ejemplo: https://tu-frontend.com/reset-password?token=XXXX

        Mail::raw(
            "Tu token de recuperación es: {$plainToken}",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Recuperación de contraseña');
            }
        );

        return response()->json([
            'message' => 'Si el correo existe, se enviará un enlace de recuperación.'
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $hashedToken = hash('sha256', $request->token);

        $passwordReset = PasswordReset::with('user')
            ->where('token', $hashedToken)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Token inválido.'
            ], 422);
        }

        if ($passwordReset->isUsed()) {
            return response()->json([
                'message' => 'Este token ya fue utilizado.'
            ], 422);
        }

        if ($passwordReset->isExpired()) {
            return response()->json([
                'message' => 'El token ha expirado.'
            ], 422);
        }

        DB::transaction(function () use ($request, $passwordReset) {
            $passwordReset->user->update([
                'password' => Hash::make($request->password),
            ]);

            $passwordReset->update([
                'used_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }
}