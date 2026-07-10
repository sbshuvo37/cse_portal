export interface Video {
  id: string;
  title: string;
  description: string;
  muxPlaybackId?: string;
  videoUrl: string;
  thumbnailUrl?: string;
  category: string;
  tags: string[];
  likes: number;
  dislikes: number;
  views: number;
  createdAt: string;
  downloadUrl?: string;
}

export type AdPosition = 'top' | 'bottom' | 'social';

export interface AdConfig {
  id: string;
  title: string;
  imageUrl?: string;
  linkUrl: string;
  sponsorName: string;
}
