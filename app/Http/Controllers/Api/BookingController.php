<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingTime;
use App\Models\ServiceReview;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    use ApiResponse;

    public function bookService(Request $request)
    {

        // dd($request->all());
        $request->validate([
            'owner_id'          => 'required|exists:users,id',
            'service_ids'       => 'required|array',
            'service_ids.*'     => 'required|exists:professinal_services,id',

            'scheduled_date'    => 'required|date',

            'scheduled_times'   => 'required|array',
            'scheduled_times.*' => 'required',

            'notes'             => 'nullable|string',
        ]);

        // check if ei sechedule date e available kina ei time golor jonno

        $existingBookings = ServiceBookingTime::where('scheduled_date', $request->scheduled_date)
            ->whereIn('scheduled_time', $request->scheduled_times)
            ->get();

        if ($existingBookings->count() > 0) {

            $times = $existingBookings->pluck('scheduled_time')->toArray();

            $formatttedTimes = array_map(function ($time) {
                return date('h:i A', strtotime($time));
            }, $times);

            return $this->error($formatttedTimes, 'One or more selected time slots are already booked on ' . $request->scheduled_date . '. Please choose different time slots.', 400);

        }

        try {

            DB::beginTransaction();
            $owner = User::find($request->owner_id);

            $user = $request->user();

            if (! $user || ! $owner) {
                return $this->error(null, 'User not found.', 404);
            }

            $booking = Booking::create([
                'owner_id' => $owner->id,
                'user_id'  => $user->id,
                'date'     => date('Y-m-d'),
                'status'   => 'pending',
                'points'   => 5,
                'notes'    => $request->notes ?? '',
            ]);

            // if(count($request->service_ids) != count($request->scheduled_times)){
            //     return $this->error(null, 'Service and Scheduled times mismatch. Please select the same number of services and scheduled times.', 400);
            // }

            $bookingCollection = collect($request->service_ids)->map(function ($serviceId) use ($request, $booking) {
                return [
                    'booking_id'     => $booking->id,
                    'service_id'     => $serviceId,
                    'scheduled_date' => $request->scheduled_date,
                ];
            })->toArray();

            ServiceBooking::insert($bookingCollection);

            $serviceBookingTimes = collect($request->service_ids)->map(function ($serviceId, $index) use ($request, $booking) {
                return [
                    'booking_id'     => $booking->id,
                    'scheduled_time' => $request->scheduled_times[$index],
                    'scheduled_date' => $request->scheduled_date,
                ];
            })->toArray();

            DB::table('service_booking_times')->insert($serviceBookingTimes);

            DB::commit();

            $data =
                [
                'id'         => $booking->id,
                'owner_id'   => $booking->owner_id,
                'user_id'    => $booking->user_id,
                'date'       => $booking->date,
                'status'     => $booking->status,
                'points'     => $booking->points,
                'notes'      => $booking->notes,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,

                'services'   => $booking->serviceBookings()->with('service')->get(),
                'times'      => DB::table('service_booking_times')->where('booking_id', $booking->id)->get(),

                'owner'      => [
                    'id'                => $owner->id,
                    'professional_name' => $owner->professional_name,
                    'avatar'            => $owner->avatar,
                    'thumb'             => $owner->thumb,
                ],

            ];

            return $this->success($data, 'Services booked successfully.', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to book services. ' . $e->getMessage(), 500);
        }
    }

    public function getClientBookings(Request $request)
    {
        try {
            $user = $request->user();
            $type = $request->type;
            if ($user->role != 'client') {
                return $this->error(null, 'Unauthorized access.', 403);
            }
            if (! $user) {
                return $this->error(null, 'User not found.', 404);
            }
            $today = date('Y-m-d');

            if ($type == 'upcoming') {
                $bookings = Booking::where('user_id', $user->id)
                    ->where(function ($query) {
                        $query->where('status', 'pending')
                            ->orWhere('status', 'confirmed');
                    })
                    ->whereHas('serviceBookings', function ($query) use ($today) {
                        $query->where('scheduled_date', '>=', $today);
                    })
                    ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type == 'completed') {
                $bookings = Booking::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type == 'cancelled') {
                $bookings = Booking::where('user_id', $user->id)
                    ->where('status', 'cancelled')
                    ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } else {
                $bookings = Booking::where('user_id', $user->id)
                    ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            }

            return $this->success($bookings, 'User bookings retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to retrieve bookings. ' . $e->getMessage(), 500);
        }
    }

    public function getProfessionalBookings(Request $request)
    {
        try {
            $professional = $request->user();
            $type         = $request->type;
            // if($user->role != 'professional'){
            //     return $this->error(null, 'Unauthorized access.', 403);
            // }

            if (! $professional) {
                return $this->error(null, 'Professional not found.', 404);
            }
            $today = date('Y-m-d');

            if ($type === 'upcoming') {
                $bookings = Booking::where('owner_id', $professional->id)
                    ->where(function ($query) {
                        $query->where('status', 'pending')
                            ->orWhere('status', 'confirmed');
                    })
                    ->whereHas('serviceBookings', function ($query) use ($today) {
                        $query->where('scheduled_date', '>=', $today);
                    })
                    // ->with(['serviceBookings.service:id,name', 'user:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type === 'completed') {
                $bookings = Booking::where('owner_id', $professional->id)
                    ->where('status', 'completed')
                    // ->with(['serviceBookings.service:id,name', 'user:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type === 'cancelled') {
                $bookings = Booking::where('owner_id', $professional->id)
                    ->where('status', 'cancelled')
                    // ->with(['serviceBookings.service:id,name', 'user:id,first_name,last_name,avatar'])
                    ->get();
            } else {
                $bookings = Booking::where('owner_id', $professional->id)
                    // ->with(['serviceBookings.service:id,name', 'user:id,first_name,last_name,avatar'])
                    ->get();
            }

            $data = $bookings->map(function ($booking) {


                return [
                    'id'         => $booking->id,
                    'owner_id'   => $booking->owner_id,
                    'user_id'    => $booking->user_id,
                    'date'       => $booking->date,
                    'status'     => $booking->status,
                    'points'     => $booking->points,
                    'notes'      => $booking->notes,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,

                    'services'   => $booking->serviceBookings()->with('service')->get(),

                    'times'      => DB::table('service_booking_times')->where('booking_id', $booking->id)->get() ?? null,

                    'user'       => [
                        'id'         => $booking->user->id,
                        'first_name' => $booking->user->first_name,
                        'last_name'  => $booking->user->last_name,
                        'avatar'     => $booking->user->avatar,
                        'thumb'      => $booking->user->thumb,
                    ],

                ];
            });









            return $this->success($data, 'Professional bookings retrieved successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to retrieve bookings. ' . $e->getMessage(), 500);
        }
    }

    public function approveBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        try {
            $booking = Booking::find($request->booking_id);
            if (! $booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'confirmed';
            $booking->save();

            return $this->success($booking->load('serviceBookings.service'), 'Booking status updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to update booking status. ' . $e->getMessage(), 500);
        }
    }

    public function cancelBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'reason'     => 'nullable|string',
        ]);

        try {
            $booking = Booking::find($request->booking_id);
            if (! $booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'cancelled';
            $booking->notes  = 'Reason: ' . $request->reason;
            $booking->save();

            return $this->success($booking->load('serviceBookings.service'), 'Booking cancelled successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to cancel booking. ' . $e->getMessage(), 500);
        }
    }

    public function completeBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        try {
            $booking = Booking::find($request->booking_id);
            if (! $booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = 'completed';
            $booking->save();

            return $this->success($booking->load('serviceBookings.service'), 'Booking marked as completed successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to complete booking. ' . $e->getMessage(), 500);
        }
    }

    public function updateBookingStatus(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'status'     => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        try {
            $booking = Booking::find($request->booking_id);
            if (! $booking) {
                return $this->error(null, 'Booking not found.', 404);
            }

            $booking->status = $request->status;
            $booking->save();

            return $this->success($booking->load('serviceBookings.service'), 'Booking status updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to update booking status. ' . $e->getMessage(), 500);
        }
    }

    public function submitReview(Request $request)
    {
        $request->validate([
            'booking_id'      => 'required|exists:bookings,id',
            'professional_id' => 'required|exists:users,id',
            'rating'          => 'required|integer|min:1|max:5',
            'comment'         => 'nullable|string',
        ]);

        $client = $request->user();
        if (! $client || $client->role != 'client') {
            return $this->error(null, 'Unauthorized action.', 403);
        }

        try {
            $review = ServiceReview::create([
                'booking_id'      => $request->booking_id,
                'client_id'       => $client->id,
                'professional_id' => $request->professional_id,
                'rating'          => $request->rating,
                'comment'         => $request->comment,
            ]);

            return $this->success($review, 'Review submitted successfully.', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error(null, 'Failed to submit review. ' . $e->getMessage(), 500);
        }
    }

}
