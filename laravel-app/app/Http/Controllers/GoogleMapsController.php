<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleMapsController extends Controller
{
    /**
     * Geocode: address → coordinates
     * GET /api/maps/geocode?address=...
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $request->query('address'),
            'key' => config('services.google_maps.api_key'),
        ]);

        return response()->json($response->json());
    }

    /**
     * Reverse geocode: coordinates → address
     * GET /api/maps/reverse-geocode?lat=...&lng=...
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => $request->query('lat') . ',' . $request->query('lng'),
            'key' => config('services.google_maps.api_key'),
        ]);

        return response()->json($response->json());
    }

    /**
     * Places autocomplete
     * GET /api/maps/places/autocomplete?input=...
     */
    public function placesAutocomplete(Request $request)
    {
        $request->validate([
            'input' => 'required|string|max:500',
        ]);

        $params = [
            'input' => $request->query('input'),
            'key' => config('services.google_maps.api_key'),
        ];

        // Optional: filter by country
        if ($request->has('country')) {
            $params['components'] = 'country:' . $request->query('country');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);

        return response()->json($response->json());
    }

    /**
     * Place details
     * GET /api/maps/places/details?place_id=...
     */
    public function placeDetails(Request $request)
    {
        $request->validate([
            'place_id' => 'required|string',
        ]);

        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $request->query('place_id'),
            'fields' => $request->query('fields', 'formatted_address,geometry,name'),
            'key' => config('services.google_maps.api_key'),
        ]);

        return response()->json($response->json());
    }
}
