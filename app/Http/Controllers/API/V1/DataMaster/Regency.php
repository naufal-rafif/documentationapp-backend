<?php

namespace App\Http\Controllers\API\V1\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\Regency\CreateRegencyRequest;
use App\Http\Requests\DataMaster\Regency\UpdateRegencyRequest;
use App\Models\DataMaster\Regency as RegencyModel;
use App\Models\DataMaster\Province as ProvinceModel;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class Regency extends Controller
{
    /**
     * Get all regency
     *
     * @OA\Get (
     *     path="/data-master/regency",
     *     tags={"Data Master - Regency"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by regency name",
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
     *     @OA\Parameter(
     *         name="province_id",
     *         in="query",
     *         description="Province id",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
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
     *                      @OA\Property(property="id", type="string", example="regency-1"),
     *                      @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *                      @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *                      @OA\Property(property="latitude", type="string", example="-6.21462"),
     *                      @OA\Property(property="longitude", type="string", example="106.84513")
     *                  )
     *              )
     *          )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 5;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;

        $data = RegencyModel::skip($offset)
            ->take($limit);

        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }
        $province = null;
        if ($request->province_id) {
            $province = ProvinceModel::where('uuid', $request->province_id)->first();
            if ($province !== null) {
                $data->where('province_id', $province->id);
            }
        }

        $total_data = RegencyModel::when($request->search, function ($query) use ($request) {
            return $query->whereLike('name', "%$request->search%");
        })->when($request->province_id, function ($query) use ($province) {
            return $query->where('province_id', $province->id);
        })
            ->count();
        $data = $data->orderBy('id', 'desc')->get();
        $data = $data->map(function ($item) {
            return [
                'id' => $item->uuid,
                'name' => $item->name,
                'alt_name' => $item->alt_name,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
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
     * Create a new regency
     * 
     * @OA\Post(
     *     path="/data-master/regency",
     *     tags={"Data Master - Regency"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         description="Regency data",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="province_id", type="string", example="province-1"),
     *             @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="latitude", type="string", example="-6.21462"),
     *             @OA\Property(property="longitude", type="string", example="106.84513")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="regency-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="latitude", type="string", example="-6.21462"),
     *                 @OA\Property(property="longitude", type="string", example="106.84513")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(CreateRegencyRequest $request)
    {
        $province = ProvinceModel::where('uuid', $request->province_id)->first();
        $data = RegencyModel::create([
            'uuid' => Uuid::uuid4(),
            'province_id' => $province->id,
            'name' => $request->name,
            'alt_name' => $request->alt_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);
        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'OK',
            'data' => $data
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a regency by uuid
     *
     * @OA\Get(
     *     path="/data-master/regency/{uuid}",
     *     summary="Show regency by uuid",
     *     tags={"Data Master - Regency"},
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
     *                 @OA\Property(property="id", type="string", example="regency-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="latitude", type="string", example="-6.21462"),
     *                 @OA\Property(property="longitude", type="string", example="106.84513")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Data not found"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */

    public function show($uuid)
    {
        $data = RegencyModel::where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Data not found',
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
            'data' => [
                'id' => $data->uuid,
                'name' => $data->name,
                'alt_name' => $data->alt_name,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
            ]
        ]);
    }

    /**
     * Update a regency by uuid
     * 
     * @OA\Put(
     *     path="/data-master/regency/{uuid}",
     *     summary="Update regency by uuid",
     *     tags={"Data Master - Regency"},
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
     *             @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="province_id", type="string", example="province-1"),
     *             @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="latitude", type="string", example="-6.21462"),
     *             @OA\Property(property="longitude", type="string", example="106.84513")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="regency-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *                 @OA\Property(property="latitude", type="string", example="-6.21462"),
     *                 @OA\Property(property="longitude", type="string", example="106.84513")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Data not found"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function update(UpdateRegencyRequest $request, $uuid)
    {
        $data = RegencyModel::where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Regency not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $provinces = ProvinceModel::where('uuid', $request->province_id)->first();
        $data->update([
            'name' => $request->name,
            'province_id' => $provinces->id,
            'alt_name' => $request->alt_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        $output = [
            'id' => $data->uuid,
            'name' => $request->name ?: $data->name,
            'alt_name' => $request->alt_name ?: $data->alt_name,
            'latitude' => $request->latitude ?: $data->latitude,
            'longitude' => $request->longitude ?: $data->longitude
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Regency updated successfully',
            'data' => $output
        ]);
    }

    /**
     * Delete a regency by uuid
     * 
     * @OA\Delete(
     *     path="/data-master/regency/{uuid}",
     *     summary="Delete regency by uuid",
     *     tags={"Data Master - Regency"},
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
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Data not found"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function destroy($uuid)
    {
        $data = RegencyModel::where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Data not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $data->delete();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Regency deleted successfully',
            'data' => null
        ]);
    }
}