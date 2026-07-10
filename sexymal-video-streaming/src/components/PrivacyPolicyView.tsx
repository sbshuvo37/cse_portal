import React from 'react';
import { Shield, Eye, HelpCircle, Lock, AlertTriangle } from 'lucide-react';

export default function PrivacyPolicyView() {
  return (
    <div className="max-w-4xl mx-auto px-4 py-8 animate-fade-in font-sans text-left">
      <div className="text-center mb-10">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-550/10 text-rose-550 mb-3 border border-rose-500/10 shadow-lg shadow-rose-500/5">
          <Shield className="w-7 h-7" />
        </div>
        <h1 className="text-3xl font-black uppercase tracking-tight text-slate-800 dark:text-zinc-100">
          Privacy Policy
        </h1>
        <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1 font-mono uppercase tracking-widest">
          Privacy Policy for sexymal.top
        </p>
      </div>

      <div className="space-y-6 text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 sm:p-8 rounded-3xl shadow-sm">
        
        <p>
          At <strong>sexymal</strong> (<a href="https://sexymal.top" className="text-rose-500 underline">https://sexymal.top</a>), your privacy and safety are extremely important to us. This Privacy Policy explains how we collect, use, and protect your personal information while you browse our website. By accessing or using our site, you acknowledge that you have read, understood, and agree to this policy.
        </p>

        <div className="bg-amber-500/5 border border-amber-500/10 p-4 rounded-2xl flex items-start gap-3 my-4">
          <AlertTriangle className="w-5 h-5 text-amber-550 flex-shrink-0 mt-0.5" />
          <p className="text-xs text-amber-700 dark:text-amber-400 font-bold">
            Please note: sexymal contains adult content intended strictly for individuals of legal age in their respective jurisdiction. If you are not of legal age, please exit immediately.
          </p>
        </div>

        {/* Section 1 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            1. Who We Are
          </h2>
          <p>
            Our official website is: <a href="https://sexymal.top" className="text-rose-500 underline">https://sexymal.top</a>
          </p>
          <p>
            <strong>sexymal.top</strong> is not a producer (primary or secondary) of any content displayed on this website. All media available on this site is hosted, embedded, or streamed from third-party sources that are believed to be in compliance with applicable laws.
          </p>
        </div>

        {/* Section 2 */}
        <div className="space-y-3 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            2. Information We Collect
          </h2>
          <p>
            Depending on how you use our site, we may collect certain types of information:
          </p>

          <div className="pl-4 space-y-3 border-l border-slate-100 dark:border-zinc-800">
            <div>
              <h3 className="font-bold text-slate-700 dark:text-zinc-200">2.1 18 U.S.C. § 2257</h3>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                In accordance with 18 U.S.C. § 2257, records required for any content found on this website are maintained by the original producers of such content. Any request regarding 18 U.S.C. § 2257 compliance should be directed to the respective content producers or hosting providers.
              </p>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1.5">
                <strong>sexymal.top</strong> operates as a video streaming and content aggregation platform that allows general viewing of various forms of media provided by third parties.
              </p>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1.5">
                To ensure legal compliance and responsible use, <strong>sexymal.top</strong> follows these practices:
              </p>
              <ul className="list-disc pl-5 mt-1.5 text-xs text-slate-500 dark:text-zinc-400 space-y-1">
                <li>By accessing the site, users confirm that they meet the minimum age requirement and accept full responsibility for compliance with local laws.</li>
                <li>Access to this website is strictly limited to individuals who are 18 years of age or older.</li>
              </ul>
            </div>

            <div>
              <h3 className="font-bold text-slate-700 dark:text-zinc-200">2.2 Media Uploads</h3>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                <strong>sexymal.top</strong> does not host, upload, or own any video content available on this website. All media displayed on the site is sourced from publicly available locations on the internet and from third-party platforms or forums.
              </p>
            </div>

            <div>
              <h3 className="font-bold text-slate-700 dark:text-zinc-200">2.3 Contact Forms</h3>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                When you reach out through our contact form, we collect your name, email address, and any other details you provide, only for the purpose of responding to your inquiry.
              </p>
            </div>

            <div>
              <h3 className="font-bold text-slate-700 dark:text-zinc-200">2.4 Cookies</h3>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                We use cookies to improve your browsing experience. Cookies may:
              </p>
              <ul className="list-disc pl-5 mt-1 text-xs text-slate-500 dark:text-zinc-400 space-y-1">
                <li>Save your login preferences and display settings.</li>
                <li>Store form inputs for your convenience.</li>
                <li>Help us track and analyze visitor activity through analytics tools.</li>
              </ul>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1.5">
                You can disable cookies in your browser, but some features of the site may not function properly.
              </p>
            </div>

            <div>
              <h3 className="font-bold text-slate-700 dark:text-zinc-200">2.5 Analytics</h3>
              <p className="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                We use third-party analytics (such as Google Analytics) to understand visitor behavior and improve site performance. These tools may collect details like IP address, device type, pages visited, and session time.
              </p>
            </div>
          </div>
        </div>

        {/* Section 3 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            3. How We Use Your Information
          </h2>
          <p>We may use collected data for:</p>
          <ul className="list-disc pl-5 space-y-1">
            <li>Spam detection and site security.</li>
            <li>Improving website content and functionality.</li>
            <li>Responding to user inquiries.</li>
            <li>Complying with legal obligations if required.</li>
          </ul>
        </div>

        {/* Section 4 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            4. Sharing of Information
          </h2>
          <p>We do not sell or trade your data. However, we may share limited information with:</p>
          <ul className="list-disc pl-5 space-y-1">
            <li>Service providers (hosting, analytics, anti-spam tools).</li>
            <li>Legal authorities (if required by law).</li>
            <li>Business transfers (if <strong>sexymal</strong> merges or is acquired).</li>
            <li>With your consent (in cases where you explicitly allow).</li>
          </ul>
        </div>

        {/* Section 5 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            5. Data Retention
          </h2>
          <ul className="list-disc pl-5 space-y-1">
            <li>Comments and metadata are stored indefinitely to recognize follow-ups.</li>
            <li>Contact form entries may be stored temporarily for support purposes.</li>
            <li>Analytics data is retained for a commercially reasonable period.</li>
          </ul>
        </div>

        {/* Section 6 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            6. Your Rights
          </h2>
          <p>Depending on your location, you may have the right to:</p>
          <ul className="list-disc pl-5 space-y-1">
            <li>Request access to your data.</li>
            <li>Ask for corrections or updates.</li>
            <li>Request deletion of personal data (except for legal obligations).</li>
            <li>Object to certain types of data processing.</li>
          </ul>
          <p>
            To exercise these rights, you can contact us via our official Support Form.
          </p>
        </div>

        {/* Section 7 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            7. Data Security
          </h2>
          <p>
            We take appropriate security measures (technical and administrative) to safeguard your data from unauthorized access, loss, or misuse. However, no internet system is 100% secure, and we cannot guarantee absolute protection.
          </p>
        </div>

        {/* Section 8 */}
        <div className="space-y-2 pt-2">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            8. Industry-Specific Compliance
          </h2>
          <p>
            As an adult content website, <strong>sexymal</strong> strictly prohibits and removes:
          </p>
          <ul className="list-disc pl-5 space-y-1">
            <li>Child sexual abuse material (CSAM).</li>
            <li>Non-consensual content (revenge porn, blackmail, exploitation).</li>
            <li>Personally identifiable information posted without consent.</li>
          </ul>
          <p>
            Violating users may be blocked permanently and reported to authorities.
          </p>
        </div>

        {/* Section 9 */}
        <div className="space-y-2 pt-2 border-t border-slate-100 dark:border-zinc-850/60 pt-4">
          <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight">
            9. Updates to this Policy
          </h2>
          <p>
            We may update this Privacy Policy from time to time. The updated version will always be available at <a href="https://sexymal.top/privacy-policy" className="text-rose-500 underline">https://sexymal.top/privacy-policy</a> with the latest "Last Updated" date.
          </p>
        </div>

      </div>
    </div>
  );
}
