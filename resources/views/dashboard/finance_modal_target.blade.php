{{-- Modal input target. Parameter: $year, $tabel
     Aturan:
       - Omset  : WAJIB diisi nominal (Rp), per bulan bisa custom. Total omset = 100%.
       - Profit : diisi nominal ATAU persen (% dari omset bulan itu).
       - Cost   : otomatis = Omset - Profit (tidak diisi).
     Yang dikirim ke server selalu nominal: targets[omset|profit|cost][bulan]. --}}
@php
    $rowsOm = $tabel['omset']['rows'];
    $rowsPf = $tabel['profit']['rows'];
@endphp

<style>
    .df-seg{display:inline-flex;border:1px solid var(--line);border-radius:999px;padding:3px;background:var(--head)}
    .df-seg button{border:0;background:transparent;padding:5px 14px;border-radius:999px;font-weight:700;font-size:13px;color:var(--muted);cursor:pointer}
    .df-seg button.on{background:var(--brand);color:#fff}
    .df-hint{display:block;margin-top:4px;font-size:12px;min-height:16px}
    .df-input input[readonly]{background:var(--head);cursor:default}
    .df-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:18px}
    .df-sum > div{border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:var(--head)}
    .df-sum small{display:block;color:var(--muted);font-size:12px}
    .df-sum b{font-size:15px;font-variant-numeric:tabular-nums}
    .df-sum .sub{font-size:12px;color:var(--muted);font-weight:600}
    .df-err{display:none;margin-top:14px;background:#FDE8E0;color:var(--bad);font-weight:600;padding:10px 14px;border-radius:12px}
    @media (max-width:575.98px){ .df-sum{grid-template-columns:1fr} }
</style>

<div class="modal modal-blur fade df-modal" id="modalTarget" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content df" method="POST" action="{{ route('dashboard-finance.target.store') }}" id="formTarget">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="modal-header">
                <h3 class="modal-title" style="font-weight:400">Target {{ $year }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom:16px">
                    <li class="nav-item"><a href="#tab-target-omset"  class="nav-link active" data-bs-toggle="tab" role="tab">Omset</a></li>
                    <li class="nav-item"><a href="#tab-target-profit" class="nav-link" data-bs-toggle="tab" role="tab">Profit</a></li>
                    <li class="nav-item"><a href="#tab-target-cost"   class="nav-link" data-bs-toggle="tab" role="tab">Cost</a></li>
                </ul>

                <div class="tab-content">

                    {{-- ========== OMSET (nominal, 100%) ========== --}}
                    <div class="tab-pane fade show active" id="tab-target-omset">
                        <div class="text-muted" style="font-size:13px;margin-bottom:12px">
                            Isi target omset dalam <b>Rupiah</b>. Total omset menjadi <b>100%</b> dasar untuk Profit dan Cost. Tiap bulan bebas diisi custom.
                        </div>
                        <div style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                            <span class="text-muted" style="font-size:13px">Isi semua bulan sekaligus:</span>
                            <div class="df-input" style="width:190px"><span>Rp</span><input type="text" inputmode="numeric" class="bulk-om" placeholder="0"></div>
                            <button type="button" class="df-btn ghost btn-om-samakan">Samakan</button>
                            <button type="button" class="df-btn ghost btn-om-bagi">Bagi rata dari total</button>
                        </div>
                        <div class="df-grid">
                            @foreach ($rowsOm as $i => $r)
                                <div class="df-cell" data-m="{{ $i }}">
                                    <label>{{ $r['label'] }}</label>
                                    <div class="df-input"><span>Rp</span><input type="text" inputmode="numeric" class="m-om-view" placeholder="0"></div>
                                    <input type="hidden" class="m-om-hid" name="targets[omset][{{ $i }}]" value="{{ (int) round($r['target'] ?? 0) }}">
                                    <small class="df-hint text-muted m-om-hint"></small>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ========== PROFIT (nominal atau %) ========== --}}
                    <div class="tab-pane fade" id="tab-target-profit" data-mode="nominal">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
                            <span class="text-muted" style="font-size:13px">Isi profit dengan:</span>
                            <div class="df-seg">
                                <button type="button" class="on" data-mode="nominal">Nominal (Rp)</button>
                                <button type="button" data-mode="persen">Persentase (%)</button>
                            </div>
                        </div>
                        <div class="text-muted pf-note" style="font-size:13px;margin-bottom:12px">
                            Persentase dihitung dari <b>omset bulan itu</b> (omset = 100%). Cost otomatis mengikuti.
                        </div>
                        <div style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                            <span class="text-muted label-pf-sama" style="font-size:13px">Isi semua bulan sekaligus:</span>
                            <div class="df-input" style="width:190px"><span class="pf-unit">Rp</span><input type="text" inputmode="decimal" class="bulk-pf" placeholder="0"></div>
                            <button type="button" class="df-btn ghost btn-pf-samakan">Samakan</button>
                            <button type="button" class="df-btn ghost btn-pf-bagi">Bagi rata dari total</button>
                        </div>
                        <div class="df-grid">
                            @foreach ($rowsOm as $i => $r)
                                <div class="df-cell" data-m="{{ $i }}">
                                    <label>{{ $r['label'] }}</label>
                                    <div class="df-input"><span class="pf-unit">Rp</span><input type="text" inputmode="decimal" class="m-pf-view" placeholder="0"></div>
                                    <input type="hidden" class="m-pf-hid" name="targets[profit][{{ $i }}]" value="{{ (int) round($rowsPf[$i]['target'] ?? 0) }}">
                                    <small class="df-hint text-muted m-pf-hint"></small>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ========== COST (otomatis) ========== --}}
                    <div class="tab-pane fade" id="tab-target-cost">
                        <div class="text-muted" style="font-size:13px;margin-bottom:12px">
                            Cost <b>otomatis</b> = Omset − Profit. Tidak perlu diisi.
                        </div>
                        <div class="df-grid">
                            @foreach ($rowsOm as $i => $r)
                                <div class="df-cell" data-m="{{ $i }}">
                                    <label>{{ $r['label'] }}</label>
                                    <div class="df-input"><span>Rp</span><input type="text" class="m-c-view" readonly tabindex="-1"></div>
                                    <input type="hidden" class="m-c-hid" name="targets[cost][{{ $i }}]" value="0">
                                    <small class="df-hint text-muted m-c-hint"></small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ringkasan selalu terlihat --}}
                <div class="df-sum">
                    <div><small>Omset (100%)</small><b id="sumOm">Rp 0</b></div>
                    <div><small>Profit</small><b id="sumPf">Rp 0</b> <span class="sub" id="sumPfP"></span></div>
                    <div><small>Cost</small><b id="sumC">Rp 0</b> <span class="sub" id="sumCP"></span></div>
                </div>
                <div class="df-err" id="targetErr"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="df-btn ghost" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="df-btn">Simpan Target</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    (() => {
        const form   = document.getElementById('formTarget');
        const paneOm = form.querySelector('#tab-target-omset');
        const panePf = form.querySelector('#tab-target-profit');
        const paneC  = form.querySelector('#tab-target-cost');
        const errEl  = document.getElementById('targetErr');

        const rp     = n => (n < 0 ? '-' : '') + 'Rp ' + Math.abs(Math.round(n)).toLocaleString('id-ID');
        const fmt    = n => n ? Math.round(n).toLocaleString('id-ID') : '';
        const digits = s => parseInt((s || '').replace(/\D/g, '')) || 0;
        const num    = s => { const v = parseFloat((s || '').replace(/[^0-9,]/g, '').replace(',', '.')); return isNaN(v) ? 0 : v; };
        const fmtPct = p => (Math.round(p * 10) / 10).toLocaleString('id-ID', {maximumFractionDigits: 1});
        const isPersen = () => panePf.dataset.mode === 'persen';

        // satu objek per bulan, menyimpan semua elemen + nilai
        const M = [...paneOm.querySelectorAll('.df-cell')].map(c => {
            const k  = c.dataset.m;
            const at = (pane, sel) => pane.querySelector(`.df-cell[data-m="${k}"] ${sel}`);
            return {
                omV: at(paneOm, '.m-om-view'), omH: at(paneOm, '.m-om-hid'), omHint: at(paneOm, '.m-om-hint'),
                pfV: at(panePf, '.m-pf-view'), pfH: at(panePf, '.m-pf-hid'), pfHint: at(panePf, '.m-pf-hint'),
                cV:  at(paneC,  '.m-c-view'),  cH:  at(paneC,  '.m-c-hid'),  cHint:  at(paneC,  '.m-c-hint'),
                om: 0, pf: 0, pct: 0, c: 0,
            };
        });
        M.forEach(m => { m.om = parseInt(m.omH.value) || 0; m.pf = parseInt(m.pfH.value) || 0; });

        const recalc = () => {
            const totalOm = M.reduce((s, m) => s + m.om, 0);

            M.forEach(m => {
                if (isPersen()) m.pf = Math.max(0, Math.round(m.om * m.pct / 100));
                m.c = m.om - m.pf;

                m.omH.value = m.om; m.pfH.value = m.pf; m.cH.value = m.c;

                // omset
                m.omV.value = fmt(m.om);
                m.omHint.textContent = totalOm && m.om ? `${fmtPct(m.om / totalOm * 100)}% dari total omset` : '';

                // profit
                if (isPersen()) {
                    m.pfHint.textContent = m.om ? '= ' + rp(m.pf) : 'Isi omset dulu';
                } else {
                    m.pfV.value = fmt(m.pf);
                    m.pfHint.textContent = m.om ? `${fmtPct(m.pf / m.om * 100)}% dari omset` : (m.pf ? 'Isi omset dulu' : '');
                }

                // cost
                m.cV.value = (m.c < 0 ? '-' : '') + Math.abs(Math.round(m.c)).toLocaleString('id-ID');
                m.cV.style.color = m.c < 0 ? 'var(--bad)' : '';
                m.cHint.textContent = m.om ? `${fmtPct(m.c / m.om * 100)}% dari omset` : '';
            });

            const sPf = M.reduce((s, m) => s + m.pf, 0);
            const sC  = M.reduce((s, m) => s + m.c, 0);
            document.getElementById('sumOm').textContent  = rp(totalOm);
            document.getElementById('sumPf').textContent  = rp(sPf);
            document.getElementById('sumC').textContent   = rp(sC);
            document.getElementById('sumPfP').textContent = totalOm ? `(${fmtPct(sPf / totalOm * 100)}%)` : '';
            document.getElementById('sumCP').textContent  = totalOm ? `(${fmtPct(sC / totalOm * 100)}%)` : '';
        };

        /* ---------- Omset ---------- */
        M.forEach(m => m.omV.addEventListener('input', () => { m.om = digits(m.omV.value); recalc(); }));
        const bulkOm = paneOm.querySelector('.bulk-om');
        bulkOm.addEventListener('input', () => { bulkOm.value = fmt(digits(bulkOm.value)); });
        paneOm.querySelector('.btn-om-samakan').onclick = () => { const v = digits(bulkOm.value); M.forEach(m => m.om = v); recalc(); };
        paneOm.querySelector('.btn-om-bagi').onclick    = () => { const v = Math.floor(digits(bulkOm.value) / 12); M.forEach(m => m.om = v); recalc(); };

        /* ---------- Profit ---------- */
        const bulkPf = panePf.querySelector('.bulk-pf');
        M.forEach(m => m.pfV.addEventListener('input', () => {
            if (isPersen()) {
                m.pfV.value = m.pfV.value.replace(/[^0-9,]/g, '');
                m.pct = num(m.pfV.value);
            } else {
                m.pf = digits(m.pfV.value);
            }
            recalc();
        }));
        bulkPf.addEventListener('input', () => {
            bulkPf.value = isPersen() ? bulkPf.value.replace(/[^0-9,]/g, '') : fmt(digits(bulkPf.value));
        });
        panePf.querySelector('.btn-pf-samakan').onclick = () => {
            if (isPersen()) {
                const p = num(bulkPf.value);
                M.forEach(m => { m.pct = p; m.pfV.value = bulkPf.value; });
            } else {
                const v = digits(bulkPf.value);
                M.forEach(m => m.pf = v);
            }
            recalc();
        };
        panePf.querySelector('.btn-pf-bagi').onclick = () => {
            const v = Math.floor(digits(bulkPf.value) / 12);
            M.forEach(m => m.pf = v);
            recalc();
        };

        // ganti mode profit
        panePf.querySelectorAll('.df-seg button').forEach(b => b.onclick = () => {
            panePf.dataset.mode = b.dataset.mode;
            panePf.querySelectorAll('.df-seg button').forEach(x => x.classList.toggle('on', x === b));
            panePf.querySelectorAll('.pf-unit').forEach(u => u.textContent = isPersen() ? '%' : 'Rp');
            panePf.querySelector('.label-pf-sama').textContent = isPersen() ? 'Isi semua bulan dengan % dari omset:' : 'Isi semua bulan sekaligus:';
            panePf.querySelector('.btn-pf-bagi').style.display = isPersen() ? 'none' : '';
            bulkPf.value = '';
            if (isPersen()) {
                M.forEach(m => { m.pct = m.om ? m.pf / m.om * 100 : 0; m.pfV.value = m.om ? fmtPct(m.pct) : ''; });
            }
            recalc();
        });

        /* ---------- Validasi saat simpan ---------- */
        const tampilError = (pesan, tabId) => {
            errEl.textContent = pesan; errEl.style.display = 'block';
            const link = form.querySelector(`a[href="#${tabId}"]`);
            try { window.bootstrap.Tab.getOrCreateInstance(link).show(); } catch (e) { link && link.click(); }
        };
        form.addEventListener('input', () => { errEl.style.display = 'none'; });
        form.addEventListener('submit', e => {
            if (M.reduce((s, m) => s + m.om, 0) <= 0) {
                e.preventDefault();
                tampilError('Target omset wajib diisi dalam nominal (Rp) terlebih dahulu.', 'tab-target-omset');
                return;
            }
            if (M.some(m => m.c < 0)) {
                e.preventDefault();
                tampilError('Profit tidak boleh melebihi omset (cost menjadi minus). Periksa bulan yang berwarna merah di tab Cost.', 'tab-target-cost');
            }
        });

        recalc();
    })();
</script>
@endpush