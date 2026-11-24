@php($rating = (float) ($product->visible_reviews_avg_rating ?? 0))
<div class="mt-1 flex items-center gap-1.5 text-xs" aria-label="{{ $product->visible_reviews_count }} ulasan, rating {{ number_format($rating, 1) }} dari 5">
    <span class="font-semibold text-amber-500" aria-hidden="true">
        @for ($star = 1; $star <= 5; $star++)
            {{ $star <= round($rating) ? '★' : '☆' }}
        @endfor
    </span>
    <span class="text-slate-500">{{ $product->visible_reviews_count }} ulasan</span>
</div>
