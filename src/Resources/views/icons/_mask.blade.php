@if ($vector ?? true)
    <span
        class="inline-block shrink-0 {{ $class }}"
        style="background-color: currentColor; mask: url('{{ $url }}') center / contain no-repeat; -webkit-mask: url('{{ $url }}') center / contain no-repeat;"
        aria-hidden="true"
    ></span>
@else
    <img
        src="{{ $url }}"
        class="inline-block shrink-0 object-contain {{ $class }}"
        alt=""
        aria-hidden="true"
        loading="lazy"
    >
@endif
