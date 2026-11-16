@component('admin::emails.layout')
    <p style="font-family: 'Inter', Arial, sans-serif;font-weight: 600;font-size: 20px;color: #1F2937;line-height: 24px;margin: 0 0 24px 0;">
        @lang('admin::app.emails.hello')
    </p>

    <p style="font-family: 'Inter', Arial, sans-serif;font-weight: 600;font-size: 16px;color: #1F2937;line-height: 24px;margin: 0 0 8px 0;">
        {{ $title }}
    </p>

    <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 24px 0;">
        {{ $description }}
    </p>

    @if (filled($comment))
        <p style="font-family: 'Inter', Arial, sans-serif;font-size: 14px;font-weight: 600;color: #1F2937;line-height: 20px;margin: 0 0 4px 0;">
            @lang('masterdata::app.approvals.fields.comment')
        </p>

        <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 24px 0;white-space: pre-line;">
            {{ $comment }}
        </p>
    @endif

    <div style="margin: 16px 0 40px 0;">
        <a
            href="{{ $url }}"
            style="display: inline-block;font-family: 'Inter', Arial, sans-serif;padding: 12px 32px;border-radius: 6px;background: #7C3AED;border: 1px solid #6D28D9;color: #F9FAFB;text-decoration: none;font-weight: 600;font-size: 14px;line-height: 20px;"
        >
            {{ $action }}
        </a>
    </div>
@endcomponent
