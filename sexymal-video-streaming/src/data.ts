import { Video } from './types';

// Helper to construct Mux thumbnail fallback URL
export function getMuxThumbnailUrl(playbackId: string): string {
  return `https://image.mux.com/${playbackId}/thumbnail.jpg`;
}

// Fallback high-quality images based on categories
export function getFallbackThumbnail(category: string, index: number): string {
  const images: Record<string, string[]> = {
    'Nature & Travel': [
      'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1501854140801-50d01698950b?auto=format&fit=crop&w=800&q=80',
    ],
    'Tech & Gadgets': [
      'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?auto=format&fit=crop&w=800&q=80',
    ],
    'Cars & Speed': [
      'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80',
    ],
    'Music & Art': [
      'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?auto=format&fit=crop&w=800&q=80',
    ],
    'Cinematic Shorts': [
      'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=800&q=80',
    ],
    'Action Sports': [
      'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1507608869274-d3177c8bb4c7?auto=format&fit=crop&w=800&q=80',
    ]
  };

  const categoryImages = images[category] || [
    'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=800&q=80'
  ];

  return categoryImages[index % categoryImages.length];
}

// Get final resolved thumbnail URL for a video
export function getVideoThumbnail(video: Video, index: number = 0): string {
  if (video.thumbnailUrl && video.thumbnailUrl.trim() !== '') {
    return video.thumbnailUrl;
  }
  if (video.muxPlaybackId && video.muxPlaybackId.trim() !== '') {
    return getMuxThumbnailUrl(video.muxPlaybackId);
  }
  return getFallbackThumbnail(video.category, index);
}

// Smart recommended videos sorting algorithm
export function getRecommendedVideos(currentVideo: Video, allVideos: Video[]): Video[] {
  // 1. Remove current video
  const otherVideos = allVideos.filter(v => v.id !== currentVideo.id);

  // 2. Count matching tags for each video
  const scoredVideos = otherVideos.map(v => {
    const matchingTagsCount = v.tags.filter(tag => currentVideo.tags.includes(tag)).length;
    return { video: v, score: matchingTagsCount };
  });

  // 3. Sort: those with matching tags (score > 0) go first, sorted by score descending, then fill with the rest
  scoredVideos.sort((a, b) => {
    if (a.score > 0 && b.score === 0) return -1;
    if (a.score === 0 && b.score > 0) return 1;
    if (a.score > 0 && b.score > 0) {
      return b.score - a.score; // Higher match count first
    }
    // If both have 0 matches, keep original/random order or views count
    return b.video.views - a.video.views;
  });

  // 4. Return all other videos sorted by score
  return scoredVideos.map(item => item.video);
}

export const DEFAULT_CATEGORIES = [
  'All',
  'Nature & Travel',
  'Tech & Gadgets',
  'Cars & Speed',
  'Music & Art',
  'Cinematic Shorts',
  'Action Sports'
];

export const DEFAULT_VIDEOS: Video[] = [
  {
    id: 'vid_1',
    title: 'Adrenaline Rush: Downhill Mountain Biking',
    description: 'Experience extreme downhill mountain biking on treacherous trails in the Swiss Alps. Speed, dangerous curves, and stunning heights.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
    category: 'Action Sports',
    tags: ['bike', 'extreme', 'sports', 'alps', 'speed'],
    likes: 1240,
    dislikes: 12,
    views: 45020,
    createdAt: '2026-06-15T10:00:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4'
  },
  {
    id: 'vid_2',
    title: 'Sintel - Dynamic Cinematic Masterpiece',
    description: 'A beautiful, emotional animated short movie of a girl searching for her baby dragon. Witness stellar visuals and music scores.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
    category: 'Cinematic Shorts',
    tags: ['animation', 'fantasy', 'dragon', 'cinematic', 'art'],
    likes: 5820,
    dislikes: 85,
    views: 120530,
    createdAt: '2026-05-10T14:30:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4'
  },
  {
    id: 'vid_3',
    title: 'Tears of Steel - Science Fiction Showcase',
    description: 'Explore a futuristic Amsterdam where a group of scientists tries to rescue the world from giant destructive robots.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
    category: 'Cinematic Shorts',
    tags: ['scifi', 'robots', 'amsterdam', 'cinematic', 'future'],
    likes: 3120,
    dislikes: 42,
    views: 89400,
    createdAt: '2026-06-20T09:15:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4'
  },
  {
    id: 'vid_4',
    title: 'Big Buck Bunny - Absolute Classic Comedy',
    description: 'A large and lovable rabbit overcomes bully forest rodents in a hilariously funny slapstick animation.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
    category: 'Cinematic Shorts',
    tags: ['animation', 'comedy', 'bunny', 'classic', 'fun'],
    likes: 9540,
    dislikes: 140,
    views: 310920,
    createdAt: '2026-01-01T12:00:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'
  },
  {
    id: 'vid_5',
    title: 'Exploring the Hidden Treasures of Iceland',
    description: 'A visual journey through green moss canyons, black sand beaches, roaring geysers, and the active volcanoes of Iceland.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
    category: 'Nature & Travel',
    tags: ['iceland', 'nature', 'travel', 'volcano', 'scenic'],
    likes: 820,
    dislikes: 5,
    views: 14300,
    createdAt: '2026-07-01T08:00:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4'
  },
  {
    id: 'vid_6',
    title: 'Supercar Track Battle: Carbon Fiber Beasts',
    description: 'Watch three high-performance carbon-fiber supercars go head-to-head on a legendary racing track under stormy skies.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
    category: 'Cars & Speed',
    tags: ['supercar', 'racing', 'track', 'speed', 'carbon-fiber'],
    likes: 2190,
    dislikes: 18,
    views: 52400,
    createdAt: '2026-06-28T16:45:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4'
  },
  {
    id: 'vid_7',
    title: 'Deep House Beats & Abstract Light Installation',
    description: 'An immersive live dynamic light synthesis synced with smooth, relaxing deep house beats designed for ambient chill sessions.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
    category: 'Music & Art',
    tags: ['music', 'ambient', 'lights', 'art', 'dj-set'],
    likes: 1430,
    dislikes: 11,
    views: 33200,
    createdAt: '2026-07-04T22:00:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4'
  },
  {
    id: 'vid_8',
    title: 'Smart Home Automation: The Ultimate 2026 Tour',
    description: 'Take a comprehensive tour of the ultimate smart home setup in 2026, featuring integrated AI hubs, smart glass, and voice-controlled appliances.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4',
    category: 'Tech & Gadgets',
    tags: ['gadgets', 'tech', 'automation', 'future', 'smart-home'],
    likes: 3120,
    dislikes: 35,
    views: 75200,
    createdAt: '2026-06-18T11:20:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4'
  },
  {
    id: 'vid_9',
    title: 'Mux Demo Stream - Seamless Adaptive Video',
    description: 'An official Mux-powered HLS adaptive stream showcasing incredible delivery speed and multi-bitrate high-performance playback.',
    muxPlaybackId: 'DS00S6Aax9gEdS019KK101d6asK02nBs8To00',
    videoUrl: 'https://stream.mux.com/DS00S6Aax9gEdS019KK101d6asK02nBs8To00.m3u8',
    category: 'Tech & Gadgets',
    tags: ['mux', 'video-player', 'streaming', 'hls', 'future'],
    likes: 450,
    dislikes: 3,
    views: 9810,
    createdAt: '2026-07-08T15:00:00Z',
    downloadUrl: 'https://stream.mux.com/DS00S6Aax9gEdS019KK101d6asK02nBs8To00.m3u8'
  },
  {
    id: 'vid_10',
    title: 'Serene Forest Streams and Birdsong Sounds',
    description: 'Unwind with beautiful 4K high-definition streams of pristine woodland creeks accompanied by gentle, soothing chirps.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
    category: 'Nature & Travel',
    tags: ['nature', 'forest', 'calm', 'relax', 'scenic'],
    likes: 2200,
    dislikes: 9,
    views: 41200,
    createdAt: '2026-07-06T06:30:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4'
  },
  {
    id: 'vid_11',
    title: 'Cyberpunk Tokyo Drift & Tuning Meets',
    description: 'Experience Tokyo night street car meets with neon reflections, massive exhaust notes, and professional drift action.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
    category: 'Cars & Speed',
    tags: ['supercar', 'tokyo', 'neon', 'speed', 'drift'],
    likes: 4320,
    dislikes: 31,
    views: 98400,
    createdAt: '2026-07-02T23:45:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4'
  },
  {
    id: 'vid_12',
    title: 'Neon Symphony: Interactive Art Lab',
    description: 'An artistic, interactive laser lights show creating glowing patterns, abstract geometries, and vibrant fluorescent visual fields.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
    category: 'Music & Art',
    tags: ['art', 'neon', 'installation', 'creativity', 'visuals'],
    likes: 1800,
    dislikes: 22,
    views: 45600,
    createdAt: '2026-05-15T18:00:00Z',
    downloadUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4'
  },
  // To reach 26 videos (to showcase 21 videos/page pagination):
  {
    id: 'vid_13',
    title: 'Alpine Hiking Adventure: Sky High Trails',
    description: 'Scaling the highest peaks of the Swiss Alps, hiking through snow blankets, and resting in modern geometric glass cabins.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
    category: 'Nature & Travel',
    tags: ['alps', 'hiking', 'nature', 'travel', 'scenic'],
    likes: 670,
    dislikes: 4,
    views: 11200,
    createdAt: '2026-07-03T11:00:00Z'
  },
  {
    id: 'vid_14',
    title: 'Desert Rally: Driving on Infinite Dunes',
    description: 'Rally cars racing through the beautiful Sahara Desert dunes under blazing hot afternoon suns. High torque adrenaline.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
    category: 'Cars & Speed',
    tags: ['racing', 'desert', 'supercar', 'speed', 'extreme'],
    likes: 1390,
    dislikes: 14,
    views: 29400,
    createdAt: '2026-06-25T14:00:00Z'
  },
  {
    id: 'vid_15',
    title: 'The Future of AI Wearables: Sci-Fi to Reality',
    description: 'Unboxing and reviewing the latest AI smart glasses and pin devices that project live information holograms directly to your vision.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4',
    category: 'Tech & Gadgets',
    tags: ['tech', 'gadgets', 'future', 'scifi', 'smart-home'],
    likes: 2150,
    dislikes: 40,
    views: 59300,
    createdAt: '2026-06-12T15:30:00Z'
  },
  {
    id: 'vid_16',
    title: 'Surf Session: Riding Giant Waves in Maui',
    description: 'Watch professional big wave surfers tackle some of the largest, cleanest barrels in Maui, Hawaii. Massive water power.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
    category: 'Action Sports',
    tags: ['extreme', 'sports', 'surf', 'ocean', 'maui'],
    likes: 1980,
    dislikes: 15,
    views: 38700,
    createdAt: '2026-07-07T07:10:00Z'
  },
  {
    id: 'vid_17',
    title: 'Lo-Fi Chill Hop: Study & Work Beats 24/7',
    description: 'The absolute best lo-fi hip hop tracks blended perfectly into a seamless relaxing loop with a cozy pixel art aesthetic.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
    category: 'Music & Art',
    tags: ['music', 'ambient', 'calm', 'art', 'relax'],
    likes: 3410,
    dislikes: 25,
    views: 112000,
    createdAt: '2026-07-05T09:00:00Z'
  },
  {
    id: 'vid_18',
    title: 'Canyon Eco Lodge: Ultimate Off-Grid Living',
    description: 'Touring a state-of-the-art sustainable carbon-negative home carved inside a gorgeous desert canyon in Utah.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
    category: 'Nature & Travel',
    tags: ['nature', 'travel', 'automation', 'scenic', 'smart-home'],
    likes: 910,
    dislikes: 7,
    views: 19200,
    createdAt: '2026-06-30T10:00:00Z'
  },
  {
    id: 'vid_19',
    title: 'Drifting Chronicles: Mountain Pass Attack',
    description: 'Custom tuned racing cars attacking a narrow mountain pass with professional tire-shredding sideways action.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
    category: 'Cars & Speed',
    tags: ['racing', 'speed', 'drift', 'supercar', 'extreme'],
    likes: 2890,
    dislikes: 21,
    views: 64100,
    createdAt: '2026-06-22T21:00:00Z'
  },
  {
    id: 'vid_20',
    title: 'Unboxing the Quantum Processor: Deep Tech Lab',
    description: 'An exclusive look inside a cryo-cooled quantum laboratory computing experimental bits at sub-zero temperatures.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
    category: 'Tech & Gadgets',
    tags: ['tech', 'future', 'scifi', 'robots', 'gadgets'],
    likes: 4350,
    dislikes: 49,
    views: 104500,
    createdAt: '2026-05-20T13:00:00Z'
  },
  {
    id: 'vid_21',
    title: 'Skatepark Flow: Smooth Bowl Lines',
    description: 'Sleek, fluid transitions and highly technical skate tricks filmed at a modern concrete skate park in California.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
    category: 'Action Sports',
    tags: ['extreme', 'sports', 'skate', 'california', 'speed'],
    likes: 1090,
    dislikes: 8,
    views: 22800,
    createdAt: '2026-06-10T16:00:00Z'
  },
  // PAGE 2 VIDEOS START HERE (Since index starts from 21)
  {
    id: 'vid_22',
    title: 'Deep Sea Explorer: Ocean Abyss Secrets',
    description: 'Submersible footage showing glowing biological creatures and active thermal vents 4000 meters below sea level.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
    category: 'Nature & Travel',
    tags: ['nature', 'ocean', 'scenic', 'future', 'travel'],
    likes: 1250,
    dislikes: 11,
    views: 29000,
    createdAt: '2026-07-08T09:00:00Z'
  },
  {
    id: 'vid_23',
    title: 'F1 Cockpit View: Speeds of 350 km/h',
    description: 'Feel the incredible G-force and blinding reflexes through an ultra-wide helmet cam in a modern Formula racing car.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
    category: 'Cars & Speed',
    tags: ['racing', 'speed', 'supercar', 'extreme', 'track'],
    likes: 5400,
    dislikes: 38,
    views: 139000,
    createdAt: '2026-07-09T08:00:00Z'
  },
  {
    id: 'vid_24',
    title: 'Abstract 3D Claymation Journey',
    description: 'A mesmerizing stop-motion animation using colorful clays that morph, twist, and flow into artistic organic shapes.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
    category: 'Music & Art',
    tags: ['animation', 'art', 'creativity', 'classic', 'fantasy'],
    likes: 930,
    dislikes: 6,
    views: 18400,
    createdAt: '2026-06-05T14:00:00Z'
  },
  {
    id: 'vid_25',
    title: 'Snowboard Freeride: Fresh Powder Carver',
    description: 'Carving immaculate white tracks down untouched powder slopes in the beautiful mountains of Japan.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
    category: 'Action Sports',
    tags: ['extreme', 'sports', 'alps', 'snow', 'scenic'],
    likes: 1650,
    dislikes: 12,
    views: 31200,
    createdAt: '2026-07-07T11:00:00Z'
  },
  {
    id: 'vid_26',
    title: 'The Microchip Revolution: Nanometer Wonders',
    description: 'An educational visual guide looking into the extreme ultraviolet lithography machines crafting 2nm silicone chips.',
    videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4',
    category: 'Tech & Gadgets',
    tags: ['tech', 'future', 'gadgets', 'automation', 'scifi'],
    likes: 3890,
    dislikes: 27,
    views: 89000,
    createdAt: '2026-07-05T15:00:00Z'
  },
  {
    id: 'vid_1036',
    title: 'Video 1036',
    description: 'A stunning visual stream exploring creative landscapes and dynamic cinematic angles.',
    muxPlaybackId: '4xMmf9aucFvs0162USxJsLJ9iMmUITz026HuC81d3s5t00',
    videoUrl: 'https://stream.mux.com/4xMmf9aucFvs0162USxJsLJ9iMmUITz026HuC81d3s5t00.m3u8',
    category: 'Cinematic Shorts',
    tags: ['cinematic', 'art', 'creative', 'short'],
    likes: 1450,
    dislikes: 12,
    views: 24800,
    createdAt: '2026-07-09T10:00:00Z'
  },
  {
    id: 'vid_1037',
    title: 'Video 1037',
    description: 'An immersive showcase of high-speed pacing and modern urban aesthetics.',
    muxPlaybackId: '5RsOMApcmp5v00Ln00eVDdVbg22YpMgDr4o00xGASfnOq4',
    videoUrl: 'https://stream.mux.com/5RsOMApcmp5v00Ln00eVDdVbg22YpMgDr4o00xGASfnOq4.m3u8',
    category: 'Cars & Speed',
    tags: ['speed', 'racing', 'drift', 'urban'],
    likes: 3240,
    dislikes: 18,
    views: 56100,
    createdAt: '2026-07-09T09:30:00Z'
  },
  {
    id: 'vid_1038',
    title: 'Video 1038',
    description: 'Soothing natural ambiance showcasing beautiful landscapes and outdoor serenity.',
    muxPlaybackId: '019bFZIboFC7PGlMxDlPHKxatmG1YbwDHUhVyFuTJ6cw',
    videoUrl: 'https://stream.mux.com/019bFZIboFC7PGlMxDlPHKxatmG1YbwDHUhVyFuTJ6cw.m3u8',
    category: 'Nature & Travel',
    tags: ['nature', 'scenic', 'travel', 'calm'],
    likes: 1890,
    dislikes: 8,
    views: 32000,
    createdAt: '2026-07-09T09:00:00Z'
  },
  {
    id: 'vid_1039',
    title: 'Video 1039',
    description: 'A deep dive into next-generation smart electronics and creative engineering.',
    muxPlaybackId: 'dd24yFLzNm9bGg011oSMdt6z9VIAMh42rcTb02pASquSM',
    videoUrl: 'https://stream.mux.com/dd24yFLzNm9bGg011oSMdt6z9VIAMh42rcTb02pASquSM.m3u8',
    category: 'Tech & Gadgets',
    tags: ['tech', 'gadgets', 'future', 'review'],
    likes: 2110,
    dislikes: 15,
    views: 41900,
    createdAt: '2026-07-09T08:30:00Z'
  },
  {
    id: 'vid_1054',
    title: 'Video 1054',
    description: 'Energetic action capture highlighting intense athletic performance and movement.',
    muxPlaybackId: 'SvF52i4C859zKey5iCUWkf866ch8DQ02pOZuNVJxsCMc',
    videoUrl: 'https://stream.mux.com/SvF52i4C859zKey5iCUWkf866ch8DQ02pOZuNVJxsCMc.m3u8',
    category: 'Action Sports',
    tags: ['sports', 'extreme', 'action', 'movement'],
    likes: 2750,
    dislikes: 22,
    views: 49800,
    createdAt: '2026-07-09T08:00:00Z'
  },
  {
    id: 'vid_1055',
    title: 'Video 1055',
    description: 'An experimental synth art setup syncing ambient lighting with high-fidelity beats.',
    muxPlaybackId: 'lMWVgJrPBzE2xzpnJH0102eqy4YdDfRQ9ZRbSngN2gv9Q',
    videoUrl: 'https://stream.mux.com/lMWVgJrPBzE2xzpnJH0102eqy4YdDfRQ9ZRbSngN2gv9Q.m3u8',
    category: 'Music & Art',
    tags: ['music', 'ambient', 'art', 'synth'],
    likes: 1620,
    dislikes: 9,
    views: 28400,
    createdAt: '2026-07-09T07:30:00Z'
  },
  {
    id: 'vid_1056',
    title: 'Video 1056',
    description: 'Spectacular wilderness vistas capturing early morning mist and sunrise over mountains.',
    muxPlaybackId: 'lTzJ3BiehdWSKrFezqdH4rtarmmTs86eUzTlSpkoTlw',
    videoUrl: 'https://stream.mux.com/lTzJ3BiehdWSKrFezqdH4rtarmmTs86eUzTlSpkoTlw.m3u8',
    category: 'Nature & Travel',
    tags: ['nature', 'scenic', 'travel', 'mountains'],
    likes: 1320,
    dislikes: 5,
    views: 22100,
    createdAt: '2026-07-09T07:00:00Z'
  },
  {
    id: 'vid_1057',
    title: 'Video 1057',
    description: 'High performance sports showcase featuring dramatic angles and sleek control.',
    muxPlaybackId: 'HgiN5WI01Mh56RjpUIEkfwTnO67MmS00Gbm4j4tnhwG01Q',
    videoUrl: 'https://stream.mux.com/HgiN5WI01Mh56RjpUIEkfwTnO67MmS00Gbm4j4tnhwG01Q.m3u8',
    category: 'Action Sports',
    tags: ['extreme', 'sports', 'speed', 'control'],
    likes: 1980,
    dislikes: 14,
    views: 35000,
    createdAt: '2026-07-09T06:30:00Z'
  },
  {
    id: 'vid_1058',
    title: 'Video 1058',
    description: 'Futuristic product showcase checking out the limits of smart design integration.',
    muxPlaybackId: 'gPpV0101NUVFvaK01ICkzjBHrw3AV00BSumA7k9e3EeCl6c',
    videoUrl: 'https://stream.mux.com/gPpV0101NUVFvaK01ICkzjBHrw3AV00BSumA7k9e3EeCl6c.m3u8',
    category: 'Tech & Gadgets',
    tags: ['tech', 'future', 'gadgets', 'design'],
    likes: 2450,
    dislikes: 19,
    views: 43200,
    createdAt: '2026-07-09T06:00:00Z'
  },
  {
    id: 'vid_1059',
    title: 'Video 1059',
    description: 'Splendid night drift session under futuristic neon signs and glowing lights.',
    muxPlaybackId: '02GwNbkYioUe6A69kEX8WWM6qtt5pftm2GVVRrKQNeiY',
    videoUrl: 'https://stream.mux.com/02GwNbkYioUe6A69kEX8WWM6qtt5pftm2GVVRrKQNeiY.m3u8',
    category: 'Cars & Speed',
    tags: ['neon', 'speed', 'drift', 'racing'],
    likes: 3890,
    dislikes: 24,
    views: 67500,
    createdAt: '2026-07-09T05:30:00Z'
  },
  {
    id: 'vid_2188',
    title: 'Video 2188',
    description: 'Mesmerizing stop-motion organic transformation with amazing audio design.',
    muxPlaybackId: 'mpaAwwBl1aazIFNinweoTmWMaCi00hbjyGe9OPugP1JA',
    videoUrl: 'https://stream.mux.com/mpaAwwBl1aazIFNinweoTmWMaCi00hbjyGe9OPugP1JA.m3u8',
    category: 'Music & Art',
    tags: ['art', 'animation', 'creativity', 'music'],
    likes: 1120,
    dislikes: 6,
    views: 19500,
    createdAt: '2026-07-09T05:00:00Z'
  },
  {
    id: 'vid_2189',
    title: 'Video 2189',
    description: 'Fresh winter powder snow run captured in extreme high definition slow-mo.',
    muxPlaybackId: '2tTDeEedheotqEJzsJWT702B93qOhXGH1tySkMWqnn2w',
    videoUrl: 'https://stream.mux.com/2tTDeEedheotqEJzsJWT702B93qOhXGH1tySkMWqnn2w.m3u8',
    category: 'Action Sports',
    tags: ['sports', 'extreme', 'snow', 'alps'],
    likes: 2190,
    dislikes: 11,
    views: 38200,
    createdAt: '2026-07-09T04:30:00Z'
  },
  {
    id: 'vid_2190',
    title: 'Video 2190',
    description: 'A deep space lithography animation visualizing the future of chip manufacturing.',
    muxPlaybackId: 'aWzyMut300jBoP6j5BrLMLkmEfYRfaacBbNp4TB5PNq4',
    videoUrl: 'https://stream.mux.com/aWzyMut300jBoP6j5BrLMLkmEfYRfaacBbNp4TB5PNq4.m3u8',
    category: 'Tech & Gadgets',
    tags: ['tech', 'future', 'gadgets', 'scifi'],
    likes: 4120,
    dislikes: 35,
    views: 79300,
    createdAt: '2026-07-09T04:00:00Z'
  },
  {
    id: 'vid_2191',
    title: 'Video 2191',
    description: 'An elegant narrative sequence tracing a girl and her baby dragon through a fantasy world.',
    muxPlaybackId: '6fWtbiiJEADgKXmeeCkVPa3hBEusHOfHhXbr8jSi02GE',
    videoUrl: 'https://stream.mux.com/6fWtbiiJEADgKXmeeCkVPa3hBEusHOfHhXbr8jSi02GE.m3u8',
    category: 'Cinematic Shorts',
    tags: ['cinematic', 'animation', 'fantasy', 'dragon'],
    likes: 5120,
    dislikes: 42,
    views: 98700,
    createdAt: '2026-07-09T03:30:00Z'
  },
  {
    id: 'vid_2192',
    title: 'Video 2192',
    description: 'A dynamic off-grid luxury oasis showcasing carbon-negative materials.',
    muxPlaybackId: '29j665hBIO98mmHsoiL5X01r9edrZlp7W00RPKwUjmIxc',
    videoUrl: 'https://stream.mux.com/29j665hBIO98mmHsoiL5X01r9edrZlp7W00RPKwUjmIxc.m3u8',
    category: 'Nature & Travel',
    tags: ['nature', 'travel', 'scenic', 'calm'],
    likes: 1540,
    dislikes: 10,
    views: 27900,
    createdAt: '2026-07-09T03:00:00Z'
  },
  {
    id: 'vid_2193',
    title: 'Video 2193',
    description: 'Riding beautiful curling barrels on a sunny afternoon in beautiful Hawaii.',
    muxPlaybackId: '01281atYm02sWtFlWaOvKWmjg1E5Ng10059u00qB3ZbdmkU',
    videoUrl: 'https://stream.mux.com/01281atYm02sWtFlWaOvKWmjg1E5Ng10059u00qB3ZbdmkU.m3u8',
    category: 'Action Sports',
    tags: ['extreme', 'sports', 'surf', 'ocean'],
    likes: 3120,
    dislikes: 18,
    views: 54100,
    createdAt: '2026-07-09T02:30:00Z'
  },
  {
    id: 'vid_2194',
    title: 'Video 2194',
    description: 'An epic F1 helmet POV sequence demonstrating high-precision racing track maneuvers.',
    muxPlaybackId: 'JdunagSnLiSBjdT3GK8029rHRhiJCxj8ShB00GLsrxHvQ',
    videoUrl: 'https://stream.mux.com/JdunagSnLiSBjdT3GK8029rHRhiJCxj8ShB00GLsrxHvQ.m3u8',
    category: 'Cars & Speed',
    tags: ['racing', 'speed', 'track', 'extreme'],
    likes: 6200,
    dislikes: 45,
    views: 114000,
    createdAt: '2026-07-09T02:00:00Z'
  },
  {
    id: 'vid_2195',
    title: 'Video 2195',
    description: 'Relaxing ambient Lo-Fi hip hop session coupled with gorgeous retro pixel animation.',
    muxPlaybackId: 'NIryUHUH6nFfVZowleUGQegTZplaI5OQgo18Pncr9Hc',
    videoUrl: 'https://stream.mux.com/NIryUHUH6nFfVZowleUGQegTZplaI5OQgo18Pncr9Hc.m3u8',
    category: 'Music & Art',
    tags: ['music', 'ambient', 'calm', 'relax'],
    likes: 4890,
    dislikes: 32,
    views: 88500,
    createdAt: '2026-07-09T01:30:00Z'
  }
];
