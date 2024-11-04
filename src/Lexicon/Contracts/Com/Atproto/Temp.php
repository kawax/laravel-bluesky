<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Com\Atproto;

interface Temp
{
    public const checkSignupQueue = 'com.atproto.temp.checkSignupQueue';
    public const fetchLabels = 'com.atproto.temp.fetchLabels';
    public const requestPhoneVerification = 'com.atproto.temp.requestPhoneVerification';

    /**
     * Check accounts location in signup queue.
     *
     * method: get
     */
    public function checkSignupQueue();

    /**
     * DEPRECATED: use queryLabels or subscribeLabels instead -- Fetch all labels from a labeler created after a certain date.
     *
     * method: get
     */
    public function fetchLabels(?int $since = null, ?int $limit = 50);

    /**
     * Request a verification code to be sent to the supplied phone number
     *
     * method: post
     */
    public function requestPhoneVerification(string $phoneNumber);
}
