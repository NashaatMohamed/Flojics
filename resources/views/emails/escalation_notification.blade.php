<x-mail::message>
# Ticket Escalation Alert

The following ticket has been escalated and requires immediate attention:

<x-mail::panel>
**Ticket ID:** #{{ $notification->ticket->id }}  
**Subject:** {{ $notification->ticket->subject }}  
**Priority:** {{ $notification->ticket->priority->value }}  
**Escalated At:** {{ $notification->ticket->escalated_at?->toIso8601String() ?? $notification->ticket->escalated_at }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/tickets/' . $notification->ticket->id">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
