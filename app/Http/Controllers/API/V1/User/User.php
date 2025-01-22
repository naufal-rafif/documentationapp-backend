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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class User extends Controller
{

    /**
     * Retrieve a paginated list of users with their roles.
     *
     * @OA\Get(
     *     path="/user",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by user name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit data per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_data", type="integer", example=100),
     *                 @OA\Property(property="total_pages", type="integer", example=20),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=5)
     *             ),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="uuid"),
     *                     @OA\Property(property="name", type="string", example="User Name"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="roles", type="array",
     *                         @OA\Items(type="string", example="Admin")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(Request $request)
    {
        $limit = $request->limit ?? 5;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $user_level = Auth::user()->roles[0]->level;
        $data = UserModel::with('roles')->skip($offset)
            ->take($limit)
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('users.id','!=', Auth::user()->id)
            ->where('roles.level', '>', $user_level);
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
     * Store a newly created user in storage.
     *
     * @OA\Post(
     *     path="/user/user",
     *     summary="Create user",
     *     tags={"User - User"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="address", type="string", example="Jalan Jalan 1", nullable=true),
     *             @OA\Property(property="phone_number", type="string", example="081234567890", nullable=true),
     *             @OA\Property(property="birth_date", type="string", example="1990-01-01", nullable=true, format="date"),
     *             @OA\Property(property="gender", type="string", example="male", nullable=true),
     *             @OA\Property(property="status_account", type="string", example="active", nullable=true, default="active"),
     *             @OA\Property(property="company_id", type="string", example="company_uuid", nullable=true),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(type="string", example="Guest")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Bad Request")
     *         )
     *     )
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
     * Get user by uuid
     * 
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
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="user_uuid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="address", type="string", example="Address", nullable=true),
     *                     @OA\Property(property="avatar", type="string", example=null, nullable=true),
     *                     @OA\Property(property="phone_number", type="string", example="1234567890", nullable=true),
     *                     @OA\Property(property="birth_date", type="string", example="1990-01-01", nullable=true),
     *                     @OA\Property(property="gender", type="string", example="male", nullable=true),
     *                     @OA\Property(property="status_account", type="string", example="active", nullable=true)
     *                 ),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="name", type="string", example="Guest"),
     *                         @OA\Property(property="uuid", type="string", example="role_uuid")
     *                     )
     *                 )
     *             )
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
     *             @OA\Property(property="password", type="string", example="password", nullable=true),
     *             @OA\Property(property="company_id", type="string", example="company_uuid", nullable=true),
     *             @OA\Property(property="role_id", type="string", example="role_uuid", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User Successfully updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="user_uuid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", example="role_uuid"),
     *                         @OA\Property(property="name", type="string", example="role_name"),
     *                     )
     *                 )
     *             )
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
     * Update user status
     *
     * @OA\Post(
     *     path="/user/user/{uuid}/status",
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
     *             type="object",
     *             @OA\Property(property="status", type="string", example="active", description="The status of the user", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User update successfully",
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
        if (!$user_detail) {
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
     * Delete user by UUID
     *
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
        ], Response::HTTP_OK);
    }
}
