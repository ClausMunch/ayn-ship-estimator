import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';

export default function Login() {
    const { errors } = usePage().props;
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post('/admin/login', { email, password }, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <div className="min-h-screen bg-[#0c0c14] text-[#e0e0e8] font-mono flex items-center justify-center p-6">
            <div className="w-full max-w-sm bg-[#15151f] border border-[#2a2a3a] rounded-2xl p-7">
                <h1 className="font-sans text-xl font-semibold text-white text-center mb-6">
                    Admin Login
                </h1>

                <form onSubmit={handleSubmit}>
                    <label className="block text-[10px] font-semibold uppercase tracking-[1.5px] text-[#555570] mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        className="w-full rounded-lg px-3 py-2.5 text-sm text-white outline-none mb-4 font-mono bg-[#1a1a28] border border-[#2a2a3a] focus:border-[#818cf8]"
                    />

                    <label className="block text-[10px] font-semibold uppercase tracking-[1.5px] text-[#555570] mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        required
                        className="w-full rounded-lg px-3 py-2.5 text-sm text-white outline-none mb-4 font-mono bg-[#1a1a28] border border-[#2a2a3a] focus:border-[#818cf8]"
                    />

                    {errors.email && (
                        <div className="text-xs text-red-400 mb-4">{errors.email}</div>
                    )}

                    <button
                        type="submit"
                        disabled={submitting}
                        className="w-full rounded-lg py-2.5 text-xs font-semibold cursor-pointer transition-all duration-150 bg-[#818cf8] text-white border-none disabled:opacity-40 hover:bg-[#6d78e4]"
                    >
                        {submitting ? 'Signing in...' : 'Sign in'}
                    </button>
                </form>
            </div>
        </div>
    );
}
