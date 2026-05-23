@php
    $inst = $meta->institution;
    $logoSrc = $inst->logoAbsolutePath ?? $inst->logoUrl ?? $inst->logoDataUri;
@endphp
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%; margin:0 0 12px; border-collapse:collapse;">
    <tr>
        @if($logoSrc)
            <td width="76" style="width:76px; vertical-align:middle; padding:0 12px 0 0; border:none;">
                <img src="{{ $logoSrc }}" alt="" style="max-width:68px; max-height:58px; display:block;">
            </td>
        @endif
        <td style="vertical-align:middle; border:none; border-left:3px solid #0f4c75; padding-left:12px;">
            <div style="font-size:11.5px; font-weight:bold; color:#0f172a; line-height:1.3;">
                {{ $inst->name }}
            </div>
            <table width="100%" cellspacing="0" cellpadding="0" style="width:100%; margin-top:6px; border-collapse:collapse; font-size:8.5px; color:#475569;">
                <tr>
                    @if($inst->ruc)
                        <td style="border:none; padding:2px 14px 2px 0; white-space:nowrap;">
                            <span style="font-weight:bold; color:#64748b;">RUC:</span> {{ $inst->ruc }}
                        </td>
                    @endif
                    @if($inst->phone)
                        <td style="border:none; padding:2px 14px 2px 0; white-space:nowrap;">
                            <span style="font-weight:bold; color:#64748b;">Tel.:</span> {{ $inst->phone }}
                        </td>
                    @endif
                    @if($inst->email)
                        <td style="border:none; padding:2px 0; white-space:nowrap;">
                            <span style="font-weight:bold; color:#64748b;">Correo:</span> {{ $inst->email }}
                        </td>
                    @endif
                </tr>
                @if($inst->address || $inst->website)
                    <tr>
                        @if($inst->address)
                            <td colspan="2" style="border:none; padding:2px 14px 2px 0;">
                                <span style="font-weight:bold; color:#64748b;">Dirección:</span> {{ $inst->address }}
                            </td>
                        @endif
                        @if($inst->website)
                            <td style="border:none; padding:2px 0;">
                                <span style="font-weight:bold; color:#64748b;">Web:</span> {{ $inst->website }}
                            </td>
                        @endif
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
<table width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="height:1px; background:#cbd5e1; border:none; padding:0; line-height:1px; font-size:1px;">&nbsp;</td>
    </tr>
</table>
