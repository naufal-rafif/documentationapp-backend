<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Permission\AddPermissionRequest;
use App\Http\Requests\User\Permission\UpdatePermissionRequest;
use App\Models\PermissionGroup as PermissionGroupModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission as PermissionModel;
use Symfony\Component\HttpFoundation\Response;

class Permission extends Controller
{
    
    /**
     * @OA\Get (
     *     path="/user/permission",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by permission name",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status_code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="OK"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="permissions", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="string"),
     *                              @OA\Property(property="label", type="string"),
     *                              @OA\Property(property="description", type="string")
     *                          )
     *                      )
     *                  )
     *              ),
     *          )
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $user_level = Auth::user()->roles[0]->level;
        $permissions = PermissionGroupModel::select('name', 'description', 'id')->with([
            'permissions' => function ($query) use ($request, $user_level) {
                $query->select(['uuid', 'name', 'label', 'description', 'group_id'])->where('level','>', $user_level);
                if ($request->search) {
                    $query->whereLike('label', "%$request->search%");
                }
            },
        ])->get()->filter(function ($permission_group) {
            return $permission_group->permissions->count() > 0;
        });

        $permissions = $permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'description' => $permission->description,
                'permissions' => $permission->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->uuid,
                        'label' => $permission->label,
                        'description' => $permission->description
                    ];
                })
            ];
        });

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => $permissions
        ]);
    }
    
    /**
     * 
     * Get user permission by uuid
     * @OA\Get(
     *     path="/user/permission/{uuid}",
     *     summary="Get permission by uuid",
     *     tags={"User - Permission"},
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
     *                 @OA\Property(property="id", type="string", example="view_users"),
     *                 @OA\Property(property="name", type="string", example="view_users"),
     *                 @OA\Property(property="label", type="string", example="View Users"),
     *                 @OA\Property(property="description", type="string", example="View Users")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Permission Not Found")
     *         )
     *     )
     * )
     */
    public function show($uuid)
    {
        $permission = PermissionModel::where([
            'uuid' => $uuid
        ])->first();

        if (!$permission) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Permission Not Found',
            ], 404);
        }

        $permission = [
            'id' => $permission->uuid,
            'name' => $permission->name,
            'label' => $permission->label,
            'description' => $permission->description
        ];

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => $permission
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/user/permission",
     *     summary="Create permission",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="group_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="view_users"),
     *             @OA\Property(property="label", type="string", example="View Users"),
     *             @OA\Property(property="description", type="string", example="View Users"),
     *             @OA\Property(property="level", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="b6b5b2b9-5b7a-41b1-8f5e-deb2c6a5e3d6"),
     *                 @OA\Property(property="group_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view_users"),
     *                 @OA\Property(property="label", type="string", example="View Users"),
     *                 @OA\Property(property="description", type="string", example="View Users"),
     *                 @OA\Property(property="level", type="integer", example=1)
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group ID not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Group ID not found")
     *         )
     *     )
     * )
     */
    public function store(AddPermissionRequest $request)
    {
        if (!$request->group_id) {
            return response()->json([
                'status_code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Group ID is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        $permission_group = PermissionGroupModel::where('id', $request->input('group_id'))->first();
        if ($permission_group == null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Group ID not found'
            ], Response::HTTP_NOT_FOUND);
        }
        $permission = PermissionModel::create([
            'uuid' => Uuid::uuid4(),
            'guard_name' => 'api',
            'group_id' => $permission_group->id,
            'name' => $request->input('name'),
            'level' => $request->input('level'),
            'label' => $request->input('label'),
            'description' => $request->input('description')
        ]);

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Permission created successfully',
            'data' => $permission
        ], Response::HTTP_CREATED);
    }

    
    /**
     * @OA\Put(
     *     path="/user/permission/{uuid}",
     *     summary="Update permission by uuid",
     *     tags={"User - Permission"},
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
     *             @OA\Property(property="name", type="string", example="view_users"),
     *             @OA\Property(property="label", type="string", example="View Users"),
     *             @OA\Property(property="description", type="string", example="For View Users"),
     *             @OA\Property(property="level", type="integer", example=3),
     *             @OA\Property(property="group_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="1"),
     *                 @OA\Property(property="name", type="string", example="view_users"),
     *                 @OA\Property(property="label", type="string", example="View Users"),
     *                 @OA\Property(property="description", type="string", example="For View Users"),
     *                 @OA\Property(property="level", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Permission Not Found")
     *         )
     *     )
     * )
     */
    public function update(UpdatePermissionRequest $request, $uuid)
    {
        $permission = PermissionModel::where('uuid', $uuid)->first();

        if (!$permission) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Permission Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        $permission->name = $request->input('name') ?: $permission->name;
        $permission->description = $request->input('description') ?: $permission->description;
        $permission->label = $request->input('label') ?: $permission->label;
        $permission->level = $request->input('level') ?: $permission->level;
        $permission->group_id = $request->input('group_id') ?: $permission->group_id;
        $permission->save();

        $permission = [
            'id' => $permission->uuid,
            'name' => $permission->name,
            'label' => $permission->label,
            'description' => $permission->description,
            'level' => $permission->level
        ];

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Permission updated successfully',
            'data' => $permission
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/user/permission/{uuid}",
     *     summary="Delete a permission",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Permission Not Found")
     *         )
     *     )
     * )
     */
    public function destroy($uuid)
    {
        $permission = PermissionModel::where('uuid', $uuid)->first();

        if (!$permission) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Permission Not Found',
            ], 404);
        }

        $permission->roles()->detach();

        $permission->users()->detach();

        $permission->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Permission deleted successfully'
        ]);
    }
}
