import React, { useEffect, useRef } from 'react';

export default function PlayerBannerAd() {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!containerRef.current) return;

    // Clear any stale content
    containerRef.current.innerHTML = '';

    // 1. Define atOptions globally on window
    (window as any).atOptions = {
      'key': '4bdec1ff524a98b0efb00e27a35dcb17',
      'format': 'iframe',
      'height': 50,
      'width': 320,
      'params': {}
    };

    // 2. Create the script element
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://www.highperformanceformat.com/4bdec1ff524a98b0efb00e27a35dcb17/invoke.js';
    script.async = true;

    // 3. Append script to the container
    containerRef.current.appendChild(script);

    return () => {
      // Cleanup window variable and container on unmount
      try {
        delete (window as any).atOptions;
      } catch (e) {
        (window as any).atOptions = undefined;
      }
      if (containerRef.current) {
        containerRef.current.innerHTML = '';
      }
    };
  }, []);

  return (
    <div className="w-full flex items-center justify-center transition-all">
      <div 
        ref={containerRef} 
        className="w-[320px] h-[50px] overflow-hidden flex items-center justify-center transition-all"
        id="container-4bdec1ff524a98b0efb00e27a35dcb17"
      />
    </div>
  );
}
