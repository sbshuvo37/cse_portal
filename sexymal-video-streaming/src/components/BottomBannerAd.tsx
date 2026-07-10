import React, { useEffect, useRef } from 'react';

export default function BottomBannerAd() {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!containerRef.current) return;

    // Clear container
    containerRef.current.innerHTML = '';

    // Create the target div element required by the script
    const innerContainer = document.createElement('div');
    innerContainer.id = 'container-6553d42d02c4dac430e7165db5305946';
    containerRef.current.appendChild(innerContainer);

    // Create the script element
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://pl29800542.effectivecpmnetwork.com/6553d42d02c4dac430e7165db5305946/invoke.js';
    script.async = true;
    script.setAttribute('data-cfasync', 'false');

    // Append script to container
    containerRef.current.appendChild(script);

    return () => {
      if (containerRef.current) {
        containerRef.current.innerHTML = '';
      }
    };
  }, []);

  return (
    <div className="w-full flex items-center justify-center py-2 bg-slate-50 dark:bg-zinc-950/20 border-t border-slate-100 dark:border-zinc-900 transition-all mt-1">
      <div ref={containerRef} className="w-full flex justify-center items-center" />
    </div>
  );
}
