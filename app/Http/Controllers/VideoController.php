<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VideoController extends Controller
{
    public function index()
    {
        $videos = File::files(storage_path('app/videos'));
        return view('index', compact('videos'));
    }

    // public function compress(Request $request)
    // {
    //     $request->validate(['video_file' => 'required|string']);

    //     $fileName = $request->input('video_file');
    //     $filePath = storage_path('app/videos/' . $fileName);
    //     $tempCompressedFileName = 'temp_' . $fileName;
    //     $finalCompressedFileName = 'compressed_' . $fileName;
    //     $tempCompressedFilePath = storage_path('app/videos/' . $tempCompressedFileName);
    //     $finalCompressedFilePath = storage_path('app/videos/' . $finalCompressedFileName);

    //     $ffmpegPath = '/opt/homebrew/bin/ffmpeg'; // Adjust this path as needed

    //     $process = new Process([
    //         $ffmpegPath, '-i', $filePath,
    //         '-vcodec', 'libx265', '-crf', '28',
    //         '-preset', 'ultrafast', '-threads', '0',
    //         $tempCompressedFilePath
    //     ]);
    //     $process->setTimeout(null);
    //     $process->run();

    //     if (!$process->isSuccessful()) {
    //         throw new ProcessFailedException($process);
    //     }

    //     rename($tempCompressedFilePath, $finalCompressedFilePath);

    //     return response()->json(['success' => true, 'compressed_file' => $finalCompressedFileName]);
    // }

    public function compress(Request $request)
{
    $request->validate(['video_file' => 'required|string']);

    $fileName = $request->input('video_file');
    $filePath = storage_path('app/videos/' . $fileName);
    $tempCompressedFileName = 'temp_' . $fileName;
    $finalCompressedFileName = 'new_' . $fileName;  // Changed to 'new_' as required
    $tempCompressedFilePath = storage_path('app/videos/' . $tempCompressedFileName);
    $finalCompressedFilePath = storage_path('app/videos/' . $finalCompressedFileName);

    $ffmpegPath = '/opt/homebrew/bin/ffmpeg'; // Adjust this path as needed

    // Start the compression process
    $process = new Process([
        $ffmpegPath, '-i', $filePath,
        '-vcodec', 'libx265', '-crf', '28',
        '-preset', 'ultrafast', '-threads', '0',
        $tempCompressedFilePath
    ]);
    $process->setTimeout(null);
    $process->run();

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }

    // Rename temp compressed file to the final file name
    rename($tempCompressedFilePath, $finalCompressedFilePath);

    return response()->json(['success' => true, 'compressed_file' => $finalCompressedFileName]);
}

    public function convert(Request $request)
    {
        $request->validate(['video_file' => 'required|string']);

        $fileName = $request->input('video_file');
        $filePath = storage_path('app/videos/' . $fileName);

        $ffmpegPath = '/opt/homebrew/bin/ffmpeg'; // Adjust this path as needed
        $outputDir = storage_path('app/public/hls');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $resolutions = [
            '360p' => ['width' => 640, 'height' => 360, 'bitrate' => '500k', 'crf' => 28],
            '480p' => ['width' => 854, 'height' => 480, 'bitrate' => '800k', 'crf' => 26],
            '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => '1200k', 'crf' => 24],
            '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '2000k', 'crf' => 22],
        ];

        $processes = [];
        $masterPlaylist = "#EXTM3U\n#EXT-X-VERSION:3\n";

        foreach ($resolutions as $key => $config) {
            $resolutionFileName = "{$fileName}_{$key}.m3u8";
            $segmentFilePattern = "{$outputDir}/{$fileName}_{$key}_%03d.ts";

            $ffmpegCmd = [
                $ffmpegPath, '-i', $filePath,
                '-c:v', 'libx265', '-crf', $config['crf'],
                '-preset', 'ultrafast', '-threads', '0',
                '-b:v', $config['bitrate'], '-b:a', '64k',
                '-ac', '2', '-vf', "scale={$config['width']}:{$config['height']}",
                '-hls_time', '10', '-hls_list_size', '0',
                '-hls_flags', 'independent_segments',
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $segmentFilePattern,
                "{$outputDir}/{$resolutionFileName}",
            ];

            $process = new Process($ffmpegCmd);
            $process->setTimeout(null);
            $process->start();
            $processes[] = $process;

            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH=" . (int)$config['bitrate'] * 1000 . ",RESOLUTION={$config['width']}x{$config['height']}\n";
            $masterPlaylist .= $resolutionFileName . "\n";
        }

        foreach ($processes as $process) {
            $process->wait();
        }

        $masterPlaylistFile = "{$outputDir}/{$fileName}_master.m3u8";
        file_put_contents($masterPlaylistFile, $masterPlaylist);

        $masterM3u8Url = asset('storage/hls/' . $fileName . '_master.m3u8');

        return response()->json([
            'success' => true,
            'm3u8_url' => $masterM3u8Url
        ]);
    }
}
