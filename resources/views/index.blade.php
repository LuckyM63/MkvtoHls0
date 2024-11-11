<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video to HLS Converter</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f7f7f7; margin-top: 20px; }
        
        /* Style for the "Convert to HLS" button */
        .convert-btn {
            background-color: #28a745 !important; /* Green color */
            border-color: #28a745 !important;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Video Files</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Video Name</th>
                <th>Action</th>
                <th>Link</th>
            </tr>
        </thead>
        <tbody>
            @foreach($videos as $video)
                @php
                    $isCompressed = str_starts_with($video->getFilename(), 'new');
                @endphp
                <tr>
                    <td>{{ $video->getFilename() }}</td>
                    <td>
                        <button class="btn {{ $isCompressed ? 'convert-btn' : 'btn-primary compress-btn' }}" data-file="{{ $video->getFilename() }}">
                            {{ $isCompressed ? 'Convert to HLS' : 'Process Your Video' }}
                        </button>
                    </td>
                    <td>
                        <span class="link-display" id="link-{{ $video->getFilename() }}"></span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.compress-btn').on('click', function() {
            var fileName = $(this).data('file');
            $.post('{{ route('compress') }}', {
                _token: '{{ csrf_token() }}',
                video_file: fileName
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Compression failed');
                }
            });
        });

        $('.convert-btn').on('click', function() {
            var fileName = $(this).data('file');
            var linkDisplay = $('#link-' + fileName);
            $.post('{{ route('convert') }}', {
                _token: '{{ csrf_token() }}',
                video_file: fileName
            }, function(response) {
                if (response.success) {
                    linkDisplay.text(response.m3u8_url);
                } else {
                    alert('Conversion failed');
                }
            });
        });
    });
    </script>
</div>
</body>
</html>
