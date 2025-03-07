<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\House;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'house_id' => 'required|exists:houses,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
        ]);
        
        $house = House::findOrFail($validatedData['house_id']);
        
        // Vérifier disponibilité
        $isAvailable = Reservation::where('house_id', $house->id)
            ->where(function($query) use ($validatedData) {
                $query->whereBetween('check_in', [$validatedData['check_in'], $validatedData['check_out']])
                    ->orWhereBetween('check_out', [$validatedData['check_in'], $validatedData['check_out']]);
            })
            ->where('status', 'confirmed')
            ->doesntExist();
            
        if (!$isAvailable) {
            return response()->json([
                'message' => 'La maison n\'est pas disponible pour ces dates'
            ], 400);
        }
        
        // Calculer le prix total
        $checkIn = Carbon::parse($validatedData['check_in']);
        $checkOut = Carbon::parse($validatedData['check_out']);
        $days = $checkIn->diffInDays($checkOut);
        $totalPrice = $house->price * $days;
        
        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'house_id' => $house->id,
            'check_in' => $validatedData['check_in'],
            'check_out' => $validatedData['check_out'],
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);
        
        return response()->json($reservation, 201);
    }
    
    // show, update, delete
}
