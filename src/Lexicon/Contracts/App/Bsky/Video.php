<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\App\Bsky;

interface Video
{
    public const getJobStatus = 'app.bsky.video.getJobStatus';
    public const getUploadLimits = 'app.bsky.video.getUploadLimits';
    public const uploadVideo = 'app.bsky.video.uploadVideo';

    /**
     * Get status details for a video processing job.
     *
     * method: get
     */
    public function getJobStatus(string $jobId);

    /**
     * Get video upload limits for the authenticated user.
     *
     * method: get
     */
    public function getUploadLimits();

    /**
     * Upload a video to be processed then stored on the PDS.
     *
     * method: post
     */
    public function uploadVideo();
}
