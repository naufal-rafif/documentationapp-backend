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
     * Get All Role
     * @OA\Get (
     *     path="/user/role",
     *     tags={"User - Role"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by role name",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit data per page",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=5
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page data",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=1
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status_code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="OK"),
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="total_data", type="integer", example=10),
     *                  @OA\Property(property="total_pages", type="integer", example=1),
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="per_page", type="integer", example=5)
     *              ),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="string"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="permissions", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="string"),
     *                              @OA\Property(property="label", type="string"),
     *                          )
     *                      )
     *                  )
     *              ),
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
                $query->select(['uuid', 'name', 'label', 'description', 'group_id']);
            }
        ])
            ->where('level', '>', $role_level)
            ->skip($offset)
            ->take($limit);
        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }
        $total_data = RoleModel::where('level', '>', $role_level)->count();
        $data = $data->orderBy('id', 'asc')->get()
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
     * @OA\Post(
     *     path="/user/role",
     *     summary="Create role",
     *     description="Create role",
     *     tags={"User - Role"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="role_name", description="The name of the role"),
     *             @OA\Property(property="level", type="integer", example=1, description="The level of the role", nullable=true),
     *             @OA\Property(property="description", type="string", example="role_description", description="The description of the role", nullable=true),
     *             @OA\Property(property="company_id", type="string", example="company_uuid", description="The uuid of the company", nullable=true),
     *             @OA\Property(property="permissions", type="array", collectionFormat="multi", @OA\Items(type="string", example="permission_uuid"), description="The uuid of the permissions", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="role_uuid"),
     *                 @OA\Property(property="name", type="string", example="role_name"),
     *                 @OA\Property(property="level", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example="role_description")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="You are not allowed to create a role with a higher level than your own")
     *         )
     *     ),
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
     *         description="Role found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="role_uuid"),
     *                 @OA\Property(property="name", type="string", example="role_name"),
     *                 @OA\Property(property="description", type="string", example="role_description"),
     *                 @OA\Property(property="permissions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", example="permission_uuid"),
     *                         @OA\Property(property="label", type="string", example="permission_label"),
     *                     )
     *                 )
     *             )
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
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/user/role/{uuid}",
     *     summary="Update role by uuid",
     *     tags={"User - Role"},
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
     *             @OA\Property(property="name", type="string", example="role_name", description="The name of the role"),
     *             @OA\Property(property="description", type="string", example="role_description", description="The description of the role", nullable=true),
     *             @OA\Property(property="permissions", type="array", collectionFormat="multi", @OA\Items(type="string", example="permission_uuid"), description="The uuid of the permissions", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="role_uuid"),
     *                 @OA\Property(property="name", type="string", example="role_name"),
     *                 @OA\Property(property="description", type="string", example="role_description")
     *             )
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
        $role = RoleModel::where(['uuid' => $uuid, 'guard_name' => 'api'])->where('level', '>', $user_level)->first();

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
     * Delete role by uuid
     * 
     * Delete role by uuid
     * @OA\Delete(
     *     path="/user/role/{uuid}",
     *     summary="Delete role by uuid",
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
