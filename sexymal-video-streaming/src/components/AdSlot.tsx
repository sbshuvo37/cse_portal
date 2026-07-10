import React, { useState } from 'react';
import { X, ExternalLink, ShieldAlert } from 'lucide-react';
import { AdPosition } from '../types';

interface AdSlotProps {
  position: AdPosition;
}

export default function AdSlot({ position }: AdSlotProps) {
  const [closed, setClosed] = useState(false);

  if (closed) return null;

  // Render different styling and content based on position
  const getAdDetails = () => {
    switch (position) {
      case 'top':
        return {
          containerClass: 'w-full bg-slate-100 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 py-3 px-4 transition-all',
          wrapperClass: 'max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-xs',
          badgeText: 'SPONSOR BANNER',
          title: 'Premium Cloud Hosting Starting at $3.50/mo',
          desc: 'Get near-instant provisioning, free SSL, global CDN, and scale-to-zero capabilities with cloud run container templates.',
          actionText: 'Deploy Now',
          link: 'https://ai.studio/build',
        };
      case 'bottom':
        return {
          containerClass: 'w-full bg-slate-50 dark:bg-zinc-950 border-t border-slate-200 dark:border-zinc-800 py-4 px-4 mt-8 transition-all',
          wrapperClass: 'max-w-4xl mx-auto flex flex-col md:flex-row items-center gap-4 justify-between',
          badgeText: 'RECOMMENDED AD',
          title: 'Unleash Your Creativity with Gemini 1.5 Flash',
          desc: 'Integrate multi-modal models, text translation, and audio processing seamlessly into your backend stacks using the official GenAI SDK.',
          actionText: 'Get API Key',
          link: 'https://ai.studio/build',
        };
      case 'social':
        return {
          containerClass: 'w-full bg-gradient-to-r from-red-500/10 to-pink-500/10 dark:from-red-950/20 dark:to-pink-950/20 border border-red-200 dark:border-red-950/50 rounded-xl p-4 my-4 transition-all shadow-sm',
          wrapperClass: 'flex flex-col sm:flex-row items-start sm:items-center gap-4 justify-between text-sm',
          badgeText: 'PARTNER AD',
          title: 'Connect with Sexymal Pro Creators',
          desc: 'Access ultra-high performance streaming, premium video quality controllers, and exclusive raw cinematic uploads.',
          actionText: 'Join Sexymal Pro',
          link: '#pro',
        };
    }
  };

  const ad = getAdDetails();

  return (
    <div className={ad.containerClass}>
      <div className={ad.wrapperClass}>
        <div className="flex items-start gap-3">
          <div className="bg-amber-500 text-[10px] font-bold text-white px-2 py-0.5 rounded-sm tracking-wider flex-shrink-0 mt-0.5 shadow-sm">
            {ad.badgeText}
          </div>
          <div className="text-left">
            <h4 className="font-semibold text-slate-800 dark:text-zinc-200 flex items-center gap-1">
              {ad.title}
              <ExternalLink className="w-3 h-3 text-slate-400" />
            </h4>
            <p className="text-slate-500 dark:text-zinc-400 text-xs mt-1 leading-relaxed">
              {ad.desc}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3 flex-shrink-0 w-full sm:w-auto justify-end mt-2 sm:mt-0">
          <a
            href={ad.link}
            target="_blank"
            rel="noopener noreferrer"
            className="px-4 py-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium text-xs rounded-full transition-colors text-center shadow-md shadow-red-500/15"
          >
            {ad.actionText}
          </a>
          <button
            onClick={() => setClosed(true)}
            className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 rounded-full hover:bg-slate-200 dark:hover:bg-zinc-800 transition-all"
            title="Dismiss Ad"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  );
}
