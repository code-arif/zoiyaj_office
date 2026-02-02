<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CheckInBooking;
use App\Models\PointTransaction;
use App\Models\ProfessinalService;
use App\Models\RedeemTier;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingTime;
use App\Models\ServiceReview;
use App\Models\User;
use App\Services\CoinService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nette\Utils\Random;

use function Symfony\Component\Clock\now;

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
                'owner_id'       => $owner->id,
                'booking_number' => rand(1000000000, 999999999999),

                'user_id'        => $user->id,
                'date'           => date('Y-m-d'),
                'status'         => 'pending',
                // 'points'         => 5,
                'notes'          => $request->notes ?? '',
            ]);

            // if(count($request->service_ids) != count($request->scheduled_times)){
            //     return $this->error(null, 'Service and Scheduled times mismatch. Please select the same number of services and scheduled times.', 400);
            // }

            $bookingCollection = collect($request->service_ids)->map(function ($serviceId) use ($request, $booking) {

                $service = ProfessinalService::find($serviceId);

                return [
                    'booking_id'     => $booking->id,
                    'service_id'     => $serviceId,
                    'category_id'    => $service->category_id,
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
                'id'             => $booking->id,
                'booking_number' => $booking->booking_number,
                'owner_id'       => $booking->owner_id,
                'user_id'        => $booking->user_id,
                'date'           => $booking->date,
                'status'         => $booking->status,
                'points'         => $booking->points,
                'notes'          => $booking->notes,
                'created_at'     => $booking->created_at,
                'updated_at'     => $booking->updated_at,

                'services'       => $booking->serviceBookings()->with('service')->get(),
                'times'          => DB::table('service_booking_times')->where('booking_id', $booking->id)->get(),

                'owner'          => [
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
                // ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type == 'completed') {
                $bookings = Booking::where('user_id', $user->id)
                    ->where('status', 'completed')
                // ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } elseif ($type == 'cancelled') {
                $bookings = Booking::where('user_id', $user->id)
                    ->where('status', 'cancelled')
                // ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            } else {
                $bookings = Booking::where('user_id', $user->id)
                // ->with(['serviceBookings.service:id,name', 'owner:id,first_name,last_name,avatar'])
                    ->get();
            }

            $bookings = $bookings->map(function ($booking) {
                return [
                    'id'         => $booking->id,
                    'booking_id' => $booking->booking_number,
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

                    'owner'      => [
                        'id'             => $booking->owner->id,
                        'first_name'     => $booking->owner->first_name,
                        'last_name'      => $booking->owner->last_name,
                        'avatar'         => $booking->owner->avatar,
                        'thumb'          => $booking->owner->thumb,
                        'location'       => $booking->owner->address . ', ' . $booking->owner->city . ', ' . $booking->owner->country,
                        'total_reviews'  => Random::generate(1, '0-9'),
                        'total_reatings' => Random::generate(1, '0-9'),
                    ],

                ];
            });

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
                        'location'   => $booking->user->location,
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

            if ($booking->status == "confirmed") {
                return $this->success(null, 'Booking Already Confirmed.');
            }

            $points = 5;

            CoinService::rewardUser($booking->user_id, 'client', $booking->id, $points, 'booking_confirm');
            CoinService::rewardUser($booking->owner_id, 'professional', $booking->id, $points, 'booking_confirm');

            $booking->status = 'confirmed';
            $booking->points = $points;
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

    public function checkinBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'redeem_id'  => 'nullable|exists:redeem_tiers,id',
        ]);

        try {

            $booking = Booking::findOrFail($request->booking_id);

            // Prevent duplicate check-in
            $existing = CheckInBooking::where('booking_id', $booking->id)->first();

            if ($existing) {
                return $this->error(null, 'Already checked in for this booking.', 400);
            }

            $redeemId = null;

            if ($request->filled('redeem_id')) {
                $redeemId = $request->redeem_id;
            }

            $checkin = CheckInBooking::create([
                'booking_id'           => $booking->id,
                'client_id'            => auth('api')->id(),
                'redeem_tier_id'       => $redeemId,
                'professional_id'      => $booking->owner_id,
                'client_checked_in_at' => now(),
                'status'               => 'waiting',
            ]);

            return $this->success($checkin,
                'Booking check-in submitted successfully.',
                200
            );

        } catch (\Exception $e) {

            Log::error('Checkin Error: ' . $e->getMessage());

            return $this->error(
                null,
                'Failed to check in booking. Please try again.',
                500
            );
        }
    }

    public function confirmCheckin(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        try {

            DB::beginTransaction();

            $booking      = Booking::with(['user', 'owner'])->findOrFail($request->booking_id);
            $professional = $booking->owner;

            // Authorization: only professional can confirm check-in
            if (auth('api')->id() !== $professional->id) {
                throw new \Exception("Unauthorized");
            }

            // professional confirms check-in
            $checkin = CheckInBooking::where('booking_id', $booking->id)->firstOrFail();

            if ($checkin->status === 'confirmed') {
                return $this->error([], "Check-in already confirmed", 400);
            }

            // Update checkin status to confirmed
            $checkin->status                    = 'confirmed';
            $checkin->professional_confirmed_at = now();
            $checkin->save();

            // Give points to client and professional
            if (! $checkin->points_given_on_checkin_confirmed) {
                $points = 10;

                CoinService::rewardUser($booking->user_id, 'client', $booking->id, $points, 'checkin_confirm');
                CoinService::rewardUser($booking->owner_id, 'professional', $booking->id, $points, 'checkin_confirm');

                $checkin->points_given_on_checkin_confirmed = true;
                $checkin->save();
            }


            if ($checkin->redeem_tier_id) {
                $redeem = RedeemTier::find($checkin->redeem_tier_id);
                if ($redeem) {

                    $client = $booking->user;

                    // Check if client has enough points (assuming 'points' is current balance)
                    if ($client->total_redeem_points < $redeem->points_required) {
                        throw new \Exception("Client does not have enough points for this redeem");
                    }


                    $client->total_redeem_points = $client->total_redeem_points - $redeem->points_required;
                    $client->save();

                    PointTransaction::create([
                        'user_id'    => $booking->user_id,
                        'user_type'  => 'client',
                        'booking_id' => $booking->id,
                        'points'     => -$redeem->points_required,
                        'action'     => 'redeem',
                        'confirm_date' => now()
                    ]);


                }
            }

            DB::commit();

            return $this->success([
                'booking' => $booking->load('serviceBookings.service'),
                'checkin' => $checkin,
            ], 'Check-in confirmed, points updated, redeem processed if pending.', 200);

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Checkin confirmation error: ' . $e->getMessage());
            return $this->error(null, 'Failed to confirm check-in. ' . $e->getMessage(), 500);

        }
    }

}
