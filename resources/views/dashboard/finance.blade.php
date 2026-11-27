@extends('tablar::page')
@section('content')

<style>
    .df{--ink:#13211F;--muted:#62726F;--line:#DCE4E2;--surface:#fff;--head:#F6F9F8;--brand:#000;--brand-soft:#D9EEEA;--amber:#D9941A;--good:#544a1e;--mid:#B7791F;--bad:#C2410C;font-family:'Poppins',system-ui,sans-serif;color:var(--ink)}
    .df *{box-sizing:border-box}
    .df-head{display:flex;justify-content:space-between;align-items:center;margin:0px 18px 24px}
    .df-head h1{margin:0;font-size:26px;font-weight:400;letter-spacing:-.02em}
    .df-chip{background:var(--brand-soft);color:var(--brand);font-weight:700;padding:6px 14px;border-radius:999px}
    .df-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;overflow:hidden;min-width:0;max-width:100%;contain:inline-size;;margin:0px 18px 24px}
    .df-scroll{overflow-x:auto}
    .df table{border-collapse:separate;border-spacing:0;width:100%;min-width:1500px}
    .df th,.df td{padding:11px 12px;text-align:right;white-space:nowrap;border-bottom:1px solid var(--line);font-variant-numeric:tabular-nums}
    .df th{background:var(--head);font-size:13px;font-weight:700;padding-top:14px;padding-bottom:14px}
    .df th:first-child,.df td:first-child{position:sticky;left:0;z-index:2;text-align:left;background:var(--surface);min-width:150px;border-right:1px solid var(--line)}
    .df th:first-child{background:var(--head)}
    .df .tot{position:sticky;right:0;z-index:2;background:var(--brand-soft);border-left:1px solid var(--line);min-width:150px;font-weight:700}
    .df th.tot{color:var(--brand)}
    .df tr:last-child td{border-bottom:0}
    .df .lbl b{display:block}
    .df .lbl small{color:var(--muted);font-size:12px}
    .df .dash{color:var(--muted)}
    .df .good{color:var(--good)} .df .mid{color:var(--mid)} .df .bad{color:var(--bad)}
    .df .pct{font-weight:700}
    .df .bar{height:5px;border-radius:3px;background:var(--line);margin:5px 0 0 auto;width:110px;overflow:hidden}
    .df .bar i{display:block;height:100%;border-radius:3px}
    .df .bar i.good{background:var(--good)} .df .bar i.mid{background:var(--mid)} .df .bar i.bad{background:var(--bad)}
    .df-sec{margin-top:28px}
    .df-sec h2{font-size:18px;margin:0 0 12px}
    .df-chart{padding:18px;height:420px;display:flex;flex-direction:column;gap:14px}
    .df-chart-wrap{position:relative;flex:1;min-height:0}
    .df-legend{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
    .df-toggle{--c:#000;--k:#fff;display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);background:var(--surface);color:var(--ink);border-radius:999px;padding:5px 14px 5px 6px;font:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s,background .15s,border-color .15s}
    .df-toggle:hover{border-color:var(--c)}
    .df-toggle:focus-visible{outline:2px solid var(--c);outline-offset:2px}
    .df-toggle .box{width:20px;height:20px;border-radius:50%;background:var(--c);border:2px solid var(--c);color:var(--k);display:grid;place-items:center;transition:background .15s}
    .df-toggle .box svg{width:11px;height:11px;transition:opacity .15s}
    .df-toggle[aria-pressed="false"]{opacity:.6;background:var(--head)}
    .df-toggle[aria-pressed="false"] .box{background:transparent}
    .df-toggle[aria-pressed="false"] .box svg{opacity:0}
    .df-ctrl{display:flex;gap:10px;align-items:center}
    .df-btn{white-space:nowrap}
    .df-year{display:flex;align-items:center;gap:6px}
    .df-year select.df-chip{border:0;appearance:none;-webkit-appearance:none;cursor:pointer;padding:6px 16px;text-align:center;outline:0}
    .df-nav{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid var(--line);background:var(--surface);color:var(--ink);font-size:20px;line-height:1;text-decoration:none}
    .df-nav:hover{background:var(--brand-soft);color:var(--brand)}
    .df-btn{background:var(--brand);color:#fff;border:0;font-weight:700;padding:8px 18px;border-radius:999px;cursor:pointer}
    .df-btn:hover{filter:brightness(1.1)}
    .df-btn.ghost{background:var(--head);color:var(--ink);border:1px solid var(--line)}
    .df-alert{background:var(--brand-soft);color:var(--brand);font-weight:600;padding:10px 16px;border-radius:12px;margin:14px 18px}
    .df-alert-err{background:#FDE8E0;color:var(--bad)}
    .df-modal .modal-content{border-radius:16px;border:1px solid var(--line)}
    .df-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px}
    .df-grid label{font-size:13px;font-weight:700;margin-bottom:4px;display:block}
    .df-input{display:flex;align-items:center;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff}
    .df-input span{padding:0 10px;color:var(--muted);background:var(--head);align-self:stretch;display:flex;align-items:center;font-size:13px}
    .df-input input{border:0;outline:0;padding:9px 10px;width:100%;text-align:right;font-variant-numeric:tabular-nums}
    .df-input:focus-within{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-soft)}
    .df-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:0 18px 24px}

@media (max-width: 991.98px) {
    .df-head {
        margin-left: 14px;
        margin-right: 14px;
    }

    .df-card {
        margin-left: 14px;
        margin-right: 14px;
    }
}

@media (max-width: 575.98px) {

    /* Header: judul 1 baris, kontrol turun ke bawah */
    .df-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .df-head h1 {
        font-size: 22px;
        white-space: nowrap;
    }
    .df-ctrl {
        width: 100%;
        justify-content: space-between;
    }

    .df table {
        min-width: 1300px;           /* 12 bulan x ~95px + label + total */
    }

    .df th,
    .df td {
        padding: 10px 8px;
        font-size: 12px;
    }

    /* Kolom bulan: kasih lebar minimum supaya angka tidak terpotong */
    .df th:not(:first-child):not(.tot),
    .df td:not(:first-child):not(.tot) {
        min-width: 100px;
    }

    /* Kolom label: lebih ramping, tetap sticky */
    .df th:first-child,
    .df td:first-child {
        min-width: 92px;
        max-width: 92px;
        white-space: normal;         /* label boleh turun baris */
        padding-left: 8px;
        padding-right: 6px;
    }

    /* Kolom Total: TIDAK sticky di mobile, ikut digeser */
    .df .tot {
        position: static;
        min-width: 120px;
        border-left: 1px solid var(--line);
    }

    .df .bar {
        width: 80px;
    }
}

</style>

<div class="df" style="width:100%;min-width:0;margin:0;padding:90px 0 64px">
    <div class="df-head">
        <div>
            <h1>Dashboard Finance</h1>
            {{-- <div style="color:var(--muted)">Omset dan target bulanan {{ $year }}</div> --}}
        </div>
        <div class="df-ctrl">
            <button type="button" class="df-btn" data-bs-toggle="modal" data-bs-target="#modalTarget">Atur Target</button>
            <div class="df-year">
                <a href="{{ request()->fullUrlWithQuery(['year' => $year - 1]) }}" class="df-nav" title="Tahun sebelumnya">&#8249;</a>
                <select class="df-chip" onchange="location.href=this.value" aria-label="Pilih tahun">
                    @for ($y = now()->year + 2; $y >= now()->year - 5; $y--)
                        <option value="{{ request()->fullUrlWithQuery(['year' => $y]) }}" @selected($y == $year)>{{ $y }}</option>
                    @endfor
                </select>
                <a href="{{ request()->fullUrlWithQuery(['year' => $year + 1]) }}" class="df-nav" title="Tahun berikutnya">&#8250;</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="df-alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="df-alert df-alert-err">{{ $errors->first() }}</div>
    @endif
    <div class="df-sec">
        <h2 class="page-title">Kurva</h2>
        <div class="df-card df-chart">
            <div class="df-legend" id="dfLegend" role="group" aria-label="Tampilkan atau sembunyikan data kurva"></div>
            <div class="df-chart-wrap"><canvas id="kurvaFinance"></canvas></div>
        </div>
    </div>
    <div class="df-sec">
        <h2 class="page-title">Omset</h2>
    @include('dashboard.finance_table', [
        'judul' => 'Omset', 'rows' => $tabel['omset']['rows'], 'total' => $tabel['omset']['total'],
    ])
    </div>
    <div class="df-sec">
        <h2 class="page-title">Cost</h2>
        @include('dashboard.finance_table', [
            'judul' => 'Cost', 'rows' => $tabel['cost']['rows'], 'total' => $tabel['cost']['total'], 'kurang' => true,
        ])
    </div>

    <div class="df-sec">
        <h2 class="page-title">Profit</h2>
        @include('dashboard.finance_table', [
            'judul' => 'Profit', 'rows' => $tabel['profit']['rows'], 'total' => $tabel['profit']['total'],
        ])
    </div>
</div>

@include('dashboard.finance_modal_target')
@endsection
@push('js')

{{-- <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script> --}}
<script>
    const labels = @json(collect($tabel['omset']['rows'])->pluck('label')->values());
    const jt = a => a.map(v => v === null ? null : v / 1e6);
    @php
        $o = $tabel['omset']['rows'];
        $c = $tabel['cost']['rows'];
        $p = $tabel['profit']['rows'];
    @endphp
    const barBase = {borderRadius:6, maxBarThickness:22, barPercentage:.9, categoryPercentage:.8};

    const kurvaChart = new Chart(document.getElementById('kurvaFinance'), {
        data: {
            labels,
            datasets: [
                // Bar: Omset | Cost | Profit (berdampingan per bulan)
                {...barBase, type:'bar', label:'Omset {{ $year }}', data: jt(@json(collect($o)->pluck('real')->values())),
                 backgroundColor:'#000', hoverBackgroundColor:'#333', order:3},

                {...barBase, type:'bar', label:'Cost {{ $year }}', data: jt(@json(collect($c)->pluck('real')->values())),
                 backgroundColor:'#C2410C', hoverBackgroundColor:'#9A3412', order:3},

                {...barBase, type:'bar', label:'Profit {{ $year }}', data: jt(@json(collect($p)->pluck('real')->values())),
                 backgroundColor:'#58595b', hoverBackgroundColor:'#58595b', order:3},

                // Line: Target & Omset tahun lalu
                {type:'line', label:'Target Omset {{ $year }}', data: jt(@json(collect($o)->pluck('target')->values())),
                 borderColor:'#fbee47', backgroundColor:'#fbee47', cubicInterpolationMode:'monotone', borderWidth:2.5,
                 pointRadius:3, pointHoverRadius:6, pointHitRadius:16, order:1},

                // Target Cost & Profit (garis putus-putus, awalnya disembunyikan; aktifkan lewat chip)
                {type:'line', label:'Target Cost {{ $year }}', data: jt(@json(collect($c)->pluck('target')->values())),
                 borderColor:'#C2410C', backgroundColor:'#C2410C', borderDash:[4,4], cubicInterpolationMode:'monotone', borderWidth:2.5,
                 pointRadius:3, pointHoverRadius:6, pointHitRadius:16, order:1, hidden:true},

                {type:'line', label:'Target Profit {{ $year }}', data: jt(@json(collect($p)->pluck('target')->values())),
                 borderColor:'#58595b', backgroundColor:'#58595b', borderDash:[4,4], cubicInterpolationMode:'monotone', borderWidth:2.5,
                 pointRadius:3, pointHoverRadius:6, pointHitRadius:16, order:1, hidden:true},

                {type:'line', label:'Omset {{ $year - 1 }}', data: jt(@json(collect($o)->pluck('lalu')->values())),
                 borderColor:'#bbbdbf', borderDash:[6,5], cubicInterpolationMode:'monotone', borderWidth:2,
                 pointRadius:2, pointHoverRadius:6, pointHitRadius:16, order:2, hidden:true},
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            events:['mousemove','mouseout','click','touchstart','touchmove'],
            interaction:{mode:'nearest', intersect:true},
            plugins:{
                legend:{display:false},
                tooltip:{
                    displayColors:true,
                    callbacks:{
                        label:c => {
                            const v = Math.round((c.parsed.y || 0) * 1e6);
                            return `${c.dataset.label}: ${v < 0 ? '-' : ''}Rp ${Math.abs(v).toLocaleString('id-ID')}`;
                        }
                    }
                }
            },
            scales:{
                // profit bisa minus, jadi sumbu Y tidak dipaksa mulai dari 0
                x:{grid:{display:false}, ticks:{padding:6}},
                y:{ticks:{callback:v => v + ' jt'}, title:{display:true, text:'Rupiah (juta)'}}
            }
        }
    });
    // Legenda kustom: chip centang untuk tampil/sembunyi tiap data
    (function () {
        const wrap = document.getElementById('dfLegend');
        const terang = ['#58595b', '#c4c4c4']; // warna muda -> centang gelap
        kurvaChart.data.datasets.forEach((ds, i) => {
            const warna = ds.backgroundColor || ds.borderColor;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'df-toggle';
            btn.style.setProperty('--c', warna);
            btn.style.setProperty('--k', terang.includes(warna) ? '#333' : '#fff');
            btn.setAttribute('aria-pressed', kurvaChart.isDatasetVisible(i));
            btn.innerHTML = '<span class="box"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.5l2.5 2.5 4.5-5.5"/></svg></span><span></span>';
            btn.lastElementChild.textContent = ds.label;
            btn.addEventListener('click', () => {
                const tampil = !kurvaChart.isDatasetVisible(i);
                kurvaChart.setDatasetVisibility(i, tampil);
                btn.setAttribute('aria-pressed', tampil);
                kurvaChart.update();
            });
            wrap.appendChild(btn);
        });
    })();
</script>
@endpush