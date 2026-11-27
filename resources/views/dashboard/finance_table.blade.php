{{--
    Tabel bulanan yang dipakai bersama oleh Omset, Cost, dan Profit.
    Parameter: $judul ('Omset'|'Cost'|'Profit'), $rows, $total, $year, $kurang (true = makin kecil makin bagus, untuk Cost)
--}}
@php
    $rpS     = fn ($v) => ($v < 0 ? '-' : '') . 'Rp ' . number_format(abs($v), 0, ',', '.');
    $pct     = fn ($v) => number_format($v, 1, ',', '.') . '%';
    $kurang  = $kurang ?? false;
    $wProg   = fn ($v) => $kurang
        ? ($v <= 100 ? 'good' : ($v <= 120 ? 'mid' : 'bad'))
        : ($v >= 100 ? 'good' : ($v >= 70 ? 'mid' : 'bad'));
    $wTumbuh = fn ($v) => $kurang ? ($v <= 100 ? 'good' : 'bad') : ($v >= 100 ? 'good' : 'bad');
    $semua   = array_merge($rows, ['total' => $total]);
    $nama    = strtolower($judul);
@endphp

<div class="df-card">
    <div class="df-scroll">
        <table>
            <thead>
                <tr>
                    <th></th>
                    @foreach ($rows as $r)
                        <th>{{ $r['label'] }}</th>
                    @endforeach
                    <th class="tot">Total {{ $year }}</th>
                </tr>
            </thead>
            <tbody>
                {{-- Tahun lalu --}}
                <tr>
                    <td class="lbl"><b>{{ $judul }} {{ $year - 1 }}</b><small>pembanding</small></td>
                    @foreach ($rows as $r)
                        <td>{{ $rpS($r['lalu']) }}</td>
                    @endforeach
                    <td class="tot">{{ $rpS($total['lalu']) }}</td>
                </tr>

                {{-- Target --}}
                <tr>
                    <td class="lbl"><b>Target</b><small>rencana {{ $year }}</small></td>
                    @foreach ($rows as $r)
                        <td>{{ $rpS($r['target']) }}</td>
                    @endforeach
                    <td class="tot">{{ $rpS($total['target']) }}</td>
                </tr>

                {{-- Realisasi --}}
                <tr>
                    <td class="lbl"><b>{{ $judul }}</b><small>realisasi {{ $year }}</small></td>
                    @foreach ($rows as $r)
                        <td>{!! $r['real'] === null ? '<span class="dash">–</span>' : e($rpS($r['real'])) !!}</td>
                    @endforeach
                    <td class="tot">{!! $total['real'] === null ? '<span class="dash">–</span>' : e($rpS($total['real'])) !!}</td>
                </tr>

                {{-- Progress = realisasi / target --}}
                <tr>
                    <td class="lbl"><b>Progress</b><small>{{ $nama }} ÷ target</small></td>
                    @foreach ($semua as $key => $r)
                        <td class="{{ $key === 'total' ? 'tot' : '' }}">
                            @if ($r['progress'] === null)
                                <span class="dash">–</span>
                            @else
                                @php $w = $wProg($r['progress']); @endphp
                                <span class="pct {{ $w }}">{{ $pct($r['progress']) }}</span>
                                <div class="bar"><i class="{{ $w }}" style="width:{{ max(0, min($r['progress'], 100)) }}%"></i></div>
                            @endif
                        </td>
                    @endforeach
                </tr>

                {{-- Pertumbuhan = realisasi / tahun lalu --}}
                <tr>
                    <td class="lbl"><b>Pertumbuhan</b><small>{{ $nama }} ÷ {{ $nama }} {{ $year - 1 }}</small></td>
                    @foreach ($semua as $key => $r)
                        <td class="{{ $key === 'total' ? 'tot' : '' }}">
                            @if ($r['tumbuh'] === null)
                                <span class="dash">–</span>
                            @else
                                <span class="pct {{ $wTumbuh($r['tumbuh']) }}">{{ $pct($r['tumbuh']) }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>