import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X, Layers, ShieldCheck, Mail, Info, FileText, Phone, Award, Lock, ExternalLink, ShieldAlert, Home } from 'lucide-react';
import { handleAdClick } from '../utils/adHelper';
import logoImg from '../assets/images/logo.png';

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
  categories: string[];
  selectedCategory: string;
  setSelectedCategory: (category: string) => void;
  activeView: 'home' | 'video' | 'admin' | 'about' | 'contact' | 'removal' | 'privacy';
  setActiveView: (view: 'home' | 'video' | 'admin' | 'about' | 'contact' | 'removal' | 'privacy') => void;
  setCurrentPage: (page: number) => void;
  setSearchQuery: (query: string) => void;
}

export default function Sidebar({
  isOpen,
  onClose,
  categories,
  selectedCategory,
  setSelectedCategory,
  activeView,
  setActiveView,
  setCurrentPage,
  setSearchQuery,
}: SidebarProps) {
  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Dark Overlay backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 0.5 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
          />

          {/* Sliding Sidebar Drawer */}
          <motion.div
            initial={{ x: '100%' }}
            animate={{ x: 0 }}
            exit={{ x: '100%' }}
            transition={{ type: 'spring', damping: 25, stiffness: 220 }}
            className="fixed top-0 right-0 bottom-0 z-50 w-full max-w-sm bg-white dark:bg-zinc-950 shadow-2xl border-l border-slate-100 dark:border-zinc-800 flex flex-col transition-colors overflow-hidden"
          >
            {/* Header / Brand */}
            <div className="p-5 border-b border-slate-100 dark:border-zinc-900 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <img
                  src={logoImg}
                  alt="Logo"
                  referrerPolicy="no-referrer"
                  className="w-9 h-9 rounded-lg object-cover shadow-md"
                />
                <span className="font-sans font-black tracking-tighter text-xl bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">
                  SEXYMAL
                </span>
              </div>
              <button
                onClick={onClose}
                className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-900 rounded-full transition-all"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Scrollable Container Content */}
            <div className="flex-1 overflow-y-auto p-5 space-y-8">
              
              {/* Home Navigation Button */}
              <div className="space-y-3 text-left">
                <h3 className="text-[10px] font-black uppercase text-slate-400 dark:text-zinc-500 tracking-widest flex items-center gap-1.5">
                  <Home className="w-3.5 h-3.5 text-blue-500" />
                  <span>Main Navigation</span>
                </h3>
                <button
                  onClick={() => {
                    setSelectedCategory('All');
                    setCurrentPage(1);
                    setSearchQuery('');
                    setActiveView('home');
                    onClose();
                  }}
                  className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                    activeView === 'home'
                      ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-500/15'
                      : 'bg-slate-50 dark:bg-zinc-900/60 border-slate-100 dark:border-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                  }`}
                >
                  <span className="flex items-center gap-2">
                    <Home className="w-4 h-4" />
                    <span>Home Grid</span>
                  </span>
                  <span className="text-[9px] px-1.5 py-0.5 bg-black/10 dark:bg-white/10 rounded font-mono">
                    MAIN
                  </span>
                </button>
              </div>

              {/* Dynamic Categories Filtering List */}
              <div className="space-y-3 text-left">
                <h3 className="text-[10px] font-black uppercase text-slate-400 dark:text-zinc-500 tracking-widest flex items-center gap-1.5">
                  <Layers className="w-3.5 h-3.5 text-red-500" />
                  <span>Media Categories</span>
                </h3>
                <div className="flex flex-col gap-1">
                  {categories.map((category) => {
                    const isSelected = selectedCategory === category && activeView === 'home';
                    return (
                      <button
                        key={category}
                        onClick={() => {
                          setSelectedCategory(category);
                          setCurrentPage(1);
                          setSearchQuery('');
                          setActiveView('home');
                          onClose();
                        }}
                        className={`w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between ${
                          isSelected
                            ? 'bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-500 font-black'
                            : 'text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-900/60'
                        }`}
                      >
                        <span>{category}</span>
                        {isSelected && <span className="w-1.5 h-1.5 rounded-full bg-red-600 dark:bg-red-500" />}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Secure Creator Controls */}
              <div className="space-y-3 text-left border-t border-slate-100 dark:border-zinc-900 pt-5">
                <h3 className="text-[10px] font-black uppercase text-slate-400 dark:text-zinc-500 tracking-widest flex items-center gap-1.5">
                  <Lock className="w-3.5 h-3.5 text-amber-500" />
                  <span>Creator Section</span>
                </h3>
                <button
                  onClick={(e) => {
                    handleAdClick(e, () => {
                      setActiveView('admin');
                      onClose();
                    });
                  }}
                  className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                    activeView === 'admin'
                      ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-500/15'
                      : 'bg-slate-50 dark:bg-zinc-900 border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                  }`}
                >
                  <span className="flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4" />
                    <span>Creator Studio Panel</span>
                  </span>
                  <span className="text-[9px] px-1.5 py-0.5 bg-black/10 dark:bg-white/10 rounded font-mono">
                    SECURE
                  </span>
                </button>
              </div>

              {/* Custom View Navigation - About, Contact, DMCA, Privacy */}
              <div className="space-y-3 text-left border-t border-slate-100 dark:border-zinc-900 pt-5">
                <h3 className="text-[10px] font-black uppercase text-slate-400 dark:text-zinc-500 tracking-widest flex items-center gap-1.5 mb-1">
                  <Layers className="w-3.5 h-3.5 text-rose-500" />
                  <span>Information & Support</span>
                </h3>

                <div className="flex flex-col gap-2">
                  {/* About Button */}
                  <button
                    onClick={(e) => {
                      handleAdClick(e, () => {
                        setActiveView('about');
                        onClose();
                      });
                    }}
                    className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                      activeView === 'about'
                        ? 'bg-rose-600 border-rose-600 text-white shadow-md shadow-rose-600/15'
                        : 'bg-slate-50 dark:bg-zinc-900/60 border-slate-100 dark:border-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                    }`}
                  >
                    <span className="flex items-center gap-2">
                      <Info className="w-4 h-4 flex-shrink-0" />
                      <span>About sexymal.top</span>
                    </span>
                    <span className="text-[9px] px-1.5 py-0.5 bg-black/10 dark:bg-white/10 rounded font-mono">
                      ABOUT
                    </span>
                  </button>

                  {/* Video Removal / DMCA Button */}
                  <button
                    onClick={(e) => {
                      handleAdClick(e, () => {
                        setActiveView('removal');
                        onClose();
                      });
                    }}
                    className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                      activeView === 'removal'
                        ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-600/15'
                        : 'bg-slate-50 dark:bg-zinc-900/60 border-slate-100 dark:border-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                    }`}
                  >
                    <span className="flex items-center gap-2">
                      <ShieldAlert className="w-4 h-4 flex-shrink-0" />
                      <span>Video Removal / DMCA</span>
                    </span>
                    <span className="text-[9px] px-1.5 py-0.5 bg-red-500/10 text-red-550 dark:text-red-400 rounded font-mono">
                      DMCA
                    </span>
                  </button>

                  {/* Contact Us Button */}
                  <button
                    onClick={(e) => {
                      handleAdClick(e, () => {
                        setActiveView('contact');
                        onClose();
                      });
                    }}
                    className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                      activeView === 'contact'
                        ? 'bg-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-600/15'
                        : 'bg-slate-50 dark:bg-zinc-900/60 border-slate-100 dark:border-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                    }`}
                  >
                    <span className="flex items-center gap-2">
                      <Mail className="w-4 h-4 flex-shrink-0" />
                      <span>Contact Us!</span>
                    </span>
                    <span className="text-[9px] px-1.5 py-0.5 bg-black/10 dark:bg-white/10 rounded font-mono">
                      FORM
                    </span>
                  </button>

                  {/* Privacy Policy Button */}
                  <button
                    onClick={(e) => {
                      handleAdClick(e, () => {
                        setActiveView('privacy');
                        onClose();
                      });
                    }}
                    className={`w-full text-left px-3.5 py-3 rounded-xl text-xs font-bold transition-all border flex items-center justify-between shadow-sm cursor-pointer ${
                      activeView === 'privacy'
                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-600/15'
                        : 'bg-slate-50 dark:bg-zinc-900/60 border-slate-100 dark:border-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
                    }`}
                  >
                    <span className="flex items-center gap-2">
                      <ShieldCheck className="w-4 h-4 flex-shrink-0" />
                      <span>Privacy Policy</span>
                    </span>
                    <span className="text-[9px] px-1.5 py-0.5 bg-black/10 dark:bg-white/10 rounded font-mono">
                      LEGAL
                    </span>
                  </button>
                </div>
              </div>

            </div>

            {/* Footer inside Sidebar */}
            <div className="p-5 border-t border-slate-100 dark:border-zinc-900 bg-slate-50/50 dark:bg-zinc-950/50 text-center text-[10px] font-bold tracking-wider text-slate-400 font-mono uppercase">
              SEXYMAL v2.4 • EST. 2026
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}
