<?php

namespace App\Http\Controllers\API\V1\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\District\CreateDistrictRequest;
use App\Http\Requests\DataMaster\District\UpdateDistrictRequest;
use App\Models\DataMaster\District as DistrictModel;
use App\Models\DataMaster\Regency as RegencyModel;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class District extends Controller
{
    /**
     * Get all district
     *
     * @OA\Get (
     *     path="/data-master/district",
     *     tags={"Data Master - District"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by district name",
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
     *         name="regency_id",
     *         in="query",
     *         description="Regency id",
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
     *                      @OA\Property(property="id", type="string", example=""),
     *                      @OA\Property(property="name", type="string", example=""),
     *                      @OA\Property(property="alt_name", type="string", example=""),
     *                      @OA\Property(property="latitude", type="string", example=""),
     *                      @OA\Property(property="longitude", type="string", example="")
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

        $data = DistrictModel::skip($offset)
            ->take($limit);

        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }

        $regency = null;
        if ($request->regency_id) {
            $regency = RegencyModel::where('uuid', $request->regency_id)->first();
            if ($regency !== null) {
                $data->where('regency_id', $regency->id);
            }
        }

        $total_data = DistrictModel::when($request->search, function ($query) use ($request) {
            return $query->whereLike('name', "%$request->search%");
        })->when($request->regency_id, function ($query) use ($regency) {
            return $query->where('regency_id', $regency->id);
        })
            ->count();
        $data = $data->orderBy('id', 'desc')->get();
        $result = $data->map(function ($item) {
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
            'data' => $result
        ]);
    }

    /**
     * Create a district
     * 
     * @OA\Post(
     *     path="/data-master/district",
     *     summary="Create a district",
     *     tags={"Data Master - District"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="latitude", type="string", example="-6.21462"),
     *             @OA\Property(property="longitude", type="string", example="106.84513"),
     *             @OA\Property(property="regency_id", type="string", example="regency-1")
     *         )
     *     ),
     *     @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="status_code", type="integer", example=201),
     *              @OA\Property(property="message", type="string", example="OK"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="string", example=""),
     *                  @OA\Property(property="name", type="string", example=""),
     *                  @OA\Property(property="alt_name", type="string", example=""),
     *                  @OA\Property(property="latitude", type="string", example=""),
     *                  @OA\Property(property="longitude", type="string", example="")
     *              )
     *          )
     *     )
     * )
     */
    public function store(CreateDistrictRequest $request)
    {
        $regency = RegencyModel::where('uuid', $request->regency_id)->first();
        $data = DistrictModel::create([
            'uuid' => Uuid::uuid4(),
            'name' => $request->name,
            'alt_name' => $request->alt_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'regency_id' => $regency->id
        ]);
        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'OK',
            'data' => $data
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a district by uuid
     * 
     * @OA\Get(
     *     path="/data-master/district/{uuid}",
     *     summary="Show district by uuid",
     *     tags={"Data Master - District"},
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
     *                 @OA\Property(property="id", type="string", example="district-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta Selatan"),
     *                 @OA\Property(property="alt_name", type="string", example="Jakarta Selatan"),
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
     *             @OA\Property(property="message", type="string", example="Data not found")
     *         )
     *     )
     * )
     */
    public function show($uuid)
    {
        $data = DistrictModel::with('regency')->where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Data not found'
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
                'regency' => [
                    'id' => $data->regency->uuid,
                    'name' => $data->regency->name,
                    'alt_name' => $data->regency->alt_name,
                    'latitude' => $data->regency->latitude,
                    'longitude' => $data->regency->longitude
                ]
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Update a district by uuid.
     * 
     * @OA\Put(
     *     path="/data-master/district/{uuid}",
     *     summary="Update district by uuid",
     *     tags={"Data Master - District"},
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
     *             @OA\Property(property="alt_name", type="string", example="Jakarta Pusat"),
     *             @OA\Property(property="latitude", type="string", example="-6.21462"),
     *             @OA\Property(property="longitude", type="string", example="106.84513"),
     *             @OA\Property(property="regency_id", type="string", example="regency-1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="district-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta Selatan"),
     *                 @OA\Property(property="alt_name", type="string", example="Jakarta Selatan"),
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
     *             @OA\Property(property="message", type="string", example="Data not found")
     *         )
     *     )
     * )
     */
    public function update(UpdateDistrictRequest $request, $uuid)
    {
        $data = DistrictModel::where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'District not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $regency = RegencyModel::where('uuid', $request->regency_id)->first();
        $data->update([
            'name' => $request->name,
            'regency_id' => $regency->id,
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
            'message' => 'District updated successfully',
            'data' => $output
        ]);
    }

    /**
     * Delete a district by uuid
     * 
     * @OA\Delete(
     *     path="/data-master/district/{uuid}",
     *     summary="Delete district by uuid",
     *     tags={"Data Master - District"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="District deleted successfully",
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
     *             @OA\Property(property="message", type="string", example="Data not found")
     *         )
     *     )
     * )
     */
    public function destroy($uuid)
    {
        $data = DistrictModel::where('uuid', $uuid)->first();
        if ($data === null) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Data not found',
            ], Response::HTTP_NOT_FOUND);
        }
        $data->delete();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Dictrict deleted successfully',
            'data' => null
        ]);
    }
}