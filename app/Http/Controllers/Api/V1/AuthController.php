<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\TwoFactorChallenge;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(AuthenticateUserRequest $request)
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error(
                'Invalid credentials',
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($user->hasTwoFactorEnabled()) {
            $rawChallengeToken = Str::random(256);
            TwoFactorChallenge::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $rawChallengeToken),
                'expires_at' => now()->addMinutes(5),
            ]);

            return ApiResponse::success([
                'requires_two_factor' => true,
                'challenge_token' => $rawChallengeToken,
                'expires_in_seconds' => 300,
            ], 'Two-Factor Authentication is required to complete login');
        }

        $token = Auth::guard('api')->login($user);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'Successfully logged in');
    }

    /**
     * Verifikasi Challenge 2FA (Step 2 Login)
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string',
        ]);

        $tokenHash = hash('sha256', $request->input('challenge_token'));
        $challenge = TwoFactorChallenge::where('token_hash', $tokenHash)->first();

        if (!$challenge || $challenge->consumed_at || $challenge->expires_at->isPast()) {
            return ApiResponse::error('Invalid or expired 2FA challenge', Response::HTTP_UNAUTHORIZED);
        }

        $user = $challenge->user;
        $google2fa = new Google2FA();
        $cleanCode = str_replace(['-', ' '], '', strtoupper(trim($request->input('code'))));

        $isValid = false;

        if (strlen($cleanCode) === 6 && ctype_digit($cleanCode)) {
            $isValid = (bool) $google2fa->verifyKey($user->two_factor_secret, $cleanCode);
        }

        if (!$isValid) {
            $codeHash = hash('sha256', $cleanCode);
            $recovery = TwoFactorRecoveryCode::where('user_id', $user->id)
                ->where('code_hash', $codeHash)
                ->whereNull('used_at')
                ->first();

            if ($recovery) {
                $recovery->update(['used_at' => now()]);
                $isValid = true;
            }
        }

        if (!$isValid) {
            $challenge->increment('attempts');
            return ApiResponse::error('Invalid 2FA code or recovery code', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $challenge->update(['consumed_at' => now()]);

        $token = Auth::guard('api')->login($user);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], '2FA verified successfully. Logged in.');
    }

    public function setupTwoFactor(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(32);

        $issuer = config('app.name', 'Kuliner API');
        $otpauthUri = $google2fa->getQRCodeUrl($issuer, $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($otpauthUri);
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null])->save();

        $recoveryCodes = [];
        TwoFactorRecoveryCode::where('user_id', $user->id)->delete();
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(5) . '-' . Str::random(5));
            $recoveryCodes[] = $code;
            TwoFactorRecoveryCode::create([
                'user_id' => $user->id,
                'code_hash' => hash('sha256', str_replace('-', '', $code)),
            ]);
        }

        return ApiResponse::success([
            'secret' => $secret,
            'otpauth_uri' => $otpauthUri,
            'qr_code_svg' => $qrCodeSvg,
            'recovery_codes' => $recoveryCodes,
        ], 'Scan QR code or use secret key in Google Authenticator, then confirm with 6-digit OTP');
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();
        $google2fa = new Google2FA();

        if (!$user->two_factor_secret || !$google2fa->verifyKey($user->two_factor_secret, $request->input('code'))) {
            return ApiResponse::error('Invalid verification code', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return ApiResponse::success(null, 'Two-Factor Authentication is now ENABLED');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(
            new UserResource($request->user()),
            'User Data'
        );
    }

    public function refresh()
    {
        $newToken = Auth::guard('api')->refresh();

        return ApiResponse::success([
            'access_token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'Token refreshed successfully');
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return ApiResponse::success(null, 'Successfully logged out');
    }
}
