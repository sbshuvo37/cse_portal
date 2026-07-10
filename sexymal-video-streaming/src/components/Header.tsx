import React from 'react';
import { Sun, Moon, Search, Menu } from 'lucide-react';
import logoImg from '../assets/images/logo.png';

interface HeaderProps {
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  isDarkMode: boolean;
  toggleDarkMode: () => void;
  activeView: 'home' | 'video' | 'admin' | 'about' | 'contact' | 'removal' | 'privacy';
  setActiveView: (view: 'home' | 'video' | 'admin' | 'about' | 'contact' | 'removal' | 'privacy') => void;
  selectedCategory: string;
  setSelectedCategory: (category: string) => void;
  onOpenSidebar: () => void;
}

export default function Header({
  searchQuery,
  setSearchQuery,
  isDarkMode,
  toggleDarkMode,
  activeView,
  setActiveView,
  selectedCategory,
  setSelectedCategory,
  onOpenSidebar,
}: HeaderProps) {
  return (
    <header className="sticky top-0 z-40 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md border-b border-slate-100 dark:border-zinc-900 transition-colors">
      <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        
        {/* Logo - SEXYMAL */}
        <button
          onClick={() => {
            setActiveView('home');
            setSelectedCategory('All');
            setSearchQuery('');
          }}
          className="flex items-center gap-2 group cursor-pointer focus:outline-none"
        >
          <img
            src={logoImg}
            alt="Logo"
            referrerPolicy="no-referrer"
            className="w-10 h-10 rounded-xl object-cover shadow-lg shadow-red-500/20 group-hover:scale-105 transition-transform"
          />
          <span className="font-sans font-black tracking-tighter text-2xl bg-gradient-to-r from-red-600 via-pink-600 to-amber-500 bg-clip-text text-transparent group-hover:opacity-90 transition-opacity">
            SEXYMAL
          </span>
        </button>

        {/* Search Bar */}
        <div className="hidden sm:flex items-center flex-1 max-w-md mx-4 gap-2">
          <div className="relative flex-1 flex items-center">
            <Search className="w-4 h-4 text-slate-400 dark:text-zinc-500 absolute left-3.5 pointer-events-none" />
            <input
              type="text"
              placeholder="Search videos, categories, or tags..."
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                if (activeView !== 'home') {
                  setActiveView('home');
                }
              }}
              className="w-full pl-10 pr-14 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-full text-sm font-medium text-slate-800 dark:text-zinc-200 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3.5 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 font-bold"
              >
                Clear
              </button>
            )}
          </div>
          <button
            onClick={() => {
              if (activeView !== 'home') {
                setActiveView('home');
              }
            }}
            className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-full transition-colors flex items-center gap-1 shadow-sm shadow-red-600/10 cursor-pointer"
          >
            <span>Search</span>
          </button>
        </div>

        {/* Action Controls */}
        <div className="flex items-center gap-2 sm:gap-3">
          
          {/* Light/Dark Toggle */}
          <button
            onClick={toggleDarkMode}
            className="p-2 bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-850 rounded-full transition-all"
            title={isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
          >
            {isDarkMode ? <Sun className="w-4 h-4 text-amber-500" /> : <Moon className="w-4 h-4" />}
          </button>

          {/* Three-line Menu Button for Sidebar drawer */}
          <button
            onClick={onOpenSidebar}
            className="p-2 bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-850 rounded-full transition-all flex items-center justify-center cursor-pointer"
            title="Open Sidebar Menu"
          >
            <Menu className="w-5 h-5 text-slate-700 dark:text-zinc-200" />
          </button>
        </div>
      </div>

      {/* Mobile Search Bar Row */}
      <div className="sm:hidden px-4 pb-3 pt-1 border-b border-slate-100 dark:border-zinc-900">
        <div className="flex items-center gap-2">
          <div className="relative flex-1 flex items-center">
            <Search className="w-4 h-4 text-slate-400 dark:text-zinc-500 absolute left-3 pointer-events-none" />
            <input
              type="text"
              placeholder="Search videos, tags..."
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                if (activeView !== 'home') {
                  setActiveView('home');
                }
              }}
              className="w-full pl-9 pr-12 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-900 rounded-full text-xs font-medium text-slate-800 dark:text-zinc-200 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3.5 text-[10px] text-slate-400 dark:text-zinc-500 font-bold"
              >
                Clear
              </button>
            )}
          </div>
          <button
            onClick={() => {
              if (activeView !== 'home') {
                setActiveView('home');
              }
            }}
            className="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-full transition-colors cursor-pointer"
          >
            Search
          </button>
        </div>
      </div>
    </header>
  );
}
