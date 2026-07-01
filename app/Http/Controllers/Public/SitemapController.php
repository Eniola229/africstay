<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

/**
 * Dynamic XML sitemap — static marketing pages + the hotel directory +
 * every active, bookable hotel's public page. Cached for an hour so we're
 * not hitting the DB on every crawler request.
 */
class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = [];

            // Static marketing pages
            $urls[] = [
                'loc' => route('home'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ];
            $urls[] = [
                'loc' => route('public.hotels.index'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => route('legal.terms'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ];
            $urls[] = [
                'loc' => route('legal.privacy'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ];
            $urls[] = [
                'loc' => route('register'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
            $urls[] = [
                'loc' => route('login'),
                'changefreq' => 'monthly',
                'priority' => '0.2',
            ];

            // Every active, publicly bookable hotel
            Hotel::where('is_active', true)
                ->where('online_booking_enabled', true)
                ->select(['slug', 'updated_at'])
                ->chunk(200, function ($hotels) use (&$urls) {
                    foreach ($hotels as $hotel) {
                        $urls[] = [
                            'loc' => route('public.hotel.show', $hotel->slug),
                            'lastmod' => $hotel->updated_at->toAtomString(),
                            'changefreq' => 'daily',
                            'priority' => '0.8',
                        ];
                    }
                });

            return view('sitemap.index', ['urls' => $urls])->render();
        });

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}