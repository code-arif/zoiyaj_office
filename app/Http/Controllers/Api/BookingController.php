<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\ServiceReview;
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

    public function getClientBookings(Request $request)
    {
        try {
            $user = $request->user();
            $type = $request->type;
            if($user->role != 'client'){
                return $this->error(null, 'Unauthorized access.', 403);
            }
            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }
            $today = date('Y-m-d');

            if($type == 'upcoming'){
                $bookings = $user->service_bookings()
                                ->where('status', 'pending')
                                ->orWhere('status', 'confirmed')
                                ->where('scheduled_date', '>=', $today)
                                ->with('service')->get();
            } elseif($type == 'completed'){
                $bookings = $user->service_bookings()->where('status', 'completed')->with('service')->get();
            } elseif($type == 'cancelled'){
                $bookings = $user->service_bookings()->where('status', 'cancelled')->with('service')->get();
            } else {
                $bookings = $user->service_bookings()->with('service')->get();
            }

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
            $type = $request->type;
            // if($user->role != 'professional'){
            //     return $this->error(null, 'Unauthorized access.', 403);
            // }

            if (!$professional) {
                return $this->error(null, 'Professional not found.', 404);
            }
            $today = date('Y-m-d');

            if($type === 'upcoming'){
                $bookings = ServiceBooking::whereHas('service', function ($query) use ($professional) {
                    $query->where('user_id', $professional->id);
                })
                ->where('status', 'pending')
                ->orWhere('status', 'confirmed')
                ->where('scheduled_date', '>=', $today)
                ->with('service')->get();
            } elseif($type === 'completed'){
                $bookings = ServiceBooking::whereHas('service', function ($query) use ($professional) {
                    $query->where('user_id', $professional->id);
                })->where('status', 'completed')->with('service')->get();
            } elseif($type === 'cancelled'){
                $bookings = ServiceBooking::whereHas('service', function ($query) use ($professional) {
                    $query->where('user_id', $professional->id);
                })->where('status', 'cancelled')->with('service')->get();
            } else {
                $bookings = ServiceBooking::whereHas('service', function ($query) use ($professional) {
                    $query->where('user_id', $professional->id);
                })->with('service')->get();
            }

            return $this->success($bookings, 'Professional bookings retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to retrieve bookings. '.$e->getMessage(), 500);
        }
    }

    public function approveBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:service_bookings,id',
        ]);

        try {
            $booking = ServiceBooking::find($request->booking_id);
            if (!$booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'confirmed';
            $booking->save();

            return $this->success($booking, 'Booking status updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to update booking status. '.$e->getMessage(), 500);
        }
    }

    public function cancelBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:service_bookings,id',
            'reason' => 'nullable|string',
        ]);

        try {
            $booking = ServiceBooking::find($request->booking_id);
            if (!$booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'cancelled';
            $booking->notes = 'Reason: ' . $request->reason;
            $booking->save();

            return $this->success($booking, 'Booking cancelled successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to cancel booking. '.$e->getMessage(), 500);
        }
    }

    public function completeBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:service_bookings,id',
        ]);

        try {
            $booking = ServiceBooking::find($request->booking_id);
            if (!$booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'completed';
            $booking->save();

            return $this->success($booking, 'Booking marked as completed successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to complete booking. '.$e->getMessage(), 500);
        }
    }

    public function updateBookingStatus(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:service_bookings,id',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        try {
            $booking = ServiceBooking::find($request->booking_id);
            if (!$booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = $request->status;
            $booking->save();

            return $this->success($booking, 'Booking status updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to update booking status. '.$e->getMessage(), 500);
        }
    }

    public function submitReview(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:service_bookings,id',
            'service_id' => 'required|exists:professinal_services,id',
            'client_id' => 'required|exists:users,id',
            'professional_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        try {
            $review = ServiceReview::create([
                'booking_id' => $request->booking_id,
                'service_id' => $request->service_id,
                'client_id' => $request->client_id,
                'professional_id' => $request->professional_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return $this->success($review, 'Review submitted successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to submit review. '.$e->getMessage(), 500);
        }
    }

}
