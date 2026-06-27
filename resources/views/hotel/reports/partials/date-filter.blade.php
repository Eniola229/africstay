<form method="GET" class="d-flex gap-2 mb-3 align-items-end flex-wrap">
    <div>
        <label class="form-label fs-12 fw-bold mb-1">From</label>
        <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="form-control form-control-sm">
    </div>
    <div>
        <label class="form-label fs-12 fw-bold mb-1">To</label>
        <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="form-control form-control-sm">
    </div>
    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
</form>
