{{--
  ICTHospital - write or edit a prescription.

  The medicine rows repeat. A drug can be picked from the catalogue or typed in
  free, because a doctor writing something the hospital does not stock should not
  be blocked by the stock list.

  Copyright (c) ICT Innovations <https://www.ictinnovations.com>
  Part of ICTHospital <https://www.icthospital.com>
  Licensed under the GNU General Public License v3.0.
--}}
@extends('layouts.master')
@section('style')
@stop
@section('content')

    @php($editing = (bool) $prescription->id)
    @php($rows = old('items', $editing ? $prescription->items->toArray() : []))
    @php($rows = count($rows) ? $rows : [[]])

    <div class="row">
        <div class="box col-md-12">
            <div class="box-inner">
                <div data-original-title="" class="box-header well">
                    <h2><i class="glyphicon glyphicon-list-alt"></i>
                        {{ $editing ? 'Edit prescription' : 'Write prescription' }}
                    </h2>
                    <div class="box-icon">
                        <a href="{{ url('/prescriptions') }}" class="btn btn-default btn-sm">Back to list</a>
                    </div>
                </div>
                <div class="box-content">

                    @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            <strong>Check the form.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form role="form" method="post" id="prescription-form"
                          action="{{ $editing ? url('/prescriptions/' . $prescription->id) : url('/prescriptions') }}">
                        {{ csrf_field() }}
                        @if ($editing)
                            <input type="hidden" name="_method" value="PUT">
                        @endif
                        @if (isset($templates) && $templates->isNotEmpty())
                            <div class="row" style="margin-bottom:15px;">
                                <div class="col-md-6 form-group">
                                    <label><i class="glyphicon glyphicon-th-list"></i> Template</label>
                                    <div class="input-group">
                                        <select id="rx-template-select" class="form-control">
                                            <option value="">-- Choose a prescription template --</option>
                                            @foreach ($templates as $t)
                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default" id="rx-apply-template">Apply</button>
                                        </span>
                                    </div>
                                    <span class="help-block" style="font-size:12px; margin-bottom:0;">Fills medicines, dosage, instructions, and advice.</span>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Patient <span class="text-danger">*</span></label>
                                <select name="patient" class="form-control" required>
                                    <option value="">Choose a patient</option>
                                    @foreach ($patients as $p)
                                        <option value="{{ $p->id }}"
                                            {{ (string) old('patient', $prescription->patient) === (string) $p->id ? 'selected' : '' }}>
                                            {{ $p->name }} ({{ $p->patient_id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Doctor <span class="text-danger">*</span></label>
                                <select name="doctor" class="form-control" required>
                                    <option value="">Choose a doctor</option>
                                    @foreach ($doctors as $d)
                                        <option value="{{ $d->id }}"
                                            {{ (string) old('doctor', $prescription->doctor) === (string) $d->id ? 'selected' : '' }}>
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required
                                       value="{{ old('date', $prescription->date ?: now()->toDateString()) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Symptoms</label>
                            <input type="text" name="symptom" class="form-control" maxlength="100"
                                   value="{{ old('symptom', $prescription->symptom) }}">
                        </div>

                        <h4>Medicines <span class="text-danger">*</span></h4>
                        <table class="table table-bordered" id="medicine-rows">
                            <thead>
                                <tr>
                                    <th style="width:28%">From the catalogue</th>
                                    <th style="width:22%">or type a name</th>
                                    <th style="width:15%">Dosage</th>
                                    <th style="width:15%">Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][medicine_id]" class="form-control">
                                                <option value="">Not from the catalogue</option>
                                                @foreach ($medicines as $m)
                                                    <option value="{{ $m->id }}"
                                                        {{ (string) ($row['medicine_id'] ?? '') === (string) $m->id ? 'selected' : '' }}>
                                                        {{ $m->name }}{{ $m->generic ? ' (' . $m->generic . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[{{ $i }}][name]" class="form-control"
                                                   value="{{ $row['name'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][dosage]" class="form-control"
                                                   placeholder="1+0+1" value="{{ $row['dosage'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][duration]" class="form-control"
                                                   placeholder="5 days" value="{{ $row['duration'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $i }}][instructions]" class="form-control"
                                                   placeholder="After food" value="{{ $row['instructions'] ?? '' }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-default btn-sm" id="add-row">
                            <i class="glyphicon glyphicon-plus"></i> Add another medicine
                        </button>

                        <div class="row" style="margin-top:15px">
                            <div class="col-md-6 form-group">
                                <label>Advice</label>
                                <textarea name="advice" class="form-control" rows="3" maxlength="1000">{{ old('advice', $prescription->advice) }}</textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Notes</label>
                                <textarea name="note" class="form-control" rows="3" maxlength="1000">{{ old('note', $prescription->note) }}</textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Valid until</label>
                            <input type="text" name="validity" class="form-control" maxlength="100"
                                   value="{{ old('validity', $prescription->validity) }}">
                        </div>

                        <div class="form-group">
                            <label>Save as a prescription template <small class="text-muted">(optional, saves medications &amp; advice for future use)</small></label>
                            <input type="text" name="template_name" class="form-control" maxlength="120"
                                   placeholder="e.g. Fever &amp; Pain Adult Pack, Post-op Antibiotic regimen"
                                   value="{{ old('template_name') }}">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ $editing ? 'Save changes' : 'Save prescription' }}
                        </button>
                        <a href="{{ url('/prescriptions') }}" class="btn btn-default">Cancel</a>
                    </form>

                </div>
            </div>
        </div>
    </div>
@stop
@section('script')
<script>
// Clones the last medicine row and renumbers its field names. Plain DOM so the
// page keeps working without the app pulling in a front end framework.
document.getElementById('add-row').addEventListener('click', function () {
    var body = document.querySelector('#medicine-rows tbody');
    var rows = body.querySelectorAll('tr');
    var last = rows[rows.length - 1];
    var copy = last.cloneNode(true);
    var next = rows.length;

    copy.querySelectorAll('select, input').forEach(function (field) {
        field.name = field.name.replace(/items\[\d+\]/, 'items[' + next + ']');
        if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
        } else {
            field.value = '';
        }
    });

    body.appendChild(copy);
});

// Prescription templates loader
(function () {
    var templates = @json(isset($templates) ? $templates->keyBy('id') : []);
    var applyBtn = document.getElementById('rx-apply-template');
    var tplSelect = document.getElementById('rx-template-select');
    if (!applyBtn || !tplSelect) { return; }

    applyBtn.addEventListener('click', function () {
        var tplId = tplSelect.value;
        if (!tplId || !templates[tplId]) { return; }
        var tpl = templates[tplId];

        if (tpl.symptom) {
            var symInput = document.querySelector('[name="symptom"]');
            if (symInput) { symInput.value = tpl.symptom; }
        }
        if (tpl.advice) {
            var advInput = document.querySelector('[name="advice"]');
            if (advInput) { advInput.value = tpl.advice; }
        }
        if (tpl.note) {
            var noteInput = document.querySelector('[name="note"]');
            if (noteInput) { noteInput.value = tpl.note; }
        }
        if (tpl.validity) {
            var valInput = document.querySelector('[name="validity"]');
            if (valInput) { valInput.value = tpl.validity; }
        }

        var items = tpl.items || [];
        if (!Array.isArray(items) || items.length === 0) { return; }

        var tbody = document.querySelector('#medicine-rows tbody');
        var firstRow = tbody.querySelector('tr');
        var templateRow = firstRow.cloneNode(true);

        tbody.innerHTML = '';

        items.forEach(function (item, idx) {
            var row = templateRow.cloneNode(true);
            var select = row.querySelector('select');
            var nameInput = row.querySelector('input[name*="[name]"]');
            var dosageInput = row.querySelector('input[name*="[dosage]"]');
            var durInput = row.querySelector('input[name*="[duration]"]');
            var instInput = row.querySelector('input[name*="[instructions]"]');

            if (select) {
                select.name = 'items[' + idx + '][medicine_id]';
                select.selectedIndex = 0;
                if (item.medicine_id) {
                    select.value = item.medicine_id;
                } else if (item.name) {
                    for (var i = 0; i < select.options.length; i++) {
                        if (select.options[i].text.toLowerCase().indexOf(item.name.toLowerCase()) !== -1) {
                            select.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
            if (nameInput) {
                nameInput.name = 'items[' + idx + '][name]';
                nameInput.value = item.name || '';
            }
            if (dosageInput) {
                dosageInput.name = 'items[' + idx + '][dosage]';
                dosageInput.value = item.dosage || '';
            }
            if (durInput) {
                durInput.name = 'items[' + idx + '][duration]';
                durInput.value = item.duration || '';
            }
            if (instInput) {
                instInput.name = 'items[' + idx + '][instructions]';
                instInput.value = item.instructions || '';
            }

            tbody.appendChild(row);
        });
    });
})();
</script>
@stop
