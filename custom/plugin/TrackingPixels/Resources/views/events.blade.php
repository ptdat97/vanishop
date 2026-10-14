{{-- Sự kiện của trang; script trong head.blade.php gửi khi khách đã đồng ý cookie. --}}
@foreach ($events as [$name, $data])
<script type="application/json" data-vani-track="{{ $name }}">@json($data)</script>
@endforeach
