<?php

namespace App\Http\Controllers\API\V1\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\Province\CreateProvinceRequest;
use App\Http\Requests\DataMaster\Province\UpdateProvinceRequest;
use App\Models\DataMaster\Province as ProvinceModal;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class Province extends Controller
{
    /**
     * Get all province
     *
     * @OA\Get (
     *     path="/data-master/province",
     *     tags={"Data Master - Province"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by province name",
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
     *                      type="object",
     *                      @OA\Property(property="id", type="string", example="4f5b5d75-2a9b-4d35-9d4a-5a7b02b3f37b"),
     *                      @OA\Property(property="name", type="string", example="DKI JAKARTA"),
     *                      @OA\Property(property="alt_name", type="string", example="DKI JAKARTA"),
     *                      @OA\Property(property="latitude", type="float", example=-6.21462),
     *                      @OA\Property(property="longitude", type="float", example=106.84513),
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

        $data = ProvinceModal::skip($offset)
            ->take($limit);

        if ($request->search) {
            $data->whereLike('name', "%$request->search%");
        }

        $total_data = ProvinceModal::when($request->search, function ($query) use ($request) {
            return $query->whereLike('name', "%$request->search%");
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
     * Create a new province
     * 
     * @OA\Post(
     *     path="/data-master/province",
     *     summary="Create a new province",
     *     tags={"Data Master - Province"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         description="Province data",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="DKI JAKARTA"),
     *             @OA\Property(property="alt_name", type="string", example="DKI JAKARTA"),
     *             @OA\Property(property="latitude", type="float", example=-6.21462),
     *             @OA\Property(property="longitude", type="float", example=106.84513)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="4f5b5d75-2a9b-4d35-9d4a-5a7b02b3f37b"),
     *                 @OA\Property(property="name", type="string", example="DKI JAKARTA"),
     *                 @OA\Property(property="alt_name", type="string", example="DKI JAKARTA"),
     *                 @OA\Property(property="latitude", type="float", example=-6.21462),
     *                 @OA\Property(property="longitude", type="float", example=106.84513)
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
    public function store(CreateProvinceRequest $request)
    {
        $data = ProvinceModal::create([
            'uuid' => Uuid::uuid4(),
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
     * Show a province by uuid
     * 
     * @OA\Get(
     *     path="/data-master/province/{uuid}",
     *     summary="Show province by uuid",
     *     tags={"Data Master - Province"},
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
     *                 @OA\Property(property="id", type="string", example="province-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta"),
     *                 @OA\Property(property="alt_name", type="string", example="DKI Jakarta"),
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
        $data = ProvinceModal::where('uuid', $uuid)->first();
        if (!$data) {
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
            ]
        ]);
    }


    /**
     * Update a province by uuid
     * 
     * @OA\Put(
     *     path="/data-master/province/{uuid}",
     *     summary="Update province by uuid",
     *     tags={"Data Master - Province"},
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
     *             @OA\Property(property="name", type="string", example="Jakarta"),
     *             @OA\Property(property="alt_name", type="string", example="DKI Jakarta"),
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
     *                 @OA\Property(property="id", type="string", example="province-1"),
     *                 @OA\Property(property="name", type="string", example="Jakarta"),
     *                 @OA\Property(property="alt_name", type="string", example="DKI Jakarta"),
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
    public function update(UpdateProvinceRequest $request, $uuid)
    {
        $data = ProvinceModal::where('uuid', $uuid)->first();
        if (!$data) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Provinces not found'
            ], Response::HTTP_NOT_FOUND);
        }
        $data->name = $request->name;
        $data->alt_name = $request->alt_name;
        $data->latitude = $request->latitude;
        $data->longitude = $request->longitude;
        $data->save();

        $output = [
            'id' => $data->uuid,
            'name' => $data->name,
            'alt_name' => $data->alt_name,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Province updated successfully',
            'data' => $output
        ]);
    }

    /**
     * Delete a province by uuid
     *
     * @OA\Delete(
     *     path="/data-master/province/{uuid}",
     *     summary="Delete a province by uuid",
     *     tags={"Data Master - Province"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Province deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="OK")
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
        $data = ProvinceModal::where('uuid', $uuid)->first();
        if (!$data) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Data not found'
            ], Response::HTTP_NOT_FOUND);
        }
        $data->delete();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'OK'
        ], Response::HTTP_OK);
    }
}