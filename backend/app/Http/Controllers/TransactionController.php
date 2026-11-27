<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\TransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->repository->all($request->user()->id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        return response()->json(
            $this->repository->create(
                $request->user()->id,
                $data
            ),
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(
            $this->repository->find(
                $request->user()->id,
                $id
            )
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validatedData($request);

        return response()->json(
            $this->repository->update(
                $request->user()->id,
                $id,
                $data
            )
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->repository->delete(
            $request->user()->id,
            $id
        );

        return response()->json(null, 204);
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json(
            Category::where('user_id', $request->user()->id)->get()
        );
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'category_id' => 'required|integer',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01|max:9999999999.99|decimal:0,2',
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date_format:Y-m-d',
        ]);

        $validCategory = Category::where('id', $data['category_id'])
            ->where('user_id', $request->user()->id)
            ->where('type', $data['type'])
            ->exists();

        if (! $validCategory) {
            throw ValidationException::withMessages([
                'category_id' => 'Invalid category.',
            ]);
        }

        return $data;
    }
}
