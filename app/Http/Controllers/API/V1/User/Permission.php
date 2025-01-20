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
     * 
     * Get all user permission
     * @OA\Get(
     *     path="/user/permission",
     *     summary="Get list of permissions",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="view_users")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $permissions = PermissionGroupModel::select('name', 'description', 'id')->with([
            'permissions' => function ($query) use ($request) {
                $query->select(['uuid', 'name', 'label', 'description', 'group_id']);
                if ($request->search) {
                    $query->whereLike('label', "%$request->search%");
                }
            },
        ])->get();

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
     * @OA\Get(
     *     path="/user/permission/{uuid}",
     *     summary="Get permission by permission uuid",
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view_users")
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
     *     ),
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
     * @OA\Post(
     *     path="/user/permission",
     *     summary="Create a new permission",
     *     tags={"User - Permission"},
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
     *                          property="label",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="description",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="group_id",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="level",
     *                          type="integer"
     *                      ),
     *                 ),
     *                 example={
     *                     "name":"create_user",
     *                     "label":"Create User",
     *                     "group_id":1,
     *                     "description" : "Permission to create User",
     *                     "level" : 1
     *                }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view_users")
     *             )
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
     *     path="/user/permission/{name}",
     *     summary="Update a permission",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
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
     *                          property="label",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="description",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="group_id",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="level",
     *                          type="integer"
     *                      ),
     *                 ),
     *                 example={
     *                     "name":"create_user",
     *                     "label":"Create User",
     *                     "group_id":1,
     *                     "description" : "Permission to create User",
     *                     "level" : 1
     *                }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view_users")
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
     *     path="/user/permission/{name}",
     *     summary="Delete a permission",
     *     tags={"User - Permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="name",
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
