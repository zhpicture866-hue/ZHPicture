<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Event;
use App\Support\OgImage;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $events = null;

        // Pengaman: kalau model/tabel belum ada di server, homepage tetap tampil
        // memakai data contoh (tidak error 500).
        if (class_exists(Event::class) && Schema::hasTable('events')) {
            $data = Event::published()
                ->with('category')
                ->orderByRaw('CASE WHEN end_at < ? THEN 1 ELSE 0 END', [now()])
                ->orderBy('start_at')
                ->limit(6)
                ->get();
            $events = $data->isNotEmpty() ? $data : null;
        }

        // PERBAIKAN: hanya artikel yang sudah terbit (sebelumnya draft ikut tampil di beranda)
        $articles = (class_exists(Article::class) && Schema::hasTable((new Article)->getTable()))
            ? Article::published()
                ->latest('published_at')
                ->take(12)
                ->get()
            : collect();
        JsonLd::setType('WebSite')->setTitle('Ruang Kembali')->setUrl(url('/'));
        return view('welcome', ['events' => $events, 'articles' => $articles]);
    }

    public function show(string $slug)
    {
        $event = Event::published()->with(['fotos', 'employee.user', 'province', 'city', 'district', 'subDistrict', 'postalCode'])->where('slug', $slug)->firstOrFail();

        $this->applySeo($event, $slug);

        return view('listing.show', compact('listing'));
    }

        /** Judul, deskripsi, canonical, dan gambar share untuk halaman listing. */
    private function applySeo(Event $event, string $slug): void
    {
        $title = trim((string) $event->judul) ?: 'Properti Dijual';

        $location = collect([
            $event->district?->name,
            $event->city?->name,
            $event->province?->name,
        ])->filter()->implode(', ');

        $rawDesc = (string) $event->deskripsi;
        $desc    = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', $rawDesc))));
        if ($desc === '') {
            $desc = $title . ($location ? " di {$location}" : '') . '. Hubungi Antosa Architect untuk informasi lengkap.';
        }
        $desc = Str::limit($desc, 160);

        $url = route('listing.show', $slug);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($desc);
        SEOMeta::setCanonical($url);

        OpenGraph::setTitle($title)->setDescription($desc)->setUrl($url)
            ->setType('website')->setSiteName('Antosa Architect');
        TwitterCard::setTitle($title)->setDescription($desc);
        JsonLd::setType('WebPage')->setTitle($title)->setDescription($desc)->setUrl($url);

        // Foto utama dulu, baru foto tambahan pertama
        $raw = $event->foto ?: optional($event->fotos->first())->path;

        if ($raw) {
            $path = ltrim(preg_replace('~^/?storage/~', '', $raw), '/');
            $og   = OgImage::ensure($path);

            if ($og) {
                $imgUrl = asset('storage/' . $og);
                OpenGraph::addImage($imgUrl, ['width' => 1200, 'height' => 630]);
                TwitterCard::setImage($imgUrl);
                JsonLd::addImage($imgUrl);
            }
        }
    }
}