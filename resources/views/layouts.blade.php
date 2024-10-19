<div x-data="layouts(
    `{{ $addRoute }}`,
    `{{ $column }}`
)"
    {{ $attributes }}
    data-top-level="true"
>
    <div class="_layouts-blocks">
        @foreach($fields as $layout)
            {!! $layout !!}
        @endforeach
    </div>

    <div>
        {!! $dropdown !!}
    </div>

    <br />
</div>
