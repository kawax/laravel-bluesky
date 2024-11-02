<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon;

enum Bsky: string
{
    case getPreferences = 'app.bsky.actor.getPreferences';
    case getProfile = 'app.bsky.actor.getProfile';
    case getProfiles = 'app.bsky.actor.getProfiles';
    case getSuggestions = 'app.bsky.actor.getSuggestions';
    case putPreferences = 'app.bsky.notification.putPreferences';
    case searchActors = 'app.bsky.actor.searchActors';
    case searchActorsTypeahead = 'app.bsky.actor.searchActorsTypeahead';
    case describeFeedGenerator = 'app.bsky.feed.describeFeedGenerator';
    case getActorFeeds = 'app.bsky.feed.getActorFeeds';
    case getActorLikes = 'app.bsky.feed.getActorLikes';
    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case getFeed = 'app.bsky.feed.getFeed';
    case getFeedGenerator = 'app.bsky.feed.getFeedGenerator';
    case getFeedGenerators = 'app.bsky.feed.getFeedGenerators';
    case getFeedSkeleton = 'app.bsky.feed.getFeedSkeleton';
    case getLikes = 'app.bsky.feed.getLikes';
    case getListFeed = 'app.bsky.feed.getListFeed';
    case getPostThread = 'app.bsky.feed.getPostThread';
    case getPosts = 'app.bsky.feed.getPosts';
    case getQuotes = 'app.bsky.feed.getQuotes';
    case getRepostedBy = 'app.bsky.feed.getRepostedBy';
    case getSuggestedFeeds = 'app.bsky.feed.getSuggestedFeeds';
    case getTimeline = 'app.bsky.feed.getTimeline';
    case searchPosts = 'app.bsky.feed.searchPosts';
    case sendInteractions = 'app.bsky.feed.sendInteractions';
    case getActorStarterPacks = 'app.bsky.graph.getActorStarterPacks';
    case getBlocks = 'app.bsky.graph.getBlocks';
    case getFollowers = 'app.bsky.graph.getFollowers';
    case getFollows = 'app.bsky.graph.getFollows';
    case getKnownFollowers = 'app.bsky.graph.getKnownFollowers';
    case getList = 'app.bsky.graph.getList';
    case getListBlocks = 'app.bsky.graph.getListBlocks';
    case getListMutes = 'app.bsky.graph.getListMutes';
    case getLists = 'app.bsky.graph.getLists';
    case getMutes = 'app.bsky.graph.getMutes';
    case getRelationships = 'app.bsky.graph.getRelationships';
    case getStarterPack = 'app.bsky.graph.getStarterPack';
    case getStarterPacks = 'app.bsky.graph.getStarterPacks';
    case getSuggestedFollowsByActor = 'app.bsky.graph.getSuggestedFollowsByActor';
    case muteActor = 'app.bsky.graph.muteActor';
    case muteActorList = 'app.bsky.graph.muteActorList';
    case muteThread = 'app.bsky.graph.muteThread';
    case unmuteActor = 'app.bsky.graph.unmuteActor';
    case unmuteActorList = 'app.bsky.graph.unmuteActorList';
    case unmuteThread = 'app.bsky.graph.unmuteThread';
    case getServices = 'app.bsky.labeler.getServices';
    case getUnreadCount = 'app.bsky.notification.getUnreadCount';
    case listNotifications = 'app.bsky.notification.listNotifications';
    case registerPush = 'app.bsky.notification.registerPush';
    case updateSeen = 'app.bsky.notification.updateSeen';
    case getConfig = 'app.bsky.unspecced.getConfig';
    case getPopularFeedGenerators = 'app.bsky.unspecced.getPopularFeedGenerators';
    case getSuggestionsSkeleton = 'app.bsky.unspecced.getSuggestionsSkeleton';
    case getTaggedSuggestions = 'app.bsky.unspecced.getTaggedSuggestions';
    case searchActorsSkeleton = 'app.bsky.unspecced.searchActorsSkeleton';
    case searchPostsSkeleton = 'app.bsky.unspecced.searchPostsSkeleton';
    case getJobStatus = 'app.bsky.video.getJobStatus';
    case getUploadLimits = 'app.bsky.video.getUploadLimits';
    case uploadVideo = 'app.bsky.video.uploadVideo';
}
