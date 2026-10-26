{{--
  ICTHospital - printable prescription template for patient and pharmacy.

  Copyright (c) ICT Innovations <https://www.ictinnovations.com>
  Part of ICTHospital <https://www.icthospital.com>
  Licensed under the GNU General Public License v3.0.
--}}
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prescription #{{ $prescription->id }} - {{ optional($patient)->name }} ({{ $prescription->date }})</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
    max-width: 190mm;
    margin: 16px auto;
    color: #1e293b;
    font-size: 13px;
    line-height: 1.5;
    background: #fff;
  }
  .header-table {
    width: 100%;
    border-bottom: 2px solid #0f766e;
    padding-bottom: 12px;
    margin-bottom: 16px;
  }
  .hospital-name {
    font-size: 20px;
    font-weight: bold;
    color: #0f766e;
    margin: 0;
  }
  .hospital-sub {
    font-size: 12px;
    color: #64748b;
    margin: 2px 0 0;
  }
  .doc-title {
    text-align: right;
    font-size: 16px;
    font-weight: bold;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .meta-card {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    margin-bottom: 16px;
    border-collapse: collapse;
  }
  .meta-card td {
    padding: 6px 12px;
    font-size: 12.5px;
    vertical-align: top;
    border-bottom: 1px solid #f1f5f9;
  }
  .meta-label {
    font-weight: bold;
    color: #475569;
    width: 15%;
  }
  .meta-value {
    color: #0f172a;
    width: 35%;
  }
  .rx-symbol {
    font-size: 26px;
    font-weight: bold;
    font-family: Georgia, serif;
    color: #0f766e;
    margin: 12px 0 6px;
    line-height: 1;
  }
  .med-table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0 18px;
  }
  .med-table th {
    background-color: #f8fafc;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-top: 1px solid #cbd5e1;
    border-bottom: 2px solid #0f766e;
    padding: 8px 10px;
    text-align: left;
  }
  .med-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 13px;
    vertical-align: middle;
  }
  .med-table tr:last-child td {
    border-bottom: 1px solid #cbd5e1;
  }
  .section-heading {
    font-size: 13px;
    font-weight: bold;
    color: #0f766e;
    margin: 14px 0 4px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 2px;
  }
  .section-text {
    margin: 4px 0 10px;
    white-space: pre-line;
    color: #334155;
  }
  .footer-table {
    width: 100%;
    margin-top: 48px;
    border-collapse: collapse;
  }
  .footer-table td {
    vertical-align: bottom;
  }
  .sig-line {
    border-top: 1px solid #334155;
    width: 200px;
    text-align: center;
    padding-top: 4px;
    font-size: 12px;
    color: #475569;
  }
  .btn-print {
    background: #0f766e;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    padding: 8px 18px;
    font-size: 13px;
    cursor: pointer;
    font-weight: bold;
  }
  .btn-print:hover {
    background: #115e59;
  }
  .controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #cbd5e1;
  }
  @media print {
    body {
      margin: 0;
      max-width: 100%;
      font-size: 12pt;
    }
    .noprint, .controls-bar {
      display: none !important;
    }
  }
</style>
</head>
<body>
@php
  $inst = \Illuminate\Support\Facades\Schema::hasTable('institute')
      ? \Illuminate\Support\Facades\DB::table('institute')->first()
      : null;
@endphp

<div class="controls-bar noprint">
  <div>
    <button type="button" onclick="window.print()" class="btn-print">Print Prescription</button>
  </div>
  <div>
    <a href="{{ url('/prescriptions/' . $prescription->id) }}" style="color:#0f766e; text-decoration:none; font-size:13px;">&larr; Back to Prescription Details</a>
  </div>
</div>

<table class="header-table">
  <tr>
    <td style="vertical-align:middle;">
      <h1 class="hospital-name">{{ $inst && !empty($inst->name) ? $inst->name : 'ICTHospital' }}</h1>
      <div class="hospital-sub">
        @if ($inst && !empty($inst->address)) {{ $inst->address }} &middot; @endif
        @if ($inst && !empty($inst->phoneNo)) Phone: {{ $inst->phoneNo }} &middot; @endif
        @if ($inst && !empty($inst->email)) {{ $inst->email }} @endif
      </div>
    </td>
    <td style="vertical-align:middle; text-align:right;">
      <div class="doc-title">Prescription</div>
      <div style="font-size:12px; color:#64748b;">Rx #{{ $prescription->id }} &middot; {{ $prescription->date }}</div>
    </td>
  </tr>
</table>

<table class="meta-card">
  <tr>
    <td class="meta-label">Patient:</td>
    <td class="meta-value"><strong>{{ optional($patient)->name ?: 'Walk-in Patient' }}</strong></td>
    <td class="meta-label">Date:</td>
    <td class="meta-value">{{ $prescription->date }}</td>
  </tr>
  <tr>
    <td class="meta-label">MRN:</td>
    <td class="meta-value">{{ optional($patient)->patient_id ?: '-' }}</td>
    <td class="meta-label">Doctor:</td>
    <td class="meta-value"><strong>{{ optional($doctor)->name ?: 'Consulting Physician' }}</strong></td>
  </tr>
  <tr>
    <td class="meta-label">Age / Sex:</td>
    <td class="meta-value">{{ optional($patient)->age ? optional($patient)->age . ' yrs' : '-' }} / {{ optional($patient)->sex ? ucfirst(optional($patient)->sex) : '-' }}</td>
    <td class="meta-label">Department:</td>
    <td class="meta-value">{{ optional($doctor)->department ?: 'Outpatient (OPD)' }}</td>
  </tr>
  @if ($prescription->symptom)
  <tr>
    <td class="meta-label">Symptoms:</td>
    <td colspan="3" class="meta-value">{{ $prescription->symptom }}</td>
  </tr>
  @endif
</table>

<div class="rx-symbol">&#8478;</div>

<table class="med-table">
  <thead>
    <tr>
      <th style="width:36%;">Medicine</th>
      <th style="width:18%;">Dosage</th>
      <th style="width:18%;">Duration</th>
      <th style="width:28%;">Instructions</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($prescription->items as $item)
      <tr>
        <td>
          <strong>{{ $item->name }}</strong>
          @if ($item->medicine && $item->medicine->generic)
            <br><small style="color:#64748b;">({{ $item->medicine->generic }})</small>
          @endif
        </td>
        <td>{{ $item->dosage ?: '-' }}</td>
        <td>{{ $item->duration ?: '-' }}</td>
        <td>{{ $item->instructions ?: '-' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="4" style="color:#94a3b8; text-align:center; padding:16px;">No medicines prescribed.</td>
      </tr>
    @endforelse
  </tbody>
</table>

@if ($prescription->advice)
  <div class="section-heading">Advice</div>
  <div class="section-text">{{ $prescription->advice }}</div>
@endif

@if ($prescription->note)
  <div class="section-heading">Notes / Instructions</div>
  <div class="section-text">{{ $prescription->note }}</div>
@endif

<table class="footer-table">
  <tr>
    <td>
      @if ($prescription->validity)
        <div style="font-size:12px; color:#64748b;">
          <strong>Validity:</strong> Valid until {{ $prescription->validity }}
        </div>
      @endif
      <div style="font-size:11px; color:#94a3b8; margin-top:4px;">
        Generated on {{ now()->format('d M Y, h:i A') }}
      </div>
    </td>
    <td style="text-align:right;">
      <div class="sig-line" style="margin-left:auto;">
        Doctor Signature &amp; Stamp
      </div>
    </td>
  </tr>
</table>

</body>
</html>
