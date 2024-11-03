<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum Bsky: string
{
    /**
     * Get private preferences attached to the current account. Expected use is synchronization between multiple devices, and import/export during account migration. Requires auth.
     */
    case getPreferences = 'app.bsky.actor.getPreferences';

    /**
     * Get detailed profile view of an actor. Does not require auth, but contains relevant metadata with auth.
     */
    case getProfile = 'app.bsky.actor.getProfile';

    /**
     * Get detailed profile views of multiple actors.
     */
    case getProfiles = 'app.bsky.actor.getProfiles';

    /**
     * Get a list of suggested actors. Expected use is discovery of accounts to follow during new account onboarding.
     */
    case getSuggestions = 'app.bsky.actor.getSuggestions';

    /**
     * Set notification-related preferences for an account. Requires auth.
     */
    case putPreferences = 'app.bsky.notification.putPreferences';

    /**
     * Find actors (profiles) matching search criteria. Does not require auth.
     */
    case searchActors = 'app.bsky.actor.searchActors';

    /**
     * Find actor suggestions for a prefix search term. Expected use is for auto-completion during text field entry. Does not require auth.
     */
    case searchActorsTypeahead = 'app.bsky.actor.searchActorsTypeahead';

    /**
     * Get information about a feed generator, including policies and offered feed URIs. Does not require auth; implemented by Feed Generator services (not App View).
     */
    case describeFeedGenerator = 'app.bsky.feed.describeFeedGenerator';

    /**
     * Get a list of feeds (feed generator records) created by the actor (in the actor's repo).
     */
    case getActorFeeds = 'app.bsky.feed.getActorFeeds';

    /**
     * Get a list of posts liked by an actor. Requires auth, actor must be the requesting account.
     */
    case getActorLikes = 'app.bsky.feed.getActorLikes';

    /**
     * Get a view of an actor's 'author feed' (post and reposts by the author). Does not require auth.
     */
    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';

    /**
     * Get a hydrated feed from an actor's selected feed generator. Implemented by App View.
     */
    case getFeed = 'app.bsky.feed.getFeed';

    /**
     * Get information about a feed generator. Implemented by AppView.
     */
    case getFeedGenerator = 'app.bsky.feed.getFeedGenerator';

    /**
     * Get information about a list of feed generators.
     */
    case getFeedGenerators = 'app.bsky.feed.getFeedGenerators';

    /**
     * Get a skeleton of a feed provided by a feed generator. Auth is optional, depending on provider requirements, and provides the DID of the requester. Implemented by Feed Generator Service.
     */
    case getFeedSkeleton = 'app.bsky.feed.getFeedSkeleton';

    /**
     * Get like records which reference a subject (by AT-URI and CID).
     */
    case getLikes = 'app.bsky.feed.getLikes';

    /**
     * Get a feed of recent posts from a list (posts and reposts from any actors on the list). Does not require auth.
     */
    case getListFeed = 'app.bsky.feed.getListFeed';

    /**
     * Get posts in a thread. Does not require auth, but additional metadata and filtering will be applied for authed requests.
     */
    case getPostThread = 'app.bsky.feed.getPostThread';

    /**
     * Gets post views for a specified list of posts (by AT-URI). This is sometimes referred to as 'hydrating' a 'feed skeleton'.
     */
    case getPosts = 'app.bsky.feed.getPosts';

    /**
     * Get a list of quotes for a given post.
     */
    case getQuotes = 'app.bsky.feed.getQuotes';

    /**
     * Get a list of reposts for a given post.
     */
    case getRepostedBy = 'app.bsky.feed.getRepostedBy';

    /**
     * Get a list of suggested feeds (feed generators) for the requesting account.
     */
    case getSuggestedFeeds = 'app.bsky.feed.getSuggestedFeeds';

    /**
     * Get a view of the requesting account's home timeline. This is expected to be some form of reverse-chronological feed.
     */
    case getTimeline = 'app.bsky.feed.getTimeline';

    /**
     * Find posts matching search criteria, returning views of those posts.
     */
    case searchPosts = 'app.bsky.feed.searchPosts';

    /**
     * Send information about interactions with feed items back to the feed generator that served them.
     */
    case sendInteractions = 'app.bsky.feed.sendInteractions';

    /**
     * Get a list of starter packs created by the actor.
     */
    case getActorStarterPacks = 'app.bsky.graph.getActorStarterPacks';

    /**
     * Enumerates which accounts the requesting account is currently blocking. Requires auth.
     */
    case getBlocks = 'app.bsky.graph.getBlocks';

    /**
     * Enumerates accounts which follow a specified account (actor).
     */
    case getFollowers = 'app.bsky.graph.getFollowers';

    /**
     * Enumerates accounts which a specified account (actor) follows.
     */
    case getFollows = 'app.bsky.graph.getFollows';

    /**
     * Enumerates accounts which follow a specified account (actor) and are followed by the viewer.
     */
    case getKnownFollowers = 'app.bsky.graph.getKnownFollowers';

    /**
     * Gets a 'view' (with additional context) of a specified list.
     */
    case getList = 'app.bsky.graph.getList';

    /**
     * Get mod lists that the requesting account (actor) is blocking. Requires auth.
     */
    case getListBlocks = 'app.bsky.graph.getListBlocks';

    /**
     * Enumerates mod lists that the requesting account (actor) currently has muted. Requires auth.
     */
    case getListMutes = 'app.bsky.graph.getListMutes';

    /**
     * Enumerates the lists created by a specified account (actor).
     */
    case getLists = 'app.bsky.graph.getLists';

    /**
     * Enumerates accounts that the requesting account (actor) currently has muted. Requires auth.
     */
    case getMutes = 'app.bsky.graph.getMutes';

    /**
     * Enumerates public relationships between one account, and a list of other accounts. Does not require auth.
     */
    case getRelationships = 'app.bsky.graph.getRelationships';

    /**
     * Gets a view of a starter pack.
     */
    case getStarterPack = 'app.bsky.graph.getStarterPack';

    /**
     * Get views for a list of starter packs.
     */
    case getStarterPacks = 'app.bsky.graph.getStarterPacks';

    /**
     * Enumerates follows similar to a given account (actor). Expected use is to recommend additional accounts immediately after following one account.
     */
    case getSuggestedFollowsByActor = 'app.bsky.graph.getSuggestedFollowsByActor';

    /**
     * Creates a mute relationship for the specified account. Mutes are private in Bluesky. Requires auth.
     */
    case muteActor = 'app.bsky.graph.muteActor';

    /**
     * Creates a mute relationship for the specified list of accounts. Mutes are private in Bluesky. Requires auth.
     */
    case muteActorList = 'app.bsky.graph.muteActorList';

    /**
     * Mutes a thread preventing notifications from the thread and any of its children. Mutes are private in Bluesky. Requires auth.
     */
    case muteThread = 'app.bsky.graph.muteThread';

    /**
     * Unmutes the specified account. Requires auth.
     */
    case unmuteActor = 'app.bsky.graph.unmuteActor';

    /**
     * Unmutes the specified list of accounts. Requires auth.
     */
    case unmuteActorList = 'app.bsky.graph.unmuteActorList';

    /**
     * Unmutes the specified thread. Requires auth.
     */
    case unmuteThread = 'app.bsky.graph.unmuteThread';

    /**
     * Get information about a list of labeler services.
     */
    case getServices = 'app.bsky.labeler.getServices';

    /**
     * Count the number of unread notifications for the requesting account. Requires auth.
     */
    case getUnreadCount = 'app.bsky.notification.getUnreadCount';

    /**
     * Enumerate notifications for the requesting account. Requires auth.
     */
    case listNotifications = 'app.bsky.notification.listNotifications';

    /**
     * Register to receive push notifications, via a specified service, for the requesting account. Requires auth.
     */
    case registerPush = 'app.bsky.notification.registerPush';

    /**
     * Notify server that the requesting account has seen notifications. Requires auth.
     */
    case updateSeen = 'app.bsky.notification.updateSeen';

    /**
     * Get miscellaneous runtime configuration.
     */
    case getConfig = 'app.bsky.unspecced.getConfig';

    /**
     * An unspecced view of globally popular feed generators.
     */
    case getPopularFeedGenerators = 'app.bsky.unspecced.getPopularFeedGenerators';

    /**
     * Get a skeleton of suggested actors. Intended to be called and then hydrated through app.bsky.actor.getSuggestions
     */
    case getSuggestionsSkeleton = 'app.bsky.unspecced.getSuggestionsSkeleton';

    /**
     * Get a list of suggestions (feeds and users) tagged with categories
     */
    case getTaggedSuggestions = 'app.bsky.unspecced.getTaggedSuggestions';

    /**
     * Backend Actors (profile) search, returns only skeleton.
     */
    case searchActorsSkeleton = 'app.bsky.unspecced.searchActorsSkeleton';

    /**
     * Backend Posts search, returns only skeleton
     */
    case searchPostsSkeleton = 'app.bsky.unspecced.searchPostsSkeleton';

    /**
     * Get status details for a video processing job.
     */
    case getJobStatus = 'app.bsky.video.getJobStatus';

    /**
     * Get video upload limits for the authenticated user.
     */
    case getUploadLimits = 'app.bsky.video.getUploadLimits';

    /**
     * Upload a video to be processed then stored on the PDS.
     */
    case uploadVideo = 'app.bsky.video.uploadVideo';
}
