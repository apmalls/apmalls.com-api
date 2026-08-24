<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\DTOs\GoogleUserDTO;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\GoogleAuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Customer\Customer;
use App\Helpers\NumberHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthService implements GoogleAuthServiceInterface
{
    public function __construct(

        protected UserRepositoryInterface $userRepository,
        protected CustomerRepositoryInterface $customerRepository

    ) {
    }

    /**
     * Redirect to Google Login.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google Callback.
     */
    public function callback(): RedirectResponse
    {
        try {

            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            $googleData = $googleUser->user;

            $name = $this->parseName(
                $googleUser->getName() ?? ''
            );

            $dto = new GoogleUserDTO(

                googleId: $googleUser->getId(),

                firstName: $googleData['given_name']
                ?? $name['first_name'],

                lastName: $googleData['family_name']
                ?? $name['last_name'],

                email: $googleUser->getEmail(),

                avatar: $googleUser->getAvatar()

            );

            $user = DB::transaction(function () use ($dto) {

                /**
                 * Find User
                 */
                $user = $this->userRepository
                    ->findByGoogleId($dto->googleId);

                if (!$user) {

                    $user = $this->userRepository
                        ->findByEmail($dto->email);

                }

                /**
                 * Create User
                 */
                if (!$user) {

                    $user = $this->userRepository
                        ->create([

                            'first_name' => $dto->firstName,

                            'last_name' => $dto->lastName,

                            'username' => strtolower(
                                Str::slug(
                                    $dto->firstName . '-' . Str::random(5)
                                )
                            ),

                            'email' => $dto->email,

                            'google_id' => $dto->googleId,

                            'avatar' => $dto->avatar,

                            'mobile' => null,

                            'password' => Hash::make(
                                Str::random(40)
                            ),

                            'email_verified_at' => now(),

                            'is_active' => true,

                        ]);

                } else {

                    /**
                     * Update Existing User
                     */
                    $user = $this->userRepository
                        ->update(

                            $user->id,

                            [

                                'google_id' => $dto->googleId,

                                'avatar' => $dto->avatar,

                                'email_verified_at' => now(),

                            ]

                        );

                }

                /**
                 * Create Customer
                 */
                $customer = $this->customerRepository
                    ->findByUser($user->id);

                if (!$customer) {

                    $this->customerRepository
                        ->create([

                            'user_id' => $user->id,

                            'customer_code' => NumberHelper::generate(

                                Customer::class,

                                'customer_code',

                                'CUS'

                            ),

                            'customer_type' => 'Retail',

                            'first_name' => $user->first_name,

                            'last_name' => $user->last_name,

                            'email' => $user->email,

                            'mobile' => null,

                            'opening_balance' => 0,

                            'credit_limit' => 0,

                            'reward_points' => 0,

                            'is_active' => true,

                        ]);

                }

                /**
                 * Assign Customer Role
                 */
                if (!$user->hasRole('customer')) {

                    $user->assignRole('customer');

                }

                return $user;

            });

            /**
             * Remove Old Tokens
             */
            $user->tokens()->delete();

            /**
             * Generate New Token
             */
            $token = $user
                ->createToken('Website')
                ->plainTextToken;

            /**
             * Redirect Frontend
             */
            return redirect(

                config('app.frontend_url')
                . '/auth/google/callback?token='
                . urlencode($token)

            );

        } catch (\Throwable $exception) {

            report($exception);

            return redirect(

                config('app.frontend_url')
                . '/login?error='
                . urlencode('Google login failed.')

            );

        }
    }

    /**
     * Parse Google Name.
     */
    private function parseName(
        string $name
    ): array {

        $parts = preg_split(
            '/\s+/',
            trim($name)
        );

        return [

            'first_name' => array_shift($parts),

            'last_name' => !empty($parts)
                ? implode(' ', $parts)
                : null,

        ];

    }
}
