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
     *     summary="Login user",
     *     description="Login user and return token",
     *     operationId="login",
     *     tags={"Authentication - Basic"},
     *     @OA\RequestBody(
     *         description="Input data format",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="developer@example.com", description="The email of the user"),
     *             @OA\Property(property="password", type="string", example="password", description="The password of the user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTYyNjQ0NjM3NCwiZXhwIjoxNjI2NDUwOTc0LCJuYmYiOjE2MjY0NDYzNzQsImp0aSI6IjM2YmM3MzcxNWZiOGJmMTciLCJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaGFuIjoiMjMwfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="user1@mail.com"),
     *                     @OA\Property(property="permissions", type="array", collectionFormat="multi", @OA\Items(type="string", example="view_users")),
     *                     @OA\Property(property="details", type="object",
     *                         @OA\Property(property="full_name", type="string", example="John Doe"),
     *                         @OA\Property(property="address", type="string", example="Jl. Jend. Sudirman No. 1, Jakarta Pusat"),
     *                         @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                         @OA\Property(property="phone_number", type="string", example="081234567890"),
     *                         @OA\Property(property="birth_date", type="string", example="1990-01-01"),
     *                         @OA\Property(property="gender", type="string", example="male"),
     *                         @OA\Property(property="status_account", type="string", example="active")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (!$token = Auth::guard('api')->setTTL(525600)->attempt($credentials)) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user = UserModel::select('id', 'name', 'email')->with([
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
            'permissions' => $user->permissions,
            'roles' => $user->roles->pluck('name'),
            'details' => [
                'full_name' => $user->details->full_name,
                'address' => $user->details->address,
                'avatar' => $user->details->avatar,
                'phone_number' => $user->details->phone_number,
                'birth_date' => $user->details->birth_date,
                'gender' => $user->details->gender,
                'status_account' => $user->details->status_account,
            ]
        ];

        return $this->respondWithToken($token, $user);
    }

    /**
     * Create a new user and assign the default role for the company
     * 
     * @OA\Post (
     *     path="/auth/basic/register",
     *     tags={"Authentication - Basic"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="John Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     example="john.doe@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string",
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="full_name",
     *                     type="string",
     *                     example="Johnathan Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="123 Main Street, Springfield",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="phone_number",
     *                     type="string",
     *                     example="1234567890",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     example="1990-01-01",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="gender",
     *                     type="string",
     *                     enum={"male", "female", "other"},
     *                     example="male",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="status_account",
     *                     type="string",
     *                     enum={"active", "inactive"},
     *                     example="active",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     example="string (binary file)"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User Created Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="integer",
     *                 example=201
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User Created Successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(
     *                     property="code",
     *                     type="number",
     *                     example=422
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="error"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="object",
     *                     @OA\Property(
     *                         property="email",
     *                         type="array",
     *                         collectionFormat="multi",
     *                         @OA\Items(
     *                             type="string",
     *                             example="The email has already been taken."
     *                         ),
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={},
     *             ),
     *         ),
     *     ),
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
        $default_role = 'Guest';

        $role = Role::where('name', $default_role)->first();
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
