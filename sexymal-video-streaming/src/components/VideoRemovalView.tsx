import React, { useState } from 'react';
import { ShieldAlert, Info, AlertOctagon, Send, CheckCircle2, RefreshCw } from 'lucide-react';
import { collection, addDoc } from 'firebase/firestore';
import { db } from '../firebase';

export default function VideoRemovalView() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    videoUrl: '',
    reason: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.videoUrl || !formData.reason) {
      setError('Please fill in all required fields.');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await addDoc(collection(db, 'video_removals'), {
        ...formData,
        submittedAt: new Date().toISOString(),
      });
      setSubmitSuccess(true);
      setFormData({
        name: '',
        email: '',
        videoUrl: '',
        reason: '',
      });
    } catch (err: any) {
      console.error('Error submitting removal form:', err);
      setError('Failed to submit report. Please check your internet connection and try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-5xl mx-auto px-4 py-8 animate-fade-in font-sans">
      <div className="text-center mb-10">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-550/10 text-rose-550 mb-3 border border-rose-500/10 shadow-lg shadow-rose-500/5">
          <ShieldAlert className="w-7 h-7" />
        </div>
        <h1 className="text-3xl font-black uppercase tracking-tight text-slate-800 dark:text-zinc-100">
          Content Complaint / DMCA
        </h1>
        <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1 font-mono uppercase tracking-widest">
          Video Removal Form
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {/* Left Column: Guidelines & Information */}
        <div className="lg:col-span-7 space-y-6 text-left">
          
          {/* Main Statement */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-4 shadow-sm">
            <h2 className="text-base font-black uppercase text-slate-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
              <AlertOctagon className="w-5 h-5 text-rose-550" />
              <span>Content Complaint</span>
            </h2>
            <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
              At <strong className="text-rose-550 dark:text-rose-400">sexymal.top</strong>, we take user safety and privacy very seriously. While we encourage freedom of expression, we do not allow harmful, illegal, non-consensual content of any kind or child sexual abuse material (CSAM) on our platform.
            </p>
            <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
              If any content indexed on <strong className="text-rose-550 dark:text-rose-400">sexymal.top</strong> infringes upon your rights or causes concern, please notify us by email or via the form. Upon receiving a valid request, the referenced content will be reviewed and removed promptly without unnecessary delay.
            </p>
            <p className="text-xs sm:text-sm font-bold text-slate-700 dark:text-zinc-300 leading-relaxed italic">
              sexymal.top follows a policy of expeditious removal of reported content.
            </p>
          </div>

          {/* DMCA Compliance Box */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-3.5 shadow-sm">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider">
              DMCA Compliance Notice
            </h2>
            <p className="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              In compliance with the Digital Millennium Copyright Act (DMCA), 17 U.S.C. § 512, <strong className="text-slate-700 dark:text-zinc-300">sexymal.top</strong> operates as a "Service Provider" as defined under the DMCA and is entitled to the applicable safe harbor protections. We respect the rights of copyright owners and adhere to the DMCA Notice and Takedown procedures.
            </p>
            <p className="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              We have adopted and implemented a policy to respond to valid notifications of alleged copyright infringement and will take appropriate action in accordance with applicable laws.
            </p>
          </div>

          {/* What Can Be Reported */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-4 shadow-sm">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider border-b border-slate-100 dark:border-zinc-850/50 pb-3">
              What Can Be Reported?
            </h2>
            <ul className="list-disc pl-5 space-y-2 text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              <li>
                <strong className="text-slate-700 dark:text-zinc-300">Non-consensual content:</strong> Revenge content, blackmail, exploitation, or recordings shared without explicit permission.
              </li>
              <li>
                <strong className="text-slate-700 dark:text-zinc-300">Exposure of personal details:</strong> Phone numbers, physical addresses, emails, or other sensitive private data.
              </li>
              <li>
                <strong className="text-slate-700 dark:text-zinc-300">Illegal or abusive material:</strong> Anything that violates regional or international laws.
              </li>
              <li>
                <strong className="text-slate-700 dark:text-zinc-300">Harassment or threats:</strong> Targeted abuse directed towards you or another individual.
              </li>
            </ul>
          </div>

          {/* How to File a Complaint */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-3.5 shadow-sm">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider">
              How to File a Complaint
            </h2>
            <p className="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              To ensure a quick and accurate review, please include:
            </p>
            <ul className="list-decimal pl-5 space-y-1.5 text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              <li>The precise URL(s) of the content in question.</li>
              <li>A clear description of why you believe it violates our rules or your rights.</li>
              <li>Any supporting information or evidence that will help our team verify and act on the issue.</li>
            </ul>
            <p className="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed pt-2">
              Our moderation team will treat your complaint confidentially and respond as soon as possible.
            </p>
          </div>

          {/* Important Warning */}
          <div className="bg-red-500/5 border border-red-500/10 p-5 rounded-3xl flex items-start gap-3.5 shadow-sm">
            <Info className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div className="space-y-1">
              <h4 className="text-xs font-black uppercase text-red-500 tracking-wider">
                Crucial Warning
              </h4>
              <p className="text-xs text-red-600 dark:text-red-400/90 leading-relaxed">
                <strong>Important:</strong> Submitting false, fake, or repeated spam reports may result in your IP address being blocked permanently from accessing <strong className="text-red-700 dark:text-red-300">sexymal</strong>.
              </p>
            </div>
          </div>

        </div>

        {/* Right Column: Video Removal Request Form */}
        <div className="lg:col-span-5">
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl shadow-sm sticky top-24">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider border-b border-slate-100 dark:border-zinc-850/50 pb-3 mb-5">
              Removal Request Form
            </h2>

            {submitSuccess ? (
              <div className="text-center py-8 space-y-4 animate-scale-up">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-550/10 text-rose-550 border border-rose-500/10">
                  <CheckCircle2 className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-800 dark:text-zinc-200">Complaint Submitted</h3>
                  <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1.5 leading-relaxed">
                    Thank you. We have received your video removal request. Our moderation team will review the URL immediately and take dynamic action if a violation is verified.
                  </p>
                </div>
                <button
                  onClick={() => setSubmitSuccess(false)}
                  className="px-5 py-2 bg-slate-100 dark:bg-zinc-850 text-slate-700 dark:text-zinc-300 text-xs font-bold rounded-full transition-colors hover:bg-slate-200 dark:hover:bg-zinc-800 cursor-pointer"
                >
                  Submit Another Report
                </button>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-4 text-left">
                {error && (
                  <div className="p-3.5 bg-red-500/5 border border-red-500/10 rounded-xl flex items-start gap-2 text-xs text-red-500 animate-scale-up">
                    <AlertOctagon className="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <p>{error}</p>
                  </div>
                )}

                <div>
                  <label className="block text-slate-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] mb-1">
                    Your Name <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Enter your legal full name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-4 py-2.5 rounded-xl border border-slate-100 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-950/20 text-slate-800 dark:text-zinc-100 text-xs focus:ring-1 focus:ring-rose-500/50 focus:border-rose-500 focus:bg-white dark:focus:bg-zinc-900 outline-none transition-all"
                  />
                </div>

                <div>
                  <label className="block text-slate-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] mb-1">
                    Your Email <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="email"
                    required
                    placeholder="yourname@domain.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full px-4 py-2.5 rounded-xl border border-slate-100 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-950/20 text-slate-800 dark:text-zinc-100 text-xs focus:ring-1 focus:ring-rose-500/50 focus:border-rose-500 focus:bg-white dark:focus:bg-zinc-900 outline-none transition-all"
                  />
                </div>

                <div>
                  <label className="block text-slate-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] mb-1">
                    Reported Content URL <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="url"
                    required
                    placeholder="https://sexymal.top/..."
                    value={formData.videoUrl}
                    onChange={(e) => setFormData({ ...formData, videoUrl: e.target.value })}
                    className="w-full px-4 py-2.5 rounded-xl border border-slate-100 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-950/20 text-slate-800 dark:text-zinc-100 text-xs focus:ring-1 focus:ring-rose-500/50 focus:border-rose-500 focus:bg-white dark:focus:bg-zinc-900 outline-none transition-all"
                  />
                </div>

                <div>
                  <label className="block text-slate-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] mb-1">
                    Reason / Details <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    required
                    rows={6}
                    placeholder="Describe why you believe this content should be removed (e.g. copyright infringement, non-consensual content, privacy violation...)"
                    value={formData.reason}
                    onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                    className="w-full px-4 py-2.5 rounded-xl border border-slate-100 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-950/20 text-slate-800 dark:text-zinc-100 text-xs focus:ring-1 focus:ring-rose-500/50 focus:border-rose-500 focus:bg-white dark:focus:bg-zinc-900 outline-none transition-all resize-none"
                  />
                </div>

                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full py-3 bg-red-650 hover:bg-red-700 text-white font-sans font-black text-xs uppercase tracking-wider rounded-xl transition-all shadow-md hover:shadow-red-650/15 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                  {isSubmitting ? (
                    <>
                      <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                      <span>Submitting report...</span>
                    </>
                  ) : (
                    <>
                      <Send className="w-3.5 h-3.5" />
                      <span>Submit Removal Request</span>
                    </>
                  )}
                </button>
              </form>
            )}
          </div>
        </div>

      </div>
    </div>
  );
}
