import React, { useState, useEffect, useRef } from 'react';
import { ThumbsUp, ThumbsDown, Share2, Download, Eye, Check, ChevronDown, Sparkles } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { Video } from '../types';
import { getRecommendedVideos, getVideoThumbnail } from '../data';
import VideoCard from './VideoCard';
import PlayerBannerAd from './PlayerBannerAd';
import { handleAdClick } from '../utils/adHelper';

interface VideoDetailProps {
  video: Video;
  allVideos: Video[];
  onSelectVideo: (video: Video) => void;
  onLike: (videoId: string, type: 'like' | 'dislike') => void;
  userInteractions: Record<string, 'like' | 'dislike' | null>;
}

export default function VideoDetail({
  video,
  allVideos,
  onSelectVideo,
  onLike,
  userInteractions,
}: VideoDetailProps) {
  const [showShareToast, setShowShareToast] = useState(false);
  const [showDownloadToast, setShowDownloadToast] = useState(false);
  
  // Pagination limit state for recommended videos - 18 items initially
  const [visibleCount, setVisibleCount] = useState(18);

  const videoRef = useRef<HTMLVideoElement>(null);

  // Reset pagination limit, scroll to top, and inject dynamic SEO meta tags & Google JSON-LD Schema
  useEffect(() => {
    setVisibleCount(18);
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // 1. Update Document Title dynamically
    document.title = `${video.title} - Sexymal Streaming`;

    // 2. Update Description & Keywords meta tags
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) {
      metaDesc.setAttribute('content', `${video.description.slice(0, 160)} - Stream now on Sexymal.`);
    }
    const metaKeywords = document.querySelector('meta[name="keywords"]');
    if (metaKeywords) {
      metaKeywords.setAttribute('content', `${video.tags.join(', ')}, ${video.category}, sexymal, stream`);
    }

    // 3. Inject Schema.org JSON-LD Video Object for google bot search indexing
    const schemaId = 'seo-video-schema';
    let script = document.getElementById(schemaId) as HTMLScriptElement;
    if (!script) {
      script = document.createElement('script');
      script.id = schemaId;
      script.type = 'application/ld+json';
      document.head.appendChild(script);
    }
    
    const videoSchema = {
      "@context": "https://schema.org",
      "@type": "VideoObject",
      "name": video.title,
      "description": video.description,
      "thumbnailUrl": video.thumbnailUrl || (video.muxPlaybackId ? `https://image.mux.com/${video.muxPlaybackId}/thumbnail.jpg` : 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=1200&q=80'),
      "uploadDate": video.createdAt || new Date().toISOString(),
      "contentUrl": video.videoUrl || (video.muxPlaybackId ? `https://stream.mux.com/${video.muxPlaybackId}.m3u8` : ''),
      "embedUrl": `https://sexymal-streaming.com/video/${video.id}`,
      "interactionStatistic": {
        "@type": "InteractionCounter",
        "interactionType": { "@type": "https://schema.org/WatchAction" },
        "userInteractionCount": video.views
      }
    };
    script.textContent = JSON.stringify(videoSchema);

    // Clean up when unmounting / switching videos
    return () => {
      document.title = "Sexymal - Premium Cinematic Video Streaming Platform";
      const existingScript = document.getElementById(schemaId);
      if (existingScript) {
        existingScript.remove();
      }
    };
  }, [video.id, video.title, video.description, video.tags, video.category, video.thumbnailUrl, video.muxPlaybackId, video.videoUrl, video.createdAt, video.views]);

  // Handle high-performance HLS streaming and standard video playback
  useEffect(() => {
    const videoElement = videoRef.current;
    if (!videoElement) return;

    let hlsInstance: any = null;
    const playUrl = video.videoUrl || (video.muxPlaybackId ? `https://stream.mux.com/${video.muxPlaybackId}.m3u8` : '');

    if (!playUrl) return;

    // Reset current source before loading new one
    videoElement.src = '';

    const isHls = playUrl.includes('.m3u8') || playUrl.includes('stream.mux.com');

    if (isHls) {
      import('hls.js').then(({ default: Hls }) => {
        if (Hls.isSupported()) {
          // Destroy any existing instance if active
          if (hlsInstance) {
            hlsInstance.destroy();
          }
          hlsInstance = new Hls({
            enableWorker: true,
            lowLatencyMode: true,
          });
          hlsInstance.loadSource(playUrl);
          hlsInstance.attachMedia(videoElement);
          hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
            videoElement.play().catch((e) => console.log('Autoplay prevented:', e));
          });
        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
          // Native HLS support (Safari / iOS)
          videoElement.src = playUrl;
          videoElement.play().catch((e) => console.log('Autoplay prevented:', e));
        }
      });
    } else {
      // Standard direct MP4/WebM video
      videoElement.src = playUrl;
      videoElement.play().catch((e) => console.log('Autoplay prevented:', e));
    }

    return () => {
      if (hlsInstance) {
        hlsInstance.destroy();
      }
    };
  }, [video.videoUrl, video.muxPlaybackId]);

  const recommendedVideos = getRecommendedVideos(video, allVideos);
  const currentInteraction = userInteractions[video.id] || null;

  // Web Share API with fallback copy
  const handleShare = async () => {
    const shareUrl = window.location.href;
    const shareTitle = video.title;

    if (navigator.share) {
      try {
        await navigator.share({
          title: shareTitle,
          text: video.description,
          url: shareUrl,
        });
      } catch (err) {
        copyToClipboard(shareUrl);
      }
    } else {
      copyToClipboard(shareUrl);
    }
  };

  const copyToClipboard = (text: string) => {
    try {
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).catch((err) => {
          console.warn('Failed to copy using writeText:', err);
          fallbackCopyToClipboard(text);
        });
      } else {
        fallbackCopyToClipboard(text);
      }
    } catch (e) {
      console.warn('Clipboard error, using fallback:', e);
      fallbackCopyToClipboard(text);
    }
    setShowShareToast(true);
    setTimeout(() => setShowShareToast(false), 2500);
  };

  const fallbackCopyToClipboard = (text: string) => {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      document.execCommand('copy');
    } catch (err) {
      console.error('Fallback copy failed:', err);
    }
    document.body.removeChild(textArea);
  };

  // Direct download handler
  const handleDownload = () => {
    setShowDownloadToast(true);
    setTimeout(() => {
      setShowDownloadToast(false);
      
      const link = document.createElement('a');
      link.href = video.videoUrl;
      link.setAttribute('download', `${video.title.replace(/\s+/g, '_')}.mp4`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }, 2000);
  };

  // Construct looping recommended videos so they never run out and Load More button is endless
  let displayedVideos = recommendedVideos;
  if (recommendedVideos.length > 0) {
    while (displayedVideos.length <= visibleCount + 10) {
      displayedVideos = [
        ...displayedVideos,
        ...recommendedVideos.map((v, i) => ({
          ...v,
          id: `${v.id}_dup_${displayedVideos.length}_${i}`
        }))
      ];
    }
  }
  const visibleRecommended = displayedVideos.slice(0, visibleCount);

  return (
    <div className="w-full max-w-screen-3xl mx-auto px-2 sm:px-4 py-2 sm:py-4 transition-colors text-left">
      
      {/* Dynamic Toasts Container */}
      <div className="fixed top-20 right-4 z-50 flex flex-col gap-2 pointer-events-none">
        <AnimatePresence>
          {showShareToast && (
            <motion.div
              initial={{ opacity: 0, x: 20, scale: 0.95 }}
              animate={{ opacity: 1, x: 0, scale: 1 }}
              exit={{ opacity: 0, x: 20, scale: 0.95 }}
              className="bg-zinc-900/95 dark:bg-white/95 text-white dark:text-zinc-900 px-4 py-3 rounded-xl shadow-xl flex items-center gap-2.5 text-xs font-semibold backdrop-blur"
            >
              <Check className="w-4 h-4 text-emerald-500" />
              <span>Link successfully copied to clipboard!</span>
            </motion.div>
          )}

          {showDownloadToast && (
            <motion.div
              initial={{ opacity: 0, x: 20, scale: 0.95 }}
              animate={{ opacity: 1, x: 0, scale: 1 }}
              exit={{ opacity: 0, x: 20, scale: 0.95 }}
              className="bg-red-600 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-2.5 text-xs font-semibold"
            >
              <span className="w-2.5 h-2.5 rounded-full bg-white animate-ping" />
              <span>Processing secure download link...</span>
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Main Column layout: Player, then Recommended grid below */}
      <div className="flex flex-col">
        
        {/* Main Video Player Container */}
        <div className="relative aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-xl border border-slate-150 dark:border-zinc-800/80">
          <video
            ref={videoRef}
            poster={getVideoThumbnail(video)}
            controls
            autoPlay
            className="w-full h-full object-contain"
            referrerPolicy="no-referrer"
          />
        </div>

        {/* Title & Stats block */}
        <div className="text-left mt-3">
          <h1 className="font-sans font-black text-sm sm:text-base md:text-lg text-slate-800 dark:text-zinc-50 leading-tight">
            {video.title}
          </h1>

          {/* Compact Actions Bar & Stats Counts */}
          <div className="flex flex-col xs:flex-row xs:items-center justify-between gap-3 mt-2 border-b border-slate-100 dark:border-zinc-900 pb-3">
            
            {/* Left stats: views */}
            <div className="flex items-center gap-1.5 text-[10px] sm:text-xs font-bold font-mono text-slate-400 dark:text-zinc-500 uppercase tracking-wider">
              <span className="flex items-center gap-1 bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/80 px-2.5 py-1 rounded-md">
                <Eye className="w-3.5 h-3.5 text-slate-400 dark:text-zinc-500" />
                {video.views.toLocaleString()} Views
              </span>
            </div>

            {/* Right Interactive Actions - Scrollable row on mobile, wrapped on xs+ */}
            <div className="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-0.5 -mx-4 px-4 xs:mx-0 xs:px-0 flex-nowrap xs:flex-wrap w-auto max-w-full">
              
              {/* Like / Dislike Group */}
              <div className="flex items-center bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/80 rounded-full p-0.5 shadow-sm flex-shrink-0">
                <button
                  onClick={(e) => {
                    handleAdClick(e, () => onLike(video.id, 'like'));
                  }}
                  className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-black transition-all cursor-pointer ${
                    currentInteraction === 'like'
                      ? 'bg-red-600 text-white'
                      : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800'
                  }`}
                >
                  <ThumbsUp className="w-3 h-3 sm:w-3.5 h-3.5" />
                  <span>{video.likes + (currentInteraction === 'like' ? 1 : 0)}</span>
                </button>
                <div className="h-3 w-[1px] bg-slate-200 dark:bg-zinc-800 mx-0.5" />
                <button
                  onClick={(e) => {
                    handleAdClick(e, () => onLike(video.id, 'dislike'));
                  }}
                  className={`flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-black transition-all cursor-pointer ${
                    currentInteraction === 'dislike'
                      ? 'bg-zinc-800 text-white'
                      : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800'
                  }`}
                >
                  <ThumbsDown className="w-3 h-3 sm:w-3.5 h-3.5" />
                  <span>{video.dislikes + (currentInteraction === 'dislike' ? 1 : 0)}</span>
                </button>
              </div>

              {/* Share Button */}
              <button
                onClick={(e) => {
                  handleAdClick(e, () => handleShare());
                }}
                className="flex items-center gap-1 px-2.5 py-1.5 bg-slate-50 dark:bg-zinc-900 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-100 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 text-[10px] sm:text-[11px] font-black rounded-full shadow-sm transition-all cursor-pointer flex-shrink-0"
                title="Share Video"
              >
                <Share2 className="w-3 h-3 sm:w-3.5 h-3.5" />
                <span>Share</span>
              </button>

              {/* Direct Download Button */}
              <button
                onClick={(e) => {
                  handleAdClick(e, () => handleDownload());
                }}
                className="flex items-center gap-1 px-2.5 py-1.5 bg-slate-50 dark:bg-zinc-900 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-100 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 text-[10px] sm:text-[11px] font-black rounded-full shadow-sm transition-all cursor-pointer flex-shrink-0"
                title="Direct Download"
              >
                <Download className="w-3 h-3 sm:w-3.5 h-3.5" />
                <span>Download</span>
              </button>
            </div>
          </div>
        </div>

        {/* Ad Space directly between player block and recommended videos list - with tight margin */}
        <div className="w-full my-2">
          <PlayerBannerAd />
        </div>

        {/* Bottom Section: Recommended Videos (Big Cards Form) */}
        <div className="flex flex-col gap-4 mt-2">
          <div className="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-zinc-900">
            <div className="w-1.5 h-4 bg-red-600 rounded-full animate-pulse" />
            <h2 className="font-sans font-black text-xs sm:text-sm uppercase tracking-wider text-slate-800 dark:text-zinc-100 flex items-center gap-1">
              <Sparkles className="w-3.5 h-3.5 text-red-600" />
              Recommended Videos
            </h2>
          </div>

          {/* Grid Layout of Big Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {visibleRecommended.map((recVideo, idx) => (
              <VideoCard
                key={`${recVideo.id}_rec_${idx}`}
                video={recVideo}
                index={idx}
                onClick={(e) => {
                  handleAdClick(e, () => onSelectVideo(recVideo));
                }}
              />
            ))}
          </div>

          {/* Centered Load More Pagination Option */}
          <div className="flex justify-center mt-4 border-t border-slate-100 dark:border-zinc-900 pt-6">
            <button
              onClick={(e) => {
                handleAdClick(e, () => {
                  setVisibleCount((prev) => prev + 10);
                });
              }}
              className="px-8 py-3.5 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-sans font-black text-xs rounded-full transition-all shadow-md hover:scale-[1.02] cursor-pointer flex items-center gap-2"
            >
              <ChevronDown className="w-4 h-4" />
              Load More Recommended Videos
            </button>
          </div>
        </div>

      </div>
    </div>
  );
}
