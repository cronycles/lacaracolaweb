@foreach ($blocks as $block)
    @php
        $textStyle = ($block['bold'] ?? false ? 'font-weight:bold;' : '')
            . ($block['italic'] ?? false ? 'font-style:italic;' : '')
            . ($block['underline'] ?? false ? 'text-decoration:underline;' : '');
    @endphp
    @switch($block['type'])
        @case('heading')
            @if (($block['level'] ?? 2) === 1)
                <h1 style="{{ $textStyle }}">{{ $block['text'] }}</h1>
            @elseif (($block['level'] ?? 2) === 3)
                <h3 style="color:#30596C;{{ $textStyle }}">{{ $block['text'] }}</h3>
            @else
                <h2 style="color:#30596C;font-size:18px;{{ $textStyle }}">{{ $block['text'] }}</h2>
            @endif
            @break
        @case('paragraph')
            <p style="{{ $textStyle }}">{{ $block['text'] }}</p>
            @break
        @case('list')
            <ul>
                @foreach ($block['items'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @break
        @case('separator')
            <hr style="border:0;border-top:1px solid #dde3e6;margin:20px 0">
            @break
        @case('image')
            <p style="margin:18px 0;text-align:center"><img src="{{ asset('storage/'.$block['path']) }}" alt="{{ $block['alt'] ?? '' }}" style="display:block;max-width:100%;height:auto;margin:0 auto"></p>
            @break
        @case('button')
            <p><a class="btn" href="{{ $block['url'] }}">{{ $block['text'] }}</a></p>
            @break
    @endswitch
@endforeach