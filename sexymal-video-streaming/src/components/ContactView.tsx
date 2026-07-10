import React, { useState } from 'react';
import { Mail, Shield, AlertCircle, FileText, Send, CheckCircle2, RefreshCw } from 'lucide-react';
import { collection, addDoc } from 'firebase/firestore';
import { db } from '../firebase';

export default function ContactView() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: 'Suggestions',
    message: '',
    isp: '',
    os: '',
    browser: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.message) {
      setError('Please fill in all required fields (Name, Email, and Message).');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await addDoc(collection(db, 'contact_submissions'), {
        ...formData,
        submittedAt: new Date().toISOString(),
      });
      setSubmitSuccess(true);
      setFormData({
        name: '',
        email: '',
        subject: 'Suggestions',
        message: '',
        isp: '',
        os: '',
        browser: '',
      });
    } catch (err: any) {
      console.error('Error submitting contact form:', err);
      setError('Failed to send message. Please check your internet connection and try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-5xl mx-auto px-4 py-8 animate-fade-in font-sans">
      <div className="text-center mb-10">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-550/10 text-rose-550 mb-3 border border-rose-500/10 shadow-lg shadow-rose-500/5">
          <Mail className="w-7 h-7" />
        </div>
        <h1 className="text-3xl font-black uppercase tracking-tight text-slate-800 dark:text-zinc-100">
          Contact Us!
        </h1>
        <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1 font-mono uppercase tracking-widest">
          Support & Inquiries
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {/* Left Column: Guidelines & Channels (7 cols on large screens) */}
        <div className="lg:col-span-7 space-y-6 text-left">
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-4 shadow-sm">
            <p className="text-xs sm:text-sm text-slate-600 dark:text-zinc-350 leading-relaxed">
              We value your feedback, questions, and suggestions. Whether you're reaching out with a technical concern, an advertising inquiry, or content-related issues, our team is here to assist you as quickly as possible. To help us respond efficiently, please make sure your message is clear and properly formatted.
            </p>
          </div>

          {/* How to Reach Us */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-5 shadow-sm">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-zinc-850/50 pb-3">
              <Shield className="w-4 h-4 text-rose-550" />
              <span>How to Reach Us</span>
            </h2>

            <div className="space-y-4 text-xs">
              <div className="space-y-1">
                <h3 className="font-bold text-slate-700 dark:text-zinc-300">Suggestions</h3>
                <p className="text-slate-500 dark:text-zinc-400 leading-relaxed">
                  Share your ideas or feedback to help us improve the site experience.
                </p>
              </div>

              <div className="space-y-1">
                <h3 className="font-bold text-slate-700 dark:text-zinc-300">Technical Support</h3>
                <p className="text-slate-500 dark:text-zinc-400 leading-relaxed">
                  Report technical issues. Please include details such as your ISP (e.g., Jio, Airtel), IP address, operating system (e.g., Windows 10, MacOS), browser (e.g., Chrome, Firefox, Safari), and any extensions, ad-blockers, or antivirus programs you are using. This helps us diagnose the issue faster.
                </p>
              </div>

              <div className="space-y-1">
                <h3 className="font-bold text-slate-700 dark:text-zinc-300">Content Removal / DMCA</h3>
                <p className="text-slate-500 dark:text-zinc-400 leading-relaxed">
                  If you wish to request the removal of specific content, please provide your full name, address, valid ID proof, and the exact URLs in question (e.g., post, image, or video links). Submitting incomplete or false details may cause delays or prevent action on your request.
                </p>
              </div>

              <div className="space-y-1">
                <h3 className="font-bold text-slate-700 dark:text-zinc-300">Video Request</h3>
                <p className="text-slate-500 dark:text-zinc-400 leading-relaxed">
                  Looking for a specific type of content? Let us know! We'll review the request, and if it aligns with our policies, we may consider publishing it. Please avoid repeated requests as they may be flagged as spam.
                </p>
              </div>

              <div className="space-y-1">
                <h3 className="font-bold text-slate-700 dark:text-zinc-300">Advertising</h3>
                <p className="text-slate-500 dark:text-zinc-400 leading-relaxed">
                  For advertising opportunities, kindly include your company name, contact details, and a brief overview of your proposal.
                </p>
              </div>
            </div>

            <div className="bg-rose-500/5 border border-rose-500/10 p-4 rounded-2xl flex items-start gap-3 mt-4">
              <FileText className="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" />
              <p className="text-xs text-rose-600 dark:text-rose-400 leading-relaxed">
                If you are reporting content for removal, please use our <strong>Video Removal Form</strong> to ensure faster processing.
              </p>
            </div>
          </div>

          {/* Important Guidelines */}
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl space-y-4 shadow-sm">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-zinc-850/50 pb-3">
              <AlertCircle className="w-4 h-4 text-amber-550" />
              <span>Important Guidelines</span>
            </h2>
            <ul className="list-disc pl-5 space-y-2 text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
              <li>
                Please avoid sending multiple emails for the same issue. If you do not receive a response within 24-48 hours, you may resend your request once.
              </li>
              <li>
                Weekends & International Holidays may cause slight delays in responses.
              </li>
              <li>
                Always provide accurate and valid details, as incomplete or false information will result in your request being ignored.
              </li>
            </ul>
          </div>
        </div>

        {/* Right Column: Contact Form (5 cols on large screens) */}
        <div className="lg:col-span-5">
          <div className="bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-850/60 p-6 rounded-3xl shadow-sm sticky top-24">
            <h2 className="text-sm font-black uppercase text-slate-800 dark:text-zinc-100 tracking-wider border-b border-slate-100 dark:border-zinc-850/50 pb-3 mb-5">
              Send Message
            </h2>

            {submitSuccess ? (
              <div className="text-center py-8 space-y-4 animate-scale-up">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-550/10 text-emerald-550 border border-emerald-500/10">
                  <CheckCircle2 className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-800 dark:text-zinc-200">Message Sent Successfully</h3>
                  <p className="text-slate-400 dark:text-zinc-500 text-xs mt-1.5 leading-relaxed">
                    Thank you for reaching out. We will review your inquiry and get back to you within 24-48 hours.
                  </p>
                </div>
                <button
                  onClick={() => setSubmitSuccess(false)}
                  className="px-5 py-2 bg-slate-100 dark:bg-zinc-850 text-slate-700 dark:text-zinc-300 text-xs font-bold rounded-full transition-colors hover:bg-slate-200 dark:hover:bg-zinc-800 cursor-pointer"
                >
                  Send Another Message
                </button>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-4 text-left">
                {error && (
                  <div className="p-3.5 bg-red-500/5 border border-red-500/10 rounded-xl flex items-start gap-2 text-xs text-red-500">
                    <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
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
                    placeholder="Enter your full name"
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
                    Subject Line <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={formData.subject}
                    onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                    className="w-full px-4 py-2.5 rounded-xl border border-slate-100 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-950/20 text-slate-800 dark:text-zinc-100 text-xs focus:ring-1 focus:ring-rose-500/50 focus:border-rose-500 focus:bg-white dark:focus:bg-zinc-900 outline-none transition-all cursor-pointer"
                  >
                    <option value="Suggestions">Suggestions / Feedback</option>
                    <option value="Technical Support">Technical Support</option>
                    <option value="Content Removal / DMCA">Content Removal / DMCA</option>
                    <option value="Video Request">Video Request</option>
                    <option value="Advertising">Advertising Inquiry</option>
                  </select>
                </div>

                {formData.subject === 'Technical Support' && (
                  <div className="space-y-3.5 bg-slate-50 dark:bg-zinc-950/10 p-3.5 rounded-2xl border border-slate-100 dark:border-zinc-850 animate-scale-up">
                    <p className="text-[10px] font-black uppercase text-rose-500 tracking-wider">
                      Provide Technical Environment details:
                    </p>
                    
                    <div>
                      <label className="block text-slate-400 dark:text-zinc-500 font-bold text-[9px] mb-1">
                        Internet Service Provider (e.g. Jio, Airtel, Wifi)
                      </label>
                      <input
                        type="text"
                        placeholder="e.g. Jio Fiber, Airtel Mobile"
                        value={formData.isp}
                        onChange={(e) => setFormData({ ...formData, isp: e.target.value })}
                        className="w-full px-3.5 py-2 rounded-lg border border-slate-100 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 text-slate-800 dark:text-zinc-100 text-xs outline-none focus:border-rose-500 transition-colors"
                      />
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="block text-slate-400 dark:text-zinc-500 font-bold text-[9px] mb-1">
                          OS (e.g. Android, Windows)
                        </label>
                        <input
                          type="text"
                          placeholder="e.g. Windows 11"
                          value={formData.os}
                          onChange={(e) => setFormData({ ...formData, os: e.target.value })}
                          className="w-full px-3.5 py-2 rounded-lg border border-slate-100 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 text-slate-800 dark:text-zinc-100 text-xs outline-none focus:border-rose-500 transition-colors"
                        />
                      </div>
                      <div>
                        <label className="block text-slate-400 dark:text-zinc-500 font-bold text-[9px] mb-1">
                          Browser (e.g. Chrome)
                        </label>
                        <input
                          type="text"
                          placeholder="e.g. Chrome Mobile"
                          value={formData.browser}
                          onChange={(e) => setFormData({ ...formData, browser: e.target.value })}
                          className="w-full px-3.5 py-2 rounded-lg border border-slate-100 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 text-slate-800 dark:text-zinc-100 text-xs outline-none focus:border-rose-500 transition-colors"
                        />
                      </div>
                    </div>
                  </div>
                )}

                <div>
                  <label className="block text-slate-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] mb-1">
                    Your Message <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    required
                    rows={5}
                    placeholder="Describe your inquiry or concern clearly..."
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
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
                      <span>Sending inquiry...</span>
                    </>
                  ) : (
                    <>
                      <Send className="w-3.5 h-3.5" />
                      <span>Submit Inquiry</span>
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
