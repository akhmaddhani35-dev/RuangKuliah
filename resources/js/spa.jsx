import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrfToken) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

const API = '/api/v1';
const dataCache = new Map();
const dataCacheTtl = 60_000;
const fieldClass = 'mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-sky-400 dark:focus:ring-sky-900/40';
const cardClass = 'rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/[0.03] dark:border-slate-800 dark:bg-slate-900';
const navItems = [
    ['dashboard', 'Ringkasan'],
    ['mata-kuliah', 'Mata Kuliah'],
    ['jadwal', 'Jadwal'],
    ['materi', 'Materi'],
    ['tugas', 'Tugas'],
];

function getCachedData(key) {
    const cached = dataCache.get(key);

    return cached ? { ...cached, fresh: Date.now() - cached.cachedAt < dataCacheTtl } : null;
}

function setCachedData(key, data) {
    dataCache.set(key, { data, cachedAt: Date.now() });
}

function clearUserDataCache(userId) {
    const prefix = `user:${userId}:`;

    for (const key of dataCache.keys()) {
        if (key.startsWith(prefix)) dataCache.delete(key);
    }
}

function messageFrom(error) {
    const errors = error.response?.data?.errors;
    if (errors) return Object.values(errors).flat().join(' ');
    return error.response?.data?.message || 'Permintaan gagal. Silakan coba lagi.';
}

function formatDate(value) {
    if (!value) return 'Tanggal belum ditentukan';

    const dateOnly = String(value).slice(0, 10);
    const date = new Date(`${dateOnly}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
}

function deadlineInfo(value) {
    if (!value) return null;

    const deadline = Date.parse(`${String(value).slice(0, 10)}T00:00:00Z`);
    if (Number.isNaN(deadline)) return null;

    const today = new Date();
    const todayStart = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
    const daysRemaining = Math.round((deadline - todayStart) / 86_400_000);
    const label = daysRemaining < 0
        ? `Terlambat ${Math.abs(daysRemaining)} hari`
        : daysRemaining === 0
            ? 'Hari ini'
            : daysRemaining === 1
                ? 'Besok'
                : `${daysRemaining} hari lagi`;

    return {
        label,
        tone: daysRemaining < 0
            ? 'border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/50'
            : daysRemaining <= 1
                ? 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40'
                : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60',
        badgeTone: daysRemaining < 0
            ? 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200'
            : daysRemaining <= 1
                ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                : 'bg-white text-slate-600 dark:bg-slate-900 dark:text-slate-300',
    };
}

function isHttpUrl(value) {
    return /^https?:\/\/\S+$/i.test(value || '');
}

function modeLabel(mode) {
    return mode === 'Offline' ? 'Tatap muka' : mode === 'Online' ? 'Daring' : mode;
}

function LogoMark({ className = 'size-11 rounded-2xl' }) {
    return (
        <span className={`grid shrink-0 place-items-center bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 text-white shadow-md shadow-blue-950/20 ${className}`} aria-hidden="true">
            <svg viewBox="0 0 24 24" className="size-6" fill="none">
                <path d="m2.5 9 9.5-5 9.5 5-9.5 5-9.5-5Z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
                <path d="M6.5 11.2v4.3c3.4 2.4 7.6 2.4 11 0v-4.3M21.5 9v5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        </span>
    );
}

function getPage() {
    return window.location.pathname.replace(/^\/app\/?/, '') || 'dashboard';
}

function App() {
    const [page, setPage] = useState(getPage);
    const [user, setUser] = useState(window.academicAppUser || null);
    const [notice, setNotice] = useState(null);
    const [theme, setTheme] = useState(localStorage.getItem('theme') || 'light');
    const [desktopNavOpen, setDesktopNavOpen] = useState(true);
    const [mobileNavOpen, setMobileNavOpen] = useState(false);

    useEffect(() => {
        const syncPage = () => setPage(getPage());
        window.addEventListener('popstate', syncPage);
        return () => window.removeEventListener('popstate', syncPage);
    }, []);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem('theme', theme);
    }, [theme]);

    useEffect(() => {
        if (!mobileNavOpen) return undefined;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        function closeOnEscape(event) {
            if (event.key === 'Escape') setMobileNavOpen(false);
        }

        window.addEventListener('keydown', closeOnEscape);
        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', closeOnEscape);
        };
    }, [mobileNavOpen]);

    function navigate(destination) {
        const path = `/app/${destination === 'dashboard' ? '' : destination}`;
        window.history.pushState({}, '', path);
        setPage(destination || 'dashboard');
        setNotice(null);
        setMobileNavOpen(false);
    }

    function notify(message, type = 'success') {
        setNotice({ message, type });
    }

    function toggleTheme() {
        setTheme((current) => current === 'dark' ? 'light' : 'dark');
    }

    function toggleNavigation() {
        if (window.innerWidth >= 1024) {
            setDesktopNavOpen((open) => !open);
        } else {
            setMobileNavOpen((open) => !open);
        }
    }

    async function logout() {
        try {
            await axios.post('/logout');
            window.location.assign('/login');
        } catch (error) {
            notify(messageFrom(error), 'error');
        }
    }

    if (!user) {
        return <main className="grid min-h-screen place-items-center bg-slate-50 p-6 text-center text-slate-700 dark:bg-slate-950 dark:text-slate-200">{notice?.message || 'Sesi tidak dapat dimuat. Silakan masuk kembali.'}</main>;
    }

    const appNavItems = user.role === 'admin'
        ? [...navItems, ['akun-mahasiswa', 'Akun Mahasiswa'], ['profil', 'Profil Saya']]
        : [...navItems, ['profil', 'Profil Saya']];
    const activePage = appNavItems.some(([destination]) => destination === page) ? page : 'dashboard';
    const pageTitle = {
        dashboard: 'Ringkasan kelas',
        'mata-kuliah': 'Mata Kuliah',
        jadwal: 'Jadwal',
        materi: 'Materi',
        tugas: 'Tugas',
        'akun-mahasiswa': 'Akun Mahasiswa',
        profil: 'Profil Saya',
    }[activePage];
    const pageDescription = {
        dashboard: 'Gambaran singkat tentang agenda dan aktivitas akademikmu.',
        'mata-kuliah': 'Daftar mata kuliah yang tersedia untuk kelasmu.',
        jadwal: 'Jadwal rutin mingguan dan agenda UTS/UAS.',
        materi: 'Kumpulan bahan belajar dan lampiran perkuliahan.',
        tugas: 'Deadline, prioritas, dan status pengerjaan tugas.',
        'akun-mahasiswa': 'Atur nama, email, dan kata sandi akun mahasiswa.',
        profil: 'Perbarui informasi akun dan kata sandimu.',
    }[activePage];
    const todayLabel = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
    const navigationExpanded = window.innerWidth >= 1024 ? desktopNavOpen : mobileNavOpen;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
            <button type="button" aria-label="Tutup menu navigasi" aria-hidden={!mobileNavOpen} tabIndex={mobileNavOpen ? 0 : -1} disabled={!mobileNavOpen} onClick={() => setMobileNavOpen(false)} className={`fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-[2px] transition-opacity duration-300 ease-out lg:hidden ${mobileNavOpen ? 'opacity-100' : 'pointer-events-none opacity-0'}`} />
            <aside id="app-sidebar" aria-label="Menu aplikasi" aria-hidden={!navigationExpanded} inert={!navigationExpanded} className={`fixed inset-y-0 left-0 z-40 flex w-[min(19rem,88vw)] transform flex-col overflow-y-auto overscroll-contain border-r border-slate-200 bg-white px-5 py-6 shadow-2xl transition-[transform,opacity] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/40 ${mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0 pointer-events-none'} ${desktopNavOpen ? 'lg:translate-x-0 lg:opacity-100 lg:pointer-events-auto' : 'lg:-translate-x-full lg:opacity-0 lg:pointer-events-none'}`}>
                <div className="flex items-center justify-between gap-3 rounded-2xl bg-gradient-to-br from-sky-50 to-indigo-50/70 p-3 dark:from-slate-800 dark:to-indigo-950/40">
                    <div className="flex min-w-0 items-center gap-3">
                        <LogoMark className="size-11 rounded-2xl" />
                        <span className="min-w-0"><span className="block text-base font-extrabold tracking-tight">RuangKuliah</span><span className="block text-xs font-medium text-slate-500 dark:text-slate-400">Manajemen akademik</span></span>
                    </div>
                    <button type="button" onClick={() => setMobileNavOpen(false)} aria-label="Tutup menu navigasi" className="grid size-10 shrink-0 place-items-center rounded-xl text-slate-500 transition duration-200 hover:rotate-90 hover:bg-white/80 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-slate-300 dark:hover:bg-slate-700 lg:hidden">
                        <svg viewBox="0 0 24 24" className="size-5" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" /></svg>
                    </button>
                </div>
                <nav aria-label="Navigasi aplikasi" className="mt-8">
                    <p className="px-3 pb-3 text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Menu utama</p>
                    <div className="space-y-1.5">
                        {appNavItems.map(([destination, label]) => (
                            <NavButton key={destination} destination={destination} label={label} active={activePage === destination} onClick={() => navigate(destination)} />
                        ))}
                    </div>
                </nav>
                <div className="mt-auto shrink-0 border-t border-slate-200 pt-5 dark:border-slate-800">
                    <button type="button" onClick={logout} className="flex min-h-14 w-full items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50/80 px-3 text-left transition duration-200 hover:-translate-y-0.5 hover:border-rose-200 hover:bg-rose-100 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-rose-100 dark:border-rose-950 dark:bg-rose-950/40 dark:hover:bg-rose-950/70 dark:focus:ring-rose-950">
                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-rose-700 shadow-sm dark:bg-slate-900 dark:text-rose-300" aria-hidden="true">
                            <svg viewBox="0 0 24 24" className="size-5" fill="none"><path d="M10 17l5-5-5-5m5 5H3m9-9h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" /></svg>
                        </span>
                        <span><span className="block text-sm font-bold text-rose-800 dark:text-rose-200">Keluar</span><span className="mt-0.5 block text-xs text-rose-700/70 dark:text-rose-300/70">Keluar dari akun</span></span>
                    </button>
                </div>
            </aside>

            <div className={`min-h-screen transition-[padding] duration-300 ease-in-out ${desktopNavOpen ? 'lg:pl-72' : 'lg:pl-0'}`}>
                <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 shadow-sm shadow-slate-900/[0.03] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-black/10">
                    <div className="mx-auto flex max-w-[1500px] items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-7 lg:px-10">
                        <div className="flex min-w-0 items-center gap-3 sm:gap-4">
                            <button type="button" onClick={toggleNavigation} aria-label="Buka atau tutup menu navigasi" aria-controls="app-sidebar" aria-expanded={navigationExpanded} className="group grid size-11 shrink-0 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition duration-200 hover:scale-105 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 active:scale-95 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-slate-800 dark:hover:text-sky-300 dark:focus:ring-sky-900/40">
                                <svg viewBox="0 0 24 24" className="size-5 transition-transform duration-200 group-hover:scale-105" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" /></svg>
                            </button>
                            <div className="flex min-w-0 items-center gap-3 lg:hidden">
                                <LogoMark className="size-10 rounded-xl" />
                                <div className="min-w-0"><p className="text-sm font-extrabold">RuangKuliah</p><p className="truncate text-xs text-slate-500 dark:text-slate-400">{user.name}</p></div>
                            </div>
                            <div className="hidden min-w-0 items-center gap-3 lg:flex lg:gap-3.5">
                                <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" className="size-5" fill="none"><rect x="3.75" y="5.25" width="16.5" height="15" rx="2" stroke="currentColor" strokeWidth="1.7" /><path d="M7.5 3.75v3M16.5 3.75v3M3.75 9.5h16.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate text-[11px] font-extrabold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">{todayLabel}</p>
                                    <p className="mt-1 truncate text-sm font-bold text-slate-800 dark:text-slate-100">Halo, {user.name}</p>
                                </div>
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                            <span className={`hidden min-h-10 items-center gap-2 rounded-full border px-3.5 text-xs font-bold sm:inline-flex ${user.role === 'admin' ? 'border-violet-100 bg-violet-50 text-violet-800 dark:border-violet-900 dark:bg-violet-950/60 dark:text-violet-200' : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'}`}>
                                <svg viewBox="0 0 24 24" className="size-4" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.1" stroke="currentColor" strokeWidth="1.7" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
                                {user.role === 'admin' ? 'Komting' : 'Mahasiswa'}
                            </span>
                            <button type="button" onClick={toggleTheme} aria-label={theme === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap'} className="grid size-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition duration-200 hover:scale-105 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 active:scale-95 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-slate-800 dark:hover:text-sky-300 dark:focus:ring-sky-900/40">
                                <svg viewBox="0 0 24 24" className="size-5 transition-transform duration-300" fill="none" aria-hidden="true">
                                    {theme === 'dark'
                                        ? <><circle cx="12" cy="12" r="3.5" stroke="currentColor" strokeWidth="1.7" /><path d="M12 2.5v2M12 19.5v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2.5 12h2m15 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></>
                                        : <><path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" /><path d="M15.5 4.5v4m-2-2h4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" /></>}
                                </svg>
                            </button>
                            <button type="button" onClick={logout} className="min-h-11 rounded-xl bg-slate-900 px-3.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 lg:hidden">Keluar</button>
                        </div>
                    </div>
                </header>

                <main className="mx-auto min-w-0 max-w-[1500px] px-4 py-7 sm:px-7 sm:py-9 lg:px-10 lg:py-10">
                    {notice && (
                        <div role={notice.type === 'error' ? 'alert' : 'status'} aria-live={notice.type === 'error' ? 'assertive' : 'polite'} className={`mb-6 flex items-start justify-between gap-4 rounded-2xl border p-4 text-sm font-semibold shadow-sm ${notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200' : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200'}`}>
                            <span className="flex items-center gap-2"><span aria-hidden="true">{notice.type === 'error' ? '!' : '✓'}</span>{notice.message}</span>
                            <button type="button" onClick={() => setNotice(null)} aria-label="Tutup pemberitahuan" className="rounded-lg px-2 py-1 text-current/70 transition hover:bg-black/5 hover:text-current focus:outline-none focus:ring-2 focus:ring-current">Tutup</button>
                        </div>
                    )}
                    <div className="mb-7">
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div><h1 className="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">{pageTitle}</h1><p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">{pageDescription}</p></div>
                            <span className="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 md:inline-flex">{todayLabel}</span>
                        </div>
                    </div>
                    {activePage === 'dashboard' && <DashboardPage user={user} onNavigate={navigate} />}
                    {activePage === 'mata-kuliah' && <ResourcePage key={activePage} kind="courses" user={user} notify={notify} />}
                    {activePage === 'materi' && <ResourcePage key={activePage} kind="materials" user={user} notify={notify} />}
                    {activePage === 'tugas' && <ResourcePage key={activePage} kind="assignments" user={user} notify={notify} />}
                    {activePage === 'jadwal' && <SchedulePage user={user} notify={notify} />}
                    {activePage === 'profil' && <ProfilePage user={user} onUpdated={setUser} notify={notify} />}
                    {activePage === 'akun-mahasiswa' && <StudentAccountsPage notify={notify} />}
                </main>
                <footer className="px-4 pb-6 text-center text-xs text-slate-500 dark:text-slate-500">RuangKuliah · Informasi akademik kelas dalam satu ruang.</footer>
            </div>
        </div>
    );
}

function AccountField({ id, label, value, onChange, type = 'text', required = true, autoComplete, hint }) {
    return (
        <label htmlFor={id} className="block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {label}
            <input id={id} type={type} value={value} onChange={(event) => onChange(event.target.value)} required={required} autoComplete={autoComplete} className={fieldClass} />
            {hint && <span className="mt-1 block text-xs font-normal leading-5 text-slate-500 dark:text-slate-400">{hint}</span>}
        </label>
    );
}

function ProfilePage({ user, onUpdated, notify }) {
    const [profile, setProfile] = useState({ name: user.name, email: user.email });
    const [password, setPassword] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [profileError, setProfileError] = useState('');
    const [passwordError, setPasswordError] = useState('');
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingPassword, setSavingPassword] = useState(false);
    const initial = String(user.name || '').trim().charAt(0).toUpperCase() || 'A';

    async function saveProfile(event) {
        event.preventDefault();
        setSavingProfile(true);
        setProfileError('');
        try {
            const response = await axios.patch(`${API}/profile`, profile);
            onUpdated((current) => ({ ...current, ...response.data.data }));
            notify('Profil berhasil diperbarui.');
        } catch (requestError) {
            setProfileError(messageFrom(requestError));
        } finally {
            setSavingProfile(false);
        }
    }

    async function savePassword(event) {
        event.preventDefault();
        setSavingPassword(true);
        setPasswordError('');
        try {
            await axios.patch(`${API}/profile/password`, password);
            setPassword({ current_password: '', password: '', password_confirmation: '' });
            notify('Kata sandi berhasil diubah.');
        } catch (requestError) {
            setPasswordError(messageFrom(requestError));
        } finally {
            setSavingPassword(false);
        }
    }

    return (
        <div className="space-y-6">
            <section className={`${cardClass} flex flex-wrap items-center gap-4`}>
                <span aria-hidden="true" className="grid size-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-xl font-extrabold text-white shadow-sm">{initial}</span>
                <div className="min-w-0 flex-1">
                    <p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">Akun aktif</p>
                    <h2 className="mt-1 truncate text-lg font-extrabold text-slate-950 dark:text-white">{user.name}</h2>
                    <p className="mt-0.5 break-all text-sm text-slate-500 dark:text-slate-400">{user.email}</p>
                </div>
                <span className="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{user.role === 'admin' ? 'Komting' : 'Mahasiswa'}</span>
            </section>

            <div className="grid items-start gap-6 xl:grid-cols-2">
                <form onSubmit={saveProfile} className={`${cardClass} flex flex-col gap-5`}>
                    <div className="border-b border-slate-100 pb-4 dark:border-slate-800">
                        <p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">Informasi pribadi</p>
                        <h2 className="mt-1 text-lg font-extrabold">Nama dan email</h2>
                        <p className="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Informasi ini digunakan untuk mengenali akun dan masuk ke aplikasi.</p>
                    </div>
                    {profileError && <p role="alert" className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{profileError}</p>}
                    <div className="space-y-4">
                        <AccountField id="profile-name" label="Nama" value={profile.name} autoComplete="name" onChange={(value) => setProfile((current) => ({ ...current, name: value }))} />
                        <AccountField id="profile-email" label="Email" type="email" value={profile.email} autoComplete="email" onChange={(value) => setProfile((current) => ({ ...current, email: value }))} />
                    </div>
                    <div className="border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button disabled={savingProfile} className="min-h-11 w-full rounded-xl bg-sky-700 px-5 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-sky-900 sm:w-auto">{savingProfile ? 'Menyimpan...' : 'Simpan profil'}</button>
                    </div>
                </form>

                <form onSubmit={savePassword} className={`${cardClass} flex flex-col gap-5`}>
                    <div className="border-b border-slate-100 pb-4 dark:border-slate-800">
                        <p className="text-xs font-bold uppercase tracking-[0.12em] text-indigo-700 dark:text-indigo-300">Keamanan</p>
                        <h2 className="mt-1 text-lg font-extrabold">Ubah kata sandi</h2>
                        <p className="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Verifikasi kata sandi saat ini sebelum membuat kata sandi baru.</p>
                    </div>
                    {passwordError && <p role="alert" className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{passwordError}</p>}
                    <div className="space-y-4">
                        <AccountField id="current-password" label="Kata sandi saat ini" type="password" autoComplete="current-password" value={password.current_password} onChange={(value) => setPassword((current) => ({ ...current, current_password: value }))} />
                        <AccountField id="new-password" label="Kata sandi baru" type="password" autoComplete="new-password" value={password.password} onChange={(value) => setPassword((current) => ({ ...current, password: value }))} />
                        <AccountField id="confirm-password" label="Ulangi kata sandi baru" type="password" autoComplete="new-password" value={password.password_confirmation} onChange={(value) => setPassword((current) => ({ ...current, password_confirmation: value }))} />
                    </div>
                    <div className="border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button disabled={savingPassword} className="min-h-11 w-full rounded-xl bg-slate-900 px-5 text-sm font-bold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-300 disabled:cursor-wait disabled:opacity-60 dark:bg-slate-700 dark:hover:bg-slate-600 dark:focus:ring-slate-800 sm:w-auto">{savingPassword ? 'Menyimpan...' : 'Ubah kata sandi'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function StudentAccountsPage({ notify }) {
    const [students, setStudents] = useState([]);
    const [selected, setSelected] = useState(null);
    const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function load(targetPage = page) {
        setLoading(true);
        setError('');
        try {
            const response = await axios.get(`${API}/students`, { params: { page: targetPage, per_page: 20 } });
            setStudents(response.data.data);
            setPage(response.data.current_page);
            setLastPage(response.data.last_page);
        } catch (requestError) {
            setError(messageFrom(requestError));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { load(1); }, []);

    function selectStudent(student) {
        setSelected(student);
        setForm({ name: student.name, email: student.email, password: '', password_confirmation: '' });
        setError('');
    }

    function clearSelection() {
        setSelected(null);
        setForm({ name: '', email: '', password: '', password_confirmation: '' });
        setError('');
    }

    async function saveStudent(event) {
        event.preventDefault();
        setSaving(true);
        setError('');
        try {
            const response = await axios.patch(`${API}/students/${selected.id}`, form);
            const updated = response.data.data;
            setStudents((current) => current.map((student) => student.id === updated.id ? updated : student));
            selectStudent(updated);
            notify('Akun mahasiswa berhasil diperbarui.');
        } catch (requestError) {
            setError(messageFrom(requestError));
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <section className={`${cardClass} flex min-w-0 flex-col gap-4`}>
                <div className="flex items-center justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div><p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">Pengelolaan akun</p><h2 className="mt-1 text-lg font-extrabold">Daftar mahasiswa</h2><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Pilih mahasiswa untuk mengatur informasi akunnya.</p></div>
                    <span className="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{loading ? 'Memuat...' : `${students.length} di halaman ini`}</span>
                </div>
                {error && !selected && <p role="alert" className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{error}</p>}
                {loading ? <p role="status" className="py-8 text-center text-sm text-slate-500">Memuat daftar mahasiswa...</p> : students.length ? (
                    <div role="list" aria-label="Daftar akun mahasiswa" className="max-h-[34rem] space-y-2 overflow-y-auto pr-1">
                        {students.map((student) => (
                            <div role="listitem" key={student.id} className={`flex min-h-[72px] items-center gap-3 rounded-xl border p-3 transition ${selected?.id === student.id ? 'border-sky-200 bg-sky-50 dark:border-sky-800 dark:bg-sky-950/40' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/60'}`}>
                                <span aria-hidden="true" className="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-sm font-extrabold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{String(student.name || '').trim().charAt(0).toUpperCase() || 'M'}</span>
                                <div className="min-w-0 flex-1">
                                    <p title={student.name} className="truncate text-sm font-bold">{student.name}</p>
                                    <p title={student.email} className="truncate text-xs text-slate-500 dark:text-slate-400">{student.email}</p>
                                </div>
                                <button type="button" aria-pressed={selected?.id === student.id} onClick={() => selectStudent(student)} className={`min-h-9 shrink-0 rounded-lg px-3 text-xs font-bold transition focus:outline-none focus:ring-4 focus:ring-sky-100 dark:focus:ring-sky-900/40 ${selected?.id === student.id ? 'bg-sky-700 text-white hover:bg-sky-800' : 'border border-slate-200 text-sky-700 hover:bg-sky-50 dark:border-slate-700 dark:text-sky-300 dark:hover:bg-slate-800'}`}>{selected?.id === student.id ? 'Dipilih' : 'Atur akun'}</button>
                            </div>
                        ))}
                    </div>
                ) : <p className="py-8 text-center text-sm text-slate-500">Belum ada akun mahasiswa.</p>}
                {(page > 1 || page < lastPage) && <div className="mt-auto flex items-center justify-between gap-2 border-t border-slate-100 pt-4 dark:border-slate-800"><button type="button" disabled={page <= 1 || loading} onClick={() => load(page - 1)} className="min-h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:hover:bg-slate-800">Sebelumnya</button><span className="text-center text-xs text-slate-500 dark:text-slate-400">Halaman {page} dari {lastPage}</span><button type="button" disabled={page >= lastPage || loading} onClick={() => load(page + 1)} className="min-h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:hover:bg-slate-800">Berikutnya</button></div>}
            </section>

            <section className={`${cardClass} min-w-0`}>
                {selected ? (
                    <form onSubmit={saveStudent} className="flex flex-col gap-5">
                        <div className="flex items-start justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                            <div className="flex min-w-0 items-center gap-3">
                                <span aria-hidden="true" className="grid size-11 shrink-0 place-items-center rounded-xl bg-sky-50 text-sm font-extrabold text-sky-700 dark:bg-sky-950 dark:text-sky-300">{String(selected.name || '').trim().charAt(0).toUpperCase() || 'M'}</span>
                                <div className="min-w-0">
                                    <p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">Mengatur akun</p>
                                    <h2 title={selected.name} className="mt-0.5 truncate text-base font-extrabold">{selected.name}</h2>
                                    <p title={selected.email} className="truncate text-xs text-slate-500 dark:text-slate-400">{selected.email}</p>
                                </div>
                            </div>
                            <button type="button" onClick={clearSelection} className="min-h-9 shrink-0 rounded-lg px-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 dark:focus:ring-slate-800">Batal</button>
                        </div>
                        {error && <p role="alert" className="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{error}</p>}
                        <div className="space-y-4">
                            <AccountField id="student-name" label="Nama" value={form.name} autoComplete="off" onChange={(value) => setForm((current) => ({ ...current, name: value }))} />
                            <AccountField id="student-email" label="Email" type="email" value={form.email} autoComplete="off" onChange={(value) => setForm((current) => ({ ...current, email: value }))} />
                        </div>
                        <div className="space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <div>
                                <h3 className="text-sm font-bold">Ganti kata sandi <span className="font-medium text-slate-400">(opsional)</span></h3>
                                <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Biarkan kedua kolom kosong jika kata sandi tidak perlu diubah.</p>
                            </div>
                            <AccountField id="student-password" label="Kata sandi baru" type="password" required={false} autoComplete="new-password" value={form.password} onChange={(value) => setForm((current) => ({ ...current, password: value }))} />
                            <AccountField id="student-password-confirmation" label="Ulangi kata sandi baru" type="password" required={false} autoComplete="new-password" value={form.password_confirmation} onChange={(value) => setForm((current) => ({ ...current, password_confirmation: value }))} />
                        </div>
                        <div className="border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button disabled={saving} className="min-h-11 w-full rounded-xl bg-sky-700 px-5 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-sky-900 sm:w-auto">{saving ? 'Menyimpan...' : 'Simpan akun'}</button>
                        </div>
                    </form>
                ) : <div className="grid min-h-72 place-items-center text-center">
                    <div className="max-w-sm">
                        <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300" aria-hidden="true"><svg viewBox="0 0 24 24" className="size-6" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" strokeWidth="1.6" /><path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" /></svg></span>
                        <h2 className="mt-4 font-extrabold">Pilih akun mahasiswa</h2>
                        <p className="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Pilih tombol <strong>Atur akun</strong> pada daftar untuk mengubah nama, email, atau kata sandi.</p>
                    </div>
                </div>}
            </section>
        </div>
    );
}

function NavButton({ destination, label, active, onClick }) {
    const icons = {
        dashboard: <><rect x="4" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="4" width="6.5" height="4" rx="1.5" /><rect x="13.5" y="10.5" width="6.5" height="9.5" rx="1.5" /><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5" /></>,
        'mata-kuliah': <><path d="M4.5 5.5a2 2 0 0 1 2-2H19v15H6.5a2 2 0 0 0-2 2v-15Z" /><path d="M4.5 17.5a2 2 0 0 1 2-2H19M8 7.5h7M8 10.5h7" strokeLinecap="round" /></>,
        jadwal: <><rect x="3.5" y="5" width="17" height="15.5" rx="2" /><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h3M8 16h3M14 13h2.5" strokeLinecap="round" /></>,
        materi: <><path d="M4 5.5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14H6a2 2 0 0 0-2 2v-16Z" /><path d="M4 18a2 2 0 0 1 2-2h14M8 8h8M8 11.5h8" strokeLinecap="round" /></>,
        tugas: <><rect x="5" y="4.5" width="14" height="17" rx="2" /><path d="M9 4.5v-1h6v1M8.5 10h7M8.5 14h7M8.5 18h4" strokeLinecap="round" /></>,
        'akun-mahasiswa': <><circle cx="9" cy="8" r="3" /><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.8" strokeLinecap="round" /></>,
        profil: <><circle cx="12" cy="8" r="3.3" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" strokeLinecap="round" /></>,
    };

    return (
        <button type="button" aria-current={active ? 'page' : undefined} onClick={onClick} className={`group relative flex min-h-12 w-full items-center gap-3 rounded-2xl border px-3 text-left text-sm font-bold transition duration-200 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:focus:ring-sky-900/40 ${active ? 'border-sky-100 bg-sky-50 text-sky-900 shadow-sm shadow-sky-950/[0.03] dark:border-sky-900 dark:bg-sky-950/70 dark:text-sky-100' : 'border-transparent text-slate-600 hover:translate-x-0.5 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'}`}>
            {active && <span className="absolute bottom-3 left-0 top-3 w-1 rounded-full bg-sky-600" aria-hidden="true"></span>}
            <span className={`grid size-9 shrink-0 place-items-center rounded-xl transition-colors ${active ? 'bg-white text-sky-700 shadow-sm dark:bg-slate-900 dark:text-sky-300' : 'text-slate-400 group-hover:bg-white group-hover:text-sky-700 dark:group-hover:bg-slate-900 dark:group-hover:text-sky-300'}`} aria-hidden="true">
                <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="1.65" strokeLinejoin="round">{icons[destination]}</svg>
            </span>
            <span className="min-w-0 flex-1">{label}</span>
            {active && <svg viewBox="0 0 24 24" className="size-4 shrink-0 text-sky-600 dark:text-sky-300" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>}
        </button>
    );
}

function DashboardPage({ user, onNavigate }) {
    const cacheKey = `user:${user.id}:dashboard`;
    const initialData = getCachedData(cacheKey);
    const [dashboard, setDashboard] = useState(initialData?.data ?? null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(!initialData);

    async function load(force = false) {
        const cached = getCachedData(cacheKey);
        if (cached) {
            setDashboard(cached.data);
            setLoading(false);
            if (!force && cached.fresh) return;
        } else {
            setLoading(true);
        }

        setError('');
        try {
            const response = await axios.get(`${API}/dashboard`);
            setDashboard(response.data.data);
            setCachedData(cacheKey, response.data.data);
            setError('');
        } catch (requestError) {
            setError(cached ? `Data terakhir tetap ditampilkan. ${messageFrom(requestError)}` : messageFrom(requestError));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { load(); }, []);

    if (loading && !dashboard) {
        return (
            <div role="status" aria-label="Memuat ringkasan kelas" className="space-y-5">
                <span className="sr-only">Memuat ringkasan kelas...</span>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{navItems.slice(0, 4).map(([item]) => <div key={item} className="h-28 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div>)}</div>
                <div className="grid gap-4 lg:grid-cols-2">{[1, 2, 3, 4].map((item) => <div key={item} className="h-48 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div>)}</div>
            </div>
        );
    }

    if (error && !dashboard) {
        return <div role="alert" className="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-rose-800 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200"><p className="font-bold">Ringkasan belum dapat dimuat</p><p className="mt-1 text-sm">{error}</p><button type="button" onClick={() => load(true)} className="mt-4 min-h-10 rounded-xl bg-rose-700 px-4 text-sm font-bold text-white focus:outline-none focus:ring-4 focus:ring-rose-200">Coba lagi</button></div>;
    }

    const summary = dashboard.summary;
    const taskCount = summary.jumlah_tugas ?? 0;
    const remainingTasks = summary.jumlah_tugas_belum_selesai ?? 0;
    const completedTasks = Math.max(0, taskCount - remainingTasks);
    return (
        <div className="space-y-6">
            {error && <p role="alert" className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">{error}</p>}
            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard label="Mata kuliah" value={summary.jumlah_mata_kuliah} helper="Lihat daftar" tone="sky" onClick={() => onNavigate('mata-kuliah')} />
                <SummaryCard label="Jadwal hari ini" value={dashboard.jadwal_hari_ini.length} helper="Lihat jadwal" tone="indigo" onClick={() => onNavigate('jadwal')} />
                <SummaryCard label="Deadline dekat" value={summary.jumlah_tugas_mendekat} helper="Dalam 3 hari · Lihat tugas" tone="amber" onClick={() => onNavigate('tugas')} />
                <SummaryCard label={dashboard.is_admin ? 'Tugas dibagikan' : 'Belum selesai'} value={dashboard.is_admin ? taskCount : remainingTasks} helper="Lihat daftar tugas" tone="emerald" onClick={() => onNavigate('tugas')} />
            </section>
            {!dashboard.is_admin && summary.persentase_selesai !== null && (
                <section className={`${cardClass} py-4`}>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div><h2 className="font-bold">Progres tugas</h2><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{completedTasks} dari {taskCount} tugas selesai</p></div>
                        <div className="flex items-center gap-3"><span className="text-sm font-bold text-sky-700 dark:text-sky-300">{summary.persentase_selesai}%</span><button type="button" onClick={() => onNavigate('tugas')} className="min-h-9 rounded-lg px-3 text-sm font-bold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-sky-300 dark:hover:bg-sky-950/60">Perbarui status</button></div>
                    </div>
                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div className="h-full rounded-full bg-sky-600 transition-all duration-500" style={{ width: `${summary.persentase_selesai}%` }} /></div>
                </section>
            )}
            <div className="grid auto-rows-fr gap-6 lg:grid-cols-2">
                <DashboardList type="schedule" title="Jadwal hari ini" items={dashboard.jadwal_hari_ini} destination="jadwal" onNavigate={onNavigate} />
                <DashboardList type="task" title="Deadline terdekat" items={dashboard.tugas_mendekat} showSource={!dashboard.is_admin} destination="tugas" onNavigate={onNavigate} />
                <DashboardList type="material" title="Materi terbaru" items={dashboard.materi_terbaru} showSource={!dashboard.is_admin} destination="materi" onNavigate={onNavigate} />
                <DashboardList type="exam" title="Agenda UTS/UAS" items={dashboard.ujian_mendatang} destination="jadwal" onNavigate={onNavigate} />
            </div>
        </div>
    );
}

function SummaryCard({ label, value, helper, tone, onClick }) {
    const tones = {
        sky: 'bg-sky-600 group-hover:bg-sky-700',
        indigo: 'bg-indigo-600 group-hover:bg-indigo-700',
        amber: 'bg-amber-500 group-hover:bg-amber-600',
        emerald: 'bg-emerald-600 group-hover:bg-emerald-700',
    };

    return (
        <button type="button" onClick={onClick} className={`${cardClass} group relative flex h-full min-h-[132px] w-full flex-col overflow-hidden text-left transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-sky-100 dark:hover:border-slate-700 dark:focus:ring-sky-900/50`}>
            <span className={`absolute inset-y-0 left-0 w-1 transition ${tones[tone]}`} aria-hidden="true"></span>
            <span className="flex items-center justify-between gap-3">
                <span className="text-xs font-bold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">{label}</span>
                <span className="text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-sky-700 dark:group-hover:text-sky-300" aria-hidden="true">→</span>
            </span>
            <span className="mt-3 block text-3xl font-extrabold tracking-tight">{value ?? 0}</span>
            <span className="mt-auto block pt-1 text-xs text-slate-500 dark:text-slate-400">{helper}</span>
        </button>
    );
}

function DashboardList({ type, title, items, showSource = false, destination, onNavigate }) {
    const emptyMessages = {
        schedule: 'Tidak ada jadwal kuliah hari ini.',
        task: 'Tidak ada tugas dengan deadline dekat.',
        material: 'Belum ada materi terbaru.',
        exam: 'Belum ada agenda UTS atau UAS.',
    };

    return (
        <section className={`${cardClass} flex h-full min-h-[300px] flex-col p-0`}>
            <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div className="min-w-0">
                    <h2 className="font-bold">{title}</h2>
                    <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{items.length ? `${items.length} kegiatan` : 'Belum ada kegiatan'}</p>
                </div>
                <button type="button" onClick={() => onNavigate(destination)} aria-label={`Lihat ${title.toLowerCase()}`} className="shrink-0 rounded-lg px-2.5 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-sky-300 dark:hover:bg-sky-950/50">Lihat semua</button>
            </div>
            {items.length ? (
                <ul className="flex-1 divide-y divide-slate-100 px-5 dark:divide-slate-800">
                    {items.slice(0, 4).map((item) => {
                        const primary = type === 'schedule' ? item.course_name || item.nama_mata_kuliah
                            : type === 'task' || type === 'material' ? item.judul
                                : `${item.jenis} · ${item.course_name}`;
                        const secondary = type === 'schedule' ? `${item.waktu_mulai.slice(0, 5)}–${item.waktu_selesai.slice(0, 5)} · ${item.ruang}`
                            : type === 'task' ? `${item.course_name || 'Mata kuliah'} · Batas ${formatDate(item.deadline)}`
                                : type === 'material' ? item.course_name || item.nama_mata_kuliah
                                    : `${formatDate(item.tanggal)} · ${item.waktu_mulai.slice(0, 5)}–${item.waktu_selesai.slice(0, 5)}`;

                        return (
                            <li key={item.id}>
                                <button type="button" onClick={() => onNavigate(destination)} className="flex min-h-16 w-full items-center gap-3 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-slate-800/50">
                                    <span className="min-w-0 flex-1">
                                        <span title={primary} className="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{primary}</span>
                                        <span title={secondary} className="mt-1 block min-h-4 truncate text-xs text-slate-500 dark:text-slate-400">{secondary}</span>
                                    </span>
                                    <span className="flex shrink-0 flex-col items-end gap-1.5">
                                        {type === 'task' && <span className={`rounded-full px-2 py-1 text-[11px] font-bold ${item.prioritas === 'Tinggi' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-200' : item.prioritas === 'Rendah' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200'}`}>{item.prioritas}</span>}
                                        {showSource && <span className={`rounded-full px-2 py-1 text-[11px] font-bold ${item.is_admin_shared ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-200' : 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200'}`}>{item.is_admin_shared ? 'Komting' : 'Pribadi'}</span>}
                                        <span className="pl-1 text-slate-400" aria-hidden="true">›</span>
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            ) : <p className="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">{emptyMessages[type]}</p>}
            {items.length > 4 && <p className="mt-auto border-t border-slate-100 px-5 py-3 text-xs font-semibold text-slate-500 dark:border-slate-800 dark:text-slate-400">+{items.length - 4} kegiatan lainnya</p>}
        </section>
    );
}

const configs = {
    courses: {
        endpoint: 'mata-kuliahs',
        title: 'Mata Kuliah',
        formDescription: 'Isi nama mata kuliah dan informasi pengajarnya.',
        fields: [
            { name: 'nama', label: 'Nama mata kuliah', placeholder: 'Contoh: Pemrograman Web', required: true },
            { name: 'kode_mk', label: 'Kode mata kuliah', placeholder: 'Contoh: IF201', required: true },
            { name: 'dosen', label: 'Nama dosen', placeholder: 'Contoh: Ibu Sari, M.Kom.', required: true },
            { name: 'sks', label: 'Jumlah SKS', type: 'number', min: 1, placeholder: 'Contoh: 3', required: true },
        ],
        itemTitle: (item) => item.nama,
        itemSubtitle: (item) => `${item.kode_mk} · ${item.dosen} · ${item.sks} SKS`,
    },
    materials: {
        endpoint: 'materis',
        title: 'Materi',
        formDescription: 'Isi judul dan pertemuan, lalu tambahkan tautan atau file materi.',
        fields: [
            { name: 'id_mata_kuliah', label: 'Mata kuliah (opsional)', type: 'course', helpText: 'Pilih mata kuliah dari daftar. Jika belum ada, isi nama mata kuliah di bawah.' },
            { name: 'nama_mata_kuliah', label: 'Nama mata kuliah lainnya', placeholder: 'Contoh: Pengantar Desain', helpText: 'Isi jika mata kuliah belum tersedia di daftar.', hideWhenCourseSelected: true, requiredWhenCourseMissing: true },
            { name: 'judul', label: 'Judul materi', placeholder: 'Contoh: Pengenalan HTML', required: true },
            { name: 'pertemuan', label: 'Pertemuan atau topik', placeholder: 'Contoh: Pertemuan 1', required: true },
            { name: 'tipe', label: 'Jenis materi', type: 'select', options: ['Link', 'File'], required: true },
            { name: 'detail', label: 'Tautan atau keterangan materi', placeholder: 'Tempel tautan atau tulis keterangan singkat', helpText: 'Isi tautan jika materi tersedia online. Jika mengunggah file, bagian ini boleh dikosongkan.', wide: true },
            { name: 'file', label: 'Unggah file materi (PDF, Word, atau PowerPoint; maks. 20 MB)', type: 'file', helpText: 'Format yang didukung: PDF, DOC, DOCX, PPT, dan PPTX.', wide: true },
        ],
        itemTitle: (item) => item.judul,
        itemSubtitle: (item) => `${item.course_name} · Pertemuan ${item.pertemuan} · ${item.tipe}`,
    },
    assignments: {
        endpoint: 'tugas',
        title: 'Tugas',
        formDescription: 'Isi instruksi tugas, batas pengumpulan, dan prioritasnya.',
        fields: [
            { name: 'id_mata_kuliah', label: 'Mata kuliah', type: 'course', helpText: 'Pilih mata kuliah yang terkait dengan tugas ini.', required: true },
            { name: 'judul', label: 'Nama tugas', placeholder: 'Contoh: Makalah jaringan komputer', required: true },
            { name: 'deskripsi', label: 'Instruksi tugas', type: 'textarea', placeholder: 'Tuliskan apa yang harus dikerjakan dan cara mengumpulkannya.', required: true, wide: true },
            { name: 'deadline', label: 'Batas pengumpulan', type: 'date', helpText: 'Pilih tanggal terakhir tugas dikumpulkan.', required: true },
            { name: 'prioritas', label: 'Prioritas tugas', type: 'select', options: ['Tinggi', 'Sedang', 'Rendah'], required: true },
            { name: 'file', label: 'Lampiran tugas (opsional, maks. 20 MB)', type: 'file', helpText: 'Format yang didukung: PDF, DOC, DOCX, PPT, dan PPTX.', wide: true },
        ],
        itemTitle: (item) => item.judul,
        itemSubtitle: (item) => `${item.course_name} · Deadline ${item.deadline} · ${item.prioritas}`,
    },
};

function ResourcePage({ kind, user, notify }) {
    const config = configs[kind];
    const canAdd = kind !== 'courses' || user.role === 'admin';
    const showSourceOnCard = user.role !== 'admin' && (kind === 'assignments' || kind === 'materials');
    const filterable = kind === 'assignments' || kind === 'materials';
    const formToggleable = filterable || kind === 'courses';
    const emptyFilters = { search: '', course_id: '', date_from: '', date_to: '', source: '', status: '', type: '' };
    const cacheKeyFor = (targetPage, activeFilters) => {
        const params = new URLSearchParams();
        if (filterable) {
            Object.entries(activeFilters).forEach(([name, value]) => {
                if (value) params.set(name, value);
            });
        }
        return `user:${user.id}:resource:${kind}:${targetPage}:${params.toString()}`;
    };
    const courseCacheKey = `user:${user.id}:courses`;
    const initialData = getCachedData(cacheKeyFor(1, emptyFilters));
    const initialCourses = getCachedData(courseCacheKey);
    const [items, setItems] = useState(initialData?.data.items ?? []);
    const [courses, setCourses] = useState(initialCourses?.data ?? []);
    const [filters, setFilters] = useState(emptyFilters);
    const [filterDraft, setFilterDraft] = useState(emptyFilters);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [formOpen, setFormOpen] = useState(false);
    const [page, setPage] = useState(initialData?.data.currentPage ?? 1);
    const [hasNext, setHasNext] = useState(initialData?.data.hasNext ?? false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({});
    const [loading, setLoading] = useState(!initialData);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [expandedCourses, setExpandedCourses] = useState({});

    async function load(targetPage = page, force = false, activeFilters = filters) {
        const cacheKey = cacheKeyFor(targetPage, activeFilters);
        const cached = getCachedData(cacheKey);
        if (cached) {
            setItems(cached.data.items);
            setPage(cached.data.currentPage);
            setHasNext(cached.data.hasNext);
            setLoading(false);
            if (!force && cached.fresh) return;
        } else {
            setLoading(true);
        }

        const cachedCourses = getCachedData(courseCacheKey);
        const refreshCourses = kind !== 'courses' && (!cachedCourses || !cachedCourses.fresh);
        if (cachedCourses) setCourses(cachedCourses.data);
        setError('');
        try {
            const params = { page: targetPage };
            if (filterable) {
                Object.entries(activeFilters).forEach(([name, value]) => {
                    if (value) params[name] = value;
                });
            }
            const requests = [axios.get(`${API}/${config.endpoint}`, { params })];
            if (refreshCourses) {
                requests.push(axios.get(`${API}/mata-kuliahs`, { params: { per_page: 100 } }));
            }
            const [response, courseResponse] = await Promise.all(requests);
            const data = {
                items: response.data.data,
                currentPage: response.data.current_page || targetPage,
                hasNext: Boolean(response.data.next_page_url),
            };
            setItems(data.items);
            setPage(data.currentPage);
            setHasNext(data.hasNext);
            setCachedData(cacheKey, data);
            if (courseResponse) {
                setCourses(courseResponse.data.data);
                setCachedData(courseCacheKey, courseResponse.data.data);
            }
            setError('');
        } catch (requestError) {
            setError(cached ? `Data terakhir tetap ditampilkan. ${messageFrom(requestError)}` : messageFrom(requestError));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        setFilters(emptyFilters);
        setFilterDraft(emptyFilters);
        setFiltersOpen(false);
        setExpandedCourses({});
        load(1, false, emptyFilters);
    }, [kind]);

    function applyFilters(event) {
        event.preventDefault();
        const nextFilters = { ...filterDraft };
        setFilters(nextFilters);
        setFiltersOpen(false);
        setExpandedCourses({});
        load(1, true, nextFilters);
    }

    function resetFilters() {
        setFilters(emptyFilters);
        setFilterDraft(emptyFilters);
        setFiltersOpen(false);
        setExpandedCourses({});
        load(1, true, emptyFilters);
    }

    function removeFilter(name) {
        const nextFilters = { ...filters };
        if (name === 'date_range') {
            nextFilters.date_from = '';
            nextFilters.date_to = '';
        } else {
            nextFilters[name] = '';
        }
        setFilters(nextFilters);
        setFilterDraft(nextFilters);
        setFiltersOpen(false);
        setExpandedCourses({});
        load(1, true, nextFilters);
    }

    function changePage(targetPage) {
        setExpandedCourses({});
        load(targetPage);
    }

    function startEdit(item) {
        setEditing(item);
        setFormOpen(true);
        setError('');
        setForm(Object.fromEntries(config.fields.filter((field) => field.type !== 'file').map((field) => [field.name, item[field.name] ?? ''])));
    }

    function resetForm() {
        setEditing(null);
        setForm({});
        setError('');
        if (formToggleable) setFormOpen(false);
    }

    function openCreateForm() {
        setEditing(null);
        setForm({});
        setError('');
        setFormOpen(true);
    }

    function renderField(field) {
        const fieldElement = <FormField key={field.name} field={field} value={form[field.name] ?? ''} form={form} courses={courses} onChange={(value) => setForm((current) => ({ ...current, [field.name]: value }))} />;

        return formToggleable
            ? <div key={field.name} className={field.wide ? 'md:col-span-2' : ''}>{fieldElement}</div>
            : fieldElement;
    }

    async function save(event) {
        event.preventDefault();
        setSaving(true);
        setError('');
        const body = new FormData();
        Object.entries(form).forEach(([name, value]) => {
            if (value !== undefined && value !== null) body.append(name, value);
        });
        const file = event.currentTarget.elements.namedItem('file')?.files?.[0];
        if (file) body.append('file', file);
        if (editing) body.append('_method', 'PATCH');
        try {
            await axios.post(`${API}/${config.endpoint}${editing ? `/${editing.id}` : ''}`, body);
            clearUserDataCache(user.id);
            notify(`${config.title} berhasil disimpan.`);
            resetForm();
            await load(1, true);
        } catch (requestError) {
            setError(messageFrom(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function remove(item) {
        if (!window.confirm(`Hapus "${config.itemTitle(item)}"?`)) return;
        try {
            await axios.delete(`${API}/${config.endpoint}/${item.id}`);
            clearUserDataCache(user.id);
            notify(`${config.title} berhasil dihapus.`);
            await load(page, true);
        } catch (requestError) {
            setError(messageFrom(requestError));
        }
    }

    async function updateStatus(item, status) {
        try {
            await axios.patch(`${API}/tugas/${item.id}/status`, { status });
            clearUserDataCache(user.id);
            notify('Status tugas diperbarui.');
            await load(page, true);
        } catch (requestError) {
            setError(messageFrom(requestError));
        }
    }

    function renderItem(item) {
        if (!filterable) {
            return (
                <article key={item.id} className={`${cardClass} group transition duration-200 hover:border-sky-200 hover:shadow-md dark:hover:border-sky-800`}>
                    <p className="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Mata kuliah</p>
                    <h2 className="mt-1 break-words text-lg font-bold">{config.itemTitle(item)}</h2>
                    <div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700 dark:bg-slate-800 dark:text-slate-200">Kode: {item.kode_mk}</span>
                        <span className="rounded-full bg-sky-50 px-2.5 py-1 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200">{item.sks} SKS</span>
                    </div>
                    <p className="mt-3 text-sm text-slate-600 dark:text-slate-300">Dosen: {item.dosen}</p>
                    {item.can_manage && <div className="mt-4 flex gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <button type="button" onClick={() => startEdit(item)} className="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Edit</button>
                        <button type="button" onClick={() => remove(item)} className="text-sm font-semibold text-rose-700 dark:text-rose-300">Hapus</button>
                    </div>}
                </article>
            );
        }

        const isAssignment = kind === 'assignments';
        const materialLink = !isAssignment && item.tipe === 'Link' && isHttpUrl(item.detail);
        const taskDescription = String(item.deskripsi || '').trim();
        const materialDescription = materialLink ? '' : String(item.detail || '').trim();
        const deadline = isAssignment && item.my_status !== 'Selesai' ? deadlineInfo(item.deadline) : null;
        const sourceAccent = showSourceOnCard
            ? item.is_admin_shared
                ? 'border-l-4 border-l-violet-500 ring-1 ring-violet-100 dark:border-l-violet-400 dark:ring-violet-950'
                : 'border-l-4 border-l-sky-500 ring-1 ring-sky-100 dark:border-l-sky-400 dark:ring-sky-950'
            : '';
        const priorityClass = item.prioritas === 'Tinggi'
            ? 'bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-200'
            : item.prioritas === 'Rendah'
                ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
                : 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200';

        return (
            <article key={item.id} className={`${cardClass} ${sourceAccent} group flex h-full ${isAssignment ? 'min-h-[360px] gap-3' : 'min-h-[420px] gap-4'} flex-col transition duration-200 hover:border-sky-200 hover:shadow-md dark:hover:border-sky-800`}>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className={`rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide ${isAssignment ? 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200' : 'bg-sky-50 text-sky-800 dark:bg-sky-950 dark:text-sky-200'}`}>
                            {isAssignment ? 'Tugas' : 'Materi'}
                        </span>
                        {showSourceOnCard && <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs font-bold ${item.is_admin_shared ? 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-800 dark:bg-violet-950/70 dark:text-violet-200' : 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800 dark:bg-sky-950/70 dark:text-sky-200'}`}>
                            <svg viewBox="0 0 24 24" className="size-3.5" fill="none" aria-hidden="true">
                                {item.is_admin_shared
                                    ? <><circle cx="9" cy="8" r="3" stroke="currentColor" strokeWidth="1.7" /><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.8" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></>
                                    : <><circle cx="12" cy="8" r="3.2" stroke="currentColor" strokeWidth="1.7" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></>}
                            </svg>
                            {item.is_admin_shared ? 'Dari Komting' : 'Pribadi'}
                        </span>}
                    </div>
                    {isAssignment
                        ? <span className={`rounded-full px-3 py-1.5 text-xs font-bold ${priorityClass}`}>Prioritas {item.prioritas || 'Sedang'}</span>
                        : <span className="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{item.tipe === 'Link' ? 'Tautan online' : 'Berkas'}</span>}
                </div>
                <div className="min-w-0">
                    <p title={item.course_name || item.nama_mata_kuliah} className="truncate text-sm font-bold text-slate-600 dark:text-slate-300">{item.course_name || item.nama_mata_kuliah || 'Mata kuliah belum ditentukan'}</p>
                    <h2 title={config.itemTitle(item)} className={`mt-1 line-clamp-2 ${isAssignment ? '' : 'min-h-[3.5rem]'} break-words text-lg font-extrabold leading-7 tracking-tight text-slate-950 dark:text-white`}>{config.itemTitle(item)}</h2>
                </div>
                {isAssignment ? (
                    <div className={`flex flex-wrap items-center justify-between gap-2 rounded-xl border px-3 py-2 ${deadline?.tone || 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60'}`}>
                        <div>
                            <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Batas pengumpulan</p>
                            <p className="text-sm font-bold text-slate-900 dark:text-white">{formatDate(item.deadline)}</p>
                        </div>
                        {deadline && <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${deadline.badgeTone}`}>{deadline.label}</span>}
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pertemuan atau topik</p>
                        <p className="mt-1 text-sm font-bold text-slate-900 dark:text-white">{item.pertemuan || 'Belum dicantumkan'}</p>
                    </div>
                )}
                <div className={`rounded-xl bg-slate-50 ${isAssignment ? 'p-2.5' : 'p-3'} dark:bg-slate-800/60`}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{isAssignment ? 'Instruksi tugas' : 'Keterangan materi'}</p>
                    <p className={`mt-1 ${isAssignment ? '' : 'min-h-10'} line-clamp-2 whitespace-pre-line break-words text-sm leading-5 text-slate-700 dark:text-slate-200`}>
                        {isAssignment
                            ? taskDescription || 'Instruksi belum tersedia.'
                            : materialDescription || (materialLink ? 'Materi tersedia melalui tautan online.' : item.download_url ? 'Berkas materi siap diunduh.' : 'Belum ada keterangan tambahan.')}
                    </p>
                    {isAssignment && taskDescription.length > 90 && <details className="mt-2 border-t border-slate-200 pt-2 dark:border-slate-700">
                        <summary className="cursor-pointer text-xs font-bold text-sky-700 dark:text-sky-300">Baca instruksi lengkap</summary>
                        <p className="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-600 dark:text-slate-300">{taskDescription}</p>
                    </details>}
                    {!isAssignment && materialDescription.length > 90 && <details className="mt-2 border-t border-slate-200 pt-2 dark:border-slate-700">
                        <summary className="cursor-pointer text-xs font-bold text-sky-700 dark:text-sky-300">Baca keterangan lengkap</summary>
                        <p className="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-600 dark:text-slate-300">{materialDescription}</p>
                    </details>}
                </div>
                <div className="mt-auto space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    {isAssignment && item.my_status !== null && (
                        <label className="flex min-h-11 items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <span className="shrink-0 text-xs font-bold text-slate-600 dark:text-slate-300">Status tugas</span>
                            <select aria-label={`Status ${item.judul}`} value={item.my_status || 'Belum dikerjakan'} onChange={(event) => updateStatus(item, event.target.value)} className="min-h-9 min-w-0 max-w-[210px] flex-1 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-semibold text-slate-800 focus:border-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                {['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'].map((status) => <option key={status}>{status}</option>)}
                            </select>
                        </label>
                    )}
                    <div className="flex flex-wrap gap-2">
                        {!isAssignment && materialLink && <a className="inline-flex min-h-11 min-w-[140px] flex-1 items-center justify-center rounded-xl bg-sky-700 px-4 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200" href={item.detail} target="_blank" rel="noreferrer">Buka materi online</a>}
                        {item.download_url && <a className="inline-flex min-h-11 min-w-[140px] flex-1 items-center justify-center rounded-xl bg-indigo-50 px-4 text-sm font-bold text-indigo-800 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100 dark:bg-indigo-950 dark:text-indigo-200 dark:hover:bg-indigo-900 dark:focus:ring-indigo-900" href={item.download_url}>{isAssignment ? 'Unduh lampiran tugas' : 'Unduh file materi'}</a>}
                        {!isAssignment && !materialLink && !item.download_url && <p className="text-xs text-slate-500 dark:text-slate-400">Belum ada file atau tautan untuk dibuka.</p>}
                    </div>
                    {item.can_manage && <div className="flex gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                        <button type="button" onClick={() => startEdit(item)} className="min-h-9 rounded-lg px-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-100 dark:text-indigo-300 dark:hover:bg-indigo-950">Edit</button>
                        <button type="button" onClick={() => remove(item)} className="min-h-9 rounded-lg px-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100 dark:text-rose-300 dark:hover:bg-rose-950">Hapus</button>
                    </div>}
                </div>
            </article>
        );
    }

    const courseGroups = [];
    const groupsByCourse = new Map();
    items.forEach((item) => {
        const name = item.course_name || item.nama_mata_kuliah || 'Mata kuliah belum ditentukan';
        const key = item.id_mata_kuliah ? `id:${item.id_mata_kuliah}` : `name:${name.toLowerCase()}`;
        let group = groupsByCourse.get(key);
        if (!group) {
            group = { key, name, items: [] };
            groupsByCourse.set(key, group);
            courseGroups.push(group);
        }
        group.items.push(item);
    });
    const hasActiveFilters = filterable && Object.values(filters).some(Boolean);
    const activeAdvancedFilterCount = Number(Boolean(filters.course_id))
        + Number(Boolean(filters.date_from || filters.date_to))
        + Number(Boolean(kind === 'assignments' && user.role !== 'admin' && filters.source))
        + Number(Boolean(kind === 'assignments' && user.role !== 'admin' && filters.status))
        + Number(Boolean(kind === 'materials' && filters.type));
    const activeFilterChips = [
        filters.search && { name: 'search', label: `Kata kunci: ${filters.search}` },
        filters.course_id && { name: 'course_id', label: `Mata kuliah: ${courses.find((course) => String(course.id) === String(filters.course_id))?.nama || 'Dipilih'}` },
        (filters.date_from || filters.date_to) && {
            name: 'date_range',
            label: `${kind === 'assignments' ? 'Deadline' : 'Tanggal materi'}: ${filters.date_from ? formatDate(filters.date_from) : 'awal'} – ${filters.date_to ? formatDate(filters.date_to) : 'akhir'}`,
        },
        kind === 'assignments' && user.role !== 'admin' && filters.status && {
            name: 'status',
            label: `Status: ${filters.status}`,
        },
        kind === 'assignments' && user.role !== 'admin' && filters.source && {
            name: 'source',
            label: `Sumber: ${filters.source === 'shared' ? 'Dari Komting' : 'Pribadi'}`,
        },
        kind === 'materials' && filters.type && {
            name: 'type',
            label: `Jenis: ${filters.type === 'Link' ? 'Tautan online' : 'Berkas'}`,
        },
    ].filter(Boolean);

    return (
        <div className={formToggleable ? 'space-y-5' : canAdd || editing ? 'grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]' : 'space-y-6'}>
            {formToggleable && <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm font-bold text-slate-800 dark:text-slate-100">{kind === 'materials' ? 'Pustaka materi' : kind === 'assignments' ? 'Daftar tugas' : 'Daftar mata kuliah'}</p>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{kind === 'materials' ? 'Cari bahan kuliah atau tambahkan materi baru.' : kind === 'assignments' ? 'Temukan deadline dan pantau status tugasmu.' : 'Pilih mata kuliah untuk melihat atau mengatur informasinya.'}</p>
                </div>
                {canAdd && <button type="button" onClick={openCreateForm} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-sky-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">
                    <span className="text-lg leading-none" aria-hidden="true">+</span>
                    {`Tambah ${config.title}`}
                </button>}
            </div>}
            <section className="space-y-4">
                {error && !formOpen && <p role="alert" className="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{error}</p>}
                {filterable && <form onSubmit={applyFilters} className={`${cardClass} space-y-4 p-4`}>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <label className="flex min-h-11 min-w-0 flex-1 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 transition focus-within:border-sky-600 focus-within:ring-4 focus-within:ring-sky-100 dark:border-slate-700 dark:bg-slate-950 dark:focus-within:border-sky-400 dark:focus-within:ring-sky-900/40">
                            <span className="sr-only">Cari berdasarkan judul atau keterangan</span>
                            <svg viewBox="0 0 24 24" className="size-5 shrink-0 text-slate-400" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" strokeWidth="1.7" /><path d="m16 16 4.5 4.5" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
                            <input type="search" value={filterDraft.search} onChange={(event) => setFilterDraft((current) => ({ ...current, search: event.target.value }))} placeholder={`Cari ${kind === 'materials' ? 'materi' : 'tugas'}...`} className="min-w-0 flex-1 bg-transparent py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-slate-100" />
                        </label>
                        <button type="submit" className="min-h-11 rounded-xl bg-sky-700 px-5 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">Cari</button>
                        <button type="button" onClick={() => setFiltersOpen((open) => !open)} aria-expanded={filtersOpen} aria-controls={`${kind}-advanced-filters`} className={`inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border px-4 text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-sky-100 dark:focus:ring-sky-900/40 ${filtersOpen || activeAdvancedFilterCount > 0 ? 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-800 dark:bg-sky-950/60 dark:text-sky-200' : 'border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'}`}>
                            <svg viewBox="0 0 24 24" className="size-4" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" /></svg>
                            <span>Filter</span>
                            {activeAdvancedFilterCount > 0 && <span className="grid size-5 place-items-center rounded-full bg-sky-700 text-xs font-bold text-white">{activeAdvancedFilterCount}</span>}
                            <span aria-hidden="true">{filtersOpen ? '−' : '+'}</span>
                        </button>
                    </div>
                    {hasActiveFilters && <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                        <span className="mr-1 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Filter aktif</span>
                        {activeFilterChips.map((chip) => <button key={chip.name} type="button" onClick={() => removeFilter(chip.name)} aria-label={`Hapus ${chip.label}`} className="inline-flex min-h-8 max-w-full items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 text-xs font-semibold text-sky-800 transition hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-sky-900 dark:bg-sky-950/70 dark:text-sky-200 dark:hover:bg-sky-900">
                            <span className="max-w-[240px] truncate">{chip.label}</span>
                            <span aria-hidden="true" className="text-sm leading-none">×</span>
                        </button>)}
                        <button type="button" onClick={resetFilters} className="min-h-8 rounded-full px-3 text-xs font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Hapus semua</button>
                    </div>}
                    {filtersOpen && <div id={`${kind}-advanced-filters`} className="space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="text-sm font-extrabold text-slate-900 dark:text-slate-100">Filter tambahan</h3>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{kind === 'materials' ? 'Pilih mata kuliah, jenis materi, atau tanggal penambahan.' : user.role === 'admin' ? 'Pilih mata kuliah atau rentang deadline.' : 'Pilih mata kuliah, deadline, sumber, atau status tugas.'}</p>
                            </div>
                            <button type="button" onClick={resetFilters} className="min-h-8 shrink-0 rounded-lg px-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Hapus semua</button>
                        </div>
                        <div className={`grid gap-3 sm:grid-cols-2 ${kind === 'assignments' ? user.role === 'admin' ? 'xl:grid-cols-3' : 'xl:grid-cols-5' : 'xl:grid-cols-4'}`}>
                            <label className="block text-sm font-medium">
                                <span>Mata kuliah</span>
                                <select value={filterDraft.course_id} onChange={(event) => setFilterDraft((current) => ({ ...current, course_id: event.target.value }))} className={fieldClass}>
                                    <option value="">Semua mata kuliah</option>
                                    {courses.map((course) => <option key={course.id} value={course.id}>{course.nama}</option>)}
                                </select>
                            </label>
                            {kind === 'assignments' && user.role !== 'admin' && <label className="block text-sm font-medium">
                                <span>Sumber tugas</span>
                                <select value={filterDraft.source} onChange={(event) => setFilterDraft((current) => ({ ...current, source: event.target.value }))} className={fieldClass}>
                                    <option value="">Semua sumber</option>
                                    <option value="shared">Dari Komting</option>
                                    <option value="personal">Pribadi</option>
                                </select>
                            </label>}
                            {kind === 'assignments' && user.role !== 'admin' && <label className="block text-sm font-medium">
                                <span>Status tugas</span>
                                <select value={filterDraft.status} onChange={(event) => setFilterDraft((current) => ({ ...current, status: event.target.value }))} className={fieldClass}>
                                    <option value="">Semua status</option>
                                    {['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'].map((status) => <option key={status}>{status}</option>)}
                                </select>
                            </label>}
                            {kind === 'materials' && <label className="block text-sm font-medium">
                                <span>Jenis materi</span>
                                <select value={filterDraft.type} onChange={(event) => setFilterDraft((current) => ({ ...current, type: event.target.value }))} className={fieldClass}>
                                    <option value="">Semua jenis</option>
                                    <option value="Link">Tautan online</option>
                                    <option value="File">Berkas</option>
                                </select>
                            </label>}
                            <label className="block text-sm font-medium">
                                <span>{kind === 'assignments' ? 'Deadline dari' : 'Tanggal dari'}</span>
                                <input type="date" value={filterDraft.date_from} onChange={(event) => setFilterDraft((current) => ({ ...current, date_from: event.target.value }))} className={fieldClass} />
                            </label>
                            <label className="block text-sm font-medium">
                                <span>{kind === 'assignments' ? 'Deadline sampai' : 'Tanggal sampai'}</span>
                                <input type="date" value={filterDraft.date_to} onChange={(event) => setFilterDraft((current) => ({ ...current, date_to: event.target.value }))} className={fieldClass} />
                            </label>
                        </div>
                        <div className="flex flex-col justify-between gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center dark:border-slate-800">
                            <p className="text-xs leading-5 text-slate-500 dark:text-slate-400">{kind === 'materials' ? 'Tanggal mengacu pada waktu materi ditambahkan.' : 'Tanggal mengacu pada batas pengumpulan tugas.'}</p>
                            <button type="submit" className="min-h-10 rounded-xl bg-sky-700 px-4 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">Terapkan filter</button>
                        </div>
                    </div>}
                </form>}
                {loading ? <div role="status" aria-label={`Memuat ${config.title.toLowerCase()}`} className="space-y-3"><span className="sr-only">Memuat data...</span><div className="h-28 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div><div className="h-28 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div></div> : items.length ? filterable ? (
                    <div className="space-y-3">
                        {courseGroups.map((group, index) => {
                            const hasExpansionOverride = Object.prototype.hasOwnProperty.call(expandedCourses, group.key);
                            const expanded = hasExpansionOverride ? expandedCourses[group.key] : hasActiveFilters;
                            const panelId = `${kind}-course-group-${index}`;

                            return <section key={group.key} className={`${cardClass} overflow-hidden p-0 transition hover:border-slate-300 dark:hover:border-slate-700`}>
                                <button type="button" aria-expanded={expanded} aria-controls={panelId} onClick={() => setExpandedCourses((current) => {
                                    const currentExpanded = Object.prototype.hasOwnProperty.call(current, group.key) ? current[group.key] : hasActiveFilters;
                                    return { ...current, [group.key]: !currentExpanded };
                                })} className="flex min-h-[76px] w-full items-center gap-3 p-4 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-slate-800/60 sm:p-5">
                                    <span className={`grid size-11 shrink-0 place-items-center rounded-xl ${kind === 'assignments' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300'}`} aria-hidden="true">
                                        {kind === 'assignments'
                                            ? <svg viewBox="0 0 24 24" className="size-5" fill="none"><path d="M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v14.25H5.25V6A2.25 2.25 0 0 1 7.5 3.75Z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" /><path d="M8.5 9h7M8.5 12.5h7M8.5 16h4" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>
                                            : <svg viewBox="0 0 24 24" className="size-5" fill="none"><path d="M5 4.75A1.75 1.75 0 0 1 6.75 3H20v16H6.75A1.75 1.75 0 0 0 5 20.75v-16Z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" /><path d="M5 18.5c0-1 .8-1.75 1.75-1.75H20M8.5 7h7M8.5 10.5h7" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" /></svg>}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Mata kuliah</span>
                                        <span title={group.name} className="mt-0.5 block truncate text-base font-extrabold text-slate-900 dark:text-slate-100">{group.name}</span>
                                    </span>
                                    <span className="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{group.items.length} {kind === 'assignments' ? 'tugas' : 'materi'}</span>
                                    <svg viewBox="0 0 24 24" className={`size-5 shrink-0 text-slate-500 transition-transform dark:text-slate-400 ${expanded ? 'rotate-180' : ''}`} fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                </button>
                                {expanded && <div id={panelId} className="border-t border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
                                    <div className="grid auto-rows-fr gap-4 xl:grid-cols-2">{group.items.map(renderItem)}</div>
                                </div>}
                            </section>;
                        })}
                    </div>
                ) : <div className="grid auto-rows-fr gap-4 xl:grid-cols-2">{items.map(renderItem)}</div> : <div className={`${cardClass} px-6 py-10 text-center`}>
                    <span className="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true"><span className="text-xl">{hasActiveFilters ? '⌕' : '＋'}</span></span>
                    <h2 className="mt-4 font-bold">{hasActiveFilters ? 'Tidak ada hasil yang cocok' : `Belum ada ${config.title.toLowerCase()}`}</h2>
                    <p className="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">{hasActiveFilters ? 'Coba ubah kata kunci, mata kuliah, atau rentang tanggal.' : kind === 'materials' ? 'Materi yang ditambahkan akan muncul di sini.' : kind === 'assignments' ? 'Tugas yang dibagikan atau kamu tambahkan akan muncul di sini.' : 'Mata kuliah yang tersedia akan muncul di sini.'}</p>
                    {hasActiveFilters && <button type="button" onClick={resetFilters} className="mt-4 text-sm font-bold text-sky-700 hover:text-sky-800 dark:text-sky-300 dark:hover:text-sky-200">Hapus semua filter</button>}
                </div>}
                {(page > 1 || hasNext) && <div className="flex items-center justify-between"><button type="button" disabled={page <= 1 || loading} onClick={() => changePage(page - 1)} className="min-h-10 rounded-xl border border-slate-200 px-4 text-sm font-semibold transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:hover:bg-slate-800">Sebelumnya</button><span className="text-sm text-slate-500 dark:text-slate-400">Halaman {page}</span><button type="button" disabled={!hasNext || loading} onClick={() => changePage(page + 1)} className="min-h-10 rounded-xl border border-slate-200 px-4 text-sm font-semibold transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:hover:bg-slate-800">Berikutnya</button></div>}
            </section>
            {formOpen && (canAdd || editing) && <div role="dialog" aria-modal="true" aria-labelledby={`${kind}-form-title`} className="fixed inset-0 z-40 grid place-items-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm">
                <form onSubmit={save} className={`${cardClass} my-auto max-h-[calc(100vh-2rem)] w-full max-w-2xl space-y-4 overflow-y-auto shadow-2xl`}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">{kind === 'courses' ? 'Informasi mata kuliah' : kind === 'materials' ? 'Bahan belajar' : 'Tugas perkuliahan'}</p>
                            <h2 id={`${kind}-form-title`} className="mt-1 text-xl font-extrabold">{editing ? `Edit ${config.title}` : `Tambah ${config.title}`}</h2>
                            <p className="mt-1 text-sm font-normal leading-6 text-slate-500 dark:text-slate-400">{config.formDescription} Kolom bertanda * wajib diisi.</p>
                        </div>
                        <button type="button" onClick={resetForm} aria-label="Tutup formulir" className="grid size-9 shrink-0 place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:hover:bg-slate-800">×</button>
                    </div>
                    {error && <p role="alert" className="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">{error}</p>}
                    <div className="grid gap-4 md:grid-cols-2">
                        {config.fields.filter((field) => (!field.hideWhenCourseSelected || !form.id_mata_kuliah) && (!field.showWhen || field.showWhen(form))).map(renderField)}
                    </div>
                    {kind === 'materials' && editing?.download_url && <label className="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-700"><input type="checkbox" checked={Boolean(form.remove_file)} onChange={(event) => setForm((current) => ({ ...current, remove_file: event.target.checked ? '1' : '' }))} className="mt-0.5" /><span><span className="font-semibold">Hapus file yang saat ini terlampir</span><span className="mt-1 block text-xs font-normal text-slate-500 dark:text-slate-400">Centang jika file lama tidak ingin disimpan lagi.</span></span></label>}
                    <div className="flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button disabled={saving} className="min-h-11 rounded-xl bg-sky-700 px-5 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-wait disabled:opacity-60">{saving ? 'Menyimpan...' : editing ? 'Simpan Perubahan' : `Simpan ${config.title}`}</button>
                        <button type="button" onClick={resetForm} className="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:hover:bg-slate-800">Batal</button>
                    </div>
                </form>
            </div>}
        </div>
    );
}

function FormField({ field, value, form, courses, onChange }) {
    const required = field.required
        || (field.requiredWhenOnline && form.mode === 'Online')
        || (field.requiredWhenCourseMissing && !form.id_mata_kuliah);

    return (
        <label className="block text-sm font-medium">
            <span>{field.label}{required && <span className="ml-1 text-rose-600" aria-hidden="true">*</span>}</span>
            <FieldInput field={field} value={value} required={required} courses={courses} onChange={onChange} />
            {field.helpText && <span className="mt-1 block text-xs font-normal leading-5 text-slate-500 dark:text-slate-400">{field.helpText}</span>}
        </label>
    );
}

function FieldInput({ field, value, required = field.required, courses = [], onChange }) {
    if (field.type === 'file') return <input name={field.name} type="file" accept={field.accept || '.pdf,.doc,.docx,.ppt,.pptx'} required={required} className={fieldClass} />;
    if (field.type === 'course') {
        return <select required={required} value={value} onChange={(event) => onChange(event.target.value)} className={fieldClass}>
            <option value="">{field.placeholder || 'Pilih mata kuliah'}</option>
            {courses.map((course) => <option key={course.id} value={course.id}>{course.nama}</option>)}
        </select>;
    }
    if (field.type === 'select') {
        return <select required={required} value={value} onChange={(event) => onChange(event.target.value)} className={fieldClass}>
            <option value="">Pilih {field.label.toLowerCase()}</option>
            {field.options.map((option) => {
                const label = field.name === 'tipe' && option === 'Link' ? 'Tautan'
                    : field.name === 'mode' && option === 'Offline' ? 'Tatap muka'
                        : field.name === 'mode' && option === 'Online' ? 'Daring'
                            : option;
                return <option key={option} value={option}>{label}</option>;
            })}
        </select>;
    }
    if (field.type === 'textarea') return <textarea required={required} value={value} onChange={(event) => onChange(event.target.value)} placeholder={field.placeholder} rows={field.rows || 4} className={fieldClass} />;
    return <input required={required} min={field.min} type={field.type || 'text'} value={value} onChange={(event) => onChange(event.target.value)} placeholder={field.placeholder} className={fieldClass} />;
}

function SchedulePage({ user, notify }) {
    const cacheKey = `user:${user.id}:schedule`;
    const initialData = getCachedData(cacheKey);
    const [schedules, setSchedules] = useState(initialData?.data.schedules ?? []);
    const [exams, setExams] = useState(initialData?.data.exams ?? []);
    const [courses, setCourses] = useState(initialData?.data.courses ?? []);
    const [editor, setEditor] = useState(null);
    const [form, setForm] = useState({});
    const [loading, setLoading] = useState(!initialData);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function load(force = false) {
        const cached = getCachedData(cacheKey);
        if (cached) {
            setSchedules(cached.data.schedules);
            setExams(cached.data.exams);
            setCourses(cached.data.courses);
            setLoading(false);
            if (!force && cached.fresh) return;
        } else {
            setLoading(true);
        }

        setError('');
        try {
            const [scheduleResponse, examResponse, courseResponse] = await Promise.all([
                axios.get(`${API}/jadwals`),
                axios.get(`${API}/agenda-ujians`),
                axios.get(`${API}/mata-kuliahs`, { params: { per_page: 100 } }),
            ]);
            const data = {
                schedules: scheduleResponse.data.data,
                exams: examResponse.data.data,
                courses: courseResponse.data.data,
            };
            setSchedules(data.schedules);
            setExams(data.exams);
            setCourses(data.courses);
            setCachedData(cacheKey, data);
            setError('');
        } catch (requestError) {
            setError(cached ? `Data terakhir tetap ditampilkan. ${messageFrom(requestError)}` : messageFrom(requestError));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { load(); }, []);

    const scheduleFields = [
        { name: 'id_mata_kuliah', label: 'Mata kuliah', type: 'course', helpText: 'Pilih mata kuliah untuk jadwal ini.', required: true },
        { name: 'hari', label: 'Hari kuliah', type: 'select', options: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], required: true },
        { name: 'waktu_mulai', label: 'Waktu mulai', type: 'time', helpText: 'Waktu dalam zona waktu setempat.', required: true },
        { name: 'waktu_selesai', label: 'Waktu selesai', type: 'time', helpText: 'Harus setelah waktu mulai.', required: true },
        { name: 'ruang', label: 'Ruang kelas', placeholder: 'Contoh: Gedung A, ruang 201', required: true },
        { name: 'dosen', label: 'Dosen pengampu', placeholder: 'Nama dosen', required: true },
        { name: 'mode', label: 'Metode perkuliahan', type: 'select', options: ['Offline', 'Online'], required: true },
        { name: 'link_zoom', label: 'Tautan perkuliahan online', placeholder: 'https://...', showWhen: (values) => values.mode === 'Online', helpText: 'Tambahkan tautan pertemuan online jika tersedia.' },
    ];
    const examFields = [
        { name: 'id_mata_kuliah', label: 'Mata kuliah', type: 'course', helpText: 'Pilih mata kuliah untuk agenda ujian ini.', required: true },
        { name: 'jenis', label: 'Jenis ujian', type: 'select', options: ['UTS', 'UAS'], required: true },
        { name: 'tanggal', label: 'Tanggal ujian', type: 'date', required: true },
        { name: 'waktu_mulai', label: 'Waktu mulai', type: 'time', required: true },
        { name: 'waktu_selesai', label: 'Waktu selesai', type: 'time', helpText: 'Harus setelah waktu mulai.', required: true },
        { name: 'ruang', label: 'Ruang ujian (opsional)', placeholder: 'Contoh: Gedung A, ruang 201' },
        { name: 'mode', label: 'Metode ujian', type: 'select', options: ['Offline', 'Online'], required: true },
        { name: 'link_zoom', label: 'Tautan ujian online', placeholder: 'https://...', showWhen: (values) => values.mode === 'Online', requiredWhenOnline: true, helpText: 'Wajib diisi untuk ujian online.' },
        { name: 'catatan', label: 'Catatan tambahan (opsional)', type: 'textarea', placeholder: 'Contoh: Bawa kartu mahasiswa dan alat tulis.' },
    ];

    function openEditor(type, item = null) {
        const fields = type === 'schedule' ? scheduleFields : examFields;
        setEditor({ type, item });
        setForm(Object.fromEntries(fields.map((field) => {
            const value = item?.[field.name] ?? '';
            const length = field.type === 'time' ? 5 : field.type === 'date' ? 10 : undefined;
            return [field.name, item ? String(value).slice(0, length) : ''];
        })));
    }

    async function save(event) {
        event.preventDefault();
        setSaving(true);
        setError('');
        const endpoint = editor.type === 'schedule' ? 'jadwals' : 'agenda-ujians';
        const body = new FormData();
        Object.entries(form).forEach(([name, value]) => body.append(name, value));
        if (editor.item) body.append('_method', 'PATCH');
        try {
            await axios.post(`${API}/${endpoint}${editor.item ? `/${editor.item.id}` : ''}`, body);
            clearUserDataCache(user.id);
            notify(`${editor.type === 'schedule' ? 'Jadwal kuliah' : 'Agenda ujian'} berhasil disimpan.`);
            setEditor(null);
            await load(true);
        } catch (requestError) {
            setError(messageFrom(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function remove(type, item) {
        const label = type === 'schedule' ? 'jadwal kuliah' : 'agenda ujian';
        if (!window.confirm(`Hapus ${label} ini?`)) return;
        const endpoint = type === 'schedule' ? 'jadwals' : 'agenda-ujians';
        try {
            await axios.delete(`${API}/${endpoint}/${item.id}`);
            clearUserDataCache(user.id);
            await load(true);
            notify(`${type === 'schedule' ? 'Jadwal kuliah' : 'Agenda ujian'} berhasil dihapus.`);
        } catch (requestError) {
            setError(messageFrom(requestError));
        }
    }

    const weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const scheduleGroups = weekdays
        .map((day) => ({
            day,
            items: schedules
                .filter((item) => item.hari === day)
                .sort((first, second) => first.waktu_mulai.localeCompare(second.waktu_mulai)),
        }))
        .filter((group) => group.items.length > 0);
    const scheduleItems = scheduleGroups.flatMap((group) => group.items);

    return (
        <div className="space-y-7">
            {error && <p role="alert" className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200">{error}</p>}
            {loading ? (
                <div role="status" aria-label="Memuat jadwal dan agenda ujian" className="grid gap-4 md:grid-cols-2">
                    <span className="sr-only">Memuat jadwal...</span>
                    <div className="h-36 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                    <div className="h-36 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                </div>
            ) : (
                <>
                    <nav aria-label="Bagian jadwal" className="grid gap-3 sm:grid-cols-2">
                        <a href="#weekly-schedule" className={`${cardClass} group flex items-center justify-between gap-3 transition hover:border-sky-200 hover:shadow-md dark:hover:border-sky-800`}>
                            <span><span className="block font-bold">Jadwal rutin</span><span className="mt-1 block text-sm text-slate-500 dark:text-slate-400">{schedules.length} jadwal mingguan</span></span>
                            <span className="text-xl text-sky-700 transition group-hover:translate-x-1 dark:text-sky-300" aria-hidden="true">→</span>
                        </a>
                        <a href="#exam-agenda" className={`${cardClass} group flex items-center justify-between gap-3 transition hover:border-violet-200 hover:shadow-md dark:hover:border-violet-800`}>
                            <span><span className="block font-bold">Agenda UTS/UAS</span><span className="mt-1 block text-sm text-slate-500 dark:text-slate-400">{exams.length} agenda ujian</span></span>
                            <span className="text-xl text-violet-700 transition group-hover:translate-x-1 dark:text-violet-300" aria-hidden="true">→</span>
                        </a>
                    </nav>
                    <section id="weekly-schedule" className="scroll-mt-32 space-y-4">
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div><h2 className="text-xl font-extrabold">Jadwal rutin mingguan</h2><p className="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">Jadwal dikelompokkan berdasarkan hari dan waktu.</p></div>
                            {user.role === 'admin' && <button type="button" onClick={() => openEditor('schedule')} className="min-h-11 rounded-xl bg-sky-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">+ Tambah jadwal</button>}
                        </div>
                        {scheduleItems.length ? <div className="grid auto-rows-fr gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {scheduleItems.map((item) => (
                                <article key={item.id} className={`${cardClass} flex h-full min-h-[250px] flex-col`}>
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-800 dark:bg-sky-950 dark:text-sky-200">{item.hari}</span>
                                        <span className="rounded-xl bg-sky-50 px-3 py-2 text-sm font-extrabold text-sky-800 dark:bg-sky-950 dark:text-sky-200">{item.waktu_mulai.slice(0, 5)}–{item.waktu_selesai.slice(0, 5)}</span>
                                    </div>
                                    <h3 title={item.course_name || item.nama_mata_kuliah} className="mt-4 line-clamp-2 min-h-[3.5rem] break-words text-lg font-bold">{item.course_name || item.nama_mata_kuliah}</h3>
                                    <div className="mt-3 grid grid-cols-2 gap-2">
                                        <div className="min-w-0 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                                            <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dosen</p>
                                            <p title={item.dosen} className="mt-1 truncate text-sm font-medium">{item.dosen}</p>
                                        </div>
                                        <div className="min-w-0 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                                            <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ruang</p>
                                            <p title={item.ruang} className="mt-1 truncate text-sm font-medium">{item.ruang}</p>
                                        </div>
                                    </div>
                                    <div className="mt-auto pt-4">
                                        <div className="flex min-h-8 flex-wrap items-center gap-2">
                                            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{modeLabel(item.mode)}</span>
                                            {item.mode === 'Online' && isHttpUrl(item.link_zoom) && <a className="text-xs font-bold text-indigo-700 underline dark:text-indigo-300" href={item.link_zoom} target="_blank" rel="noreferrer">Buka kelas</a>}
                                        </div>
                                        {user.role === 'admin' && <div className="mt-3 flex gap-2 border-t border-slate-100 pt-2 dark:border-slate-800"><button type="button" onClick={() => openEditor('schedule', item)} className="min-h-9 rounded-lg px-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-sky-300 dark:hover:bg-sky-950">Edit</button><button type="button" onClick={() => remove('schedule', item)} className="min-h-9 rounded-lg px-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100 dark:text-rose-300 dark:hover:bg-rose-950">Hapus</button></div>}
                                    </div>
                                </article>
                            ))}
                        </div> : <div className={`${cardClass} px-6 py-10 text-center`}><h3 className="font-bold">Belum ada jadwal rutin</h3><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{user.role === 'admin' ? 'Tambahkan jadwal agar mahasiswa dapat melihat susunan kelas mingguan.' : 'Jadwal kelas mingguan belum dibagikan.'}</p></div>}
                    </section>
                    <section id="exam-agenda" className="scroll-mt-32 space-y-4">
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div><h2 className="text-xl font-extrabold">Agenda UTS/UAS</h2><p className="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">Jadwal ujian ditampilkan terpisah dari kelas rutin.</p></div>
                            {user.role === 'admin' && <button type="button" onClick={() => openEditor('exam')} className="min-h-11 rounded-xl bg-violet-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-violet-800 focus:outline-none focus:ring-4 focus:ring-violet-200">+ Tambah UTS/UAS</button>}
                        </div>
                        {exams.length ? <div className="grid auto-rows-fr gap-4 lg:grid-cols-2">
                            {exams.map((item) => {
                                const examDate = new Date(`${String(item.tanggal).slice(0, 10)}T00:00:00`);
                                const dayNumber = Number.isNaN(examDate.getTime()) ? '—' : new Intl.DateTimeFormat('id-ID', { day: '2-digit' }).format(examDate);
                                const monthName = Number.isNaN(examDate.getTime()) ? '' : new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(examDate);

                                return (
                                    <article key={item.id} className={`${cardClass} flex h-full min-h-[250px] flex-col transition hover:border-violet-200 hover:shadow-md dark:hover:border-violet-800`}>
                                        <div className="flex items-start gap-4">
                                            <div className="grid min-w-[4.5rem] place-items-center rounded-2xl bg-violet-50 px-3 py-2.5 dark:bg-violet-950/70">
                                                <span className="text-2xl font-extrabold leading-none text-violet-800 dark:text-violet-200">{dayNumber}</span>
                                                <span className="mt-1 text-xs font-bold uppercase text-violet-700 dark:text-violet-300">{monthName}</span>
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-extrabold text-violet-800 dark:bg-violet-950 dark:text-violet-200">{item.jenis}</span>
                                                    <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">{modeLabel(item.mode)}</span>
                                                </div>
                                                <h3 title={item.course_name} className="mt-2 line-clamp-2 min-h-[3.5rem] break-words text-lg font-bold">{item.course_name}</h3>
                                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{formatDate(item.tanggal)} · {item.waktu_mulai.slice(0, 5)}–{item.waktu_selesai.slice(0, 5)}</p>
                                            </div>
                                        </div>
                                        <div className="mt-4 flex flex-wrap items-center gap-2">
                                            {item.ruang && <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">Ruang {item.ruang}</span>}
                                            {item.mode === 'Online' && isHttpUrl(item.link_zoom) && <a className="text-sm font-bold text-indigo-700 underline dark:text-indigo-300" href={item.link_zoom} target="_blank" rel="noreferrer">Buka tautan ujian</a>}
                                        </div>
                                        <div className="mt-auto pt-4">
                                            {item.catatan && <details className="rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/70"><summary className="cursor-pointer font-semibold text-slate-700 dark:text-slate-200">Lihat catatan</summary><p className="mt-2 whitespace-pre-line leading-6 text-slate-600 dark:text-slate-300">{item.catatan}</p></details>}
                                            {user.role === 'admin' && <div className="mt-4 flex gap-2 border-t border-slate-100 pt-2 dark:border-slate-800"><button type="button" onClick={() => openEditor('exam', item)} className="min-h-9 rounded-lg px-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-sky-300 dark:hover:bg-sky-950">Edit</button><button type="button" onClick={() => remove('exam', item)} className="min-h-9 rounded-lg px-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100 dark:text-rose-300 dark:hover:bg-rose-950">Hapus</button></div>}
                                        </div>
                                    </article>
                                );
                            })}
                        </div> : <div className={`${cardClass} px-6 py-10 text-center`}><h3 className="font-bold">Belum ada agenda UTS/UAS</h3><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{user.role === 'admin' ? 'Tambahkan jadwal ujian agar mahasiswa dapat melihat tanggal dan waktunya.' : 'Agenda ujian belum dibagikan.'}</p></div>}
                    </section>
                </>
            )}
            {editor && (
                <div role="dialog" aria-modal="true" aria-labelledby="schedule-dialog-title" className="fixed inset-0 z-40 grid place-items-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm">
                    <form onSubmit={save} className={`${cardClass} my-auto max-h-[calc(100vh-2rem)] w-full max-w-2xl space-y-4 overflow-y-auto shadow-2xl`}>
                        <div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-[0.12em] text-sky-700 dark:text-sky-300">{editor.type === 'schedule' ? 'Jadwal rutin' : 'Agenda ujian'}</p><h2 id="schedule-dialog-title" className="mt-1 text-xl font-extrabold">{editor.item ? 'Edit' : 'Tambah'} {editor.type === 'schedule' ? 'Jadwal Kuliah' : 'Agenda Ujian'}</h2><p className="mt-1 text-sm font-normal text-slate-500 dark:text-slate-400">{editor.type === 'schedule' ? 'Isi hari, waktu, mata kuliah, dan ruang kuliah.' : 'Isi jadwal ujian dan tambahkan tautan jika ujian berlangsung online.'} Kolom bertanda * wajib diisi.</p></div><button type="button" onClick={() => setEditor(null)} aria-label="Tutup formulir" className="grid size-9 shrink-0 place-items-center rounded-xl text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:hover:bg-slate-800">×</button></div>
                        {error && <p role="alert" className="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">{error}</p>}
                        {(editor.type === 'schedule' ? scheduleFields : examFields).filter((field) => !field.showWhen || field.showWhen(form)).map((field) => <FormField key={field.name} field={field} value={form[field.name] || ''} form={form} courses={courses} onChange={(value) => setForm((current) => ({ ...current, [field.name]: value }))} />)}
                        <div className="flex flex-wrap gap-2"><button disabled={saving} className="min-h-11 rounded-xl bg-sky-700 px-5 text-sm font-bold text-white transition hover:bg-sky-800 disabled:cursor-wait disabled:opacity-60">{saving ? 'Menyimpan...' : editor.item ? 'Simpan Perubahan' : 'Simpan Agenda'}</button><button type="button" onClick={() => setEditor(null)} className="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold dark:border-slate-700">Batal</button></div>
                    </form>
                </div>
            )}
        </div>
    );
}

createRoot(document.getElementById('academic-app')).render(<App />);
