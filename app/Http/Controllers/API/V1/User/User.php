<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\User\AddUserRequest;
use App\Http\Requests\User\User\UpdateUserRequest;
use App\Models\Company;
use App\Models\User as UserModel;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class User extends Controller
{
    /**
     * Get All User
     * 
     * Get All User
     * @OA\Get (
     *     path="/user/user",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status_code", type="number", example=200),
     *              @OA\Property(property="message", type="string", example="OK"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="code", type="number", example=200),
     *                  @OA\Property(property="status", type="string", example="success"),
     *                  @OA\Property(property="message", type="string", example=null),
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="code", type="number", example=422),
     *                  @OA\Property(property="status", type="string", example="error"),
     *                  @OA\Property(property="message", type="object",
     *                      @OA\Property(property="email", type="array", collectionFormat="multi",
     *                        @OA\Items(
     *                          type="string",
     *                          example="The email has already been taken.",
     *                          )
     *                      ),
     *                  ),
     *              ),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 5;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $data = UserModel::with('roles')->skip($offset)
            ->take($limit);
        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }

        $total_data = UserModel::count();
        $data = $data->get();
        $data = $data->map(function ($item) {
            return [
                'id' => $item->uuid,
                'name' => $item->name,
                'email' => $item->email,
                'roles' => $item->roles->pluck('name')->toArray()
            ];
        });
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'meta' => [
                'total_data' => $total_data,
                'total_pages' => ceil($total_data / $limit),
                'current_page' => $page,
                'per_page' => $limit
            ],
            'data' => $data
        ]);
    }

    /**
     * Create user
     * @OA\Post (
     *     path="/user/user",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="name",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="email",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *                 ),
     *                 example={
     *                     "name":"user",
     *                     "email":"user@mail.com",
     *                     "password":"password"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Valid credentials",
     *          @OA\JsonContent(
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="code", type="number", example=200),
     *                  @OA\Property(property="status", type="string", example="success"),
     *                  @OA\Property(property="message", type="string", example=null),
     *              ),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user", type="object",
     *                      @OA\Property(property="id", type="number", example=2),
     *                      @OA\Property(property="name", type="string", example="User"),
     *                      @OA\Property(property="email", type="string", example="user@test.com"),
     *                      @OA\Property(property="email_verified_at", type="string", example=null),
     *                      @OA\Property(property="updated_at", type="string", example="2022-06-28 06:06:17"),
     *                      @OA\Property(property="created_at", type="string", example="2022-06-28 06:06:17"),
     *                  ),
     *                  @OA\Property(property="access_token", type="object",
     *                      @OA\Property(property="token", type="string", example="randomtokenasfhajskfhajf398rureuuhfdshk"),
     *                      @OA\Property(property="type", type="string", example="Bearer"),
     *                      @OA\Property(property="expires_in", type="number", example=3600),
     *                  ),
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Invalid credentials",
     *          @OA\JsonContent(
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="code", type="number", example=401),
     *                  @OA\Property(property="status", type="string", example="error"),
     *                  @OA\Property(property="message", type="string", example="Incorrect username or password!"),
     *              ),
     *              @OA\Property(property="data", type="object", example={}),
     *          )
     *      )
     * )
     */
    public function store(AddUserRequest $request)
    {
        $user = new UserModel();
        $user->uuid = Uuid::uuid4();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        UserDetail::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'address' => $request->address ?? null,
            'avatar' => null, // Store the file path or null
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
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/user/user/{uuid}",
     *     summary="Get user by uuid",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="uuid", type="string", example="admin"),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="email", type="string", example="admin@localhost.com"),
     *                 @OA\Property(property="email_verified_at", type="string", example=null),
     *                 @OA\Property(property="created_at", type="string", example="2022-06-28 06:06:17"),
     *                 @OA\Property(property="updated_at", type="string", example="2022-06-28 06:06:17"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="number", example=404),
     *             @OA\Property(property="message", type="string", example="User Not Found")
     *         )
     *     )
     * )
     */
    public function show($uuid)
    {
        $user = UserModel::with('details', 'roles')->where('uuid', operator: $uuid)->first();
        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user = [
            'id' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'details' => $user->details,
            'roles' => $user->roles->select('name', 'uuid')->toArray()
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => $user
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *     path="/user/user/{uuid}",
     *     summary="Update user by uuid",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password", description="Password is required when you want to update the password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="User Successfully updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="number", example=404),
     *             @OA\Property(property="message", type="string", example="User Not Found")
     *         )
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, $uuid)
    {
        $user = UserModel::where('uuid', $uuid)->first();
        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        $company = Company::where('uuid', $request->company_id ?? null)->first();
        if ($company) {
            $default_role = $company->default_role;
        } else {
            $default_role = 'Guest';
        }

        $role = Role::where('uuid', $request->role_id ?? $default_role)->where('company_id', $company?->id ?? null)->first();
        $user->assignRole($role);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'User Successfully updated',
            'data' => $user
        ], Response::HTTP_OK);
    }

   
    /**
     * @OA\Post(
     *     path="/user/user/{uuid}",
     *     summary="Update user status",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="active"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User update successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="User Not Found")
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, $uuid)
    {
        $user = UserModel::where('uuid', $uuid)->first();
        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user_detail = UserDetail::where('user_id', $user->id)->first();
        if(!$user_detail){
            $user_detail = UserDetail::create([
                'user_id' => $user->id,
                'status_account' => $request->status
            ]);
        } else {
            $user_detail->status_account = $request->status;
            $user_detail->save();
        }
        if ($request->status) {
            $user->deleted_at = $request->status == 'active' ? null : Carbon::now();
            $user->save();
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'User update successfully'
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/user/user/{uuid}",
     *     summary="Delete user by uuid",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="User Not Found")
     *         )
     *     )
     * )
     */
    public function destroy($uuid)
    {
        $user = UserModel::where('uuid', $uuid)->first();
        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $user->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'User deleted successfully'
        ],Response::HTTP_OK);
    }
}
