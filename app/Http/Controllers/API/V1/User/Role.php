<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Role\AddRoleRequest;
use App\Http\Requests\User\Role\UpdateRoleRequest;
use App\Models\Company;
use Auth;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Symfony\Component\HttpFoundation\Response;

class Role extends Controller
{
    /**
     * Get All Role
     * 
     * @OA\Get (
     *     path="/user/role",
     *     tags={"User - Role"},
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
        $role_level = Auth::user()->roles[0]->level;
        $data = RoleModel::select('id', 'name', 'uuid', 'description')->with([
            'permissions' => function ($query) {
                $query->select(['uuid', 'name', 'label', 'description', 'group_id'])->limit(5);
            }
        ])
            ->where('level', '>=', $role_level)
            ->skip($offset)
            ->take($limit);
        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }
        $total_data = RoleModel::count();
        $data = $data->get()
            ->makeHidden('id');

        $data = $data->map(function ($item) {
            return [
                'id' => $item->uuid,
                'name' => $item->name,
                'description' => $item->description,
                'permissions' => $item->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->uuid,
                        'label' => $permission->label,
                    ];
                }),
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
     * Create Role
     * @OA\Post (
     *     path="/user/role",
     *     tags={"User - Role"},
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
     *                          property="level",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="company_id",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="description",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="permissions",
     *                          type="object"
     *                      ),
     *                 ),
     *                 example={
     *                     "name":"Role",
     *                     "level":1,
     *                     "company_id":null,
     *                     "description":"lorem ipsum dolor",
     *                     "permissions":null
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
    public function store(AddRoleRequest $request)
    {
        $user_level = Auth::user()->roles[0]->level;
        if (isset($request->level) && $user_level >= $request->level) {
            return response()->json([
                'status_code' => Response::HTTP_FORBIDDEN,
                'message' => 'You are not allowed to create a role with a higher level than your own',
            ], Response::HTTP_FORBIDDEN);
        }
        $company_id = Company::first()->id;
        if($request->company_id){
            $company = Company::where('uuid', $request->company_id)->first();
            if($company)    {
                $company_id = $company->id;
            }
        }
        $role = RoleModel::create([
            'uuid' => Uuid::uuid4(),
            'name' => $request->name,
            'guard_name' => 'api',
            'level' => $request->level ?: 10,
            'company_id' => $company_id,
            'description' => $request->description,
        ]);
        $permissions = [];
        if (isset($request->permissions)) {
            $permissions = Permission::whereIn('uuid', $request->permissions)->get();
        }
        $role->syncPermissions($permissions);
        $role = [
            'id' => $role->uuid,
            'name'=> $role->name,
            'level' => $role->level,
            'description' => $role->description
        ];

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'OK',
            'data' => $role
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/user/role/{uuid}",
     *     summary="Get role by uuid",
     *     tags={"User - Role"},
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="uuid", type="string", example="admin"),
     *                 @OA\Property(property="permissions", type="array",
     *                     @OA\Items(type="string", example="view_users")
     *                 ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Role Not Found")
     *         )
     *     )
     * )
     */
    public function show($uuid)
    {
        $role_level = Auth::user()->roles[0]->level;
        $role = RoleModel::with([
            'permissions' => function ($query) {
                $query->select(['uuid', 'name', 'label', 'description', 'group_id']);
            }
        ])
            ->where('level', '>=', $role_level)
            ->where(['uuid' => $uuid, 'guard_name' => 'api'])
            ->first();

        if (!$role) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Role Not Found',
            ], 404);
        }


        $role = [
            'id' => $role->uuid,
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->uuid,
                    'label' => $permission->label,
                ];
            }),
        ];

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => $role
        ]);
    }

    /**
     * @OA\Put(
     *     path="/user/role/{name}",
     *     summary="Update a role by name",
     *     tags={"User - Role"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="admin"),
     *             @OA\Property(property="permissions", type="array",
     *                 @OA\Items(type="string", example="view_users")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="admin"),
     *                 @OA\Property(property="permissions", type="array",
     *                     @OA\Items(type="string", example="view_users")
     *                 ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Role Not Found")
     *         )
     *     )
     * )
     */
    public function update(UpdateRoleRequest $request, $uuid)
    {
        $user_level = Auth::user()->roles[0]->level;
        $role = RoleModel::where(['uuid' => $uuid, 'guard_name' => 'api'])->where('level', '>=', $user_level)->first();

        if (!$role) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Role Not Found',
            ], Response::HTTP_NOT_FOUND);
        }
        $permissions = [];
        if ($request->permissions) {
            $permissions = Permission::whereIn('uuid', $request->permissions)->get();
        }

        $role->name = $request->input('name') ?: $role->name;
        $role->description = $request->input('description') ?: $role->description;
        $role->syncPermissions($permissions);
        $role->save();

        $role = [
            'id' => $role->uuid,
            'name' => $role->name,
            'description' => $role->description
        ];

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Role updated successfully',
            'data' => $role
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/user/role/{name}",
     *     summary="Delete a role",
     *     tags={"User - Role"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Role deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Role Not Found")
     *         )
     *     )
     * )
     */
    public function destroy($uuid)
    {
        $user_level = Auth::user()->roles[0]->level;
        $role = RoleModel::where('uuid', $uuid)->where('level', '>=', $user_level)->first();

        if (!$role) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Role Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        $role->users()->detach();

        $role->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Role deleted successfully'
        ], Response::HTTP_OK);
    }
}
