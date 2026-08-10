Has your AYN Thor {{ $milestone === 'delivered' ? 'arrived' : 'shipped' }}?

Our estimate suggests that order {{ $subscriber->order_prefix }}xx for {{ $subscriber->modelVariant->name }} should have {{ $milestone === 'delivered' ? 'arrived by now' : 'shipped by now' }}.

Confirm here: {{ $confirmationUrl }}

Confirming helps improve estimates for everyone. If it has not happened yet, simply ignore this email.
