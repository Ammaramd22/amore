{{-- AI insights panel --}}
@if(!empty($insights))
<div class="rpt-ai">
    <div class="rpt-ai-head">
        <div class="rpt-ai-badge"><i class="fas fa-wand-magic-sparkles"></i> AI Insights</div>
        <span class="rpt-ai-sub">Auto-analysed from this report’s numbers</span>
    </div>
    <div class="rpt-ai-grid">
        @foreach($insights as $insight)
        <article class="rpt-ai-card tone-{{ $insight['tone'] ?? 'info' }}">
            <h5>{{ $insight['title'] }}</h5>
            <p>{{ $insight['text'] }}</p>
        </article>
        @endforeach
    </div>
</div>
@endif
