import React from 'react';
import { Info, Target, Shield, Zap, Compass, Star } from 'lucide-react';

export default function AboutView() {
  return (
    <div className="max-w-4xl mx-auto px-4 py-8 animate-fade-in font-sans">
      <div className="text-center mb-10">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-550/10 text-rose-550 mb-3 border border-rose-500/10 shadow-lg shadow-rose-500/5">
          <Info className="w-7 h-7" />
        </div>
        <h1 className="text-3xl font-black uppercase tracking-tight text-slate-800 dark:text-zinc-100">
          About Us
        </h1>
        <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1 font-mono uppercase tracking-widest">
          The Unfiltered Desi Revolution
        </p>
      </div>

      <div className="space-y-8">
        {/* Welcome Section */}
        <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 sm:p-8 rounded-3xl shadow-sm hover:shadow-md transition-all">
          <p className="text-sm sm:text-base text-slate-600 dark:text-zinc-350 leading-relaxed">
            Welcome to <strong className="text-rose-500 dark:text-rose-400">sexymal.top</strong>, Founded in 2024, the home of bold, unfiltered desi entertainment. We're not just another site—we're a movement to push the Indian industry forward, giving performers the spotlight they deserve and fans the experiences they crave.
          </p>
        </div>

        {/* Mission Card */}
        <div className="bg-gradient-to-br from-rose-500/10 via-rose-550/5 to-transparent border border-rose-500/10 p-6 sm:p-8 rounded-3xl relative overflow-hidden">
          <div className="absolute top-4 right-4 text-rose-500/10">
            <Target className="w-24 h-24 stroke-1" />
          </div>
          <div className="relative z-10 flex items-start gap-4">
            <div className="p-3 bg-rose-500 text-white rounded-2xl shadow-lg shadow-rose-500/20">
              <Target className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-lg font-black uppercase text-slate-800 dark:text-zinc-100 mb-2 tracking-tight">
                Our Mission
              </h2>
              <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
                We believe the Indian adult scene deserves global recognition. It's time for our performers to be celebrated, acknowledged, and given the same respect as stars worldwide. Our mission is simple: make Indian entertainment hotter, bolder, and impossible to ignore.
              </p>
            </div>
          </div>
        </div>

        {/* What We Stand For */}
        <div className="space-y-4">
          <h2 className="text-sm font-black uppercase text-slate-400 dark:text-zinc-500 tracking-widest pl-2">
            What We Stand For
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-5 rounded-2xl flex gap-3.5 items-start">
              <div className="p-2.5 bg-indigo-500/10 text-indigo-500 rounded-xl">
                <Compass className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-xs font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
                  Growth of the Industry
                </h3>
                <p className="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 leading-relaxed">
                  We're here to support the rise of a strong, premium-quality Indian adult industry.
                </p>
              </div>
            </div>

            <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-5 rounded-2xl flex gap-3.5 items-start">
              <div className="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl">
                <Star className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-xs font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
                  Recognition for Performers
                </h3>
                <p className="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 leading-relaxed">
                  Every actor, model, and creator deserves real credit, proper compensation, and high-visibility.
                </p>
              </div>
            </div>

            <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-5 rounded-2xl flex gap-3.5 items-start">
              <div className="p-2.5 bg-rose-500/10 text-rose-550 rounded-xl">
                <Zap className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-xs font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
                  Always Spicy
                </h3>
                <p className="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 leading-relaxed">
                  We keep things extremely fresh, bold, exciting, and unapologetically daring.
                </p>
              </div>
            </div>

            <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-5 rounded-2xl flex gap-3.5 items-start">
              <div className="p-2.5 bg-emerald-500/10 text-emerald-500 rounded-xl">
                <Shield className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-xs font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
                  Real & Relatable
                </h3>
                <p className="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 leading-relaxed">
                  Authentic content that feels close to home, fully authentic, and completely free from fake hype.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Why We're Different */}
        <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 sm:p-8 rounded-3xl space-y-4">
          <h2 className="text-lg font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            Why We're Different
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
            We cut through the clutter. No endless pop-ups, no fake hype. Just pure, raw desi entertainment updated daily so you never run out of reasons to come back.
          </p>
          <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
            Here, fantasies go beyond the ordinary—every visit brings something wilder, hotter, and more uniquely desi.
          </p>
          <div className="pt-3 border-t border-slate-100 dark:border-zinc-850/60">
            <p className="text-sm font-bold text-rose-550 dark:text-rose-400 italic">
              "This isn't just content. It's a revolution in how Indian adult entertainment is seen, shared, and celebrated."
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
