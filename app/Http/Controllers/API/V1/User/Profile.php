<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class Profile extends Controller
{
    /**
     * Get authenticated user profile
     * 
     * @OA\Get(
     *     path="/user/profile",
     *     summary="Get authenticated user profile",
     *     tags={"User - Profile"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="user_uuid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="address", type="string", example="Jalan Jalan 1", nullable=true),
     *                     @OA\Property(property="phone_number", type="string", example="081234567890", nullable=true),
     *                     @OA\Property(property="birth_date", type="string", example="1990-01-01", nullable=true),
     *                     @OA\Property(property="gender", type="string", example="male", nullable=true),
     *                     @OA\Property(property="status_account", type="string", example="active", nullable=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user_id = Auth::user()->id;
        $user = User::with('details')->where('id', $user_id)->first();

        $user = [
            'id' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'details' => [
                'full_name' => $user->details->full_name,
                'address' => $user->details->address,
                'avatar' => $user->details->avatar,
                'phone_number' => $user->details->phone_number,
                'birth_date' => $user->details->birth_date,
                'gender' => $user->details->gender,
                'status_account' => $user->details->status_account
            ]
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => $user
        ], Response::HTTP_OK);
    }

    /**
     * Update authenticated user profile
     * 
     * @OA\Put(
     *     path="/user/profile",
     *     summary="Update authenticated user profile",
     *     tags={"User - Profile"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password", nullable=true),
     *             @OA\Property(property="full_name", type="string", example="John Doe", nullable=true),
     *             @OA\Property(property="address", type="string", example="Jalan Jalan 1", nullable=true),
     *             @OA\Property(property="avatar", type="string", example="image.jpg", format="binary", nullable=true),
     *             @OA\Property(property="phone_number", type="string", example="081234567890", nullable=true),
     *             @OA\Property(property="birth_date", type="string", example="1990-01-01", nullable=true),
     *             @OA\Property(property="gender", type="string", example="male", nullable=true),
     *             @OA\Property(property="status_account", type="string", example="active", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile Updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Profile Updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="user_uuid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="address", type="string", example="Jalan Jalan 1", nullable=true),
     *                     @OA\Property(property="phone_number", type="string", example="081234567890", nullable=true),
     *                     @OA\Property(property="birth_date", type="string", example="1990-01-01", nullable=true),
     *                     @OA\Property(property="gender", type="string", example="male", nullable=true),
     *                     @OA\Property(property="status_account", type="string", example="active", nullable=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(UpdateProfileRequest $request)
    {
        $user_id = Auth::user()->id;
        $user = User::with('details')->find($user_id);
        $user->update([
            'name' => $request->name ?: $user->name,
            'email' => $request->email ?: $user->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password
        ]);
        $avatarPath = $user->details->avatar;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            // Determine storage location
            $storageDisk = config('filesystems.default'); // 'local' or 's3'

            // Delete previous avatar
            if ($user->details->avatar) {
                Storage::disk($storageDisk)->delete($user->details->avatar);
            }

            // Store the file and get its path
            $avatarPath = $file->store('avatars', $storageDisk); // E.g., 'avatars/image.jpg'
        }


        UserDetail::where('user_id', $user_id)->update([
            'full_name' => $request->full_name ?? $user->details->full_name,
            'address' => $request->address ?? $user->details->address,
            'avatar' => $avatarPath,
            'phone_number' => $request->phone_number ?? $user->details->phone_number,
            'birth_date' => Carbon::parse($request->birth_date)->toDateString() ?? $user->details->birth_date,
            'gender' => $request->gender ?? $user->details->gender,
            'status_account' => $request->status_account ?? $user->details->status_account,
        ]);
        $user = [
            'id' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'details' => [
                'full_name' => $user->details->full_name,
                'address' => $user->details->address,
                'avatar' => $avatarPath !== null ? asset('storage/' . $avatarPath) : null,
                'phone_number' => $user->details->phone_number,
                'birth_date' => $user->details->birth_date,
                'gender' => $user->details->gender,
                'status_account' => $user->details->status_account
            ]
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Profile Updated',
            'data' => $user
        ], Response::HTTP_OK);
    }
}
