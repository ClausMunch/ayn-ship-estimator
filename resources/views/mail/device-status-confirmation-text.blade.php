Has your AYN Thor {{ $milestone === 'delivered' ? 'arrived' : 'shipped' }}?

Our estimate suggests that order {{ $subscriber->order_prefix }}xx for {{ $subscriber->modelVariant->name }} should have {{ $milestone === 'delivered' ? 'arrived by now' : 'shipped by now' }}.

Confirm here: {{ $confirmationUrl }}

Not yet? Let us know here: {{ $notYetUrl }}

Confirming either status helps improve estimates for everyone.
