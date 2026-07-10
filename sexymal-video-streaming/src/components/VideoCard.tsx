import React from 'react';
import { Play, Eye, ThumbsUp, Calendar } from 'lucide-react';
import { motion } from 'motion/react';
import { Video } from '../types';
import { getVideoThumbnail } from '../data';

interface VideoCardProps {
  key?: string | number;
  video: Video;
  index: number;
  onClick: (e: any) => void;
}

export default function VideoCard({ video, index, onClick }: VideoCardProps) {
  // Format Views count for display (e.g., 105.3K)
  const formatViews = (views: number) => {
    if (views >= 1000000) {
      return (views / 1000000).toFixed(1) + 'M';
    }
    if (views >= 1000) {
      return (views / 1000).toFixed(1) + 'K';
    }
    return views.toString();
  };

  // Format date to show nicely (e.g., June 15, 2026)
  const formatDate = (dateStr: string) => {
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });
    } catch {
      return dateStr;
    }
  };

  // Safe mock duration
  const mockDuration = `${7 + (index % 11)}:${10 + ((index * 7) % 50)}`;

  // Get resolved thumbnail URL
  const thumbnail = getVideoThumbnail(video, index);

  return (
    <motion.div
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3, delay: Math.min(index * 0.03, 0.4) }}
      onClick={onClick}
      className="group flex flex-col bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl overflow-hidden cursor-pointer shadow-sm hover:shadow-md hover:border-red-500/10 dark:hover:border-red-500/10 transition-all text-left"
    >
      {/* Thumbnail with overlay duration and play hover effect */}
      <div className="relative aspect-video w-full overflow-hidden bg-slate-900">
        <img
          src={thumbnail}
          alt={video.title}
          referrerPolicy="no-referrer"
          loading="lazy"
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
        />
        
        {/* Play Icon Overlay */}
        <div className="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-300">
          <div className="w-12 h-12 rounded-full bg-red-600 flex items-center justify-center text-white shadow-lg shadow-red-600/30 transform scale-90 group-hover:scale-100 transition-transform">
            <Play className="w-6 h-6 fill-current ml-0.5" />
          </div>
        </div>

        {/* Dynamic Duration Badge */}
        <span className="absolute bottom-2.5 right-2.5 bg-black/85 text-[10px] font-bold text-white px-2 py-0.5 rounded tracking-wide font-mono">
          {mockDuration}
        </span>

        {/* Category Badge */}
        <span className="absolute top-2.5 left-2.5 bg-red-600/90 backdrop-blur-sm text-[9px] font-black uppercase text-white px-2.5 py-0.5 rounded-full tracking-wider shadow-sm">
          {video.category}
        </span>
      </div>

      {/* Content Details */}
      <div className="p-1.5 py-2 px-2.5 flex flex-col justify-between gap-1">
        <div>
          <h3 className="font-sans font-bold text-slate-800 dark:text-zinc-100 text-[11px] sm:text-xs md:text-sm leading-tight group-hover:text-red-600 dark:group-hover:text-red-500 transition-colors line-clamp-1">
            {video.title}
          </h3>
        </div>

        <div className="flex items-center justify-between border-t border-slate-50 dark:border-zinc-800/50 pt-1 text-[9px] sm:text-[10px] font-bold tracking-wider text-slate-400 dark:text-zinc-500 font-mono uppercase">
          <span className="flex items-center gap-0.5">
            <Eye className="w-3 h-3 text-slate-300 dark:text-zinc-600" />
            {formatViews(video.views)} Views
          </span>
          <span className="flex items-center gap-0.5">
            <ThumbsUp className="w-3 h-3 text-slate-300 dark:text-zinc-600" />
            {video.likes} Likes
          </span>
        </div>
      </div>
    </motion.div>
  );
}
