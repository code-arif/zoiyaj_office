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
            $bookings = \App\Models\Booking::with(['user','serviceBookings', 'serviceBookings.service'])->orderBy('created_at', 'DESC')->get();
            return DataTables::of($bookings)
                ->addIndexColumn()
                ->addColumn('service', function($booking){
                    return $booking->service->name ?? 'N/A';
                })
                ->addColumn('client', function($booking){
                    return $booking->user->first_name . ' ' . $booking->user->last_name ?? 'N/A';
                })
                ->addColumn('professional', function($booking){
                    return $booking->owner->first_name . ' ' . $booking->owner->last_name ?? 'N/A';
                })
                ->addColumn('date', function($booking){
                    return $booking->date;
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

                    return '<a href="#" class="btn btn-primary btn-sm" onclick="showDetails('.$booking->id.')"><i class="fa fa-eye"></i></a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    }

    public function show($id)
    {
        $booking = \App\Models\Booking::with(['user', 'owner', 'serviceBookings','serviceBookings.service'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $booking
        ]);
    }
}
