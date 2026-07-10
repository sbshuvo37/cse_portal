import React, { useState, useEffect } from 'react';
import Header from './components/Header';
import BottomBannerAd from './components/BottomBannerAd';
import VideoCard from './components/VideoCard';
import VideoDetail from './components/VideoDetail';
import AdminPanel from './components/AdminPanel';
import Sidebar from './components/Sidebar';
import AboutView from './components/AboutView';
import ContactView from './components/ContactView';
import VideoRemovalView from './components/VideoRemovalView';
import PrivacyPolicyView from './components/PrivacyPolicyView';
import { Video } from './types';
import { DEFAULT_VIDEOS, DEFAULT_CATEGORIES } from './data';
import { RefreshCw, Video as VideoIcon, Sparkles, Youtube, Instagram, Twitter, ArrowUp, Globe } from 'lucide-react';
import { handleAdClick } from './utils/adHelper';
import { 
  collection, 
  onSnapshot, 
  doc, 
  setDoc, 
  deleteDoc, 
  updateDoc, 
  increment,
  getDocs,
  writeBatch,
  query,
  where
} from 'firebase/firestore';
import { db } from './firebase';


export default function App() {
  // Theme State
  const [isDarkMode, setIsDarkMode] = useState<boolean>(() => {
    const stored = localStorage.getItem('theme');
    return stored === 'dark';
  });

  // Sidebar Drawer open/close state
  const [isSidebarOpen, setIsSidebarOpen] = useState<boolean>(false);

  // Videos State (initially starts empty, then synchronizes from Firestore with local storage as a fast load fallback)
  const [videos, setVideos] = useState<Video[]>(() => {
    const stored = localStorage.getItem('sexymal_videos');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch {
        return DEFAULT_VIDEOS;
      }
    }
    return DEFAULT_VIDEOS;
  });

  // Dynamic Categories State
  const [categories, setCategories] = useState<string[]>(() => {
    const stored = localStorage.getItem('sexymal_categories');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch {
        return DEFAULT_CATEGORIES;
      }
    }
    return DEFAULT_CATEGORIES;
  });

  // Navigation and Filtering state
  const [activeView, setActiveView] = useState<'home' | 'video' | 'admin' | 'about' | 'contact' | 'removal' | 'privacy'>('home');
  const [selectedVideo, setSelectedVideo] = useState<Video | null>(null);
  const [selectedCategory, setSelectedCategory] = useState<string>('All');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [watchedCount, setWatchedCount] = useState<number>(() => {
    const stored = sessionStorage.getItem('sexymal_watched_count');
    return stored ? parseInt(stored, 10) : 0;
  });
  
  // Pagination State (27 videos per page as requested!)
  const [currentPage, setCurrentPage] = useState<number>(1);
  const videosPerPage = 27;

  // Like/Dislike Interactions state per-video
  const [userInteractions, setUserInteractions] = useState<Record<string, 'like' | 'dislike' | null>>(() => {
    const stored = localStorage.getItem('sexymal_interactions');
    return stored ? JSON.parse(stored) : {};
  });

  // Custom confirmation modal state for App.tsx (e.g. for restore defaults)
  const [appConfirmState, setAppConfirmState] = useState<{
    isOpen: boolean;
    title: string;
    message: string;
    onConfirm: () => void;
  }>({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: () => {},
  });

  // Helper: Seed default categories to Firestore if empty
  const seedCategories = async () => {
    const categoriesRef = collection(db, 'categories');
    const snap = await getDocs(categoriesRef);
    if (snap.empty) {
      const batch = writeBatch(db);
      DEFAULT_CATEGORIES.forEach((cat) => {
        const catDoc = doc(db, 'categories', cat);
        batch.set(catDoc, { name: cat });
      });
      await batch.commit();
    }
  };

  // Helper: Seed default videos to Firestore if empty with 0 stats
  const seedVideos = async () => {
    const videosRef = collection(db, 'videos');
    const snap = await getDocs(videosRef);
    if (snap.empty) {
      const batch = writeBatch(db);
      DEFAULT_VIDEOS.forEach((video) => {
        const videoDoc = doc(db, 'videos', video.id);
        batch.set(videoDoc, {
          ...video,
          views: 0,
          likes: 0,
          dislikes: 0
        });
      });
      await batch.commit();
    }
  };

  // Real-time Cloud Firestore synchronization
  useEffect(() => {
    const syncWithCloud = async () => {
      try {
        // Seed default dataset if the cloud Firestore collections are empty
        await seedCategories();
        await seedVideos();

        // One-time check and reset to ensure all video views, likes, dislikes start from 0
        const videosRef = collection(db, 'videos');
        const snap = await getDocs(videosRef);
        const batch = writeBatch(db);
        let hasChanges = false;
        
        snap.forEach((docSnap) => {
          const data = docSnap.data();
          if (data.views !== 0 || data.likes !== 0 || data.dislikes !== 0) {
            batch.update(docSnap.ref, {
              views: 0,
              likes: 0,
              dislikes: 0
            });
            hasChanges = true;
          }
        });

        if (hasChanges) {
          await batch.commit();
          console.log('Successfully reset all video stats (views, likes, dislikes) to zero in Firestore.');
        }
      } catch (err) {
        console.error('Error seeding Firestore library:', err);
      }
    };
    syncWithCloud();

    // Listen to Category list in real-time
    const unsubscribeCats = onSnapshot(collection(db, 'categories'), (snapshot) => {
      const catsList: string[] = [];
      snapshot.forEach((docSnap) => {
        const data = docSnap.data();
        if (data && data.name) {
          catsList.push(data.name);
        }
      });
      const uniqueCats = Array.from(new Set(['All', ...catsList]));
      setCategories(uniqueCats);
    }, (error) => {
      console.error('Firestore Categories read error:', error);
    });

    // Listen to Videos list in real-time
    const unsubscribeVideos = onSnapshot(collection(db, 'videos'), (snapshot) => {
      const videosList: Video[] = [];
      snapshot.forEach((docSnap) => {
        const data = docSnap.data();
        videosList.push({
          ...(data as Video),
          id: docSnap.id
        });
      });
      // Sort descending by creation date
      videosList.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
      setVideos(videosList);
    }, (error) => {
      console.error('Firestore Videos read error:', error);
    });

    return () => {
      unsubscribeCats();
      unsubscribeVideos();
    };
  }, []);

  // Apply Dark Mode Class to HTML Tag
  useEffect(() => {
    const root = window.document.documentElement;
    if (isDarkMode) {
      root.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      root.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  }, [isDarkMode]);

  // Dynamically load the popunder ad network script unconditionally on all views (home, video, pagination, about, dmca, privacy, contact, etc.)
  useEffect(() => {
    const scriptId = 'popunder-ad-script';
    if (!document.getElementById(scriptId)) {
      console.log('Injecting popunder ad script across all views...');
      const script = document.createElement('script');
      script.id = scriptId;
      script.src = 'https://pl29799955.effectivecpmnetwork.com/b5/eb/d5/b5ebd56e3c683e46d2adc4d209cb007b.js';
      script.async = true;
      document.head.appendChild(script);
    }
  }, []);

  // Persist Videos and Categories to localStorage for performance fallback
  useEffect(() => {
    localStorage.setItem('sexymal_videos', JSON.stringify(videos));
  }, [videos]);

  useEffect(() => {
    localStorage.setItem('sexymal_categories', JSON.stringify(categories));
  }, [categories]);

  // Persist Interactions
  useEffect(() => {
    localStorage.setItem('sexymal_interactions', JSON.stringify(userInteractions));
  }, [userInteractions]);

  // Handle Dynamic Theme Toggle
  const toggleDarkMode = () => setIsDarkMode(!isDarkMode);

  // Handle Like & Dislike counting in Cloud database
  const handleLike = async (videoId: string, type: 'like' | 'dislike') => {
    setUserInteractions((prev) => {
      const current = prev[videoId] || null;
      let nextState: 'like' | 'dislike' | null = null;

      if (current === type) {
        nextState = null; // undo
      } else {
        nextState = type;
      }

      let diffLikes = 0;
      let diffDislikes = 0;

      if (current === 'like') diffLikes -= 1;
      if (current === 'dislike') diffDislikes -= 1;

      if (nextState === 'like') diffLikes += 1;
      if (nextState === 'dislike') diffDislikes += 1;

      // Update Firestore document asynchronously
      const videoDocRef = doc(db, 'videos', videoId);
      updateDoc(videoDocRef, {
        likes: increment(diffLikes),
        dislikes: increment(diffDislikes)
      }).catch((err) => console.error('Error saving interaction in cloud Firestore:', err));

      return {
        ...prev,
        [videoId]: nextState,
      };
    });
  };

  // Upload/Publish New Video to Cloud Firestore
  const handleAddVideo = async (newVideoData: Omit<Video, 'id' | 'views' | 'likes' | 'dislikes' | 'createdAt'>) => {
    const videoId = `vid_${Date.now()}`;
    const newVideo: Video = {
      ...newVideoData,
      id: videoId,
      views: 0,
      likes: 0,
      dislikes: 0,
      createdAt: new Date().toISOString(),
    };

    try {
      await setDoc(doc(db, 'videos', videoId), newVideo);
      setActiveView('home');
    } catch (err) {
      console.error('Error adding video to Firestore:', err);
      alert('Error saving video to cloud database. Please try again.');
    }
  };

  // Delete Video from Cloud Firestore
  const handleDeleteVideo = async (id: string) => {
    try {
      await deleteDoc(doc(db, 'videos', id));
    } catch (err) {
      console.error('Error deleting video from Firestore:', err);
      alert('Error deleting video from cloud database.');
    }
  };

  // Update existing Video in Cloud Firestore
  const handleUpdateVideo = async (updatedVideo: Video) => {
    try {
      await setDoc(doc(db, 'videos', updatedVideo.id), updatedVideo);
    } catch (err) {
      console.error('Error updating video in Firestore:', err);
      alert('Error updating video in cloud database.');
    }
  };

  // Add Dynamic Category to Cloud Firestore
  const handleAddCategory = async (newCat: string) => {
    if (!categories.includes(newCat)) {
      try {
        await setDoc(doc(db, 'categories', newCat), { name: newCat });
      } catch (err) {
        console.error('Error adding category to Firestore:', err);
      }
    }
  };

  // Delete Category from Cloud Firestore
  const handleDeleteCategory = async (categoryName: string) => {
    if (categoryName === 'All') return;
    try {
      // 1. Try deleting directly by document ID
      await deleteDoc(doc(db, 'categories', categoryName));
      
      // 2. Query and delete any categories matching the name field
      const q = query(collection(db, 'categories'), where('name', '==', categoryName));
      const querySnap = await getDocs(q);
      const batch = writeBatch(db);
      querySnap.forEach((docSnap) => {
        batch.delete(docSnap.ref);
      });
      await batch.commit();

      if (selectedCategory === categoryName) {
        setSelectedCategory('All');
      }
    } catch (err) {
      console.error('Error deleting category from Firestore:', err);
      alert('Error deleting category from cloud database.');
    }
  };

  // Reset Data to original Seed Data inside Cloud Firestore
  const resetToDefaultSeed = () => {
    setAppConfirmState({
      isOpen: true,
      title: 'Restore Defaults',
      message: 'Are you sure you want to restore the SEXYMAL seed library (26 cinematic streams)? This will replace custom uploads with default seed library in the cloud database.',
      onConfirm: async () => {
        setAppConfirmState(prev => ({ ...prev, isOpen: false }));
        try {
          const batch = writeBatch(db);
          
          // 1. Delete all current videos and write default ones
          videos.forEach((video) => {
            batch.delete(doc(db, 'videos', video.id));
          });
          
          DEFAULT_VIDEOS.forEach((video) => {
            batch.set(doc(db, 'videos', video.id), video);
          });

          // 2. Write default categories
          DEFAULT_CATEGORIES.forEach((cat) => {
            batch.set(doc(db, 'categories', cat), { name: cat });
          });

          await batch.commit();

          setUserInteractions({});
          setSelectedCategory('All');
          setSearchQuery('');
          setCurrentPage(1);
          setActiveView('home');
          alert('Cloud database successfully reset to default seed library!');
        } catch (err) {
          console.error('Error resetting database to default seed:', err);
          alert('Failed to reset cloud database.');
        }
      }
    });
  };

  // Filter video list based on Category and Search queries
  const filteredVideos = videos.filter((video) => {
    const matchesCategory = selectedCategory === 'All' || video.category === selectedCategory;
    const matchesSearch =
      video.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      video.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      video.tags.some((tag) => tag.toLowerCase().includes(searchQuery.toLowerCase())) ||
      video.category.toLowerCase().includes(searchQuery.toLowerCase());

    return matchesCategory && matchesSearch;
  });

  // Paginated display list (Exactly 21 per page!)
  const totalPages = Math.ceil(filteredVideos.length / videosPerPage) || 1;
  const paginatedVideos = filteredVideos.slice(
    (currentPage - 1) * videosPerPage,
    currentPage * videosPerPage
  );

  const handleSelectVideo = (video: Video) => {
    // Record visual View count increment in Cloud Firestore
    const videoDocRef = doc(db, 'videos', video.id);
    updateDoc(videoDocRef, {
      views: increment(1)
    }).catch((err) => console.error('Error incrementing views in cloud Firestore:', err));

    // Increment and persist session watch count for popunder trigger gating
    const newCount = watchedCount + 1;
    setWatchedCount(newCount);
    sessionStorage.setItem('sexymal_watched_count', newCount.toString());

    setSelectedVideo(video);
    setActiveView('video');
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 flex flex-col font-sans transition-colors duration-300">
      
      {/* Modern sticky navigation bar */}
      <Header
        searchQuery={searchQuery}
        setSearchQuery={setSearchQuery}
        isDarkMode={isDarkMode}
        toggleDarkMode={toggleDarkMode}
        activeView={activeView}
        setActiveView={setActiveView}
        selectedCategory={selectedCategory}
        setSelectedCategory={setSelectedCategory}
        onOpenSidebar={() => setIsSidebarOpen(true)}
      />

      {/* Sliding Sidebar drawer component */}
      <Sidebar
        isOpen={isSidebarOpen}
        onClose={() => setIsSidebarOpen(false)}
        categories={categories}
        selectedCategory={selectedCategory}
        setSelectedCategory={setSelectedCategory}
        activeView={activeView}
        setActiveView={setActiveView}
        setCurrentPage={setCurrentPage}
        setSearchQuery={setSearchQuery}
      />

      {/* Main interactive router viewport */}
      <main className="flex-1 pb-4">
        {activeView === 'home' && (
          <div className="max-w-7xl mx-auto px-4 pt-2 pb-6">
            
            {/* Category Filter Chips Carousel Row */}
            <div className="flex items-center justify-between gap-4 mb-1 overflow-x-auto pb-1 scrollbar-none">
              <div className="flex items-center gap-2">
                {categories.map((category) => (
                  <button
                    key={category}
                    onClick={() => {
                      setSelectedCategory(category);
                      setCurrentPage(1); // Reset page indices
                    }}
                    className={`px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border cursor-pointer ${
                      selectedCategory === category
                        ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-500/15'
                        : 'bg-white dark:bg-zinc-900 border-slate-100 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-850'
                    }`}
                  >
                    {category}
                  </button>
                ))}
              </div>

              {/* Developer Restore Seeds controller */}
              <button
                onClick={(e) => {
                  handleAdClick(e, () => resetToDefaultSeed());
                }}
                className="flex items-center gap-1 px-3 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-500 text-[10px] font-black uppercase rounded-lg border border-amber-500/15 transition-all flex-shrink-0"
                title="Restore default database layout"
              >
                <RefreshCw className="w-3 h-3" />
                <span>Restore Seeds</span>
              </button>
            </div>

            {/* Empty list search placeholder */}
            {filteredVideos.length === 0 ? (
              <div className="py-20 flex flex-col items-center justify-center text-center max-w-sm mx-auto">
                <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-zinc-900 flex items-center justify-center mb-4">
                  <VideoIcon className="w-8 h-8 text-slate-400" />
                </div>
                <h3 className="font-sans font-black text-slate-800 dark:text-zinc-200">
                  No Videos Found
                </h3>
                <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1.5 leading-relaxed">
                  We couldn't locate media matching your keywords or filtering category. Publish a new stream in the Studio Panel!
                </p>
              </div>
            ) : (
              <div>
                {/* Homepage Grid: Displaying 21 videos per page layout */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-2">
                  {paginatedVideos.map((video, idx) => (
                    <VideoCard
                      key={`${video.id}_home_${idx}`}
                      video={video}
                      index={idx}
                      onClick={(e) => {
                        handleAdClick(e, () => handleSelectVideo(video));
                      }}
                    />
                  ))}
                </div>

                {/* Modern Centered Numbered Pagination controls */}
                {totalPages > 1 && (
                  <div className="flex flex-col items-center justify-center mt-5 gap-3 border-t border-slate-100 dark:border-zinc-900 pt-4">
                    
                    <div className="flex items-center gap-1.5">
                      {/* Previous Page */}
                      <button
                        disabled={currentPage === 1}
                        onClick={(e) => {
                          handleAdClick(e, () => {
                            setCurrentPage((prev) => Math.max(1, prev - 1));
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                          });
                        }}
                        className={`px-3.5 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                          currentPage === 1
                            ? 'bg-slate-50 dark:bg-zinc-950/20 border-slate-100 dark:border-zinc-900 text-slate-300 dark:text-zinc-600 cursor-not-allowed'
                            : 'bg-white dark:bg-zinc-900 border-slate-150 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-850'
                        }`}
                      >
                        Prev
                      </button>

                      {/* Numbered Buttons */}
                      {Array.from({ length: totalPages }, (_, index) => {
                        const pageNum = index + 1;
                        const isCurrent = currentPage === pageNum;
                        return (
                          <button
                            key={pageNum}
                            onClick={(e) => {
                              handleAdClick(e, () => {
                                setCurrentPage(pageNum);
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                              });
                            }}
                            className={`w-9 h-9 rounded-xl text-xs font-bold transition-all border cursor-pointer flex items-center justify-center ${
                              isCurrent
                                ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-500/15 font-black'
                                : 'bg-white dark:bg-zinc-900 border-slate-150 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-850'
                            }`}
                          >
                            {pageNum}
                          </button>
                        );
                      })}

                      {/* Next Page */}
                      <button
                        disabled={currentPage === totalPages}
                        onClick={(e) => {
                          handleAdClick(e, () => {
                            setCurrentPage((prev) => Math.min(totalPages, prev + 1));
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                          });
                        }}
                        className={`px-3.5 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer ${
                          currentPage === totalPages
                            ? 'bg-slate-50 dark:bg-zinc-950/20 border-slate-100 dark:border-zinc-900 text-slate-300 dark:text-zinc-600 cursor-not-allowed'
                            : 'bg-white dark:bg-zinc-900 border-slate-150 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-850'
                        }`}
                      >
                        Next
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {/* Dynamic Detail Viewer Viewport */}
        {activeView === 'video' && selectedVideo && (
          <VideoDetail
            video={selectedVideo}
            allVideos={videos}
            onSelectVideo={handleSelectVideo}
            onLike={handleLike}
            userInteractions={userInteractions}
          />
        )}

        {/* Secure Admin Studio Dashboard Viewport */}
        {activeView === 'admin' && (
          <AdminPanel
            videos={videos}
            categories={categories}
            onAddVideo={handleAddVideo}
            onDeleteVideo={handleDeleteVideo}
            onAddCategory={handleAddCategory}
            onDeleteCategory={handleDeleteCategory}
            onUpdateVideo={handleUpdateVideo}
          />
        )}

        {/* About Us Viewport */}
        {activeView === 'about' && (
          <AboutView />
        )}

        {/* Contact Us Viewport */}
        {activeView === 'contact' && (
          <ContactView />
        )}

        {/* Video Removal Viewport */}
        {activeView === 'removal' && (
          <VideoRemovalView />
        )}

        {/* Privacy Policy Viewport */}
        {activeView === 'privacy' && (
          <PrivacyPolicyView />
        )}
      </main>

      {/* 3. BOTTOM BANNER AD (Reserved footer slot) */}
      <BottomBannerAd />

      {/* Simplified, elegant platform footer */}
      <footer className="bg-white dark:bg-zinc-950 border-t border-slate-100 dark:border-zinc-900/60 transition-colors py-8 px-4 sm:px-6">
        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
          <div className="text-xs font-semibold text-slate-400 dark:text-zinc-500">
            © 2026 SEXYMAL Media Networks. All rights reserved.
          </div>
          <div className="text-[10px] text-slate-350 dark:text-zinc-600 font-medium sm:text-right">
            Licensed under cinematic streaming distribution guidelines.
          </div>
        </div>
      </footer>

      {/* Custom Confirmation Modal */}
      {appConfirmState.isOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 dark:bg-black/70 backdrop-blur-sm">
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-xl text-left animate-in fade-in zoom-in-95 duration-150">
            <h3 className="text-base font-black text-slate-800 dark:text-zinc-100 uppercase tracking-wider mb-2">
              {appConfirmState.title}
            </h3>
            <p className="text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-6">
              {appConfirmState.message}
            </p>
            <div className="flex justify-end gap-2.5">
              <button
                onClick={() => setAppConfirmState(prev => ({ ...prev, isOpen: false }))}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-slate-700 dark:text-zinc-200 text-xs font-bold rounded-xl transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={appConfirmState.onConfirm}
                className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all shadow cursor-pointer"
              >
                Confirm Restore
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
