@if ($requests->hasPages())
    <div class="card-footer">
        {{ $requests->links() }}
    </div>
@endif
