<?php

namespace App\Jobs;

use App\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateAttachmentThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Attachment $attachment;

    /**
     * Create a new job instance.
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $att = $this->attachment->fresh();

        if (! $att || ! str_starts_with($att->mime ?? '', 'image/')) {
            $att->processing_status = 'skipped';
            $att->save();
            return;
        }

        // Local file path
        $localPath = storage_path('app/public/' . $att->path);
        if (! file_exists($localPath)) {
            $att->processing_status = 'missing';
            $att->save();
            return;
        }

        // Lazily use Intervention Image if available
        if (! class_exists('\Intervention\Image\ImageManagerStatic')) {
            $att->processing_status = 'no_image_lib';
            $att->save();
            return;
        }

        $img = \Intervention\Image\ImageManagerStatic::make($localPath)->orientate();

        $dir = dirname($att->path);
        $ext = pathinfo($att->stored_name, PATHINFO_EXTENSION);
        $thumb320 = $dir . '/' . Str::before($att->stored_name, '.') . '_320.' . $ext;
        $thumb64 = $dir . '/' . Str::before($att->stored_name, '.') . '_64.' . $ext;

        // create 320x320
        $tmp320 = (string) $img->fit(320, 320)->encode($ext);
        Storage::disk('public')->put($thumb320, $tmp320);

        // create 64x64
        $tmp64 = (string) $img->fit(64, 64)->encode($ext);
        Storage::disk('public')->put($thumb64, $tmp64);

        $att->thumbnail_path = $thumb320;
        $att->processing_status = 'completed';
        $att->save();
    }
}
