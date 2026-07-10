import React, { useState } from 'react';
import { Eye, Plus, ShieldCheck, Video as VideoIcon, Trash2, Key, HelpCircle, Code, Layers, Sparkles, PlusCircle, UploadCloud, Pencil, X } from 'lucide-react';
import { Video } from '../types';

interface AdminPanelProps {
  videos: Video[];
  categories: string[];
  onAddVideo: (video: Omit<Video, 'id' | 'views' | 'likes' | 'dislikes' | 'createdAt'>) => void;
  onDeleteVideo: (id: string) => void;
  onAddCategory: (category: string) => void;
  onDeleteCategory: (category: string) => void;
  onUpdateVideo: (video: Video) => void;
}

export default function AdminPanel({
  videos,
  categories,
  onAddVideo,
  onDeleteVideo,
  onAddCategory,
  onDeleteCategory,
  onUpdateVideo,
}: AdminPanelProps) {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [password, setPassword] = useState('');
  const [authError, setAuthError] = useState('');

  // Form states
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [muxPlaybackId, setMuxPlaybackId] = useState('');
  const [videoUrl, setVideoUrl] = useState('');
  const [thumbnailUrl, setThumbnailUrl] = useState('');
  const [selectedCategory, setSelectedCategory] = useState(categories[1] || 'Action Sports');
  const [tagsInput, setTagsInput] = useState('');
  const [newCategoryName, setNewCategoryName] = useState('');

  // Editing state
  const [editingVideo, setEditingVideo] = useState<Video | null>(null);

  // Custom interactive inline category creation state
  const [isAddingNewCategory, setIsAddingNewCategory] = useState(false);
  const [newCategoryInputName, setNewCategoryInputName] = useState('');

  // Visualizer tabs
  const [activeTab, setActiveTab] = useState<'upload' | 'videos' | 'categories' | 'seo'>('upload');

  // Custom confirmation modal state
  const [confirmState, setConfirmState] = useState<{
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

  const showConfirm = (title: string, message: string, onConfirm: () => void) => {
    setConfirmState({
      isOpen: true,
      title,
      message,
      onConfirm: () => {
        onConfirm();
        setConfirmState(prev => ({ ...prev, isOpen: false }));
      },
    });
  };

  // Submit password
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const msgBuffer = new TextEncoder().encode(password);
      const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const enteredHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
      
      const secureHash = 'af5217f1bab0c92aa47ffb7917b55e3bb69824a9683d5a1a714d52cc6679d9c0';
      if (enteredHash === secureHash) {
        setIsAuthenticated(true);
        setAuthError('');
      } else {
        setAuthError('Incorrect secret security key.');
      }
    } catch (err) {
      console.error(err);
      setAuthError('Secure authentication error.');
    }
  };

  // Pre-populate form for editing
  const startEditVideo = (video: Video) => {
    setEditingVideo(video);
    setTitle(video.title);
    setDescription(video.description);
    setMuxPlaybackId(video.muxPlaybackId || '');
    setVideoUrl(video.videoUrl || '');
    setThumbnailUrl(video.thumbnailUrl || '');
    setSelectedCategory(video.category);
    setTagsInput(video.tags.join(', '));
    setIsAddingNewCategory(false);
    setActiveTab('upload');
  };

  const handleCancelEdit = () => {
    setEditingVideo(null);
    setTitle('');
    setDescription('');
    setMuxPlaybackId('');
    setVideoUrl('');
    setThumbnailUrl('');
    setSelectedCategory(categories[1] || 'Action Sports');
    setTagsInput('');
    setIsAddingNewCategory(false);
  };

  // Submit video
  const handleUploadVideo = (e: React.FormEvent) => {
    e.preventDefault();
    if (!title || !description) return;

    // Determine target category
    let finalCategory = selectedCategory;
    if (isAddingNewCategory) {
      const cleanCategory = newCategoryInputName.trim();
      if (!cleanCategory) {
        alert('Please specify a valid custom category name.');
        return;
      }
      onAddCategory(cleanCategory);
      finalCategory = cleanCategory;
    }

    // Determine final playback URL. If Mux ID provided, map to standard HLS URL format
    const finalVideoUrl = videoUrl.trim() 
      ? videoUrl.trim() 
      : muxPlaybackId.trim()
        ? `https://stream.mux.com/${muxPlaybackId.trim()}.m3u8`
        : 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'; // reliable fallback

    const tagsArray = tagsInput
      .split(',')
      .map(tag => tag.trim().toLowerCase())
      .filter(tag => tag.length > 0);

    if (editingVideo) {
      onUpdateVideo({
        ...editingVideo,
        title: title.trim(),
        description: description.trim(),
        muxPlaybackId: muxPlaybackId.trim() || undefined,
        videoUrl: finalVideoUrl,
        thumbnailUrl: thumbnailUrl.trim() || undefined,
        category: finalCategory,
        tags: tagsArray,
      });
      setEditingVideo(null);
      alert('Video successfully updated in the SEXYMAL video collection!');
    } else {
      onAddVideo({
        title: title.trim(),
        description: description.trim(),
        muxPlaybackId: muxPlaybackId.trim() || undefined,
        videoUrl: finalVideoUrl,
        thumbnailUrl: thumbnailUrl.trim() || undefined,
        category: finalCategory,
        tags: tagsArray,
      });
      alert('Video successfully added to the SEXYMAL video collection!');
    }

    // Reset Form & notify
    setTitle('');
    setDescription('');
    setMuxPlaybackId('');
    setVideoUrl('');
    setThumbnailUrl('');
    setTagsInput('');
    setNewCategoryInputName('');
    setIsAddingNewCategory(false);
  };

  // Submit custom category
  const handleAddCategory = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCategoryName.trim()) return;
    onAddCategory(newCategoryName.trim());
    setSelectedCategory(newCategoryName.trim());
    setNewCategoryName('');
  };

  // Generate Dynamic Sitemap XML
  const generateSitemapXml = () => {
    const today = new Date().toISOString().split('T')[0];
    let sitemap = `<?xml version="1.0" encoding="UTF-8"?>\n`;
    sitemap += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n`;
    
    // Homepage
    sitemap += `  <url>\n`;
    sitemap += `    <loc>https://sexymal-streaming.com/</loc>\n`;
    sitemap += `    <lastmod>${today}</lastmod>\n`;
    sitemap += `    <changefreq>daily</changefreq>\n`;
    sitemap += `    <priority>1.0</priority>\n`;
    sitemap += `  </url>\n`;

    // Dynamic Videos
    videos.forEach(video => {
      sitemap += `  <url>\n`;
      sitemap += `    <loc>https://sexymal-streaming.com/video/${video.id}</loc>\n`;
      sitemap += `    <lastmod>${today}</lastmod>\n`;
      sitemap += `    <changefreq>weekly</changefreq>\n`;
      sitemap += `    <priority>0.8</priority>\n`;
      sitemap += `  </url>\n`;
    });

    sitemap += `</urlset>`;
    return sitemap;
  };

  // Generate dynamic head metadata for SSR representation
  const generateSsrMetadata = (videoItem?: Video) => {
    const item = videoItem || videos[0];
    if (!item) return '';
    return `<title>${item.title} - SEXYMAL Stream</title>
<meta name="description" content="${item.description.slice(0, 150)}..." />
<meta name="keywords" content="${item.tags.join(', ')}, ${item.category}" />
<meta property="og:title" content="${item.title}" />
<meta property="og:description" content="${item.description.slice(0, 150)}..." />
<meta property="og:image" content="${thumbnailUrl || `https://image.mux.com/${item.muxPlaybackId}/thumbnail.jpg`}" />
<meta property="og:type" content="video.other" />
<meta name="twitter:card" content="summary_large_image" />`;
  };

  // Login Screen
  if (!isAuthenticated) {
    return (
      <div className="max-w-md mx-auto px-4 py-20 text-left">
        <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-8 rounded-2xl shadow-xl">
          <div className="flex items-center gap-3 text-red-600 mb-6">
            <div className="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center">
              <Key className="w-5 h-5 text-red-600" />
            </div>
            <div>
              <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100">
                SEXYMAL Studio
              </h2>
              <p className="text-slate-400 dark:text-zinc-500 text-xs">Password Protected</p>
            </div>
          </div>

          <form onSubmit={handleLogin} className="flex flex-col gap-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                Secret Security Key
              </label>
              <input
                type="password"
                placeholder="Enter password..."
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-medium text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
              />
            </div>

            {authError && (
              <p className="text-red-500 text-xs font-semibold">{authError}</p>
            )}

            <button
              type="submit"
              className="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-black text-sm rounded-xl transition-all shadow-md shadow-red-500/15"
            >
              Sign In to Studio
            </button>
          </form>

          <div className="mt-6 border-t border-slate-100 dark:border-zinc-800/60 pt-4 flex items-start gap-2 text-[11px] font-medium text-slate-400 dark:text-zinc-500">
            <HelpCircle className="w-4 h-4 text-slate-300 dark:text-zinc-600 flex-shrink-0" />
            <p>
              Authorized creators and administrators only. Enter your secure key to gain panel access.
            </p>
          </div>
        </div>
      </div>
    );
  }

  // Admin Studio Dashboard UI
  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-6 text-left transition-colors">
      <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 dark:border-zinc-800 pb-5 mb-6">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-sans font-black text-2xl text-slate-800 dark:text-zinc-50">
              Creator Studio Panel
            </h1>
            <span className="bg-emerald-500/10 text-emerald-500 text-[10px] font-black px-2.5 py-0.5 rounded-full tracking-wider uppercase border border-emerald-500/20 flex items-center gap-1">
              <ShieldCheck className="w-3 h-3" /> Secure Account
            </span>
          </div>
          <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1">
            Dynamic content publisher with automatic metadata, Mux integration, sitemap engine, and category overrides.
          </p>
        </div>

        {/* Tab buttons */}
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setActiveTab('upload')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'upload'
                ? 'bg-red-600 text-white'
                : 'bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
            }`}
          >
            <Plus className="w-3.5 h-3.5 inline mr-1" /> Publish Video
          </button>
          <button
            onClick={() => setActiveTab('videos')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'videos'
                ? 'bg-red-600 text-white'
                : 'bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
            }`}
          >
            <VideoIcon className="w-3.5 h-3.5 inline mr-1" /> Library ({videos.length})
          </button>
          <button
            onClick={() => setActiveTab('categories')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'categories'
                ? 'bg-red-600 text-white'
                : 'bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
            }`}
          >
            <Layers className="w-3.5 h-3.5 inline mr-1" /> Categories & Tags
          </button>
          <button
            onClick={() => setActiveTab('seo')}
            className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
              activeTab === 'seo'
                ? 'bg-red-600 text-white'
                : 'bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800'
            }`}
          >
            <Code className="w-3.5 h-3.5 inline mr-1" /> Live XML Sitemap & SEO
          </button>
        </div>
      </div>

      {/* Tab 1: Upload / Publish Form */}
      {activeTab === 'upload' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* Main Upload form (Left 2 columns) */}
          <div className="lg:col-span-2 bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
            <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100 mb-5 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                {editingVideo ? (
                  <Pencil className="w-5 h-5 text-blue-500 animate-pulse" />
                ) : (
                  <PlusCircle className="w-5 h-5 text-red-600" />
                )}
                <span>{editingVideo ? 'Edit Media Details' : 'Publish Dynamic Media'}</span>
              </div>
              {editingVideo && (
                <button
                  type="button"
                  onClick={handleCancelEdit}
                  className="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-850 dark:hover:bg-zinc-800 text-[10px] font-black uppercase text-slate-500 dark:text-zinc-400 rounded-lg transition-colors"
                >
                  Cancel Edit
                </button>
              )}
            </h2>

            <form onSubmit={handleUploadVideo} className="flex flex-col gap-5">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                    Video Title *
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Enter visual title..."
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    className="w-full px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                    Media Category *
                  </label>
                  <select
                    value={isAddingNewCategory ? 'NEW_CATEGORY' : selectedCategory}
                    onChange={(e) => {
                      if (e.target.value === 'NEW_CATEGORY') {
                        setIsAddingNewCategory(true);
                      } else {
                        setIsAddingNewCategory(false);
                        setSelectedCategory(e.target.value);
                      }
                    }}
                    className="w-full px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                  >
                    {categories.filter(c => c !== 'All').map((category) => (
                      <option key={category} value={category}>
                        {category}
                      </option>
                    ))}
                    <option value="NEW_CATEGORY" className="text-red-500 font-bold">+ Create New Category...</option>
                  </select>

                  {isAddingNewCategory && (
                    <div className="mt-2 animate-fadeIn">
                      <input
                        type="text"
                        required
                        placeholder="Type new category name..."
                        value={newCategoryInputName}
                        onChange={(e) => setNewCategoryInputName(e.target.value)}
                        className="w-full px-4 py-2 bg-red-50/50 dark:bg-zinc-950 border border-red-200 dark:border-zinc-800 rounded-xl text-xs font-bold text-slate-800 dark:text-zinc-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                      />
                    </div>
                  )}
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                  Description / Caption *
                </label>
                <textarea
                  required
                  placeholder="Explain video topic, highlight visual benchmarks, add meta credits..."
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  className="w-full px-4 py-3 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 h-28 resize-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 dark:border-zinc-800 pt-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Mux Playback ID (Fallback Fallback)
                  </label>
                  <p className="text-[10px] text-slate-400 dark:text-zinc-500 mb-2">
                    Auto-resolves thumbnail fallback link.
                  </p>
                  <input
                    type="text"
                    placeholder="e.g. DS00S6Aax9gEdS019KK1"
                    value={muxPlaybackId}
                    onChange={(e) => setMuxPlaybackId(e.target.value)}
                    className="w-full px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Direct Video Stream URL (MP4 / HLS)
                  </label>
                  <p className="text-[10px] text-slate-400 dark:text-zinc-500 mb-2">
                    Enter direct streaming link.
                  </p>
                  <input
                    type="url"
                    placeholder="https://...mp4"
                    value={videoUrl}
                    onChange={(e) => setVideoUrl(e.target.value)}
                    className="w-full px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Thumbnail Image
                  </label>
                  <p className="text-[10px] text-slate-400 dark:text-zinc-500 mb-2">
                    Directly upload an image file or paste a custom web image link.
                  </p>
                  
                  <div className="space-y-3">
                    {/* Drag and Drop File Input Area */}
                    <div className="flex gap-3">
                      <div className="flex-1 relative border border-dashed border-slate-200 dark:border-zinc-800 hover:border-red-500 dark:hover:border-red-500 rounded-xl p-3 flex flex-col items-center justify-center bg-slate-50/50 dark:bg-zinc-950/50 transition-colors cursor-pointer text-center group">
                        <input
                          type="file"
                          accept="image/*"
                          onChange={(e) => {
                            const file = e.target.files?.[0];
                            if (file) {
                              const reader = new FileReader();
                              reader.onloadend = () => {
                                setThumbnailUrl(reader.result as string);
                              };
                              reader.readAsDataURL(file);
                            }
                          }}
                          className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                        <UploadCloud className="w-5 h-5 text-slate-400 dark:text-zinc-500 group-hover:text-red-500 transition-colors mb-0.5" />
                        <span className="text-[10px] font-bold text-slate-500 dark:text-zinc-400">
                          Click to Upload Local Image
                        </span>
                        <span className="text-[8px] text-slate-400 dark:text-zinc-500">
                          Resized PNG, JPG, WEBP formats
                        </span>
                      </div>

                      {/* Preview Box if thumbnailUrl exists */}
                      {thumbnailUrl && (
                        <div className="w-20 h-[64px] rounded-xl border border-slate-200 dark:border-zinc-800 overflow-hidden relative group/preview bg-slate-100 dark:bg-zinc-900 flex-shrink-0 flex items-center justify-center">
                          <img 
                            src={thumbnailUrl} 
                            alt="Preview" 
                            className="w-full h-full object-cover" 
                          />
                          <button
                            type="button"
                            onClick={() => setThumbnailUrl('')}
                            className="absolute inset-0 bg-black/70 opacity-0 group-hover/preview:opacity-100 transition-opacity flex items-center justify-center text-white text-[9px] font-bold"
                          >
                            Remove
                          </button>
                        </div>
                      )}
                    </div>

                    {/* Or Paste URL Option */}
                    <div className="relative">
                      <span className="absolute inset-y-0 left-3 flex items-center text-[10px] font-black text-slate-400 dark:text-zinc-500 uppercase">
                        Or Link:
                      </span>
                      <input
                        type="url"
                        placeholder="https://images.unsplash.com/..."
                        value={thumbnailUrl.startsWith('data:') ? '' : thumbnailUrl}
                        onChange={(e) => setThumbnailUrl(e.target.value)}
                        className="w-full pl-16 pr-4 py-2 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Sorting Tags (Comma Separated) *
                  </label>
                  <p className="text-[10px] text-slate-400 dark:text-zinc-500 mb-2">
                    Critical for dynamic recommendations.
                  </p>
                  <input
                    type="text"
                    required
                    placeholder="extreme, nature, speed, alps"
                    value={tagsInput}
                    onChange={(e) => setTagsInput(e.target.value)}
                    className="w-full px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
                  />
                </div>
              </div>

              <div className="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                  type="submit"
                  className={`flex-1 py-3 text-white font-black text-sm rounded-xl transition-all shadow-lg cursor-pointer text-center ${
                    editingVideo 
                      ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/15' 
                      : 'bg-red-600 hover:bg-red-700 shadow-red-500/15'
                  }`}
                >
                  {editingVideo ? 'Save and Apply Changes' : 'Launch & Broadcast Media'}
                </button>
                {editingVideo && (
                  <button
                    type="button"
                    onClick={handleCancelEdit}
                    className="sm:w-1/3 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 font-bold text-sm rounded-xl transition-all"
                  >
                    Cancel
                  </button>
                )}
              </div>
            </form>
          </div>

          {/* Guidelines Sidebar (Right column) */}
          <div className="flex flex-col gap-5">
            <div className="bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-900 p-5 rounded-2xl">
              <h3 className="font-sans font-black text-xs uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                <Sparkles className="w-4 h-4 text-amber-500" /> Dynamic Fallback Engine
              </h3>
              <p className="text-slate-500 dark:text-zinc-400 text-xs leading-relaxed">
                SEXYMAL video templates process thumbnails automatically:
              </p>
              <ul className="text-slate-500 dark:text-zinc-400 text-[11px] list-disc ml-4 mt-2.5 space-y-2">
                <li>
                  <span className="font-bold text-slate-700 dark:text-zinc-300">Custom URL:</span> Standard absolute graphic paths display instantly.
                </li>
                <li>
                  <span className="font-bold text-slate-700 dark:text-zinc-300">Mux API Fallback:</span> If a Mux ID is present, the applet queries <code className="text-red-500 font-mono">image.mux.com</code> directly for lossless playback thumbnails.
                </li>
                <li>
                  <span className="font-bold text-slate-700 dark:text-zinc-300">Unsplash Category Sync:</span> If no Mux ID is added, high-contrast visual backdrops matching the category are applied automatically.
                </li>
              </ul>
            </div>

            <div className="bg-gradient-to-tr from-zinc-900 to-zinc-950 border border-zinc-800 p-5 rounded-2xl text-white">
              <span className="text-[10px] font-black tracking-widest text-red-500 uppercase block mb-1">
                Security Sandbox
              </span>
              <h4 className="font-sans font-bold text-sm text-zinc-100 mb-2">
                Local State Preservation
              </h4>
              <p className="text-zinc-400 text-xs leading-relaxed">
                Any media published here persists directly to the browser's <code className="text-red-400 text-[10px] font-mono font-bold bg-zinc-900/60 px-1 py-0.5 rounded">localStorage</code>. Clear the cache or toggle the reset controller to return to the original preseeded database of 26 cinematic streams.
              </p>
            </div>
          </div>

        </div>
      )}

      {/* Tab 2: Manage Library / Videos */}
      {activeTab === 'videos' && (
        <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
          <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100 mb-5">
            Published Library Collection
          </h2>

          <div className="overflow-x-auto">
            <table className="w-full text-xs font-medium text-slate-600 dark:text-zinc-300">
              <thead>
                <tr className="border-b border-slate-100 dark:border-zinc-800/80 text-left text-[10px] text-slate-400 uppercase tracking-widest font-black">
                  <th className="pb-3 w-1/12">Preview</th>
                  <th className="pb-3 w-4/12">Media Title</th>
                  <th className="pb-3 w-2/12">Category</th>
                  <th className="pb-3 w-3/12">Tags</th>
                  <th className="pb-3 w-1/12 text-center">Views</th>
                  <th className="pb-3 w-1/12 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {videos.map((vid, idx) => (
                  <tr key={`${vid.id}_admin_${idx}`} className="border-b border-slate-50 dark:border-zinc-900/40 hover:bg-slate-50/50 dark:hover:bg-zinc-950/20">
                    <td className="py-3 pr-4">
                      <div className="w-14 aspect-video rounded overflow-hidden bg-slate-900 shadow">
                        <img
                          src={vid.thumbnailUrl || (vid.muxPlaybackId ? `https://image.mux.com/${vid.muxPlaybackId}/thumbnail.jpg` : 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=150&q=80')}
                          alt={vid.title}
                          referrerPolicy="no-referrer"
                          className="w-full h-full object-cover"
                        />
                      </div>
                    </td>
                    <td className="py-3 pr-4">
                      <div className="font-bold text-slate-800 dark:text-zinc-100 line-clamp-1">{vid.title}</div>
                      <div className="text-[10px] text-slate-400 dark:text-zinc-500 mt-0.5 max-w-xs truncate">{vid.description}</div>
                    </td>
                    <td className="py-3 pr-4 font-bold text-slate-500 dark:text-zinc-400">
                      {vid.category}
                    </td>
                    <td className="py-3 pr-4">
                      <div className="flex flex-wrap gap-1">
                        {vid.tags.map((tag, tIdx) => (
                          <span key={tIdx} className="bg-slate-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-[10px] font-semibold">
                            #{tag}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="py-3 text-center font-mono text-slate-500">
                      {vid.views.toLocaleString()}
                    </td>
                    <td className="py-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          onClick={() => startEditVideo(vid)}
                          className="p-1.5 text-blue-500 hover:bg-blue-500/10 dark:hover:bg-blue-500/20 rounded-lg transition-colors cursor-pointer"
                          title="Edit Video Details"
                        >
                          <Pencil className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => {
                            showConfirm(
                              'Delete Video',
                              `Permanently remove "${vid.title}" from the video collection?`,
                              () => onDeleteVideo(vid.id)
                            );
                          }}
                          className="p-1.5 text-red-500 hover:bg-red-500/10 dark:hover:bg-red-500/20 rounded-lg transition-colors cursor-pointer"
                          title="Delete Video"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 3: Dynamic Category Manager */}
      {activeTab === 'categories' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          {/* List Categories */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
            <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100 mb-4">
              Dynamic Categories
            </h2>
            <p className="text-slate-400 dark:text-zinc-500 text-xs mb-5">
              Current active categories that render dynamically in the filter bar.
            </p>

            <div className="flex flex-wrap gap-3">
              {categories.map((cat) => (
                <div
                  key={cat}
                  className="px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-850 rounded-xl text-xs font-bold text-slate-700 dark:text-zinc-300 shadow-sm flex items-center gap-2"
                >
                  <span>{cat}</span>
                  {cat !== 'All' && (
                    <button
                      onClick={() => {
                        showConfirm(
                          'Delete Category',
                          `Are you sure you want to delete the category "${cat}"? This will not delete the videos, but they will no longer be filterable by this category.`,
                          () => onDeleteCategory(cat)
                        );
                      }}
                      className="p-0.5 text-slate-400 hover:text-red-500 hover:bg-red-500/10 dark:hover:bg-red-500/20 rounded transition-colors cursor-pointer"
                      title={`Delete category ${cat}`}
                    >
                      <X className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Add Category Form */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
            <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100 mb-4">
              Add New Category
            </h2>
            <p className="text-slate-400 dark:text-zinc-500 text-xs mb-5">
              Categories added here instantly populate the dynamic publish dropdown, filter menus, and recommended grids.
            </p>

            <form onSubmit={handleAddCategory} className="flex gap-2">
              <input
                type="text"
                required
                placeholder="e.g. Comedy, VR 360, Retro"
                value={newCategoryName}
                onChange={(e) => setNewCategoryName(e.target.value)}
                className="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500"
              />
              <button
                type="submit"
                className="px-5 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-xl transition-all shadow-md cursor-pointer"
              >
                Create
              </button>
            </form>
          </div>

        </div>
      )}

      {/* Tab 4: XML Sitemap & SSR SEO Live View */}
      {activeTab === 'seo' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          {/* XML Sitemap */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100">
                  Dynamic sitemap.xml
                </h2>
                <p className="text-slate-400 dark:text-zinc-500 text-xs mt-0.5">
                  Live updating XML indices optimized for search engine bots.
                </p>
              </div>
              <span className="bg-amber-500/10 text-amber-500 text-[9px] font-black border border-amber-500/20 px-2 py-0.5 rounded uppercase tracking-wider">
                SITEMAP ENGINE
              </span>
            </div>

            <div className="relative">
              <pre className="bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-850 rounded-xl p-4 text-[10px] text-slate-500 dark:text-zinc-400 font-mono overflow-auto max-h-96 text-left whitespace-pre">
                {generateSitemapXml()}
              </pre>
              <button
                onClick={() => {
                  navigator.clipboard.writeText(generateSitemapXml());
                  alert('Sitemap XML successfully copied to clipboard!');
                }}
                className="absolute top-3 right-3 px-3 py-1 bg-red-600 text-white font-black text-[9px] rounded uppercase shadow hover:bg-red-700"
              >
                Copy XML
              </button>
            </div>
          </div>

          {/* SSR Head Tag Viewer */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800/85 p-6 rounded-2xl shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="font-sans font-black text-lg text-slate-800 dark:text-zinc-100">
                  SSR SEO Head Meta Tags
                </h2>
                <p className="text-slate-400 dark:text-zinc-500 text-xs mt-0.5">
                  Dynamic OpenGraph & metadata injected server-side for dynamic video routes.
                </p>
              </div>
              <span className="bg-red-600/10 text-red-500 text-[9px] font-black border border-red-500/20 px-2 py-0.5 rounded uppercase tracking-wider">
                SSR INJECTOR
              </span>
            </div>

            <div className="relative">
              <pre className="bg-slate-50 dark:bg-zinc-950 border border-slate-100 dark:border-zinc-850 rounded-xl p-4 text-[10px] text-slate-500 dark:text-zinc-400 font-mono overflow-auto max-h-96 text-left whitespace-pre">
                {generateSsrMetadata()}
              </pre>
              <button
                onClick={() => {
                  navigator.clipboard.writeText(generateSsrMetadata());
                  alert('SSR SEO Meta tags successfully copied to clipboard!');
                }}
                className="absolute top-3 right-3 px-3 py-1 bg-red-600 text-white font-black text-[9px] rounded uppercase shadow hover:bg-red-700"
              >
                Copy Meta
              </button>
            </div>
          </div>

        </div>
      )}

      {/* Custom Confirmation Modal */}
      {confirmState.isOpen && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 dark:bg-black/70 backdrop-blur-sm">
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-xl text-left animate-in fade-in zoom-in-95 duration-150">
            <h3 className="text-base font-black text-slate-800 dark:text-zinc-100 uppercase tracking-wider mb-2">
              {confirmState.title}
            </h3>
            <p className="text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-6">
              {confirmState.message}
            </p>
            <div className="flex justify-end gap-2.5">
              <button
                onClick={() => setConfirmState(prev => ({ ...prev, isOpen: false }))}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-slate-700 dark:text-zinc-200 text-xs font-bold rounded-xl transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={confirmState.onConfirm}
                className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all shadow cursor-pointer"
              >
                Confirm Delete
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
