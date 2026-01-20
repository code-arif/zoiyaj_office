<?php

namespace App\Http\Controllers\Web\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class BookingController extends Controller
{
    public function index()
    {
        return view('backend.layouts.bookings.index');
    }

    public function getData(Request $request)
    {
        if($request->ajax()){
            $bookings = \App\Models\ServiceBooking::with(['user', 'service.professional'])->orderBy('created_at', 'DESC')->get();
            return DataTables::of($bookings)
                ->addIndexColumn()
                ->addColumn('service', function($booking){
                    return $booking->service->name ?? 'N/A';
                })
                ->addColumn('client', function($booking){
                    return $booking->user->first_name . ' ' . $booking->user->last_name ?? 'N/A';
                })
                ->addColumn('professional', function($booking){
                    return $booking->service->professional->first_name . ' ' . $booking->service->professional->last_name ?? 'N/A';
                })
                ->addColumn('schedule', function($booking){
                    return $booking->scheduled_date . ' ' . $booking->scheduled_time;
                })
                ->addColumn('status', function($booking){
                    $btn ='';

                    if($booking->status == 'pending'){
                        $btn = '<span class="badge bg-warning">Pending</span>';
                    }
                    if($booking->status == 'confirmed'){
                        $btn = '<span class="badge bg-info">Confirmed</span>';
                    }
                    if($booking->status == 'completed'){
                        $btn = '<span class="badge bg-success">Completed</span>';
                    }
                    if($booking->status == 'cancelled'){
                        $btn = '<span class="badge bg-danger">Cancelled</span>';
                    }

                    return $btn;
                })  
                ->addColumn('action', function($booking){
                    return '<a href="#" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    }
}
