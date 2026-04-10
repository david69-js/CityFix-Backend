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

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $resetLink = "{$frontendUrl}/reset-password?token={$plainToken}&email=" . urlencode($user->email);

        Mail::send([], [], function ($message) use ($user, $resetLink) {
            $message->to($user->email)
                ->subject('Recuperación de contraseña')
                ->html("
                    <h1>Recuperación de Contraseña</h1>
                    <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:</p>
                    <p><a href='{$resetLink}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Restablecer Contraseña</a></p>
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                    <br>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p>{$resetLink}</p>
                ");
        });

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