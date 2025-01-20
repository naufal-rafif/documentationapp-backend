<?php

namespace App\Http\Controllers\API\V1\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\Basic\RegisterRequest;
use App\Models\Company;
use App\Models\User as UserModel;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class Basic extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/basic/login",
     *     summary="Authenticate User and Generate Token",
     *     tags={"Authentication - Basic"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="The email address of the user",
     *                     example="developer@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="The password of the user",
     *                     example="password"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful and token generated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Login successful"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="John Doe"
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         example="admin@gmail.com"
     *                     ),
     *                     @OA\Property(
     *                         property="company_id",
     *                         type="integer",
     *                         example=1001
     *                     ),
     *                     @OA\Property(
     *                      property="details",
     *                      type="object",
     *                        @OA\Property(
     *                            property="full_name",
     *                            type="string",
     *                            example="Johnathan Doe"
     *                        ),
     *                        @OA\Property(
     *                            property="address",
     *                            type="string",
     *                             example="123 Main St, Anytown, USA"
     *                         ),
     *                         @OA\Property(
     *                            property="avatar",
     *                            type="string",
     *                            example="https://example.com/storage/avatars/avatar123.jpg"
     *                        ),
     *                        @OA\Property(
     *                            property="phone_number",
     *                            type="string",
     *                            example="+1234567890"
     *                        ),
     *                        @OA\Property(
     *                           property="birth_date",
     *                             type="string",
     *                             format="date",
     *                             example="1990-01-01"
     *                        ),
     *                        @OA\Property(
     *                             property="gender",
     *                             type="string",
     *                             example="male"
     *                        ),
     *                        @OA\Property(
     *                            property="status_account",
     *                             type="string",
     *                             example="active"
     *                         )
     *                      )
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="integer",
     *                 example=404
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Invalid credentials"
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user = UserModel::select('id', 'name', 'email', 'company_id')->with([
            'details' => function ($query) {
                $query->select(['user_id', 'full_name', 'address', 'avatar', 'phone_number', 'birth_date', 'gender', 'status_account']);
            },
        ])->where('email', $request->email)->first()->makeHidden('id');
        if ($user->details) {
            $user->details->makeHidden('user_id');
            $userDetails = $user->details;
            if ($userDetails && $userDetails->avatar) {
                $user->details->avatar = asset('storage/' . $userDetails->avatar); // or use S3 path if applicable
            }
        }
        $user->permissions = $user->getPermissionsViaRoles()->pluck('name');

        $user = [
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'permissions' => $user->permissions,
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

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/auth/basic/register",
     *     summary="Authentication Register",
     *     tags={"Authentication - Basic"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="The name of the user",
     *                     example="John Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="The email of the user",
     *                     example="john.doe@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="The password for the user account",
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     description="Password confirmation",
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="full_name",
     *                     type="string",
     *                     description="The full name of the user",
     *                     example="Johnathan Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     description="The address of the user",
     *                     example="123 Main Street, Springfield",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Avatar image file",
     *                     example="string (binary file)"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_number",
     *                     type="string",
     *                     description="The phone number of the user",
     *                     example="1234567890",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     description="The birth date of the user",
     *                     example="1990-01-01",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="gender",
     *                     type="string",
     *                     enum={"male", "female", "other"},
     *                     description="The gender of the user",
     *                     example="male",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="status_account",
     *                     type="string",
     *                     description="The status of the account",
     *                     example="active",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="User Created Successfully")
     *         )
     *     ),
     *     @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The name field is required. (and 1 more error)"),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="name", type="object",
     *                      @OA\Property(property="name", type="string", example="The name field is required."),
     *                  ),
     *                  @OA\Property(property="email", type="object",
     *                      @OA\Property(property="email", type="string", example="The email field is required."),
     *                  ),
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Error occurred while creating user."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        // Create the user
        $user = UserModel::create([
            'uuid' => Uuid::uuid4(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Handle avatar upload if provided
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            // Determine storage location
            $storageDisk = config('filesystems.default'); // 'local' or 's3'

            // Store the file and get its path
            $avatarPath = $file->store('avatars', $storageDisk); // E.g., 'avatars/image.jpg'
        }

        // Create the user's details
        UserDetail::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'address' => $request->address ?? null,
            'avatar' => $avatarPath, // Store the file path or null
            'phone_number' => $request->phone_number ?? null,
            'birth_date' => $request->birth_date ?? null,
            'gender' => $request->gender ?? null,
            'status_account' => $request->status_account ?? 'active', // Default status
        ]);
        $company = Company::where('uuid', $request->company_id ?? null)->first();
        if ($company) {
            $default_role = $company->default_role;
        } else {
            $default_role = 'Guest';
        }

        $role = Role::where('name', $default_role)->where('company_id', $company?->id ?? null)->first();
        $user->assignRole($role);

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'User Created Successfully',
        ], Response::HTTP_CREATED);

    }


    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
            ]
        ]);
    }
}
