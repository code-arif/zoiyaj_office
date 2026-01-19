<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\ServiceBooking;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class BookingController extends Controller
{
    use ApiResponse;

    public function bookService(Request $request)
    {
        $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'required|exists:professinal_services,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = User::find($request->user_id);
            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }

            $bookingCollection = collect($request->service_ids)->map(function ($serviceId) use ($request, $user) {
                return [
                    'user_id' => $user->id,
                    'service_id' => $serviceId,
                    'scheduled_date' => $request->scheduled_date,
                    'scheduled_time' => $request->scheduled_time,
                    'notes' => $request->notes,
                    'status' => 'pending',
                    'points' => 5,
                ];
            })->toArray();

            ServiceBooking::insert($bookingCollection);

            return $this->success(null, 'Services booked successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to book services. '.$e->getMessage(), 500);
        }         
    }

    public function getUserBookings(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }

            $bookings = $user->service_bookings()->with('service')->get();

            return $this->success($bookings, 'User bookings retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to retrieve bookings. '.$e->getMessage(), 500);
        }         
    }

    public function getProfessionalBookings(Request $request)
    {
        try {
            $professional = $request->user();

            // if($user->role != 'professional'){
            //     return $this->error(null, 'Unauthorized access.', 403);
            // }

            if (!$professional) {
                return $this->error(null, 'Professional not found.', 404);
            }

            $bookings = ServiceBooking::whereHas('service', function ($query) use ($professional) {
                $query->where('user_id', $professional->id);
            })->with('service')->get();

            return $this->success($bookings, 'Professional bookings retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to retrieve bookings. '.$e->getMessage(), 500);
        }         
    }
}
