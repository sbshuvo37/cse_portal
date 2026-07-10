import { MouseEvent } from 'react';

/**
 * Helper to handle the first-click smartlink ad.
 * On the very first click on any category, pagination, or load more buttons,
 * it opens the smartlink ad in a new window/tab, and marks the ad as clicked/shown.
 * Subsequent clicks will execute the original button action.
 */
export function handleAdClick(e: MouseEvent, action: () => void) {
  // Check if the ad has already been opened in this session
  const hasClickedAd = sessionStorage.getItem('sexymal_smartlink_clicked') === 'true';

  if (!hasClickedAd) {
    // Prevent default / propagation just in case
    e.preventDefault();
    e.stopPropagation();

    // Open the smartlink ad
    try {
      window.open(
        'https://www.effectivecpmnetwork.com/f2g0kwc3?key=bc13427fea689addfb31083e2d454ec8',
        '_blank',
        'noopener,noreferrer'
      );
    } catch (err) {
      console.error('Popup blocked or failed to open:', err);
    }

    // Set the flag in sessionStorage so we don't open it again in this session
    sessionStorage.setItem('sexymal_smartlink_clicked', 'true');
  } else {
    // Ad already shown, execute the original button logic
    action();
  }
}
