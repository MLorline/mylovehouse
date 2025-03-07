<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HouseController extends Controller
{
    public function index(Request $request)
    {
        $query = House::query();
        
        // Filtrage
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        if ($request->has('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }
        
        // Plus de filtres selon les besoins...
        
        $houses = $query->paginate(10);
        
        return response()->json($houses);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
            'price' => 'required|numeric',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'amenities' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        // images présentes
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('houses', 'public');
                $imagePaths[] = $path;
            }
        }
        
        $house = House::create([
            'user_id' => $request->user()->id,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'address' => $validatedData['address'],
            'price' => $validatedData['price'],
            'bedrooms' => $validatedData['bedrooms'],
            'bathrooms' => $validatedData['bathrooms'],
            'amenities' => json_encode($validatedData['amenities'] ?? []),
            'images' => json_encode($imagePaths),
        ]);
        
        return response()->json($house, 201);
    }
    
    // show, update, delete
}
