<?php

namespace Modules\ApiBridge\Http\Controllers\Api;

use App\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomersController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->orderBy('id', 'desc');

        if ($request->filled('email')) {
            $query->where('emails', 'like', '%' . strtolower($request->get('email')) . '%');
        }

        if ($request->filled('updatedSince')) {
            $query->where('updated_at', '>=', $this->convertUtc($request->get('updatedSince')));
        }

        if ($request->filled('createdSince')) {
            $query->where('created_at', '>=', $this->convertUtc($request->get('createdSince')));
        }

        $perPage = min(100, max(1, (int) $request->get('pageSize', 50)));
        $customers = $query->paginate($perPage);

        $data = [];
        foreach ($customers as $customer) {
            $data[] = $this->formatter->format($customer);
        }

        return $this->respondWithPagination($customers, [
            'customers' => $data,
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->respond($this->formatter->format($customer));
    }

    protected function convertUtc(string $value): string
    {
        try {
            return Carbon::parse($value, 'UTC')
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString();
        } catch (\Throwable $e) {
            return $value;
        }
    }
}


