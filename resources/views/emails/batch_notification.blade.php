<!DOCTYPE html>
<h2>New Batch Submitted</h2>

<p>
  A new batch has been created for <strong>{{ $batch->provider->name }}</strong>
  on <strong>{{ \Carbon\Carbon::parse($batch->batch_date)->toFormattedDateString() }}</strong>.
</p>

<p>Total Claims: {{ $batch->claims->count() }}</p>
<p>Total Amount: ${{ number_format($batch->total_cost, 2) }}</p>

<p>Please log in to your dashboard to process the batch.</p>
</html>