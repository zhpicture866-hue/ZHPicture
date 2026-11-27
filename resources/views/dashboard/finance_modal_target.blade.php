{{-- Modal input target. Parameter: $year, $tabel
     Alur:
       1) Profit tahunan: isi % dan Rp  ->  Omset (100%) terhitung otomatis
          Omset = (100% / %Profit) x Profit(Rp)   ;   Cost = Omset - Profit
       2) Pembagian: bagi rata 12 bulan, atau isi manual
       3) Per bulan: isi Rp atau %. Muted text menampilkan target Omset, Cost, Profit bulan itu.
          Total semua bulan wajib 100% dan nominalnya sama dengan Omset tahunan.
     Yang dikirim ke server tetap nominal: targets[omset|profit|cost][bulan]. --}}
@php
    $rowsOm = $tabel['omset']['rows'];
    $rowsPf = $tabel['profit']['rows'];
    $savedOm = collect($rowsOm)->map(fn ($r) => (int) round($r['target'] ?? 0))->values();
    $savedPf = collect($rowsOm)->map(fn ($r, $i) => (int) round($rowsPf[$i]['target'] ?? 0))->values();
@endphp

<style>
    .df-seg{display:inline-flex;border:1px solid var(--line);border-radius:999px;padding:3px;background:var(--head)}
    .df-seg button{border:0;background:transparent;padding:5px 14px;border-radius:999px;font-weight:700;font-size:13px;color:var(--muted);cursor:pointer}
    .df-seg button.on{background:var(--brand);color:#fff}
    .df-hint{display:block;margin-top:4px;font-size:12px;min-height:16px;white-space:pre-line;line-height:1.45}
    .df-input input[readonly]{background:var(--head);cursor:default}
    .df-step{border:1px solid var(--line);border-radius:14px;padding:14px 16px;margin-bottom:14px}
    .df-step-h{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
    .df-step-n{width:24px;height:24px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:13px;font-weight:700;flex:none}
    .df-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}
    .df-sum > div{border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:var(--head)}
    .df-sum small{display:block;color:var(--muted);font-size:12px}
    .df-sum b{font-size:15px;font-variant-numeric:tabular-nums}
    .df-sum .sub{font-size:12px;color:var(--muted);font-weight:600}
    .df-sum.is-ok > div{border-color:var(--good)}
    .df-sum.is-bad > div{border-color:var(--bad)}
    .df-sum.is-bad b{color:var(--bad)}
    .df-btn:disabled{opacity:.45;cursor:not-allowed;filter:none}
    .df-err{display:none;margin-top:14px;background:#FDE8E0;color:var(--bad);font-weight:600;padding:10px 14px;border-radius:12px}
    @media (max-width:575.98px){ .df-sum{grid-template-columns:1fr} }
</style>

<div class="modal modal-blur fade df-modal" id="modalTarget" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content df" method="POST" action="{{ route('dashboard-finance.target.store') }}" id="formTarget">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="modal-header">
                <h3 class="modal-title" style="font-weight:400">Target {{ $year }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">

                {{-- ========== 1. TARGET TAHUNAN ========== --}}
                <div class="df-step">
                    <div class="df-step-h">
                        <span class="df-step-n">1</span><b>Target tahunan</b>
                        <span class="text-muted" style="font-size:13px">Isi profit, omset (100%) terhitung otomatis</span>
                    </div>
                    <div class="df-grid">
                        <div>
                            <label>Profit (%)</label>
                            <div class="df-input"><input type="text" inputmode="decimal" id="pfPct" placeholder="0"><span>%</span></div>
                            <small class="df-hint text-muted">Persentase profit dari omset</small>
                        </div>
                        <div>
                            <label>Profit (Rp)</label>
                            <div class="df-input"><span>Rp</span><input type="text" inputmode="numeric" id="pfNom" placeholder="0"></div>
                            <small class="df-hint text-muted">Nominal profit setahun</small>
                        </div>
                        <div>
                            <label>Omset (100%)</label>
                            <div class="df-input"><span>Rp</span><input type="text" id="omTot" readonly tabindex="-1" placeholder="0"></div>
                            <small class="df-hint text-muted" id="omTotHint">= 100% ÷ % profit × profit</small>
                        </div>
                    </div>
                    <div class="df-sum">
                        <div><small>Omset (100%)</small><b id="sumOm">Rp 0</b></div>
                        <div><small>Cost</small><b id="sumC">Rp 0</b> <span class="sub" id="sumCP"></span></div>
                        <div><small>Profit</small><b id="sumPf">Rp 0</b> <span class="sub" id="sumPfP"></span></div>
                    </div>
                </div>

                {{-- ========== 2. PEMBAGIAN ========== --}}
                <div class="df-step">
                    <div class="df-step-h">
                        <span class="df-step-n">2</span><b>Pembagian per bulan</b>
                    </div>
                    <div class="df-seg" id="modeSeg">
                        <button type="button" class="on" data-mode="rata">Bagi rata 12 bulan</button>
                        <button type="button" data-mode="manual">Isi manual</button>
                    </div>
                </div>

                {{-- ========== 3. PER BULAN ========== --}}
                <div class="df-step" style="margin-bottom:0">
                    <div class="df-step-h">
                        <span class="df-step-n">3</span><b>Target per bulan</b>
                        <span class="text-muted" style="font-size:13px" id="step3Note"></span>
                        <div class="df-seg" id="unitSeg" style="margin-left:auto;display:none">
                            <button type="button" class="on" data-unit="nominal">Nominal (Rp)</button>
                            <button type="button" data-unit="persen">Persen (%)</button>
                        </div>
                    </div>
                    <div class="df-grid">
                        @foreach ($rowsOm as $i => $r)
                            <div class="df-cell">
                                <label>{{ $r['label'] }}</label>
                                <div class="df-input"><span class="m-unit">Rp</span><input type="text" inputmode="decimal" class="m-view" placeholder="0" readonly></div>
                                <input type="hidden" class="m-om" name="targets[omset][{{ $i }}]"  value="0">
                                <input type="hidden" class="m-pf" name="targets[profit][{{ $i }}]" value="0">
                                <input type="hidden" class="m-c"  name="targets[cost][{{ $i }}]"   value="0">
                                <small class="df-hint text-muted m-hint"></small>
                            </div>
                        @endforeach
                    </div>

                    <div class="df-sum" id="totBar">
                        <div><small>Total persen</small><b id="totPct">0%</b></div>
                        <div><small>Total nominal</small><b id="totRp">Rp 0</b></div>
                        <div><small>Sisa</small><b id="totSisa">-</b></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px">
                        <span id="totMsg" style="font-size:13px;font-weight:600"></span>
                        <button type="button" class="df-btn ghost" id="btnSisa" style="display:none">Masukkan sisa ke bulan terakhir</button>
                    </div>
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
        const form  = document.getElementById('formTarget');
        const $     = id => document.getElementById(id);
        const errEl = $('targetErr');

        const SAVED_OM = @json($savedOm);
        const SAVED_PF = @json($savedPf);

        const rp     = n => (n < 0 ? '-' : '') + 'Rp ' + Math.abs(Math.round(n)).toLocaleString('id-ID');
        const fmt    = n => n ? Math.round(n).toLocaleString('id-ID') : '';
        const fmtP   = (p, d = 2) => (Math.round(p * 10 ** d) / 10 ** d).toLocaleString('id-ID', {maximumFractionDigits: d});
        const digits = s => parseInt((s || '').replace(/\D/g, ''), 10) || 0;
        const num    = s => { const v = parseFloat((s || '').replace(/[^0-9,]/g, '').replace(',', '.')); return isNaN(v) ? 0 : v; };
        const sum    = a => a.reduce((s, v) => s + v, 0);
        const idxMax = a => a.indexOf(Math.max(...a));

        const pfPctEl = $('pfPct'), pfNomEl = $('pfNom'), omTotEl = $('omTot');
        const btnSimpan = form.querySelector('button[type="submit"]'), btnSisa = $('btnSisa');

        // satu objek per bulan
        const cells = [...form.querySelectorAll('.df-cell')].map(c => ({
            view: c.querySelector('.m-view'), unit: c.querySelector('.m-unit'), hint: c.querySelector('.m-hint'),
            omH: c.querySelector('.m-om'), pfH: c.querySelector('.m-pf'), cH: c.querySelector('.m-c'),
            om: 0,      // nominal omset bulan itu
            share: 0,   // % dari omset tahunan
        }));
        const N = cells.length;

        // S.pct = % profit, S.pn = profit (Rp) setahun, S.O = omset (Rp) setahun
        const S = {pct: 0, pn: 0, O: 0, mode: 'rata', unit: 'nominal'};
        let last = {sumOm: 0, sumShare: 0};

        /* ---------- hitung ulang semuanya ---------- */
        const recalc = (force = false) => {
            // 1) nominal & persen tiap bulan
            if (S.mode === 'rata') {
                const base = Math.floor(S.O / N);
                cells.forEach((c, i) => { c.om = i === N - 1 ? S.O - base * (N - 1) : base; });
            }
            cells.forEach(c => {
                if (S.mode === 'manual' && S.unit === 'persen') c.om = Math.round(S.O * c.share / 100);
                else c.share = S.O ? c.om / S.O * 100 : 0;
            });

            const sumShare = sum(cells.map(c => c.share));
            const om = cells.map(c => c.om);

            // selisih pembulatan (mode persen) dimasukkan ke bulan terbesar
            if (S.mode === 'manual' && S.unit === 'persen' && S.O > 0 && Math.abs(sumShare - 100) < 0.005) {
                const d = S.O - sum(om);
                if (d && Math.abs(d) <= N) om[idxMax(om)] += d;
            }
            const sumOm = sum(om);

            // 2) profit & cost per bulan; total profit dipaksa = profit tahunan
            const pf = om.map(o => Math.round(o * S.pct / 100));
            if (S.pn > 0 && S.pct > 0 && sumOm === S.O) {
                const d = S.pn - sum(pf);
                if (d && Math.abs(d) <= N) pf[idxMax(om)] += d;
            }
            const cost = om.map((o, i) => o - pf[i]);
            last = {sumOm, sumShare};

            // 3) tampilkan
            cells.forEach((c, i) => {
                c.omH.value = om[i]; c.pfH.value = pf[i]; c.cH.value = cost[i];

                if (force || document.activeElement !== c.view) {
                    if (S.mode === 'manual' && S.unit === 'persen') c.view.value = c.share ? fmtP(c.share) : '';
                    else c.view.value = fmt(om[i]);
                }
                c.hint.textContent = om[i]
                    ? `Omset: ${rp(om[i])} (${fmtP(c.share, 1)}% dari total)\nCost: ${rp(cost[i])} (${fmtP(100 - S.pct, 1)}%)\nProfit: ${rp(pf[i])} (${fmtP(S.pct, 1)}%)`
                    : '';
            });

            omTotEl.value = fmt(S.O);
            $('omTotHint').textContent = S.O ? '= 100% ÷ ' + fmtP(S.pct) + '% × ' + rp(S.pn) : '= 100% ÷ % profit × profit';
            $('sumOm').textContent  = rp(S.O);
            $('sumPf').textContent  = rp(S.pn);
            $('sumC').textContent   = rp(Math.max(0, S.O - S.pn));
            $('sumPfP').textContent = S.O ? `(${fmtP(S.pct, 1)}%)` : '';
            $('sumCP').textContent  = S.O ? `(${fmtP(100 - S.pct, 1)}%)` : '';

            $('totPct').textContent  = fmtP(sumShare) + '%';
            $('totRp').textContent   = rp(sumOm);
            $('totSisa').textContent = S.O ? `${fmtP(100 - sumShare)}% · ${rp(S.O - sumOm)}` : '-';
            $('step3Note').textContent = S.O ? `Total harus 100% = ${rp(S.O)}` : 'Isi profit dulu';
            const pesan = validasi();
            const ok    = pesan === '';
            const bar   = $('totBar');
            bar.classList.toggle('is-ok',  S.O > 0 && ok);
            bar.classList.toggle('is-bad', S.O > 0 && !ok);

            const msg = $('totMsg');
            msg.textContent = ok ? '✓ Total 100% dan nominalnya sama dengan omset tahunan' : pesan;
            msg.style.color = ok ? 'var(--good)' : 'var(--bad)';
            btnSimpan.disabled = !ok;
            btnSisa.style.display = (S.mode === 'manual' && S.O > 0 && S.pct > 0 && S.pct <= 100 && !ok) ? '' : 'none';
        };

        /* ---------- validasi ---------- */
        function validasi() {
            if (!(S.pct > 0 && S.pct <= 100)) return 'Persentase profit harus lebih dari 0 dan maksimal 100%.';
            if (S.pn <= 0) return 'Isi nominal profit tahunan terlebih dahulu.';
            if (S.O <= 0)  return 'Omset belum terhitung. Isi persentase dan nominal profit.';
            if (S.mode === 'manual') {
                if (S.unit === 'persen') {
                    if (Math.abs(last.sumShare - 100) >= 0.005) return `Total persen harus 100% (sekarang ${fmtP(last.sumShare)}%).`;
                } else if (last.sumOm !== S.O) {
                    return `Total nominal per bulan harus sama dengan omset tahunan ${rp(S.O)} (selisih ${rp(S.O - last.sumOm)}).`;
                }
            }
            return '';
        }

        /* ---------- 1. profit tahunan ---------- */
        pfNomEl.addEventListener('input', () => {
            S.pn = digits(pfNomEl.value);
            pfNomEl.value = fmt(S.pn);
            if (S.pct > 0) S.O = S.pn > 0 ? Math.round(S.pn / (S.pct / 100)) : 0;
            else if (S.O > 0) { S.pct = Math.min(100, S.pn / S.O * 100); pfPctEl.value = fmtP(S.pct); }
            recalc();
        });
        pfPctEl.addEventListener('input', () => {
            pfPctEl.value = pfPctEl.value.replace(/[^0-9,]/g, '');
            S.pct = num(pfPctEl.value);
            if (S.pct > 0 && S.pn > 0)      S.O = Math.round(S.pn / (S.pct / 100));
            else if (S.pct > 0 && S.O > 0)  { S.pn = Math.round(S.O * S.pct / 100); pfNomEl.value = fmt(S.pn); }
            else                            S.O = 0;
            recalc();
        });

        /* ---------- 2. mode pembagian ---------- */
        const setMode = mode => {
            S.mode = mode;
            form.querySelectorAll('#modeSeg button').forEach(b => b.classList.toggle('on', b.dataset.mode === mode));
            $('unitSeg').style.display = mode === 'manual' ? '' : 'none';
            cells.forEach(c => { c.view.readOnly = mode !== 'manual'; });
            if (mode === 'rata') S.unit = 'nominal';
            setUnit(S.unit);
        };
        const setUnit = unit => {
            S.unit = unit;
            form.querySelectorAll('#unitSeg button').forEach(b => b.classList.toggle('on', b.dataset.unit === unit));
            cells.forEach(c => { c.unit.textContent = unit === 'persen' ? '%' : 'Rp'; });
            recalc(true);
        };
        form.querySelectorAll('#modeSeg button').forEach(b => b.onclick = () => setMode(b.dataset.mode));
        form.querySelectorAll('#unitSeg button').forEach(b => b.onclick = () => setUnit(b.dataset.unit));

        /* ---------- 3. input per bulan (mode manual) ---------- */
        cells.forEach(c => c.view.addEventListener('input', () => {
            if (S.mode !== 'manual') return;
            if (S.unit === 'persen') {
                c.view.value = c.view.value.replace(/[^0-9,]/g, '');
                c.share = num(c.view.value);
            } else {
                c.om = digits(c.view.value);
                c.view.value = fmt(c.om);
            }
            recalc();
        }));

        /* ---------- lengkapi sisa ke bulan terakhir ---------- */
        btnSisa.onclick = () => {
            const akhir = cells[N - 1], lain = cells.slice(0, N - 1);
            if (S.unit === 'persen') akhir.share = Math.max(0, 100 - sum(lain.map(c => c.share)));
            else                     akhir.om    = Math.max(0, S.O - sum(lain.map(c => c.om)));
            recalc(true);
        };

        /* ---------- simpan ---------- */
        form.addEventListener('input', () => { errEl.style.display = 'none'; });
        form.addEventListener('submit', e => {
            recalc(true);
            const pesan = validasi();
            if (pesan) {
                e.preventDefault();
                errEl.textContent = pesan;
                errEl.style.display = 'block';
            }
        });

        /* ---------- isi awal dari target tersimpan ---------- */
        S.O  = sum(SAVED_OM);
        S.pn = sum(SAVED_PF);
        S.pct = S.O ? Math.min(100, S.pn / S.O * 100) : 0;
        cells.forEach((c, i) => { c.om = SAVED_OM[i] || 0; });
        pfPctEl.value = S.pct ? fmtP(S.pct) : '';
        pfNomEl.value = fmt(S.pn);

        const merata = S.O > 0 && SAVED_OM.every(v => Math.abs(v - S.O / N) <= 1);
        setMode(S.O > 0 && !merata ? 'manual' : 'rata');
    })();
</script>
@endpush