import { useState } from 'react';

export default function HowItWorks() {
    const [open, setOpen] = useState(false);

    return (
        <div className="mt-5 border-t border-[#2a2a3a] pt-4">
            <button
                onClick={() => setOpen(!open)}
                className="w-full flex items-center justify-center gap-1.5 text-[11px] text-[#666680] hover:text-[#8888a0] cursor-pointer transition-colors font-light"
            >
                How does this work?
                <svg
                    className={`w-3 h-3 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="mt-3 text-[11px] text-[#8888a0] font-light leading-relaxed space-y-3 animate-fade-in">
                    <p>
                        AYN publishes shipping batches on their{' '}
                        <a
                            href="https://www.ayntec.com/pages/shipment-dashboard"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="underline hover:text-[#a0a0b8]"
                        >
                            shipment dashboard
                        </a>
                        . Each batch tells us which order numbers shipped and when.
                    </p>

                    <p>
                        We collect this data and use it to estimate when your order will ship:
                    </p>

                    <ul className="space-y-2 pl-3">
                        <li className="flex gap-2">
                            <span className="inline-block w-[7px] h-[7px] rounded-full bg-[#4ade80] mt-[5px] shrink-0" />
                            <span>
                                <strong className="text-[#4ade80] font-medium">Already shipped</strong> — your
                                order number falls within a batch that has already been sent out.
                            </span>
                        </li>
                        <li className="flex gap-2">
                            <span className="inline-block w-[7px] h-[7px] rounded-full bg-[#818cf8] mt-[5px] shrink-0" />
                            <span>
                                <strong className="text-[#818cf8] font-medium">Estimated</strong> — your order
                                falls between two known batches, so we calculate where it lands based on the
                                shipping pace.
                            </span>
                        </li>
                        <li className="flex gap-2">
                            <span className="inline-block w-[7px] h-[7px] rounded-full bg-[#fbbf24] mt-[5px] shrink-0" />
                            <span>
                                <strong className="text-[#fbbf24] font-medium">Projected</strong> — your order
                                is beyond the latest known batch. We look at recent shipping rates, giving
                                more weight to larger batches, and project forward. Less certain, but our best guess.
                            </span>
                        </li>
                    </ul>

                    <p className="text-[10px] text-[#555570]">
                        Estimates update automatically as new shipping data becomes available.
                    </p>
                </div>
            )}
        </div>
    );
}
